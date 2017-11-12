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
     * @var ContactCollection
     */
    public $recipients;

    /**
     * @var ContactCollection
     */
    public $ccRecipients;

    /**
     * @var ContactCollection
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
        Contact $sender,
        ContactCollection $recipients,
        ContactCollection $ccRecipients,
        ContactCollection $bccRecipients,
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
