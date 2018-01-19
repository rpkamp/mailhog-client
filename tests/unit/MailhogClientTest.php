<?php
declare(strict_types=1);

namespace unit;

use Http\Client\Curl\Client;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use rpkamp\Mailhog\MailhogClient;

final class MailhogClientTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_remove_trailing_slashes_from_base_uri()
    {
        $client = new MailhogClient(new Client(), new GuzzleMessageFactory(), 'http://mailhog/');

        $property = new ReflectionProperty($client, 'baseUri');
        $property->setAccessible(true);

        $this->assertEquals('http://mailhog', $property->getValue($client));
    }
}
