<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Message;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

use function array_map;
use function count;
use function str_getcsv;
use function trim;

/**
 * @implements IteratorAggregate<int, Contact>
 */
class ContactCollection implements Countable, IteratorAggregate
{
    /**
     * @param Contact[] $contacts
     */
    public function __construct(private array $contacts)
    {
    }

    public static function fromString(string $contacts): ContactCollection
    {
        if (trim($contacts) === '') {
            return new self([]);
        }

        return new self(
            array_map(
                static function (string $contact) {
                    return Contact::fromString($contact);
                },
                array_map('trim', str_getcsv($contacts, escape: '\\'))
            )
        );
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

    /**
     * @return Traversable<Contact>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->contacts);
    }

    public function count(): int
    {
        return count($this->contacts);
    }
}
