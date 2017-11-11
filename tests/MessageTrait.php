<?php


namespace rpkamp\Mailhog\Tests;

use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

trait MessageTrait
{
    /**
     * @var Swift_Mailer
     */
    private $mailer;

    public function sendDummyMessage(): void
    {
        $this->sendMessage(
            $this->createDummyMessage()
        );
    }

    public function createDummyMessage(): Swift_Message
    {
        return $this->createBasicMessage('me@myself.example', 'myself@myself.example', 'Hello', 'How are you?');
    }

    public function createBasicMessage(string $from, string $to, string $subject, string $body): Swift_Message
    {
        return (new Swift_Message())
            ->setFrom($from)
            ->setTo($to)
            ->setSubject($subject)
            ->setBody($body);
    }

    public function sendMessage(Swift_Message $message): void
    {
        $this->getMailer()->send($message);
    }

    private function getMailer(): Swift_Mailer
    {
        if (null === $this->mailer) {
            $info = parse_url($_ENV['mailhog_smtp_dsn']);

            $this->mailer = new Swift_Mailer(new Swift_SmtpTransport($info['host'], $info['port']));
        }

        return $this->mailer;
    }
}
