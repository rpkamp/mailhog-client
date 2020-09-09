<?php

declare(strict_types=1);

namespace rpkamp\Mailhog\Tests\unit\Message;

use PHPUnit\Framework\TestCase;
use rpkamp\Mailhog\Message\Headers;

class HeadersTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_parse_headers(): void
    {
        $messageData = $this->getMessageData();
        $headers = Headers::fromMailHogResponse($messageData);

        $this->assertEquals(
            "Mailhog é muito bom mesmo: 😁",
            $headers->get("Subject")
        );

        $this->assertEquals(
            "José <jose@myself.example>, Letícia maranhão <leticia@myself.example>",
            $headers->get("To")
        );

        $this->assertEquals(
            "José de tal <no-reply@myself.example>",
            $headers->get("From")
        );

        $this->assertEquals(
            "quoted-printable",
            $headers->get("Content-Transfer-Encoding")
        );

        $this->assertEquals(
            "text/html; charset=utf-8",
            $headers->get("Content-Type")
        );
    }

    /**
     * @test
     */
    public function it_should_ignore_case_for_the_header_name(): void
    {
        $messageData = $this->getMessageData();
        $headers = Headers::fromMailHogResponse($messageData);

        $this->assertEquals(
            "José de tal <no-reply@myself.example>",
            $headers->get("from")
        );
    }

    /**
     * @test
     */
    public function it_should_return_the_default_value_when_the_header_does_not_exist(): void
    {
        $headers = new Headers([]);

        $this->assertEquals(
            'default value',
            $headers->get('foobar', 'default value')
        );
    }

    /**
     * @test
     */
    public function it_should_check_if_a_header_exists(): void
    {
        $headers = new Headers([]);
        $this->assertFalse($headers->has('foobar'));
    }

    /**
     * @return array<mixed, mixed>
     */
    private function getMessageData(): array
    {
        $contents = file_get_contents(__DIR__ . '/Fixtures/sample_mailhog_response.json');
        if (!$contents) {
            return [];
        }

        $allMessagesData = json_decode($contents, true);
        return $allMessagesData['items'][0];
    }
}
