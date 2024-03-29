<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;
use App\Util\UtilityBox;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\AdDeal;

class PriceNotificationCommand extends Command
{
    /**
     * @var ParameterBagInterface
     */
    protected $params;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    protected static $defaultName = 'app:price-notification';

    public function __construct(ParameterBagInterface $params, EntityManagerInterface $em) {
        $this->params = $params;
        $this->em     = $em;
        parent::__construct();
    }

    protected function configure()
    {
        $this
        ->setDescription('Checks latest USD-CUC exchange Ads. Sends a notification if found a sell Ad for a set price.')
            // ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            // ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        // Initialize HTTP client.
        $client = HttpClient::create();

        // Get banned words from settings.
        $bannedWords = $this->getParameter('banned_words');
        $bannedWords = UtilityBox::addExclPrefix($bannedWords);

        // Create string with banned words.
        $bannedWordsStr = implode(' ', $bannedWords);

        // Create the full power keyword search text.
        $searchQuery = '"' . $this->getParameter('search_text') . '" ' . $bannedWordsStr;

        // Make an HTTP GET request to https://www.revolico.com/compra-venta/divisas/search.html?q=...&min_price=...&max_price=...
        $response = $client->request('GET', $this->getParameter('search_page_url'), [
            // Set request headers.
            'headers' => [
                'User-Agent' => $this->getParameter('user_agent')
            ],

            // Set search parameters. These values are automatically encoded before including them in the URL
            'query' => [
                'q'         => $searchQuery,
                'min_price' => $this->getParameter('min_price'),
                'max_price' => $this->getParameter('max_price'),
            ],
        ]);

        // Get the status code.
        $statusCode = $response->getStatusCode();

        // Check status of request.
        if (200 !== $statusCode) {
            $io->error(sprintf('Page request failed with error: %s', $statusCode));
        } else {

            // Get the HTML contents of the page requested.
            $content = $response->getContent();

            // Send HTML to crawler.
            $crawler = new Crawler($content);

            // Obtain all the ads <li> rows.
            $adRowElements = $crawler
            ->filter('li[data-cy="adRow"]')
            // Exclude Ads with banned words in the title.
            ->reduce(function (Crawler $node, $i) use ($bannedWords) {
                // Get the adTitle element.
                $adTitleElement = $node->filter('span[data-cy="adTitle"]');

                // Check if Ad title contains banned words.
                if ($this->striposa($adTitleElement->html(), $bannedWords) === false) {
                    // Include this Ad, it seems to NOT have banned word in his title.
                    return true;
                } else {
                    // Do not include this Ad, it seems it has a banned word in his title.
                    return false;
                }
            })
            ;

            // Get AdDeal repository.
            $adDealRepository  = $this->em->getRepository(AdDeal::class);
            $saveAdDeal        = false;

            // Loop each Ad, stores in the DB if doesn't exists yet and sends a notification with a list of new Ads found.
            foreach ($adRowElements as $domElement) {
                // Continue to next element if Ad price is set in CUP, or if the Ad is missing his Ad value field.
                if (strpos($domElement->firstChild->firstChild->nodeValue, 'CUP') !== false || $domElement->firstChild->firstChild->getAttribute('data-cy') != 'adPrice') {
                    continue;
                }

                // Get Ad URI.
                $adUri = $domElement->firstChild->getAttribute('href');

                // Get the Revolico ID of the Ad from the URI.
                $revAdId = $this->getRevAdId($adUri, '-', '.html');

                // Find already saved AdDeal by his listing ID.
                $adDeal = $adDealRepository->findOneBy(['listingId' => $revAdId]);

                // If we don't have this adDeal, let's save it.
                if (!$adDeal) {
                    $adDeal = new AdDeal();
                    $adDeal->setListingId($revAdId);
                    $adDeal->setTitle($domElement->firstChild->lastChild->nodeValue);
                    $adDeal->setUrl($this->getParameter('ads_website_url') . $adUri);
                    $adDeal->setPrice(str_replace([' CUC - ', ' USD - '], '', $domElement->firstChild->firstChild->nodeValue));

                    // Tell doctrine we want to save adDeals.
                    $this->em->persist($adDeal);
                    $saveAdDeal = true;
                }
            }


            // If we have adDeals, save them.
            if ($saveAdDeal === true) {
                $this->em->flush();
                $io->success('New adDeals were saved into the database!');
            } else {
                $io->note('Seems we already have this adDeals saved into the database.');
            }

        }

        return 0;
    }


    /**
     * Finds the Revolico Ad ID.
     * @param string $string
     * @param string $startStr
     * @param string $endStr
     */
    private function getRevAdId($string,$startStr,$endStr) {
        $startpos=strrpos($string,$startStr);
        $endpos=strpos($string,$endStr,$startpos);
        $endpos=$endpos-$startpos;
        $string=substr($string,$startpos+1,$endpos-1);

        return $string;
    }


    /**
     * Custom stripos() function to find multiple needles in one haystack.
     * @param string $haystack
     * @param array $needle
     * @param bool $offset
     * @return bool
     */
    private function striposa($haystack, $needle, $offset=0) {
        if(!is_array($needle)) $needle = array($needle);
        foreach($needle as $query) {
            if(stripos($haystack, $query, $offset) !== false) return true;
        }
        return false;
    }


    /**                                 
     * Get parameter from ParameterBag                                           
     *                                           
     * @param string $name                                           
     * @return mixed                                          
     */
    private function getParameter($name)
    {
        return $this->params->get($name);
    }

}
