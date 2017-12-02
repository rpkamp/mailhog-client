<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Message;

use rpkamp\Mailhog\Message\Mime\MimePartCollection;

class MessageFactory
{
    public static function fromMailhogResponse(array $mailhogResponse): Message
    {
        $mimeParts = MimePartCollection::fromMailhogResponse($mailhogResponse['MIME']['Parts'] ?? []);
        $headers = $mailhogResponse['Content']['Headers'];

        return new Message(
            $mailhogResponse['ID'],
            Contact::fromString($headers['From'][0]),
            ContactCollection::fromString($headers['To'][0] ?? ''),
            ContactCollection::fromString($headers['Cc'][0] ?? ''),
            ContactCollection::fromString($headers['Bcc'][0] ?? ''),
            $headers['Subject'][0],
            !$mimeParts->isEmpty()
                ? $mimeParts->getBody()
                : $mailhogResponse['Content']['Body'],
            !$mimeParts->isEmpty() ? $mimeParts->getAttachments() : []
        );
    }
}
