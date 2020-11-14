<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Specification;

use rpkamp\Mailhog\Message\Message;

use function array_slice;
use function count;

final class OrSpecification implements Specification
{
    /**
     * @var Specification
     */
    private $left;

    /**
     * @var Specification
     */
    private $right;

    public function __construct(Specification $left, Specification $right)
    {
        $this->left = $left;
        $this->right = $right;
    }

    public static function any(Specification $specification, Specification ...$other): Specification
    {
        if (count($other) === 0) {
            return $specification;
        }

        if (count($other) === 1) {
            return new self($specification, $other[0]);
        }

        return new self($specification, self::any($other[0], ...array_slice($other, 1)));
    }

    public function isSatisfiedBy(Message $message): bool
    {
        return $this->left->isSatisfiedBy($message) || $this->right->isSatisfiedBy($message);
    }
}
