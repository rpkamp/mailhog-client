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
            isset($headers['Subject'][0]) ? $headers['Subject'][0] : '',
            !$mimeParts->isEmpty()
                ? $mimeParts->getBody()
                : static::getBodyFrom($mailhogResponse['Content']),
            !$mimeParts->isEmpty() ? $mimeParts->getAttachments() : []
        );
    }

    private static function getBodyFrom(array $content)
    {
        if (isset($content['Headers']['Content-Transfer-Encoding'][0]) &&
            $content['Headers']['Content-Transfer-Encoding'][0] === 'quoted-printable'
        ) {
            return quoted_printable_decode($content['Body']);
        }

        return $content['Body'];
    }
}
