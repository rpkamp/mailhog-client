<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Message\Mime;

use function array_merge;
use function count;
use function stripos;

class MimePartCollection
{
    /**
     * @var MimePart[]
     */
    private $mimeParts;

    /**
     * @param MimePart[] $mimeParts
     */
    private function __construct(array $mimeParts)
    {
        $this->mimeParts = $mimeParts;
    }

    /**
     * @param mixed[] $mimeParts
     */
    public static function fromMailhogResponse(array $mimeParts): self
    {
        return new self(self::flattenParts($mimeParts));
    }

    /**
     * @param mixed[] $mimeParts
     *
     * @return mixed[]
     */
    protected static function flattenParts(array $mimeParts): array
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

    public function isEmpty(): bool
    {
        return count($this->mimeParts) === 0;
    }

    /**
     * @return Attachment[]
     */
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

    public function getBody(): string
    {
        foreach ($this->mimeParts as $mimePart) {
            if ($mimePart->isAttachment()) {
                continue;
            }

            if (stripos($mimePart->getContentType(), 'text/html') === 0) {
                return $mimePart->getBody();
            }
        }

        foreach ($this->mimeParts as $mimePart) {
            if ($mimePart->isAttachment()) {
                continue;
            }

            if (stripos($mimePart->getContentType(), 'text/plain') === 0) {
                return $mimePart->getBody();
            }
        }

        return '';
    }
}
