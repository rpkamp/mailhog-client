<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Tests\unit\Message;

use InvalidArgumentException;
use rpkamp\Mailhog\Message\Contact;
use rpkamp\Mailhog\Message\ContactCollection;
use rpkamp\Mailhog\Message\Message;
use PHPUnit\Framework\TestCase;
use rpkamp\Mailhog\Message\Mime\Attachment;
use RuntimeException;
use stdClass;

final class MessageTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_return_values_it_was_instantiated_with(): void
    {
        $attachments = [
            new Attachment('lorem-ipsum.text', 'text/plain', 'Lorem ipsum dolor sit amet!')
        ];

        $message = new Message(
            '123',
            new Contact('me@myself.example'),
            new ContactCollection([new Contact('recipient@myself.example')]),
            new ContactCollection([new Contact('cc@myself.example')]),
            new ContactCollection([new Contact('bcc@myself.example')]),
            'Heya',
            'Hello there!',
            $attachments
        );

        $this->assertEquals('123', $message->messageId);
        $this->assertEquals(new Contact('me@myself.example'), $message->sender);
        $this->assertEquals(new ContactCollection([new Contact('recipient@myself.example')]), $message->recipients);
        $this->assertEquals(new ContactCollection([new Contact('cc@myself.example')]), $message->ccRecipients);
        $this->assertEquals(new ContactCollection([new Contact('bcc@myself.example')]), $message->bccRecipients);
        $this->assertEquals('Heya', $message->subject);
        $this->assertEquals('Hello there!', $message->body);
        $this->assertEquals($attachments, $message->attachments);
    }

    /**
     * @test
     */
    public function it_should_throw_exception_when_non_attachment_supplied_as_attachment(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Message(
            '123',
            new Contact('me@myself.example'),
            new ContactCollection([new Contact('recipient@myself.example')]),
            new ContactCollection([new Contact('cc@myself.example')]),
            new ContactCollection([new Contact('bcc@myself.example')]),
            'Heya',
            'Hello there!',
            [new stdClass()]
        );
    }
}
