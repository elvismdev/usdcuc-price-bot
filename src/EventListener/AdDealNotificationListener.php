<?php

namespace App\EventListener;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use App\Entity\AdDeal;
use Symfony\Component\Translation\TranslatorInterface;

class AdDealNotificationListener
{

	private $sendEmailTo;

	private $mailer;

	private $translator;

	public function __construct($sendEmailTo, \Swift_Mailer $mailer, TranslatorInterface $translator)
	{
		$this->mailer       = $mailer;
		$this->translator   = $translator;
		$this->sendEmailTo  = $sendEmailTo;
	}

	public function postPersist(LifecycleEventArgs $args)
	{
		$adDeal = $args->getObject();

        // only act on "AdDeal" entity
		if (!$adDeal instanceof AdDeal) {
			return;
		}

		// Get entity manager.
		$em = $args->getEntityManager();

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
}