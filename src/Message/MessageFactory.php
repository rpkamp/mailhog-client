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

        if (is_array($headers['From'][0])) {
            $from = Contact::fromArray($headers['From'][0] ?? []);
        } else {
            $from = Contact::fromString($headers['From'][0]);
        }

        if (is_array($headers['To'][0])) {
            $to = ContactCollection::fromArray($headers['To'][0] ?? []);
        } else {
            $to = ContactCollection::fromString($headers['To'][0] ?? '');
        }

        if (is_array($headers['Cc'][0])) {
            $cc = ContactCollection::fromArray($headers['Cc'][0] ?? []);
        } else {
            $cc = ContactCollection::fromString($headers['Cc'][0] ?? '');
        }

        if (is_array($headers['Bcc'][0])) {
            $bcc = ContactCollection::fromArray($headers['Bcc'][0] ?? []);
        } else {
            $bcc = ContactCollection::fromString($headers['Bcc'][0] ?? '');
        }

        return new Message(
            $mailhogResponse['ID'],
            $from,
            $to,
            $cc,
            $bcc,
            $headers['Subject'][0] ?? '',
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
