<?php

declare(strict_types=1);

namespace PHPDice\Tests\Unit\Roller;

use PHPDice\Model\DiceExpression;
use PHPDice\Model\DiceSpecification;
use PHPDice\Model\DiceType;
use PHPDice\Model\RollModifiers;
use PHPDice\Model\StatisticalData;
use PHPDice\Roller\DiceRoller;
use PHPDice\Tests\Unit\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for DiceRoller
 */
#[CoversClass(DiceRoller::class)]
class DiceRollerTest extends BaseTestCase
{
    private DiceRoller $roller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->roller = new DiceRoller();
    }

    /**
     * Test rolling basic dice
     */
    public function testRollBasicDice(): void
    {
        $expression = new DiceExpression(
            specification: new DiceSpecification(3, 6, DiceType::STANDARD),
            modifiers: new RollModifiers(),
            statistics: new StatisticalData(3, 18, 10.5),
            originalExpression: '3d6'
        );

        $result = $this->roller->roll($expression);

        $this->assertCount(3, $result->diceValues);
        $this->assertGreaterThanOrEqual(3, $result->total);
        $this->assertLessThanOrEqual(18, $result->total);

        foreach ($result->diceValues as $value) {
            $this->assertGreaterThanOrEqual(1, $value);
            $this->assertLessThanOrEqual(6, $value);
        }
    }

    /**
     * Test rolling 1d20
     */
    public function testRoll1d20(): void
    {
        $expression = new DiceExpression(
            specification: new DiceSpecification(1, 20, DiceType::STANDARD),
            modifiers: new RollModifiers(),
            statistics: new StatisticalData(1, 20, 10.5),
            originalExpression: '1d20'
        );

        $result = $this->roller->roll($expression);

        $this->assertCount(1, $result->diceValues);
        $this->assertGreaterThanOrEqual(1, $result->total);
        $this->assertLessThanOrEqual(20, $result->total);
    }

    /**
     * Test that total equals sum of dice values
     */
    public function testTotalEqualsSumOfDice(): void
    {
        $expression = new DiceExpression(
            specification: new DiceSpecification(5, 6, DiceType::STANDARD),
            modifiers: new RollModifiers(),
            statistics: new StatisticalData(5, 30, 17.5),
            originalExpression: '5d6'
        );

        $result = $this->roller->roll($expression);

        $expectedTotal = array_sum($result->diceValues);
        $this->assertSame($expectedTotal, $result->total);
    }

    /**
     * Test result contains original expression
     */
    public function testResultContainsOriginalExpression(): void
    {
        $expression = new DiceExpression(
            specification: new DiceSpecification(2, 10, DiceType::STANDARD),
            modifiers: new RollModifiers(),
            statistics: new StatisticalData(2, 20, 11.0),
            originalExpression: '2d10'
        );

        $result = $this->roller->roll($expression);

        $this->assertSame($expression, $result->expression);
        $this->assertSame('2d10', $result->expression->originalExpression);
    }
}
