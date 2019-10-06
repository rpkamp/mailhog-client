<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Specification;

use rpkamp\Mailhog\Message\Message;

final class NotSpecification implements Specification
{
    /**
     * @var Specification
     */
    private $wrapped;

    public function __construct(Specification $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    public function isSatisfiedBy(Message $message): bool
    {
        return !$this->wrapped->isSatisfiedBy($message);
    }
}
