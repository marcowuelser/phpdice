<?php

declare(strict_types=1);

namespace PHPDice\Tests\Integration;

use PHPDice\PHPDice;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for success rolls and comparisons (US8).
 *
 * @covers \PHPDice\PHPDice
 * @covers \PHPDice\Parser\DiceExpressionParser
 * @covers \PHPDice\Parser\Lexer
 * @covers \PHPDice\Roller\DiceRoller
 */
final class ComparisonTest extends TestCase
{
    private PHPDice $phpdice;

    protected function setUp(): void
    {
        $this->phpdice = new PHPDice();
    }

    /**
     * AC1: Expression includes both die value and success/failure flag.
     *
     * Given an expression "1d20 >= 15"
     * When rolled
     * Then the result includes both the die value and a success/failure flag
     */
    public function testComparisonIncludesValueAndFlag(): void
    {
        $result = $this->phpdice->roll('1d20 >= 15');

        // Should have a die value
        $this->assertNotNull($result->diceValues);
        $this->assertCount(1, $result->diceValues);
        $this->assertGreaterThanOrEqual(1, $result->diceValues[0]);
        $this->assertLessThanOrEqual(20, $result->diceValues[0]);

        // Should have isSuccess flag (boolean, not null)
        $this->assertIsBool($result->isSuccess);

        // Total should equal the die value
        $this->assertSame($result->diceValues[0], $result->total);
    }

    /**
     * AC2: Success flag is true when roll meets threshold.
     *
     * Given a comparison expression
     * When the roll meets the threshold
     * Then the success flag is true
     */
    public function testSuccessFlagTrueWhenMeetsThreshold(): void
    {
        // Use a guaranteed success: 1d20+20 >= 1 (minimum 21, threshold 1)
        $result = $this->phpdice->roll('1d20+20 >= 1');

        $this->assertTrue($result->isSuccess, 'Expected success for 1d20+20 >= 1 (always succeeds)');
        $this->assertGreaterThanOrEqual(1, $result->total);
    }

    /**
     * AC2: Test >= operator.
     */
    public function testGreaterThanOrEqualOperator(): void
    {
        // Roll many times and verify success flag matches actual comparison
        $successes = 0;
        $failures = 0;

        for ($i = 0; $i < 100; $i++) {
            $result = $this->phpdice->roll('1d20 >= 10');

            if ($result->total >= 10) {
                $this->assertTrue($result->isSuccess, "Expected success when total ({$result->total}) >= 10");
                $successes++;
            } else {
                $this->assertFalse($result->isSuccess, "Expected failure when total ({$result->total}) < 10");
                $failures++;
            }
        }

        // Should have both successes and failures (very unlikely to get all one or the other)
        $this->assertGreaterThan(0, $successes, 'Expected at least one success in 100 rolls');
        $this->assertGreaterThan(0, $failures, 'Expected at least one failure in 100 rolls');
    }

    /**
     * AC3: Success flag is false when roll fails threshold.
     *
     * Given a comparison expression
     * When the roll fails the threshold
     * Then the success flag is false
     */
    public function testSuccessFlagFalseWhenFailsThreshold(): void
    {
        // Use a guaranteed failure: 1d20 >= 25 (maximum 20, threshold 25)
        $result = $this->phpdice->roll('1d20 >= 25');

        $this->assertFalse($result->isSuccess, 'Expected failure for 1d20 >= 25 (always fails)');
        $this->assertLessThan(25, $result->total);
    }

    /**
     * AC4: Can inspect roll value, threshold, and success status.
     *
     * Given a success roll result
     * When inspected
     * Then I can see the actual roll value, the threshold, and the success status
     */
    public function testCanInspectComparisonDetails(): void
    {
        $expression = '1d20+5 >= 15';
        $result = $this->phpdice->roll($expression);

        // Can see the actual roll value (total)
        $this->assertIsNumeric($result->total);
        $this->assertGreaterThanOrEqual(6, $result->total); // 1 + 5
        $this->assertLessThanOrEqual(25, $result->total); // 20 + 5

        // Can see the threshold from the expression
        $expr = $this->phpdice->parse($expression);
        $this->assertSame(15, $expr->comparisonThreshold);
        $this->assertSame('>=', $expr->comparisonOperator);

        // Can see the success status
        $this->assertIsBool($result->isSuccess);

        // Verify success status matches the comparison
        if ($result->total >= 15) {
            $this->assertTrue($result->isSuccess);
        } else {
            $this->assertFalse($result->isSuccess);
        }
    }

    /**
     * Test comparison with arithmetic expression.
     */
    public function testComparisonWithArithmetic(): void
    {
        $expression = '1d20+3 >= 15';
        $result = $this->phpdice->roll($expression);

        // Total should include the +3 modifier
        $this->assertGreaterThanOrEqual(4, $result->total); // 1 + 3
        $this->assertLessThanOrEqual(23, $result->total); // 20 + 3

        // Success should be based on total (including +3)
        $expectedSuccess = $result->total >= 15;
        $this->assertSame($expectedSuccess, $result->isSuccess);
    }

    /**
     * Test > operator (strict greater than).
     */
    public function testGreaterThanOperator(): void
    {
        // Guaranteed success: 1d20+20 > 1 (minimum 21, threshold 1)
        $result = $this->phpdice->roll('1d20+20 > 1');
        $this->assertTrue($result->isSuccess);

        // Guaranteed failure: 1d20 > 20 (maximum 20, threshold 20, needs > not >=)
        $result2 = $this->phpdice->roll('1d20 > 20');
        $this->assertFalse($result2->isSuccess);
    }

    /**
     * Test <= operator.
     */
    public function testLessThanOrEqualOperator(): void
    {
        // Guaranteed success: 1d20 <= 25 (maximum 20, threshold 25)
        $result = $this->phpdice->roll('1d20 <= 25');
        $this->assertTrue($result->isSuccess);

        // Guaranteed failure: 1d20 <= 0 (minimum 1, threshold 0)
        $result2 = $this->phpdice->roll('1d20 <= 0');
        $this->assertFalse($result2->isSuccess);
    }

    /**
     * Test < operator.
     */
    public function testLessThanOperator(): void
    {
        // Guaranteed success: 1d20 < 25 (maximum 20, threshold 25)
        $result = $this->phpdice->roll('1d20 < 25');
        $this->assertTrue($result->isSuccess);

        // Guaranteed failure: 1d20 < 1 (minimum 1, threshold 1, needs < not <=)
        $result2 = $this->phpdice->roll('1d20 < 1');
        $this->assertFalse($result2->isSuccess);
    }

    /**
     * Test == operator.
     */
    public function testEqualityOperator(): void
    {
        // Roll many times to eventually hit the target
        $foundMatch = false;
        $foundNonMatch = false;

        for ($i = 0; $i < 100; $i++) {
            $result = $this->phpdice->roll('1d6 == 3');

            if ($result->total == 3) {
                $this->assertTrue($result->isSuccess);
                $foundMatch = true;
            } else {
                $this->assertFalse($result->isSuccess);
                $foundNonMatch = true;
            }
        }

        $this->assertTrue($foundMatch, 'Expected to roll a 3 at least once in 100 rolls');
        $this->assertTrue($foundNonMatch, 'Expected to roll non-3 at least once in 100 rolls');
    }

    /**
     * Test that expressions without comparisons don't have isSuccess.
     */
    public function testNoComparisonNoSuccessFlag(): void
    {
        $result = $this->phpdice->roll('1d20+5');

        $this->assertNull($result->isSuccess, 'Expected null isSuccess for expression without comparison');
    }

    /**
     * Test comparison with complex expression.
     */
    public function testComparisonWithComplexExpression(): void
    {
        $expression = '2d6+3 >= 10';
        $result = $this->phpdice->roll($expression);

        // Total should be 2d6 + 3
        $this->assertGreaterThanOrEqual(5, $result->total); // 2 + 3
        $this->assertLessThanOrEqual(15, $result->total); // 12 + 3

        // Success should match comparison
        $expectedSuccess = $result->total >= 10;
        $this->assertSame($expectedSuccess, $result->isSuccess);
    }

    /**
     * Test comparison with placeholders.
     */
    public function testComparisonWithPlaceholders(): void
    {
        $expression = '1d20+%bonus% >= %dc%';
        $variables = ['bonus' => 5, 'dc' => 15];

        $result = $this->phpdice->roll($expression, $variables);

        // Total should be 1d20 + 5
        $this->assertGreaterThanOrEqual(6, $result->total);
        $this->assertLessThanOrEqual(25, $result->total);

        // Success should be based on >= 15
        $expectedSuccess = $result->total >= 15;
        $this->assertSame($expectedSuccess, $result->isSuccess);
    }

    /**
     * Test that single-die comparisons are success rolls, not success counting.
     */
    public function testSingleDieComparisonIsSuccessRoll(): void
    {
        $expression = '1d20 >= 15';
        $expr = $this->phpdice->parse($expression);

        // Should be expression-level comparison, not success counting
        $this->assertSame('>=', $expr->comparisonOperator);
        $this->assertSame(15, $expr->comparisonThreshold);
        $this->assertNull($expr->modifiers->successThreshold);
        $this->assertNull($expr->modifiers->successOperator);
    }

    /**
     * Test that multi-die comparisons without arithmetic are success counting.
     */
    public function testMultiDieComparisonIsSuccessCounting(): void
    {
        $expression = '5d10 >= 7';
        $expr = $this->phpdice->parse($expression);

        // Should be success counting, not expression-level comparison
        $this->assertSame(7, $expr->modifiers->successThreshold);
        $this->assertSame('>=', $expr->modifiers->successOperator);
        $this->assertNull($expr->comparisonOperator);
        $this->assertNull($expr->comparisonThreshold);
    }

    /**
     * Test comparison with modifiers (advantage).
     */
    public function testComparisonWithAdvantage(): void
    {
        $expression = '1d20 advantage >= 15';
        $result = $this->phpdice->roll($expression);

        // Should roll 2 dice (advantage)
        $this->assertCount(2, $result->diceValues);

        // Total should be the higher of the two
        $this->assertSame(max($result->diceValues), $result->total);

        // Success should match comparison
        $expectedSuccess = $result->total >= 15;
        $this->assertSame($expectedSuccess, $result->isSuccess);
    }

    /**
     * Test comparison with keep mechanics.
     */
    public function testComparisonWithKeepHighest(): void
    {
        $expression = '4d6 keep 3 highest +0 >= 12';
        $result = $this->phpdice->roll($expression);

        // Should roll 4 dice
        $this->assertCount(4, $result->diceValues);

        // Should keep 3
        $this->assertCount(3, $result->keptDice ?? []);

        // Success should be based on total of kept dice
        $expectedSuccess = $result->total >= 12;
        $this->assertSame($expectedSuccess, $result->isSuccess);
    }

    /**
     * Test that comparison doesn't interfere with success counting.
     */
    public function testSuccessCountingStillWorksIndependently(): void
    {
        $expression = '5d10 success threshold 7';
        $result = $this->phpdice->roll($expression);

        // Should have success count
        $this->assertIsInt($result->successCount);
        $this->assertGreaterThanOrEqual(0, $result->successCount);
        $this->assertLessThanOrEqual(5, $result->successCount);

        // Should NOT have isSuccess (no comparison)
        $this->assertNull($result->isSuccess);

        // Total should equal success count in success counting mode
        $this->assertSame($result->successCount, $result->total);
    }

    /**
     * Test parsing comparison stores operator and threshold.
     */
    public function testParsingStoresComparisonDetails(): void
    {
        $testCases = [
            ['expression' => '1d20 >= 15', 'operator' => '>=', 'threshold' => 15],
            ['expression' => '1d20 > 10', 'operator' => '>', 'threshold' => 10],
            ['expression' => '1d20 <= 5', 'operator' => '<=', 'threshold' => 5],
            ['expression' => '1d20 < 20', 'operator' => '<', 'threshold' => 20],
            ['expression' => '1d20 == 15', 'operator' => '==', 'threshold' => 15],
        ];

        foreach ($testCases as $testCase) {
            $expr = $this->phpdice->parse($testCase['expression']);

            $this->assertSame(
                $testCase['operator'],
                $expr->comparisonOperator,
                "Expected operator {$testCase['operator']} for expression {$testCase['expression']}"
            );

            $this->assertSame(
                $testCase['threshold'],
                $expr->comparisonThreshold,
                "Expected threshold {$testCase['threshold']} for expression {$testCase['expression']}"
            );
        }
    }
}
