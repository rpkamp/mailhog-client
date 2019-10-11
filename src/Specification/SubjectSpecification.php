<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Specification;

use rpkamp\Mailhog\Message\Message;

final class SubjectSpecification implements Specification
{
    /**
     * @var string
     */
    private $subject;

    public function __construct(string $subject)
    {
        $this->subject = $subject;
    }

    public function isSatisfiedBy(Message $message): bool
    {
        return $message->subject === $this->subject;
    }
}
