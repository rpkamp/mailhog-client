<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Specification;

use rpkamp\Mailhog\Message\Message;

final class SubjectSpecification implements Specification
{
    public function __construct(private string $subject)
    {
    }

    public function isSatisfiedBy(Message $message): bool
    {
        return $message->subject === $this->subject;
    }
}
