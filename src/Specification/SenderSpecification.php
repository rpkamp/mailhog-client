<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Specification;

use rpkamp\Mailhog\Message\Contact;
use rpkamp\Mailhog\Message\Message;

final class SenderSpecification implements Specification
{
    public function __construct(private Contact $sender)
    {
    }

    public function isSatisfiedBy(Message $message): bool
    {
        return $message->sender->equals($this->sender);
    }
}
