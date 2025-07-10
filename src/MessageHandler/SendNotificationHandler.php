<?php

namespace App\MessageHandler;

use App\Message\Notification;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class SendNotificationHandler
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function __invoke(Notification $message)
    {
        error_log('SendNotificationHandler invoked for: ' . $message->getEmail());
        $email = (new Email())
            ->from('reema708256@gmail.com')
            ->to($message->getEmail())
            ->subject('Welcome!')
            ->text(sprintf('Hello %s, your account has been created.', $message->getName()));

        $this->mailer->send($email);
        error_log('Email sent to: ' . $message->getEmail());
    }
}
