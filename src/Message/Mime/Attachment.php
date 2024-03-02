<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Message\Mime;

class Attachment
{
    public function __construct(
        public string $filename,
        public string $mimeType,
        public string $content,
    ) {
    }
}
