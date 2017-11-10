<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Message;

class MessageFactory
{
    public static function fromMailhogResponse(array $mailhogResponse): Message
    {
        $recipients = [];
        foreach ($mailhogResponse['To'] as $recipient) {
            $recipients[] = sprintf('%s@%s', $recipient['Mailbox'], $recipient['Domain']);
        }

        $sender = sprintf('%s@%s', $mailhogResponse['From']['Mailbox'], $mailhogResponse['From']['Domain']);

        return new Message(
            $mailhogResponse['ID'],
            $sender,
            $recipients,
            $mailhogResponse['Content']['Headers']['Subject'][0],
            $mailhogResponse['Content']['Body']
        );
    }
}
