<?php

namespace rpkamp\Mailhog\Tests;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

trait MessageTrait
{
    /**
     * @var MailerInterface
     */
    private $mailer;

    public function sendDummyMessage(): void
    {
        $this->sendMessage(
            $this->createDummyMessage()
        );
    }

    public function createDummyMessage(): Email
    {
        return $this->createBasicMessage('me@myself.example', 'myself@myself.example', 'Hello', 'How are you?');
    }

    public function createBasicMessage(string $from, string $to, string $subject, string $body): Email
    {
        return (new Email())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->text($body);
    }

    public function sendMessage(Email $message): void
    {
        $this->getMailer()->send($message);
    }

    private function getMailer(): MailerInterface
    {
        if (null === $this->mailer) {
            $this->mailer = new Mailer(Transport::fromDsn($_ENV['mailhog_smtp_dsn']));
        }

        return $this->mailer;
    }
}
