<?php
declare(strict_types=1);

namespace rpkamp\Mailhog;

class Message
{
    /**
     * @var string
     */
    public $messageId;

    public function __construct(string $messageId)
    {
        $this->messageId = $messageId;
    }
}
