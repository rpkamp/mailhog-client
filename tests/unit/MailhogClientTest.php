<?php

declare(strict_types=1);

namespace rpkamp\Mailhog\Tests\unit;

use Http\Client\Curl\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use rpkamp\Mailhog\MailhogClient;

final class MailhogClientTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_remove_trailing_slashes_from_base_uri(): void
    {
        $client = new MailhogClient(new Client(), new Psr17Factory(), new Psr17Factory(), 'http://mailhog/');

        $property = new ReflectionProperty($client, 'baseUri');
        $property->setAccessible(true);

        $this->assertEquals('http://mailhog', $property->getValue($client));
    }
}
