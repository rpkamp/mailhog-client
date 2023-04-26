<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Tests\unit\Specification;

use PHPUnit\Framework\TestCase;
use rpkamp\Mailhog\Message\Contact;
use rpkamp\Mailhog\Specification\CcRecipientSpecification;
use rpkamp\Mailhog\Tests\unit\Specification\Fixtures\MessageFactory;

class CcRecipientSpecificationTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_satisfied_on_same_cc_recipient_email_address(): void
    {
        $specification = new CcRecipientSpecification(new Contact('someone-as-cc@myself.example'));

        $this->assertTrue($specification->isSatisfiedBy(MessageFactory::dummy()));
    }

    /**
     * @test
     */
    public function it_should_not_be_satisfied_on_different_cc_recipient_email_address_and_name(): void
    {
        $specification = new CcRecipientSpecification(new Contact('notme@myself.example'));

        $this->assertFalse($specification->isSatisfiedBy(MessageFactory::dummy()));
    }
}
