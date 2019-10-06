<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Tests\unit\Specification\Fixtures;

use rpkamp\Mailhog\Message\Contact;
use rpkamp\Mailhog\Message\ContactCollection;
use rpkamp\Mailhog\Message\Message;

final class MessageFactory
{
    public static function dummy(): Message
    {
        return new Message(
            '1234',
            new Contact('me@myself.example', 'Myself'),
            new ContactCollection([new Contact('someoneelse@myself.example')]),
            new ContactCollection([]),
            new ContactCollection([]),
            'Hello world!',
            'Hi there',
            []
        );
    }
}
