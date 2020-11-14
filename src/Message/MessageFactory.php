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
        $headers = Headers::fromMailhogResponse($mailhogResponse);

        return new Message(
            $mailhogResponse['ID'],
            Contact::fromString($headers->get('From')),
            ContactCollection::fromString($headers->get('To', '')),
            ContactCollection::fromString($headers->get('Cc', '')),
            ContactCollection::fromString($headers->get('Bcc', '')),
            $headers->get('Subject', ''),
            !$mimeParts->isEmpty()
                ? $mimeParts->getBody()
                : static::decodeBody($headers, $mailhogResponse['Content']['Body']),
            !$mimeParts->isEmpty() ? $mimeParts->getAttachments() : []
        );
    }

    private static function decodeBody(Headers $headers, string $body): string
    {
        if ($headers->get('Content-Transfer-Encoding') === 'quoted-printable') {
            return quoted_printable_decode($body);
        }

        return $body;
    }
}
