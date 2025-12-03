<?php

declare(strict_types=1);

namespace PHPDice\Tests\Integration;

use PHPDice\PHPDice;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Integration tests for basic dice rolling (User Story 1).
 */
#[CoversClass(PHPDice::class)]
class BasicRollingTest extends BaseTestCase
{
    private PHPDice $dice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dice = new PHPDice();
    }

    /**
     * Test parsing basic dice notation.
     */
    public function testParseBasicDiceNotation(): void
    {
        $expression = $this->dice->parse('3d6');

        $this->assertSame(3, $expression->specification->count);
        $this->assertSame(6, $expression->specification->sides);
        $this->assertSame('3d6', $expression->originalExpression);
    }

    /**
     * Test rolling basic dice.
     */
    public function testRollBasicDice(): void
    {
        $result = $this->dice->roll('3d6');

        $this->assertCount(3, $result->diceValues);
        $this->assertGreaterThanOrEqual(3, $result->total);
        $this->assertLessThanOrEqual(18, $result->total);

        // Each die should be between 1 and 6
        foreach ($result->diceValues as $value) {
            $this->assertGreaterThanOrEqual(1, $value);
            $this->assertLessThanOrEqual(6, $value);
        }
    }

    /**
     * Test rolling 1d20 (common D&D roll).
     */
    public function testRoll1d20(): void
    {
        $result = $this->dice->roll('1d20');

        $this->assertCount(1, $result->diceValues);
        $this->assertGreaterThanOrEqual(1, $result->total);
        $this->assertLessThanOrEqual(20, $result->total);
    }

    /**
     * Test rolling 2d10.
     */
    public function testRoll2d10(): void
    {
        $result = $this->dice->roll('2d10');

        $this->assertCount(2, $result->diceValues);
        $this->assertGreaterThanOrEqual(2, $result->total);
        $this->assertLessThanOrEqual(20, $result->total);
    }

    /**
     * Test statistical calculations.
     */
    public function testStatistics(): void
    {
        $expression = $this->dice->parse('3d6');
        $stats = $expression->statistics;

        $this->assertSame(3, $stats->minimum);
        $this->assertSame(18, $stats->maximum);
        $this->assertSame(10.5, $stats->expected);
    }

    /**
     * Test invalid expression - missing dice count.
     */
    public function testInvalidExpressionMissingCount(): void
    {
        $this->expectException(\PHPDice\Exception\ParseException::class);
        $this->dice->parse('d6');
    }

    /**
     * Test invalid expression - missing sides.
     */
    public function testInvalidExpressionMissingSides(): void
    {
        $this->expectException(\PHPDice\Exception\ParseException::class);
        $this->dice->parse('3d');
    }

    /**
     * Test invalid expression - non-numeric.
     */
    public function testInvalidExpressionNonNumeric(): void
    {
        $this->expectException(\PHPDice\Exception\ParseException::class);
        $this->dice->parse('abc');
    }

    /**
     * Test zero dice count validation.
     */
    public function testZeroDiceCount(): void
    {
        $this->expectException(\PHPDice\Exception\ValidationException::class);
        $this->expectExceptionMessage('Dice count must be at least 1');
        $this->dice->parse('0d6');
    }

    /**
     * Test single-sided die validation (must be at least 2 sides).
     */
    public function testSingleSidedDie(): void
    {
        $this->expectException(\PHPDice\Exception\ValidationException::class);
        $this->expectExceptionMessage('Dice must have at least 2 sides');
        $this->dice->parse('3d1');
    }

    /**
     * Test maximum dice count validation (100 max).
     */
    public function testTooManyDice(): void
    {
        $this->expectException(\PHPDice\Exception\ValidationException::class);
        $this->expectExceptionMessage('Cannot roll more than 100 dice');
        $this->dice->parse('101d6');
    }

    /**
     * Test maximum sides validation (100 max).
     */
    public function testTooManySides(): void
    {
        $this->expectException(\PHPDice\Exception\ValidationException::class);
        $this->expectExceptionMessage('Dice cannot have more than 100 sides');
        $this->dice->parse('3d101');
    }
}
