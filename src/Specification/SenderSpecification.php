<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Specification;

use rpkamp\Mailhog\Message\Contact;
use rpkamp\Mailhog\Message\Message;

final class SenderSpecification implements Specification
{
    /**
     * @var Contact
     */
    private $sender;

    public function __construct(Contact $sender)
    {
        $this->sender = $sender;
    }

    public function isSatisfiedBy(Message $message): bool
    {
        return $message->sender->equals($this->sender);
    }
}
