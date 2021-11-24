<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Message;

use InvalidArgumentException;
use rpkamp\Mailhog\Message\Mime\Attachment;

use function sprintf;

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
     * @var Attachment[]
     */
    public $attachments;

    /**
     * @var array<string, array<int, string>>|null
     */
    public $headers;

    /**
     * @param Attachment[]                           $attachments
     * @param array<string, array<int, string>>|null $headers
     */
    public function __construct(
        string $messageId,
        Contact $sender,
        ContactCollection $recipients,
        ContactCollection $ccRecipients,
        ContactCollection $bccRecipients,
        string $subject,
        string $body,
        array $attachments,
        ?array $headers = []
    ) {
        foreach ($attachments as $i => $attachment) {
            if (!$attachment instanceof Attachment) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Element %d of attachments array passed to "%s" was not an instance of "%s"',
                        $i,
                        self::class,
                        Attachment::class
                    )
                );
            }
        }

        $this->messageId = $messageId;
        $this->sender = $sender;
        $this->recipients = $recipients;
        $this->ccRecipients = $ccRecipients;
        $this->bccRecipients = $bccRecipients;
        $this->subject = $subject;
        $this->body = $body;
        $this->attachments = $attachments;
        $this->headers = $headers;
    }
}
