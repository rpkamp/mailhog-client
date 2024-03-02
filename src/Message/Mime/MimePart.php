<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Message\Mime;

use rpkamp\Mailhog\Message\Headers;

use function base64_decode;
use function explode;
use function preg_match;
use function quoted_printable_decode;
use function stripos;

class MimePart
{
    private function __construct(
        private string $contentType,
        private string |null $contentTransferEncoding,
        private bool $isAttachment,
        private string |null $filename,
        private string $body
    ) {
    }

    /**
     * @param mixed[] $mimePart
     */
    public static function fromMailhogResponse(array $mimePart): MimePart
    {
        $headers = Headers::fromMimePart($mimePart);

        $filename = null;
        if (
            $headers->has('Content-Disposition') &&
            stripos($headers->get('Content-Disposition'), 'attachment') === 0
        ) {
            $matches = [];
            preg_match('~filename=(?P<filename>.*?)(;|$)~i', $headers->get('Content-Disposition'), $matches);
            $filename = $matches['filename'];
        }

        $isAttachment = false;
        if ($headers->has('Content-Disposition')) {
            $isAttachment = stripos($headers->get('Content-Disposition'), 'attachment') === 0;
        }

        return new self(
            $headers->has('Content-Type')
                ? explode(';', $headers->get('Content-Type'))[0]
                : 'application/octet-stream',
            $headers->has('Content-Transfer-Encoding')
                ? $headers->get('Content-Transfer-Encoding')
                : null,
            $isAttachment,
            $filename,
            $mimePart['Body']
        );
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function isAttachment(): bool
    {
        return $this->isAttachment;
    }

    public function getFilename(): string
    {
        return $this->filename ?? 'unknown';
    }

    public function getBody(): string
    {
        if (false !== stripos($this->contentTransferEncoding ?? '', 'quoted-printable')) {
            return quoted_printable_decode($this->body);
        }

        if (false !== stripos($this->contentTransferEncoding ?? '', 'base64')) {
            return base64_decode($this->body);
        }

        return $this->body;
    }
}
