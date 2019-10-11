<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Specification;

use rpkamp\Mailhog\Message\Message;
use function strpos;

final class BodySpecification implements Specification
{
    /**
     * @var string
     */
    private $snippet;

    public function __construct(string $snippet)
    {
        $this->snippet = $snippet;
    }

    public function isSatisfiedBy(Message $message): bool
    {
        return false !== strpos($message->body, $this->snippet);
    }
}
