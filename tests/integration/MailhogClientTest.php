<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Tests\integration;

use Generator;
use Http\Client\Curl\Client;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Nyholm\Psr7\Factory\HttplugFactory;
use PHPUnit\Framework\TestCase;
use rpkamp\Mailhog\MailhogClient;
use rpkamp\Mailhog\Message\Mime\Attachment;
use rpkamp\Mailhog\Message\Contact;
use rpkamp\Mailhog\Message\Message;
use rpkamp\Mailhog\NoSuchMessageException;
use rpkamp\Mailhog\Tests\MailhogConfig;
use rpkamp\Mailhog\Tests\MessageTrait;
use RuntimeException;
use Swift_Attachment;
use Swift_ByteStream_FileByteStream;
use Swift_Message;

class MailhogClientTest extends TestCase
{
    use MessageTrait;

    /**
     * @var MailhogClient
     */
    private $client;

    public function setUp(): void
    {
        $this->client = new MailhogClient(new Client(), new HttplugFactory(), $_ENV['mailhog_api_uri']);
        $this->client->purgeMessages();
    }

    /**
     * @test
     */
    public function it_should_return_correct_number_of_messages_in_inbox(): void
    {
        $this->sendDummyMessage();

        $this->assertEquals(1, $this->client->getNumberOfMessages());
    }

    /**
     * @test
     */
    public function it_should_purge_the_inbox(): void
    {
        $this->sendDummyMessage();

        $this->client->purgeMessages();

        $this->assertEquals(0, $this->client->getNumberOfMessages());
    }

    /**
     * @test
     */
    public function it_should_receive_all_message_data(): void
    {
        $this->sendMessage(
            $this->createBasicMessage('me@myself.example', 'myself@myself.example', 'Test subject', 'Test body')
        );

        /** @var Message $message */
        $message = iterator_to_array($this->client->findAllMessages())[0];

        $this->assertNotEmpty($message->messageId);
        $this->assertEquals(new Contact('me@myself.example'), $message->sender);
        $this->assertTrue($message->recipients->contains(new Contact('myself@myself.example')));
        $this->assertEquals('Test subject', $message->subject);
        $this->assertEquals('Test body', $message->body);
    }

    /**
     * @test
     */
    public function it_should_handle_message_without_subject_correctly(): void
    {
        $this->sendMessage(
            $this->createBasicMessage('me@myself.example', 'myself@myself.example', '', 'Test body')
        );

        /** @var Message $message */
        $message = iterator_to_array($this->client->findAllMessages())[0];

        $this->assertEquals('', $message->subject);
    }

    /**
     * @test
     */
    public function it_should_find_latest_messages(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->sendDummyMessage();

            $this->assertCount($i, $this->client->findLatestMessages($i));
        }
    }

    /**
     * @test
     */
    public function it_should_find_last_message(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->sendMessage(
                $this->createBasicMessage('me@myself.example', 'myself@myself.example', 'Test subject '.$i, 'Test body')
            );
        }

        $message = $this->client->getLastMessage();

        $this->assertNotEmpty($message->messageId);
        $this->assertEquals(new Contact('me@myself.example'), $message->sender);
        $this->assertTrue($message->recipients->contains(new Contact('myself@myself.example')));
        $this->assertEquals('Test subject 3', $message->subject);
        $this->assertEquals('Test body', $message->body);
    }

    /**
     * @test
     */
    public function it_should_throw_exception_when_there_is_no_last_message(): void
    {
        $this->expectException(NoSuchMessageException::class);
        $this->client->getLastMessage();
    }

    /**
     * @test
     */
    public function it_should_query_mailhog_until_all_messages_have_been_received(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->sendMessage(
                $this->createBasicMessage('me@myself.example', 'myself@myself.example', 'Test subject '.$i, 'Test body')
            );
        }

        $messages = $this->client->findAllMessages(2);

        $this->assertInstanceOf(Generator::class, $messages);

        $messages = iterator_to_array($messages);

        $this->assertCount(5, $messages);

        $allSubjects = \array_map(
            function (Message $message) {
                return $message->subject;
            },
            $messages
        );

        for ($i = 0; $i < 5; $i++) {
            $this->assertContains('Test subject '.$i, $allSubjects);
        }
    }

    /**
     * @test
     * @dataProvider messageProvider
     */
    public function it_should_receive_single_message_by_id(Swift_Message $messageToSend, array $expectedRecipients): void
    {
        $this->sendMessage($messageToSend);

        $allMessages = $this->client->findAllMessages();

        $message = $this->client->getMessageById(iterator_to_array($allMessages)[0]->messageId);

        $this->assertNotEmpty($message->messageId);
        $this->assertEquals(new Contact('me@myself.example'), $message->sender);

        foreach ($expectedRecipients as $expectedRecipient) {
            $this->assertTrue($message->recipients->contains($expectedRecipient));
        }

        $this->assertEquals('Test subject', $message->subject);
        $this->assertEquals('Test body', $message->body);
    }

    public function messageProvider(): array
    {
        $message = $this->createBasicMessage('me@myself.example', 'myself@myself.example', 'Test subject', 'Test body');

        return [
            'single recipient' => [
                $message,
                [new Contact('myself@myself.example')]
            ],
            'multiple recipients' => [
                (clone $message)->addTo('i@myself.example'),
                [new Contact('myself@myself.example'), new Contact('i@myself.example')],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_hydrate_message_with_cc_and_bcc_recipients(): void
    {
        $messageToSend = (new Swift_Message())
            ->setFrom('me@myself.example')
            ->setBody('Test body')
            ->setSubject('Test subject')
            ->addTo('myself@myself.example')
            ->addCc('cc@myself.example')
            ->addCc('cc2@myself.example')
            ->addBcc('bcc@myself.example')
            ->addBcc('bcc2@myself.example');

        $this->sendMessage($messageToSend);

        $this->assertEquals(3, $this->client->getNumberOfMessages());

        $messages = $this->client->findLatestMessages(3);

        $this->assertNotEmpty($messages[0]->messageId);
        $this->assertEquals(new Contact('me@myself.example'), $messages[0]->sender);
        $this->assertTrue($messages[0]->recipients->contains(new Contact('myself@myself.example')));
        $this->assertTrue($messages[0]->ccRecipients->contains(new Contact('cc@myself.example')));
        $this->assertTrue($messages[0]->ccRecipients->contains(new Contact('cc2@myself.example')));
        $this->assertTrue($messages[0]->bccRecipients->contains(new Contact('bcc2@myself.example')));
        $this->assertEquals('Test subject', $messages[0]->subject);
        $this->assertEquals('Test body', $messages[0]->body);

        $this->assertNotEmpty($messages[1]->messageId);
        $this->assertEquals(new Contact('me@myself.example'), $messages[1]->sender);

        $this->assertTrue($messages[1]->recipients->contains(new Contact('myself@myself.example')));
        $this->assertTrue($messages[1]->ccRecipients->contains(new Contact('cc@myself.example')));
        $this->assertTrue($messages[1]->ccRecipients->contains(new Contact('cc2@myself.example')));
        $this->assertTrue($messages[1]->bccRecipients->contains(new Contact('bcc@myself.example')));
        $this->assertEquals('Test subject', $messages[1]->subject);
        $this->assertEquals('Test body', $messages[1]->body);

        $this->assertNotEmpty($messages[2]->messageId);
        $this->assertEquals(new Contact('me@myself.example'), $messages[2]->sender);
        $this->assertTrue($messages[2]->recipients->contains(new Contact('myself@myself.example')));
        $this->assertTrue($messages[2]->ccRecipients->contains(new Contact('cc@myself.example')));
        $this->assertTrue($messages[2]->ccRecipients->contains(new Contact('cc2@myself.example')));
        $this->assertCount(0, $messages[2]->bccRecipients);
        $this->assertEquals('Test subject', $messages[2]->subject);
        $this->assertEquals('Test body', $messages[2]->body);
    }

    /**
     * @test
     */
    public function it_should_hydrate_message_with_bcc_recipients_only(): void
    {
        $messageToSend = (new Swift_Message())
            ->setFrom('me@myself.example')
            ->setBody('Test body')
            ->setSubject('Test subject')
            ->addBcc('bcc@myself.example')
            ->addBcc('bcc2@myself.example');

        $this->sendMessage($messageToSend);

        $this->assertEquals(2, $this->client->getNumberOfMessages());

        $messages = $this->client->findLatestMessages(2);

        $this->assertNotEmpty($messages[0]->messageId);
        $this->assertEquals(new Contact('me@myself.example'), $messages[0]->sender);
        $this->assertCount(0, $messages[0]->recipients);
        $this->assertCount(0, $messages[0]->ccRecipients);
        $this->assertTrue($messages[0]->bccRecipients->contains(new Contact('bcc2@myself.example')));
        $this->assertEquals('Test subject', $messages[0]->subject);
        $this->assertEquals('Test body', $messages[0]->body);

        $this->assertNotEmpty($messages[1]->messageId);
        $this->assertEquals(new Contact('me@myself.example'), $messages[1]->sender);
        $this->assertCount(0, $messages[1]->recipients);
        $this->assertCount(0, $messages[1]->ccRecipients);
        $this->assertTrue($messages[1]->bccRecipients->contains(new Contact('bcc@myself.example')));
        $this->assertEquals('Test subject', $messages[1]->subject);
        $this->assertEquals('Test body', $messages[1]->body);
    }

    /**
     * @test
     */
    public function it_should_hydrate_names(): void
    {
        $messageToSend = (new Swift_Message())
            ->setFrom('me@myself.example', 'Me')
            ->setTo('myself@myself.example', 'Myself')
            ->addCc('cc@myself.example', 'CC Example')
            ->addBcc('bcc@myself.example', 'BCC Example')
            ->setBody('Test body')
            ->setSubject('Test subject');

        $this->sendMessage($messageToSend);

        $message = $this->client->getLastMessage();

        $this->assertEquals(new Contact('me@myself.example', 'Me'), $message->sender);
        $this->assertTrue($message->recipients->contains(new Contact('myself@myself.example', 'Myself')));
        $this->assertTrue($message->ccRecipients->contains(new Contact('cc@myself.example', 'CC Example')));
        $this->assertTrue($message->bccRecipients->contains(new Contact('bcc@myself.example', 'BCC Example')));
    }

    /**
     * @test
     */
    public function it_should_hydrate_message_with_attachment(): void
    {
        $message = $this->createBasicMessage('me@myself.example', 'myself@myself.example', 'Test subject', 'Test body');
        $message->attach(new Swift_Attachment(
            new Swift_ByteStream_FileByteStream(__DIR__.'/../Fixtures/lorem-ipsum.txt'),
            'lorem-ipsum.txt',
            'text/plain'
        ));

        $this->sendMessage($message);

        $allMessages = $this->client->findAllMessages();

        $message = iterator_to_array($allMessages)[0];

        $this->assertNotEmpty($message->messageId);
        $this->assertEquals(new Contact('me@myself.example'), $message->sender);
        $this->assertTrue($message->recipients->contains(new Contact('myself@myself.example')));
        $this->assertEquals('Test subject', $message->subject);
        $this->assertEquals('Test body', $message->body);
    }

    /**
     * @test
     */
    public function it_should_hydrate_message_with_attachment_before_body(): void
    {
        $message = (new Swift_Message())
            ->setFrom('me@myself.example')
            ->setTo('myself@myself.example')
            ->setSubject('Test subject')
            ->attach(new Swift_Attachment(
                new Swift_ByteStream_FileByteStream(__DIR__.'/../Fixtures/lorem-ipsum.txt'),
                'lorem-ipsum.txt',
                'text/plain'
            ))
            ->addPart('Hello world', 'text/plain');

        $this->sendMessage($message);

        $allMessages = $this->client->findAllMessages();

        $message = iterator_to_array($allMessages)[0];

        $this->assertNotEmpty($message->messageId);
        $this->assertEquals(new Contact('me@myself.example'), $message->sender);
        $this->assertTrue($message->recipients->contains(new Contact('myself@myself.example')));
        $this->assertEquals('Test subject', $message->subject);
        $this->assertEquals('Hello world', $message->body);
    }

    /**
     * @test
     * @dataProvider htmlMessageProvider
     */
    public function it_should_prefer_html_part_over_plaintext_part(Swift_Message $messageToSend): void
    {
        $this->sendMessage($messageToSend);

        $allMessages = $this->client->findAllMessages();

        $message = iterator_to_array($allMessages)[0];

        $this->assertEquals('<h1>Hello world</h1>', $message->body);
    }

    public function htmlMessageProvider(): array
    {
        $message = (new Swift_Message())
            ->setFrom('me@myself.example')
            ->setTo('myself@myself.example')
            ->setSubject('Test subject');

        return [
            'html first' => [
                (clone $message)
                    ->addPart('<h1>Hello world</h1>', 'text/html')
                    ->addPart('Hello world', 'text/plain')
            ],
            'plaintext first' => [
                (clone $message)
                    ->addPart('Hello world', 'text/plain')
                    ->addPart('<h1>Hello world</h1>', 'text/html')
            ],
            'mime capitals, html first' => [
                (clone $message)
                    ->addPart('<h1>Hello world</h1>', 'TEXT/HTML')
                    ->addPart('Hello world', 'TEXT/PLAIN')
            ],
            'mime capitals, plaintext first' => [
                (clone $message)
                    ->addPart('Hello world', 'TEXT/PLAIN')
                    ->addPart('<h1>Hello world</h1>', 'TEXT/HTML')
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_hydrate_attachments(): void
    {
        $message = (new Swift_Message())
            ->setFrom('me@myself.example')
            ->setTo('myself@myself.example')
            ->setSubject('Test subject')
            ->attach(new Swift_Attachment(
                new Swift_ByteStream_FileByteStream(__DIR__.'/../Fixtures/lorem-ipsum.txt'),
                'lorem-ipsum.txt',
                'text/plain'
            ))
            ->attach(new Swift_Attachment(
                new Swift_ByteStream_FileByteStream(__DIR__.'/../Fixtures/hog.png'),
                'hog.png',
                'image/png'
            ))
            ->addPart('Hello world', 'text/plain');

        $this->sendMessage($message);

        $allMessages = $this->client->findAllMessages();

        $message = iterator_to_array($allMessages)[0];

        $this->assertCount(2, $message->attachments);

        $fixture = file_get_contents(__DIR__.'/../Fixtures/lorem-ipsum.txt');
        if (false === $fixture) {
            throw new RuntimeException(
                sprintf('Unable to read "%s"', realpath(__DIR__).'/../Fixtures/lorem-ipsum.txt')
            );
        }

        $this->assertEquals(
            new Attachment('lorem-ipsum.txt', 'text/plain', $fixture),
            $message->attachments[0]
        );

        $fixture = file_get_contents(__DIR__ . '/../Fixtures/hog.png');
        if (false === $fixture) {
            throw new RuntimeException(
                sprintf('Unable to read "%s"', realpath(__DIR__).'/../Fixtures/hog.png')
            );
        }

        $this->assertEquals(
            new Attachment('hog.png', 'image/png', $fixture),
            $message->attachments[1]
        );
    }

    /**
     * @test
     */
    public function it_should_throw_exception_when_no_message_found_by_id(): void
    {
        $this->expectException(NoSuchMessageException::class);
        $this->client->getMessageById('123');
    }

    /**
     * @test
     */
    public function it_should_release_a_message(): void
    {
        $this->sendDummyMessage();

        $message = iterator_to_array($this->client->findAllMessages())[0];

        $info = parse_url($_ENV['mailhog_smtp_dsn']);

        $this->client->releaseMessage(
            $message->messageId,
            MailhogConfig::getHost(),
            MailhogConfig::getPort(),
            'me@myself.example'
        );

        $this->assertEquals(2, $this->client->getNumberOfMessages());
    }

    /**
     * @test
     */
    public function it_should_decode_quoted_printable_html_messages(): void
    {
        $body = <<<BODY
<!DOCTYPE html>
<html>
<head><title>Hello world</title></head>
<body><h1>Hello world</h1>If you want to search for things, go to <a href="https://www.google.com/">google</a>.</body>
</html>
BODY;

        $message = (new Swift_Message())
            ->setFrom('me@myself.example')
            ->setTo('myself@myself.example')
            ->setSubject('Test subject')
            ->addPart($body, 'text/html');

        $this->sendMessage($message);

        $message = $this->client->getLastMessage();

        $this->assertEquals(str_replace(PHP_EOL, "\r\n", $body), $message->body);
    }

    /**
     * @test
     */
    public function it_should_decode_quoted_printable_html_messages_non_mime_part(): void
    {
        $body = <<<BODY
<!DOCTYPE html>
<html>
<head><title>Hello world</title></head>
<body><h1>Hello world</h1>If you want to search for things, go to <a href="https://www.google.com/">google</a>.</body>
</html>
BODY;

        $message = (new Swift_Message())
            ->setFrom('me@myself.example', 'Myself')
            ->setTo('me@myself.example')
            ->setBody($body)
            ->setSubject('Mailhog extension for Behat');

        $this->sendMessage($message);

        $message = $this->client->getLastMessage();

        $this->assertEquals(
            str_replace(PHP_EOL, "\r\n", $body),
            $message->body
        );
    }

    /**
     * @test
     */
    public function it_should_decode_quoted_printable_text_messages(): void
    {
        $message = (new Swift_Message())
            ->setFrom('me@myself.example')
            ->setTo('myself@myself.example')
            ->setSubject('Test subject')
            ->addPart('1 + 1 = 2', 'text/plain');

        $this->sendMessage($message);

        $message = $this->client->getLastMessage();

        $this->assertEquals('1 + 1 = 2', $message->body);
    }
}
