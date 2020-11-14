<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Message\Mime;

use function base64_decode;
use function explode;
use function preg_match;
use function quoted_printable_decode;
use function stripos;

class MimePart
{
    /**
     * @var string
     */
    private $contentType;

    /**
     * @var string|null
     */
    private $contentTransferEncoding;

    /**
     * @var bool
     */
    private $isAttachment;

    /**
     * @var string|null
     */
    private $filename;

    /**
     * @var string
     */
    private $body;

    private function __construct(
        string $contentType,
        ?string $contentTransferEncoding,
        bool $isAttachment,
        ?string $filename,
        string $body
    ) {
        $this->contentType = $contentType;
        $this->contentTransferEncoding = $contentTransferEncoding;
        $this->isAttachment = $isAttachment;
        $this->filename = $filename;
        $this->body = $body;
    }

    /**
     * @param mixed[] $mimePart
     */
    public static function fromMailhogResponse(array $mimePart): MimePart
    {
        $filename = null;
        if (
            isset($mimePart['Headers']['Content-Disposition'][0]) &&
            stripos($mimePart['Headers']['Content-Disposition'][0], 'attachment') === 0
        ) {
            $matches = [];
            preg_match('~filename=(?P<filename>.*?)(;|$)~i', $mimePart['Headers']['Content-Disposition'][0], $matches);
            $filename = $matches['filename'];
        }

        $isAttachment = false;
        if (isset($mimePart['Headers']['Content-Disposition'][0])) {
            $isAttachment = stripos($mimePart['Headers']['Content-Disposition'][0], 'attachment') === 0;
        }

        return new self(
            isset($mimePart['Headers']['Content-Type'][0])
                ? explode(';', $mimePart['Headers']['Content-Type'][0])[0]
                : 'application/octet-stream',
            $mimePart['Headers']['Content-Transfer-Encoding'][0] ?? null,
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
