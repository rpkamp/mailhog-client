<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Tests\unit\Specification;

use PHPUnit\Framework\TestCase;
use rpkamp\Mailhog\Message\Contact;
use rpkamp\Mailhog\Specification\BccRecipientSpecification;
use rpkamp\Mailhog\Tests\unit\Specification\Fixtures\MessageFactory;

class BccRecipientSpecificationTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_satisfied_on_same_bcc_recipient_email_address(): void
    {
        $specification = new BccRecipientSpecification(new Contact('someone-as-bcc@myself.example'));

        $this->assertTrue($specification->isSatisfiedBy(MessageFactory::dummy()));
    }

    /**
     * @test
     */
    public function it_should_not_be_satisfied_on_different_bcc_recipient_email_address_and_name(): void
    {
        $specification = new BccRecipientSpecification(new Contact('notme@myself.example'));

        $this->assertFalse($specification->isSatisfiedBy(MessageFactory::dummy()));
    }
}
