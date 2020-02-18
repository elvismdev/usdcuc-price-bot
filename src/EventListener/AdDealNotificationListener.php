<?php

namespace App\EventListener;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use App\Entity\AdDeal;
use Symfony\Component\Translation\TranslatorInterface;
use Borsaco\TelegramBotApiBundle\Service\Bot;

class AdDealNotificationListener
{

	private $sendEmailTo;

	private $telegramChId;

	private $mailer;

	private $telegramBot;

	private $translator;

	public function __construct($sendEmailTo, $telegramChId, \Swift_Mailer $mailer, TranslatorInterface $translator, Bot $telegramBot)
	{
		$this->mailer       	= $mailer;
		$this->translator   	= $translator;
		$this->sendEmailTo  	= $sendEmailTo;
		$this->telegramBot 		= $telegramBot;
		$this->telegramChId  	= $telegramChId;
	}

	public function postPersist(LifecycleEventArgs $args)
	{
		$adDeal = $args->getObject();

        // only act on "AdDeal" entity
		if (!$adDeal instanceof AdDeal) {
			return;
		}


		// Send email notification.
		// $this->sendEmailNotification($adDeal);

		// Send Telegram notification.
		$this->sendTelegramNotification($adDeal);

	}


	/**
	 * Send email notification.
	 * @param AdDeal $adDeal
	 */
	private function sendEmailNotification($adDeal) {
		// Compose message.
		$message = (new \Swift_Message(sprintf($this->translator->trans('email_messages.new_addeal_pre_subject') . ' ' . $adDeal->getTitle())))
		->setTo([$this->sendEmailTo])
		->setBody($this->translator->trans(
			'email_messages.new_addeal',
			[
				'%adPrice%' => $adDeal->getPrice(),
				'%adUrl%' 	=> $adDeal->getUrl()
			]
		),
		'text/html');

		// Send notification about new adDeal.
		$this->mailer->send($message);
	}


	/**
	 * Send email notification.
	 * @param AdDeal $adDeal
	 */
	private function sendTelegramNotification($adDeal) {
		// Get the Telegram Bot.
		$usdCucBot = $this->telegramBot->getBot('usdcuc_bot');
		
		// Compose message.
		$messageText = $this->translator->trans('email_messages.new_addeal_pre_subject') . ' ' . $adDeal->getTitle() . "\n\n";
		$messageText .= $this->translator->trans('price') . ' $' . $adDeal->getPrice() . "\n\n";
		$messageText .= $this->translator->trans('link') . ' ' . $adDeal->getUrl() . "\n\n";

		// Set Telegram request arguments.
		$message = [
			'chat_id' 	=> '-100' . $this->telegramChId,
			'text' 		=> $messageText
		];

		// Send message to Telegram channel.
		$usdCucBot->sendMessage($message);
	}

}
