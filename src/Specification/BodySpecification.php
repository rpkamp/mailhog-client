<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Specification;

use rpkamp\Mailhog\Message\Message;

use function str_contains;

final class BodySpecification implements Specification
{
    public function __construct(private string $snippet)
    {
    }

    public function isSatisfiedBy(Message $message): bool
    {
        return str_contains($message->body, $this->snippet);
    }
}
