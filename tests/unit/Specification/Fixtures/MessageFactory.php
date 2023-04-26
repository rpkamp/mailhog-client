<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Tests\unit\Specification\Fixtures;

use rpkamp\Mailhog\Message\Contact;
use rpkamp\Mailhog\Message\ContactCollection;
use rpkamp\Mailhog\Message\Headers;
use rpkamp\Mailhog\Message\Message;
use rpkamp\Mailhog\Message\Mime\Attachment;

final class MessageFactory
{
    public static function dummy(): Message
    {
        return new Message(
            '1234',
            new Contact('me@myself.example', 'Myself'),
            new ContactCollection([new Contact('someoneelse@myself.example')]),
            new ContactCollection([new Contact('someone-as-cc@myself.example')]),
            new ContactCollection([new Contact('someone-as-bcc@myself.example')]),
            'Hello world!',
            'Hi there',
            [
                new Attachment('lorem-ipsum.txt', 'text/plain', 'Lorem ipsum dolor sit amet!'),
            ],
            new Headers([])
        );
    }
}
