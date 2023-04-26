<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Specification;

use rpkamp\Mailhog\Message\Contact;
use rpkamp\Mailhog\Message\Message;

final class BccRecipientSpecification implements Specification
{
    /**
     * @var Contact
     */
    private $bccRecipient;

    public function __construct(Contact $bccRecipient)
    {
        $this->bccRecipient = $bccRecipient;
    }

    public function isSatisfiedBy(Message $message): bool
    {
        return $message->bccRecipients->contains($this->bccRecipient);
    }
}
