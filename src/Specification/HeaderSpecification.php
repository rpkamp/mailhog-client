<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Specification;

use rpkamp\Mailhog\Message\Message;

final class HeaderSpecification implements Specification
{
    /**
     * @var string
     */
    private $headerName;

    /**
     * @var string|null
     */
    private $headerValue;

    public function __construct(string $headerName, ?string $headerValue = null)
    {
        $this->headerName = $headerName;
        $this->headerValue = $headerValue;
    }

    public function isSatisfiedBy(Message $message): bool
    {
        return $this->headerValue
            ? $message->headers->get($this->headerName) === $this->headerValue
            : $message->headers->has($this->headerName);
    }
}
