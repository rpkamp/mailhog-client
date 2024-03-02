<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Message;

use InvalidArgumentException;
use rpkamp\Mailhog\Message\Mime\Attachment;

use function sprintf;

class Message
{
    /**
     * @param Attachment[] $attachments
     */
    public function __construct(
        public string $messageId,
        public Contact $sender,
        public ContactCollection $recipients,
        public ContactCollection $ccRecipients,
        public ContactCollection $bccRecipients,
        public string $subject,
        public string $body,
        public array $attachments,
        public Headers $headers
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
    }
}
