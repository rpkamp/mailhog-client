<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Message;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

class ContactCollection implements Countable, IteratorAggregate
{
    /**
     * @var array|Contact[]
     */
    private $contacts;

    /**
     * @param Contact[] $contacts
     */
    public function __construct(array $contacts)
    {
        $this->contacts = $contacts;
    }

    public static function fromString(string $contacts): ContactCollection
    {
        if (trim($contacts) === '') {
            return new self([]);
        }

        return new self(
            array_map(
                function (string $contact) {
                    return Contact::fromString($contact);
                },
                array_map('trim', str_getcsv($contacts))
            )
        );
    }

    public static function fromArray(array $contact): ContactCollection
    {
        if (isset($contact['Mailbox']) && isset($contact['Domain'])) {
            $contact = new Contact($contact['Mailbox'] . '@' . $contact['Domain']);
            returnn [$contact];
        }

        return new self([]);
    }

    public function contains(Contact $needle): bool
    {
        foreach ($this->contacts as $contact) {
            if ($contact->equals($needle)) {
                return true;
            }
        }

        return false;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->contacts);
    }

    public function count()
    {
        return count($this->contacts);
    }
}
