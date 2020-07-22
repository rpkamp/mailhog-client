<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Message;

use rpkamp\Mailhog\Message\Mime\MimePartCollection;
use function quoted_printable_decode;

class MessageFactory
{
    /**
     * @param mixed[] $mailhogResponse
     */
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
            $headers['Subject'][0] ?? '',
            !$mimeParts->isEmpty()
                ? $mimeParts->getBody()
                : static::getBodyFrom($mailhogResponse['Content']),
            !$mimeParts->isEmpty() ? $mimeParts->getAttachments() : [],
            $headers
        );
    }

    /**
     * @param mixed[] $content
     */
    private static function getBodyFrom(array $content): string
    {
        if (isset($content['Headers']['Content-Transfer-Encoding'][0]) &&
            $content['Headers']['Content-Transfer-Encoding'][0] === 'quoted-printable'
        ) {
            return quoted_printable_decode($content['Body']);
        }

        return $content['Body'];
    }
}
