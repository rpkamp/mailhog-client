<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Specification;

use rpkamp\Mailhog\Message\Message;

final class NotSpecification implements Specification
{
    public function __construct(private Specification $wrapped)
    {
    }

    public function isSatisfiedBy(Message $message): bool
    {
        return !$this->wrapped->isSatisfiedBy($message);
    }
}
