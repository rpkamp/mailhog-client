<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Specification;

use rpkamp\Mailhog\Message\Message;

interface Specification
{
    public function isSatisfiedBy(Message $message): bool;
}
