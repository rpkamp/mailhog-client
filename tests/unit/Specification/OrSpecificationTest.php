<?php
declare(strict_types=1);

namespace rpkamp\Mailhog\Tests\unit\Specification;

use PHPUnit\Framework\TestCase;
use rpkamp\Mailhog\Specification\OrSpecification;
use rpkamp\Mailhog\Tests\unit\Specification\Fixtures\AlwaysSatisfied;
use rpkamp\Mailhog\Tests\unit\Specification\Fixtures\MessageFactory;
use rpkamp\Mailhog\Tests\unit\Specification\Fixtures\NeverSatisfied;

class OrSpecificationTest extends TestCase
{
    /**
     * @test
     * @dataProvider satisfiedOrSpecificationsProvider
     */
    public function it_should_be_satisfied_when_either_specification_is_satisfied(OrSpecification $specification): void
    {
        $this->assertTrue($specification->isSatisfiedBy(MessageFactory::dummy()));
    }

    /**
     * @test
     */
    public function it_should_not_be_satisfied_when_neither_specification_is_not_satisfied(): void
    {
        $this->assertFalse((new OrSpecification(new NeverSatisfied(), new NeverSatisfied()))->isSatisfiedBy(MessageFactory::dummy()));
    }

    /**
     * @return array<string, array{OrSpecification}>
     */
    public function satisfiedOrSpecificationsProvider(): array
    {
        return [
            'left satisfied' => [new OrSpecification(new AlwaysSatisfied(), new NeverSatisfied())],
            'right satisfied' => [new OrSpecification(new NeverSatisfied(), new AlwaysSatisfied())],
            'left and right satisfied' => [new OrSpecification(new AlwaysSatisfied(), new AlwaysSatisfied())],
        ];
    }

    /**
     * @test
     */
    public function it_should_return_specification_when_building_compound_from_one_specification(): void
    {
        $this->assertEquals(new AlwaysSatisfied(), OrSpecification::any(new AlwaysSatisfied()));
    }

    /**
     * @test
     */
    public function it_should_build_compound_and_specifications_from_multiple_specifications(): void
    {
        $expected = new OrSpecification(
            new AlwaysSatisfied(),
            new OrSpecification(
                new AlwaysSatisfied(),
                new AlwaysSatisfied()
            )
        );

        $this->assertEquals($expected, OrSpecification::any(new AlwaysSatisfied(), new AlwaysSatisfied(), new AlwaysSatisfied()));
    }
}
