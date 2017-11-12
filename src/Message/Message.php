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
     * @var Contact
     */
    public $sender;

    /**
     * @var Contact[]
     */
    public $recipients;

    /**
     * @var Contact[]
     */
    public $ccRecipients;

    /**
     * @var Contact[]
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

    /**
     * Message constructor.
     * @param string $messageId
     * @param Contact $sender
     * @param Contact[] $recipients
     * @param Contact[] $ccRecipients
     * @param Contact[] $bccRecipients
     * @param string $subject
     * @param string $body
     * @param array $attachments
     */
    public function __construct(
        string $messageId,
        Contact $sender,
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
