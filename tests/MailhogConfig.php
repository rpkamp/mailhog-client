<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Tests;

use RuntimeException;

final class MailhogConfig
{
    /**
     * @var string
     */
    private static $host;

    /**
     * @var int
     */
    private static $port;

    public static function getHost(): string
    {
        self::parse();

        return self::$host;
    }

    public static function getPort(): int
    {
        self::parse();

        return self::$port;
    }

    private static function parse(): void
    {
        if (isset(self::$host)) {
            return;
        }

        if (!isset($_ENV['mailhog_smtp_dsn'])) {
            throw new RuntimeException('Environment variable "mailhog_smtp_dsn" must be defined');
        }

        $info = parse_url($_ENV['mailhog_smtp_dsn']);

        if (!is_array($info)) {
            throw new RuntimeException(sprintf('Unable to parse DSN "%s"', $_ENV['mailhog_smtp_dsn']));
        }

        if (!isset($info['host'])) {
            throw new RuntimeException(
                sprintf(
                    'Unable to parse host from Mailhog DSN "%s"',
                    $_ENV['mailhog_smtp_dsn']
                )
            );
        }

        if (!isset($info['port'])) {
            throw new RuntimeException(
                sprintf(
                    'Unable to parse port from Mailhog DSN "%s"',
                    $_ENV['mailhog_smtp_dsn']
                )
            );
        }

        static::$host = $info['host'];
        static::$port = $info['port'];
    }
}
