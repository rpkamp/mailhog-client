<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Message;

use function preg_match;
use function stripslashes;
use function trim;

class Contact
{
    /**
     * @var string
     */
    public $emailAddress;

    /**
     * @var string|null
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

    /**
     * If both contacts have a name, they must be equal. If either or both contacts do not
     * have a name, then name is ignored and only email address is checked.
     */
    public function equals(Contact $other): bool
    {
        if (null !== $this->name && null !== $other->name && $this->name !== $other->name) {
            return false;
        }

        return $this->emailAddress === $other->emailAddress;
    }
}
