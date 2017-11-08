<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Tests\integration;

use Http\Client\Curl\Client;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use PHPUnit\Framework\TestCase;
use rpkamp\Mailhog\MailhogApiV1Client;
use rpkamp\Mailhog\Tests\MessageTrait;

class MailhogApiV1ClientTest extends TestCase
{
    use MessageTrait;

    /**
     * @var MailhogApiV1Client
     */
    private $client;

    public function setUp()
    {
        $this->client = new MailhogApiV1Client(new Client(), new GuzzleMessageFactory(), $_ENV['mailhog_api_uri']);
        $this->client->purgeMessages();
    }

    /**
     * @test
     */
    public function it_should_return_correct_number_of_messages_in_inbox()
    {
        $this->sendDummyEmail();

        $this->assertEquals(1, $this->client->getNumberOfMessages());
    }

    /**
     * @test
     */
    public function it_should_purge_the_inbox()
    {
        $this->sendDummyEmail();

        $this->client->purgeMessages();

        $this->assertEquals(0, $this->client->getNumberOfMessages());
    }

    /**
     * @test
     */
    public function it_should_receive_all_message_data()
    {
        $this->assertInternalType('array', $this->client->getAllMessages());
    }

    /**
     * @test
     */
    public function it_should_release_a_message()
    {
        $this->sendDummyEmail();

        $message = $this->client->getAllMessages()[0];

        $info = parse_url($_ENV['mailhog_smtp_dsn']);

        $this->client->releaseMessage($message->messageId, $info['host'], $info['port'], 'me@myself.example');

        $this->assertEquals(2, $this->client->getNumberOfMessages());
    }
}
