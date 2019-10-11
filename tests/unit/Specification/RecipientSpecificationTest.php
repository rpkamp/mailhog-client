<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Tests\unit\Specification;

use PHPUnit\Framework\TestCase;
use rpkamp\Mailhog\Message\Contact;
use rpkamp\Mailhog\Specification\RecipientSpecification;
use rpkamp\Mailhog\Tests\unit\Specification\Fixtures\MessageFactory;

class RecipientSpecificationTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_satisfied_on_same_recipient_email_address(): void
    {
        $specification = new RecipientSpecification(new Contact('someoneelse@myself.example'));

        $this->assertTrue($specification->isSatisfiedBy(MessageFactory::dummy()));
    }

    /**
     * @test
     */
    public function it_should_not_be_satisfied_on_different_recipient_email_address_and_name(): void
    {
        $specification = new RecipientSpecification(new Contact('notme@myself.example'));

        $this->assertFalse($specification->isSatisfiedBy(MessageFactory::dummy()));
    }
}
