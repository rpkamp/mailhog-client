<?php

namespace rpkamp\Mailhog\Message\Mime;

class MimePartCollection
{
    /**
     * @var MimePart[]
     */
    private $mimeParts;

    private function __construct(array $mimeParts)
    {
        $this->mimeParts = $mimeParts;
    }

    public static function fromMailhogResponse(array $mimeParts)
    {
        return new self(self::flattenParts($mimeParts));
    }

    protected static function flattenParts(array $mimeParts)
    {
        $flattenedParts = [];
        foreach ($mimeParts as $mimePart) {
            if (!isset($mimePart['MIME']['Parts'])) {
                $flattenedParts[] = MimePart::fromMailhogResponse($mimePart);
                continue;
            }

            $flattenedParts = array_merge($flattenedParts, self::flattenParts($mimePart['MIME']['Parts']));
        }

        return $flattenedParts;
    }

    public function isEmpty()
    {
        return count($this->mimeParts) === 0;
    }

    public function getAttachments(): array
    {
        $attachments = [];
        foreach ($this->mimeParts as $mimePart) {
            if (!$mimePart->isAttachment()) {
                continue;
            }

            $attachments[] = new Attachment(
                $mimePart->getFilename(),
                $mimePart->getContentType(),
                $mimePart->getBody()
            );
        }

        return $attachments;
    }

    public function getBody()
    {
        $textBody = '';
        foreach ($this->mimeParts as $mimePart) {
            if (stripos($mimePart->getContentType(), 'text/html') === 0) {
                return $mimePart->getBody();
            }
            if (stripos($mimePart->getContentType(), 'text/plain') === 0 && !$mimePart->isAttachment()) {
                $textBody = $mimePart->getBody();
            }
        }

        return $textBody;
    }
}
