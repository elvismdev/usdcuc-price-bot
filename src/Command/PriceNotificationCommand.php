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

class PriceNotificationCommand extends Command
{
    /**
     * @var ParameterBagInterface
     */
    protected $params;

    protected static $defaultName = 'app:price-notification';

    public function __construct(ParameterBagInterface $params) {
        $this->params = $params;
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
        
        // $arg1 = $input->getArgument('arg1');

        // if ($arg1) {
        //     $io->note(sprintf('You passed an argument: %s', $arg1));
        // }

        // if ($input->getOption('option1')) {
        //     // ...
        // }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return 0;
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
