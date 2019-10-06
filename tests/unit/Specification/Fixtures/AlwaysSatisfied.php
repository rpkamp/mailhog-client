<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Tests\unit\Specification\Fixtures;

use rpkamp\Mailhog\Message\Message;
use rpkamp\Mailhog\Specification\Specification;

final class AlwaysSatisfied implements Specification
{
    public function isSatisfiedBy(Message $message): bool
    {
        return true;
    }
}
