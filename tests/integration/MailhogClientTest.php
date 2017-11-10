<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Tests\integration;

use Generator;
use Http\Client\Curl\Client;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use PHPUnit\Framework\TestCase;
use rpkamp\Mailhog\MailhogClient;
use rpkamp\Mailhog\NoSuchMessageException;
use rpkamp\Mailhog\Tests\MessageTrait;

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

        $message = iterator_to_array($this->client->getAllMessages())[0];

        $this->assertNotEmpty($message->messageId);
        $this->assertEquals('me@myself.example', $message->sender);
        $this->assertEquals(['myself@myself.example'], $message->recipients);
        $this->assertEquals('Test subject', $message->subject);
        $this->assertEquals('Test body', $message->body);
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

        $messages = $this->client->getAllMessages(1);

        $this->assertInstanceOf(Generator::class, $messages);

        $this->assertCount(5, iterator_to_array($messages));
    }

    /**
     * @test
     */
    public function it_should_receive_single_message_by_id()
    {
        $this->sendMessage(
            $this->createBasicMessage('me@myself.example', 'myself@myself.example', 'Test subject', 'Test body')
        );

        $allMessages = $this->client->getAllMessages();

        $message = $this->client->getMessageById(iterator_to_array($allMessages)[0]->messageId);

        $this->assertNotEmpty($message->messageId);
        $this->assertEquals('me@myself.example', $message->sender);
        $this->assertEquals(['myself@myself.example'], $message->recipients);
        $this->assertEquals('Test subject', $message->subject);
        $this->assertEquals('Test body', $message->body);
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

        $message = iterator_to_array($this->client->getAllMessages())[0];

        $info = parse_url($_ENV['mailhog_smtp_dsn']);

        $this->client->releaseMessage($message->messageId, $info['host'], $info['port'], 'me@myself.example');

        $this->assertEquals(2, $this->client->getNumberOfMessages());
    }
}
