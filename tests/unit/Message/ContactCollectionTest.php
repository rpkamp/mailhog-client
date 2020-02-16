<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Tests\unit\Message;

use PHPUnit\Framework\TestCase;
use rpkamp\Mailhog\Message\Contact;
use rpkamp\Mailhog\Message\ContactCollection;

class ContactCollectionTest extends TestCase
{
    /**
     * @test
     * @param Contact[] $expectedContacts
     * @dataProvider contactsProvider
     */
    public function it_should_parse_contacts_from_string(string $contacts, array $expectedContacts): void
    {
        $collection = ContactCollection::fromString($contacts);

        foreach ($expectedContacts as $expectedContact) {
            $this->assertTrue($collection->contains($expectedContact));
        }
    }

    /**
     * @return array<string, array{string, Contact[]}>
     */
    public function contactsProvider(): array
    {
        return [
            'single e-mail address' => [
                'me@myself.example', [
                    new Contact('me@myself.example'),
                ],
            ],
            'single e-mail address and name' => [
                'Me <me@myself.example>', [
                    new Contact('me@myself.example', 'Me'),
                ],
            ],
            'single e-mail address and name with comma' => [
                '"Me, Msc" <me@myself.example>', [
                    new Contact('me@myself.example', 'Me, Msc'),
                ],
            ],
            'multiple e-mail addresses' => [
                'me@myself.example, myself@myself.example', [
                    new Contact('me@myself.example'),
                    new Contact('myself@myself.example'),
                ],
            ],
            'multiple e-mail addresses with names' => [
                'Me <me@myself.example>, Myself <myself@myself.example>', [
                    new Contact('me@myself.example', 'Me'),
                    new Contact('myself@myself.example', 'Myself'),
                ],
            ],
            'mixed e-mail addresses and names' => [
                'Me <me@myself.example>, myself@myself.example', [
                    new Contact('me@myself.example', 'Me'),
                    new Contact('myself@myself.example'),
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider equalContactsProvider
     */
    public function it_should_indicate_it_contains_a_contact_it_was_instantiated_with(Contact $instantiate, Contact $verify): void
    {
        $collection = new ContactCollection([$instantiate]);

        $this->assertTrue($collection->contains($instantiate));
    }

    /**
     * @return array<string, array{Contact, Contact}>
     */
    public function equalContactsProvider(): array
    {
        return [
            'e-mail address only' => [new Contact('me@myself.example'), new Contact('me@myself.example')],
            'e-mail address and name' => [new Contact('me@myself.example', 'Me'), new Contact('me@myself.example', 'Me')],
        ];
    }

    /**
     * @test
     * @dataProvider nonEqualContactsProvider
     */
    public function it_should_indicate_it_does_not_contain_contacts_it_was_not_instantiated_with(Contact $instantiate, Contact $verify): void
    {
        $collection = new ContactCollection([$instantiate]);

        $this->assertFalse($collection->contains($verify));
    }

    /**
     * @return array<string, array{Contact, Contact}>
     */
    public function nonEqualContactsProvider(): array
    {
        return [
            'different e-mail address' => [
                new Contact('me@myself.example'), new Contact('myself@myself.example'),
            ],
            'same e-mail address, different name' => [
                new Contact('me@myself.example', 'Me'), new Contact('me@myself.example', 'Myself'),
            ],
            'different e-mail address, same name' => [
                new Contact('me@myself.example', 'Me'), new Contact('myself@myself.example', 'Me'),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_indicate_correct_number_of_contacts_contained(): void
    {
        $collection = new ContactCollection([new Contact('me@myself.example'), new Contact('myself@myself.example')]);

        $this->assertEquals(2, count($collection));
    }

    /**
     * @test
     */
    public function it_should_return_iterator_with_contacts_it_was_instantiated_with(): void
    {
        $contacts = [new Contact('me@myself.example'), new Contact('myself@myself.example')];
        $collection = new ContactCollection($contacts);

        $retrievedContacts = iterator_to_array($collection);

        $this->assertEquals($contacts, $retrievedContacts);
    }
}
