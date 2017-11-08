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

    public function sendDummyEmail(): void
    {
        $this->sendTestEmail('me@myself.example', 'myself@myself.example', 'Hello', 'How are you?');
    }

    public function sendTestEmail(string $from, string $to, string $subject, string $body): void
    {
        $message = (new Swift_Message())
            ->setFrom($from)
            ->setTo($to)
            ->setSubject($subject)
            ->setBody($body);

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
