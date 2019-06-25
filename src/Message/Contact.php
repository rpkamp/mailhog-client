<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Message;

class Contact
{
    /**
     * @var string
     */
    public $emailAddress;

    /**
     * @var null|string
     */
    public $name;

    public function __construct(string $emailAddress, ?string $name = null)
    {
        $this->emailAddress = $emailAddress;
        $this->name = $name;
    }

    public static function fromString(string $contact): Contact
    {
        if (preg_match('~^(?P<name>.*?)\s+<(?P<email>\S*?)>$~i', $contact, $matches)) {
            return new self($matches['email'], stripslashes(trim($matches['name'])));
        }

        return new self($contact);
    }

    public static function fromArray(array $contact): Contact
    {
        if (isset($contact['Mailbox']) && isset($contact['Domain'])) {
            return new self($contact['Mailbox'] . '@' . $contact['Domain']);
        }

        return new self('');
    }

    public function equals(Contact $other): bool
    {
        return $this->emailAddress === $other->emailAddress && $this->name === $other->name;
    }
}
