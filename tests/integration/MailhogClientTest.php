<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Tests\integration;

use Generator;
use Http\Client\Curl\Client;
use Nyholm\Psr7\Factory\HttplugFactory;
use PHPUnit\Framework\TestCase;
use rpkamp\Mailhog\MailhogClient;
use rpkamp\Mailhog\Message\Mime\Attachment;
use rpkamp\Mailhog\Message\Contact;
use rpkamp\Mailhog\Message\Message;
use rpkamp\Mailhog\NoSuchMessageException;
use rpkamp\Mailhog\Specification\AndSpecification;
use rpkamp\Mailhog\Specification\BodySpecification;
use rpkamp\Mailhog\Specification\OrSpecification;
use rpkamp\Mailhog\Specification\SenderSpecification;
use rpkamp\Mailhog\Specification\Specification;
use rpkamp\Mailhog\Specification\SubjectSpecification;
use rpkamp\Mailhog\Tests\MailhogConfig;
use rpkamp\Mailhog\Tests\MessageTrait;
use RuntimeException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

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
    public function it_should_delete_the_message(): void
    {
        $this->sendDummyMessage();

        $message = $this->client->getLastMessage();

        $this->client->deleteMessage($message->messageId);

        $this->assertEquals(0, $this->client->getNumberOfMessages());
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
     * @dataProvider limitProvider
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
     * @return array<string, int[]>
     */
    public function limitProvider(): array
    {
        return [
            'one by one' => [1],
            'limit less than total' => [2],
            'limit equal to total' => [5],
            'limit more than total' => [10],
        ];
    }

    /**
     * @test
     * @dataProvider specificationProvider
     */
    public function it_should_find_messages_that_satisfy_specification(Specification $specification): void
    {
        $this->sendMessage(
            $this->createBasicMessage('me@myself.example', 'myself@myself.example', 'First message', 'Test body')
        );
        $this->sendMessage(
            $this->createBasicMessage('someoneelse@myself.example', 'myself@myself.example', 'Second message', 'Test body')
        );
        $this->sendMessage(
            $this->createBasicMessage('me@myself.example', 'myself@myself.example', 'Third message', 'Test body')
        );

        $messages = $this->client->findMessagesSatisfying($specification);

        $this->assertCount(2, $messages);

        $subjects = array_map(
            static function (Message $message) {
                return $message->subject;
            },
            $messages
        );

        $this->assertContains('First message', $subjects);
        $this->assertContains('Third message', $subjects);
    }

    /**
     * @return array<string, array{Specification}>
     */
    public function specificationProvider(): array
    {
        return [
            'sender specification' => [new SenderSpecification(new Contact('me@myself.example'))],
            'sender and body specification' => [
                new AndSpecification(
                    new SenderSpecification(new Contact('me@myself.example')),
                    new BodySpecification('Test')
                )
            ],
            'subject or other subject specification' => [
                new OrSpecification(
                    new SubjectSpecification('First message'),
                    new SubjectSpecification('Third message')
                )
            ]
        ];
    }

    /**
     * @test
     * @param Contact[] $expectedRecipients
     * @dataProvider messageProvider
     */
    public function it_should_receive_single_message_by_id(Email $messageToSend, array $expectedRecipients): void
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

    /**
     * @return array<string, array{Email, Contact[]}>
     */
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
        $messageToSend = (new Email())
            ->from('me@myself.example')
            ->text('Test body')
            ->subject('Test subject')
            ->addTo('myself@myself.example')
            ->addCc('cc@myself.example')
            ->addCc('cc2@myself.example')
            ->addBcc('bcc@myself.example')
            ->addBcc('bcc2@myself.example');

        $this->sendMessage($messageToSend);

        $this->assertEquals(1, $this->client->getNumberOfMessages());

        $messages = $this->client->findLatestMessages(1);

        $this->assertNotEmpty($messages[0]->messageId);
        $this->assertEquals(new Contact('me@myself.example'), $messages[0]->sender);
        $this->assertTrue($messages[0]->recipients->contains(new Contact('myself@myself.example')));
        $this->assertTrue($messages[0]->ccRecipients->contains(new Contact('cc@myself.example')));
        $this->assertTrue($messages[0]->ccRecipients->contains(new Contact('cc2@myself.example')));
        $this->assertEquals('Test subject', $messages[0]->subject);
        $this->assertEquals('Test body', $messages[0]->body);
    }

    /**
     * @test
     */
    public function it_should_hydrate_message_with_bcc_recipients_only(): void
    {
        $messageToSend = (new Email())
            ->from('me@myself.example')
            ->text('Test body')
            ->subject('Test subject')
            ->addBcc('bcc@myself.example')
            ->addBcc('bcc2@myself.example');

        $this->sendMessage($messageToSend);

        $this->assertEquals(1, $this->client->getNumberOfMessages());

        $messages = $this->client->findLatestMessages(1);

        $this->assertNotEmpty($messages[0]->messageId);
        $this->assertEquals(new Contact('me@myself.example'), $messages[0]->sender);
        $this->assertCount(0, $messages[0]->recipients);
        $this->assertCount(0, $messages[0]->ccRecipients);
        $this->assertEquals('Test subject', $messages[0]->subject);
        $this->assertEquals('Test body', $messages[0]->body);
    }

    /**
     * @test
     */
    public function it_should_hydrate_names(): void
    {
        $messageToSend = (new Email())
            ->from(new Address('me@myself.example', 'Me'))
            ->to(new Address('myself@myself.example', 'Myself'))
            ->addCc(new Address('cc@myself.example', 'CC Example'))
            ->addBcc(new Address('bcc@myself.example', 'BCC Example'))
            ->text('Test body')
            ->subject('Test subject');

        $this->sendMessage($messageToSend);

        $message = $this->client->getLastMessage();

        $this->assertEquals(new Contact('me@myself.example', 'Me'), $message->sender);
        $this->assertTrue($message->recipients->contains(new Contact('myself@myself.example', 'Myself')));
        $this->assertTrue($message->ccRecipients->contains(new Contact('cc@myself.example', 'CC Example')));
    }

    /**
     * @test
     */
    public function it_should_hydrate_message_with_attachment(): void
    {
        $message = $this->createBasicMessage('me@myself.example', 'myself@myself.example', 'Test subject', 'Test body');
        $message->attach(
            $this->readFile(__DIR__.'/../Fixtures/lorem-ipsum.txt'),
            'lorem-ipsum.txt',
            'text/plain'
        );

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
        $message = (new Email())
            ->from('me@myself.example')
            ->to('myself@myself.example')
            ->subject('Test subject')
            ->attach(
                $this->readFile(__DIR__.'/../Fixtures/lorem-ipsum.txt'),
                'lorem-ipsum.txt',
                'text/plain'
            )
            ->html('Hello world');

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
    public function it_should_prefer_html_part_over_plaintext_part(Email $messageToSend): void
    {
        $this->sendMessage($messageToSend);

        $allMessages = $this->client->findAllMessages();

        $message = iterator_to_array($allMessages)[0];

        $this->assertEquals('<h1>Hello world</h1>', $message->body);
    }

    /**
     * @return array<string, Email[]>
     */
    public function htmlMessageProvider(): array
    {
        $message = (new Email())
            ->from('me@myself.example')
            ->to('myself@myself.example')
            ->subject('Test subject');

        return [
            'html first' => [
                (clone $message)
                    ->html('<h1>Hello world</h1>')
                    ->text('Hello world')
            ],
            'plaintext first' => [
                (clone $message)
                    ->text('Hello world')
                    ->html('<h1>Hello world</h1>')
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_hydrate_attachments(): void
    {
        $message = (new Email())
            ->from('me@myself.example')
            ->to('myself@myself.example')
            ->subject('Test subject')
            ->attach(
                $this->readFile(__DIR__.'/../Fixtures/lorem-ipsum.txt'),
                'lorem-ipsum.txt',
                'text/plain'
            )
            ->attach(
                $this->readFile(__DIR__.'/../Fixtures/hog.png'),
                'hog.png',
                'image/png'
            )
            ->text('Hello world');

        $this->sendMessage($message);

        $allMessages = $this->client->findAllMessages();

        $message = iterator_to_array($allMessages)[0];

        $this->assertCount(2, $message->attachments);

        $fixture = $this->readFile(__DIR__.'/../Fixtures/lorem-ipsum.txt');

        $this->assertEquals(
            new Attachment('lorem-ipsum.txt', 'text/plain', $fixture),
            $message->attachments[0]
        );

        $fixture = $this->readFile(__DIR__ . '/../Fixtures/hog.png');

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

        $message = (new Email())
            ->from('me@myself.example')
            ->to('myself@myself.example')
            ->subject('Test subject')
            ->html($body);

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

        $message = (new Email())
            ->from('me@myself.example')
            ->to('me@myself.example')
            ->html($body)
            ->subject('Mailhog extension for Behat');

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
        $message = (new Email())
            ->from('me@myself.example')
            ->to('myself@myself.example')
            ->subject('Test subject')
            ->text('1 + 1 = 2');

        $this->sendMessage($message);

        $message = $this->client->getLastMessage();

        $this->assertEquals('1 + 1 = 2', $message->body);
    }

    private function readFile(string $path): string
    {
        $contents = file_get_contents($path);
        if (false === $contents) {
            throw new RuntimeException(
                sprintf(
                    'Unable to read file at "%s" - does it exist and is it readable?',
                    $path
                )
            );
        }

        return $contents;
    }
}
