<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Specification;

use rpkamp\Mailhog\Message\Message;

final class HeaderSpecification implements Specification
{
    public function __construct(private string $headerName, private string |null $headerValue = null)
    {
    }

    public function isSatisfiedBy(Message $message): bool
    {
        return $this->headerValue
            ? $message->headers->get($this->headerName) === $this->headerValue
            : $message->headers->has($this->headerName);
    }
}
