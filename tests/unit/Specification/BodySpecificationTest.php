<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Tests\unit\Specification;

use rpkamp\Mailhog\Specification\BodySpecification;
use PHPUnit\Framework\TestCase;
use rpkamp\Mailhog\Tests\unit\Specification\Fixtures\MessageFactory;

final class BodySpecificationTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_satisfied_when_snippet_is_found_in_body(): void
    {
        $specification = new BodySpecification('Hi');
        $this->assertTrue($specification->isSatisfiedBy(MessageFactory::dummy()));
    }

    /**
     * @test
     */
    public function it_should_not_be_satisfied_when_snippet_is_not_found_in_body(): void
    {
        $specification = new BodySpecification('Hello');
        $this->assertFalse($specification->isSatisfiedBy(MessageFactory::dummy()));
    }
}
