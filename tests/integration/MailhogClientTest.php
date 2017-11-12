<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Tests\integration;

use Generator;
use Http\Client\Curl\Client;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use PHPUnit\Framework\TestCase;
use rpkamp\Mailhog\MailhogClient;
use rpkamp\Mailhog\Message\Attachment;
use rpkamp\Mailhog\NoSuchMessageException;
use rpkamp\Mailhog\Tests\MessageTrait;
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

    public function setUp()
    {
        $this->client = new MailhogClient(new Client(), new GuzzleMessageFactory(), $_ENV['mailhog_api_uri']);
        $this->client->purgeMessages();
    }

    /**
     * @test
     */
    public function it_should_return_correct_number_of_messages_in_inbox()
    {
        $this->sendDummyMessage();

        $this->assertEquals(1, $this->client->getNumberOfMessages());
    }

    /**
     * @test
     */
    public function it_should_purge_the_inbox()
    {
        $this->sendDummyMessage();

        $this->client->purgeMessages();

        $this->assertEquals(0, $this->client->getNumberOfMessages());
    }

    /**
     * @test
     */
    public function it_should_receive_all_message_data()
    {
        $this->sendMessage(
            $this->createBasicMessage('me@myself.example', 'myself@myself.example', 'Test subject', 'Test body')
        );

        $message = iterator_to_array($this->client->findAllMessages())[0];

        $this->assertNotEmpty($message->messageId);
        $this->assertEquals('me@myself.example', $message->sender);
        $this->assertEquals(['myself@myself.example'], $message->recipients);
        $this->assertEquals('Test subject', $message->subject);
        $this->assertEquals('Test body', $message->body);
    }

    /**
     * @test
     */
    public function it_should_find_latest_messages()
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->sendDummyMessage();

            $this->assertCount($i, $this->client->findLatestMessages($i));
        }
    }

    /**
     * @test
     */
    public function it_should_find_last_message()
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->sendMessage(
                $this->createBasicMessage('me@myself.example', 'myself@myself.example', 'Test subject '.$i, 'Test body')
            );
        }

        $message = $this->client->getLastMessage();

        $this->assertNotEmpty($message->messageId);
        $this->assertEquals('me@myself.example', $message->sender);
        $this->assertEquals(['myself@myself.example'], $message->recipients);
        $this->assertEquals('Test subject 3', $message->subject);
        $this->assertEquals('Test body', $message->body);
    }

    /**
     * @test
     */
    public function it_should_throw_exception_when_there_is_no_last_message()
    {
        $this->expectException(NoSuchMessageException::class);
        $this->client->getLastMessage();
    }

    /**
     * @test
     */
    public function it_should_query_mailhog_until_all_messages_have_been_received()
    {
        for ($i = 0; $i < 5; $i++) {
            $this->sendMessage(
                $this->createBasicMessage('me@myself.example', 'myself@myself.example', 'Test subject', 'Test body')
            );
        }

        $messages = $this->client->findAllMessages(1);

        $this->assertInstanceOf(Generator::class, $messages);

        $this->assertCount(5, iterator_to_array($messages));
    }

    /**
     * @test
     * @dataProvider messageProvider
     */
    public function it_should_receive_single_message_by_id(Swift_Message $messageToSend, array $recipients)
    {
        $this->sendMessage($messageToSend);

        $allMessages = $this->client->findAllMessages();

        $message = $this->client->getMessageById(iterator_to_array($allMessages)[0]->messageId);

        $this->assertNotEmpty($message->messageId);
        $this->assertEquals('me@myself.example', $message->sender);
        $this->assertEquals($recipients, $message->recipients);
        $this->assertEquals('Test subject', $message->subject);
        $this->assertEquals('Test body', $message->body);
    }

    public function messageProvider()
    {
        $message = $this->createBasicMessage('me@myself.example', 'myself@myself.example', 'Test subject', 'Test body');

        return [
            'single recipient' => [
                $message,
                ['myself@myself.example']
            ],
            'multiple recipients' => [
                (clone $message)->addTo('i@myself.example'),
                ['myself@myself.example', 'i@myself.example'],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_hydrate_message_with_cc_and_bcc_recipients()
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
        $this->assertEquals('me@myself.example', $messages[0]->sender);
        $this->assertEquals(['myself@myself.example'], $messages[0]->recipients);
        $this->assertEquals(['cc@myself.example', 'cc2@myself.example'], $messages[0]->ccRecipients);
        $this->assertEquals(['bcc2@myself.example'], $messages[0]->bccRecipients);
        $this->assertEquals('Test subject', $messages[0]->subject);
        $this->assertEquals('Test body', $messages[0]->body);

        $this->assertNotEmpty($messages[1]->messageId);
        $this->assertEquals('me@myself.example', $messages[1]->sender);
        $this->assertEquals(['myself@myself.example'], $messages[1]->recipients);
        $this->assertEquals(['cc@myself.example', 'cc2@myself.example'], $messages[1]->ccRecipients);
        $this->assertEquals(['bcc@myself.example'], $messages[1]->bccRecipients);
        $this->assertEquals('Test subject', $messages[1]->subject);
        $this->assertEquals('Test body', $messages[1]->body);

        $this->assertNotEmpty($messages[2]->messageId);
        $this->assertEquals('me@myself.example', $messages[2]->sender);
        $this->assertEquals(['myself@myself.example'], $messages[2]->recipients);
        $this->assertEquals(['cc@myself.example', 'cc2@myself.example'], $messages[2]->ccRecipients);
        $this->assertEquals([], $messages[2]->bccRecipients);
        $this->assertEquals('Test subject', $messages[2]->subject);
        $this->assertEquals('Test body', $messages[2]->body);
    }

    /**
     * @test
     */
    public function it_should_hydrate_message_with_bcc_recipients_only()
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
        $this->assertEquals('me@myself.example', $messages[0]->sender);
        $this->assertEquals([], $messages[0]->recipients);
        $this->assertEquals([], $messages[0]->ccRecipients);
        $this->assertEquals(['bcc2@myself.example'], $messages[0]->bccRecipients);
        $this->assertEquals('Test subject', $messages[0]->subject);
        $this->assertEquals('Test body', $messages[0]->body);

        $this->assertNotEmpty($messages[1]->messageId);
        $this->assertEquals('me@myself.example', $messages[1]->sender);
        $this->assertEquals([], $messages[1]->recipients);
        $this->assertEquals([], $messages[1]->ccRecipients);
        $this->assertEquals(['bcc@myself.example'], $messages[1]->bccRecipients);
        $this->assertEquals('Test subject', $messages[1]->subject);
        $this->assertEquals('Test body', $messages[1]->body);
    }

    /**
     * @test
     */
    public function it_should_hydrate_message_with_attachment()
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
        $this->assertEquals('me@myself.example', $message->sender);
        $this->assertEquals(['myself@myself.example'], $message->recipients);
        $this->assertEquals('Test subject', $message->subject);
        $this->assertEquals('Test body', $message->body);
    }

    /**
     * @test
     */
    public function it_should_hydrate_message_with_attachment_before_body()
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
        $this->assertEquals('me@myself.example', $message->sender);
        $this->assertEquals(['myself@myself.example'], $message->recipients);
        $this->assertEquals('Test subject', $message->subject);
        $this->assertEquals('Hello world', $message->body);
    }

    /**
     * @test
     * @dataProvider htmlMessageProvider
     */
    public function it_should_prefer_html_part_over_plaintext_part(Swift_Message $messageToSend)
    {
        $this->sendMessage($messageToSend);

        $allMessages = $this->client->findAllMessages();

        $message = iterator_to_array($allMessages)[0];

        $this->assertEquals('<h1>Hello world</h1>', $message->body);
    }

    public function htmlMessageProvider()
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
    public function it_should_hydrate_attachments()
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

        $this->assertEquals(
            new Attachment('lorem-ipsum.txt', 'text/plain', file_get_contents(__DIR__.'/../Fixtures/lorem-ipsum.txt')),
            $message->attachments[0]
        );

        $this->assertEquals(
            new Attachment('hog.png', 'image/png', file_get_contents(__DIR__.'/../Fixtures/hog.png')),
            $message->attachments[1]
        );
    }

    /**
     * @test
     */
    public function it_should_throw_exception_when_no_message_found_by_id()
    {
        $this->expectException(NoSuchMessageException::class);
        $this->client->getMessageById('123');
    }

    /**
     * @test
     */
    public function it_should_release_a_message()
    {
        $this->sendDummyMessage();

        $message = iterator_to_array($this->client->findAllMessages())[0];

        $info = parse_url($_ENV['mailhog_smtp_dsn']);

        $this->client->releaseMessage($message->messageId, $info['host'], $info['port'], 'me@myself.example');

        $this->assertEquals(2, $this->client->getNumberOfMessages());
    }
}
