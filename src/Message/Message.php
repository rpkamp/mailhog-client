<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Message;

class Message
{
    /**
     * @var string
     */
    public $messageId;

    /**
     * @var string
     */
    public $sender;

    /**
     * @var string[]
     */
    public $recipients;

    /**
     * @var string
     */
    public $subject;

    /**
     * @var string
     */
    public $body;

    public function __construct(
        string $messageId,
        string $sender,
        array $recipients,
        string $subject,
        string $body
    ) {
        $this->messageId = $messageId;
        $this->sender = $sender;
        $this->recipients = $recipients;
        $this->subject = $subject;
        $this->body = $body;
    }
}
