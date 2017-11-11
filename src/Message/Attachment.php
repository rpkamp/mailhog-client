<?php
declare(strict_types=1);


namespace rpkamp\Mailhog\Message;

class Attachment
{
    /**
     * @var string
     */
    public $filename;

    /**
     * @var string
     */
    public $mimeType;

    /**
     * @var string
     */
    public $content;

    public function __construct(string $filename, string $mimeType, string $content)
    {
        $this->filename = $filename;
        $this->mimeType = $mimeType;
        $this->content = $content;
    }
}
