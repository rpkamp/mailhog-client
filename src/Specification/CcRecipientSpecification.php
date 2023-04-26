<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Specification;

use rpkamp\Mailhog\Message\Contact;
use rpkamp\Mailhog\Message\Message;

final class CcRecipientSpecification implements Specification
{
    /**
     * @var Contact
     */
    private $ccRecipient;

    public function __construct(Contact $ccRecipient)
    {
        $this->ccRecipient = $ccRecipient;
    }

    public function isSatisfiedBy(Message $message): bool
    {
        return $message->ccRecipients->contains($this->ccRecipient);
    }
}
