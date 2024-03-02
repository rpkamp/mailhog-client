<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Specification;

use rpkamp\Mailhog\Message\Contact;
use rpkamp\Mailhog\Message\Message;

final class RecipientSpecification implements Specification
{
    public function __construct(private Contact $recipient)
    {
    }

    public function isSatisfiedBy(Message $message): bool
    {
        return $message->recipients->contains($this->recipient);
    }
}
