<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Tests\unit\Specification;

use PHPUnit\Framework\TestCase;
use rpkamp\Mailhog\Specification\SubjectSpecification;
use rpkamp\Mailhog\Tests\unit\Specification\Fixtures\MessageFactory;

class SubjectSpecificationTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_satisfied_by_message_with_specified_subject(): void
    {
        $this->assertTrue((new SubjectSpecification('Hello world!'))->isSatisfiedBy(MessageFactory::dummy()));
    }

    /**
     * @test
     */
    public function it_should_not_be_satisfied_by_message_with_different_subject(): void
    {
        $this->assertFalse((new SubjectSpecification('Hi world!'))->isSatisfiedBy(MessageFactory::dummy()));
    }
}
