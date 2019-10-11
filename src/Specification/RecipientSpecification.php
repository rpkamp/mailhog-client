<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Specification;

use rpkamp\Mailhog\Message\Contact;
use rpkamp\Mailhog\Message\Message;

final class RecipientSpecification implements Specification
{
    /**
     * @var Contact
     */
    private $recipient;

    public function __construct(Contact $recipient)
    {
        $this->recipient = $recipient;
    }

    public function isSatisfiedBy(Message $message): bool
    {
        return $message->recipients->contains($this->recipient);
    }
}
