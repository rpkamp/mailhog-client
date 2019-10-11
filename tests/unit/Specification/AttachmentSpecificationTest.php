<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Tests\unit\Specification;

use rpkamp\Mailhog\Specification\AttachmentSpecification;
use PHPUnit\Framework\TestCase;
use rpkamp\Mailhog\Tests\unit\Specification\Fixtures\MessageFactory;

final class AttachmentSpecificationTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_satisfied_when_message_has_attachment(): void
    {
        $specification = new AttachmentSpecification('lorem-ipsum.txt');
        $this->assertTrue($specification->isSatisfiedBy(MessageFactory::dummy()));
    }

    /**
     * @test
     */
    public function it_should_not_be_satisfied_when_messages_does_not_have_attachment(): void
    {
        $specification = new AttachmentSpecification('lorem-ipsum.jpg');
        $this->assertFalse($specification->isSatisfiedBy(MessageFactory::dummy()));
    }
}
