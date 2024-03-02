<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Specification;

use rpkamp\Mailhog\Message\Message;

final class AttachmentSpecification implements Specification
{
    public function __construct(private string $filename)
    {
    }

    public function isSatisfiedBy(Message $message): bool
    {
        foreach ($message->attachments as $attachment) {
            if ($attachment->filename === $this->filename) {
                return true;
            }
        }

        return false;
    }
}
