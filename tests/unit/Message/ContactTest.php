<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Tests\unit\Message;

use PHPUnit\Framework\TestCase;
use rpkamp\Mailhog\Message\Contact;

class ContactTest extends TestCase
{
    /**
     * @test
     * @dataProvider contactProvider
     */
    public function it_should_parse_to_email_address_and_name(string $input, Contact $expectedContact)
    {
        $this->assertEquals($expectedContact, Contact::fromString($input));
    }

    public function contactProvider()
    {
        return [
            'e-mail address only' => [
                'me@myself.example',
                new Contact('me@myself.example')
            ],
            'e-mail address and name' => [
                'Me <me@myself.example>',
                new Contact('me@myself.example', 'Me')
            ],
            'e-mail address and name with <' => [
                'I <3 email <me@myself.example>',
                new Contact('me@myself.example', 'I <3 email')
            ],
            'e-mail address and name with >' => [
                'I > email <me@myself.example>',
                new Contact('me@myself.example', 'I > email')
            ],
            'e-mail address and name with double quotes' => [
                'Me \"Email King\" Myself <me@myself.example>',
                new Contact('me@myself.example', 'Me "Email King" Myself')
            ],
        ];
    }

    /**
     * @test
     * @dataProvider sameContactProvider
     */
    public function it_should_indicate_when_equal_to_other_contact(Contact $contact)
    {
        $this->assertTrue($contact->equals($contact));
    }

    public function sameContactProvider()
    {
        return [
            'e-mail address only' => [new Contact('me@myself.example')],
            'e-mail address and name' => [new Contact('me@myself.example', 'Me')],
        ];
    }
}
