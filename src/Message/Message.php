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
     * @var array
     */
    public $ccRecipients;

    /**
     * @var array
     */
    public $bccRecipients;

    /**
     * @var string
     */
    public $subject;

    /**
     * @var string
     */
    public $body;

    /**
     * @var array
     */
    public $attachments;

    public function __construct(
        string $messageId,
        string $sender,
        array $recipients,
        array $ccRecipients,
        array $bccRecipients,
        string $subject,
        string $body,
        array $attachments
    ) {
        $this->messageId = $messageId;
        $this->sender = $sender;
        $this->recipients = $recipients;
        $this->ccRecipients = $ccRecipients;
        $this->bccRecipients = $bccRecipients;
        $this->subject = $subject;
        $this->body = $body;
        $this->attachments = $attachments;
    }
}
