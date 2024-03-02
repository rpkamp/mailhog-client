<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Message;

use function iconv_mime_decode;
use function strtolower;

class Headers
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(private array $headers)
    {
    }

    /**
     * @param array<mixed, mixed> $mailhogResponse
     */
    public static function fromMailhogResponse(array $mailhogResponse): self
    {
        return self::fromRawHeaders($mailhogResponse['Content']['Headers'] ?? []);
    }

    /**
     * @param array<mixed, mixed> $mimePart
     */
    public static function fromMimePart(array $mimePart): self
    {
        return self::fromRawHeaders($mimePart['Headers']);
    }

    /**
     * @param array<string, array<string>> $rawHeaders
     */
    private static function fromRawHeaders(array $rawHeaders): self
    {
        $headers = [];
        foreach ($rawHeaders as $name => $header) {
            if (!isset($header[0])) {
                continue;
            }

            $decoded = iconv_mime_decode($header[0]);

            $headers[strtolower($name)] = $decoded ?: $header[0];
        }

        return new Headers($headers);
    }

    public function get(string $name, string $default = ''): string
    {
        $name = strtolower($name);

        if (isset($this->headers[$name])) {
            return $this->headers[$name];
        }

        return $default;
    }

    public function has(string $name): bool
    {
        $name = strtolower($name);

        return isset($this->headers[$name]);
    }
}
