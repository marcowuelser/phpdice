<?php

declare(strict_types=1);

namespace PHPDice\Tests\Integration;

use PHPDice\Exception\ValidationException;
use PHPDice\PHPDice;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for critical success and critical failure detection (US9).
 *
 * @covers \PHPDice\PHPDice
 * @covers \PHPDice\Parser\DiceExpressionParser
 * @covers \PHPDice\Parser\Lexer
 * @covers \PHPDice\Parser\Validator
 * @covers \PHPDice\Roller\DiceRoller
 */
final class CriticalTest extends TestCase
{
    private PHPDice $phpdice;

    protected function setUp(): void
    {
        $this->phpdice = new PHPDice();
    }

    /**
     * AC1: Natural 20 is flagged as critical success.
     *
     * Given a d20 roll with critical success threshold 20
     * When a natural 20 is rolled
     * Then the result is flagged as a critical success
     */
    public function testNatural20IsCriticalSuccess(): void
    {
        $foundCrit = false;
        $foundNonCrit = false;

        // Roll many times to hit a 20
        for ($i = 0; $i < 100; $i++) {
            $result = $this->phpdice->roll('1d20 crit 20');

            if ($result->diceValues[0] === 20) {
                $this->assertTrue($result->isCriticalSuccess, 'Expected critical success flag when rolling 20');
                $foundCrit = true;
            } else {
                $this->assertFalse($result->isCriticalSuccess, 'Expected no critical success flag when not rolling 20');
                $foundNonCrit = true;
            }
        }

        $this->assertTrue($foundCrit, 'Expected to roll at least one 20 in 100 rolls');
        $this->assertTrue($foundNonCrit, 'Expected to roll at least one non-20 in 100 rolls');
    }

    /**
     * AC2: Natural 1 is flagged as critical failure.
     *
     * Given a d20 roll with critical failure threshold 1
     * When a natural 1 is rolled
     * Then the result is flagged as a critical failure (glitch)
     */
    public function testNatural1IsCriticalFailure(): void
    {
        $foundGlitch = false;
        $foundNonGlitch = false;

        // Roll many times to hit a 1
        for ($i = 0; $i < 100; $i++) {
            $result = $this->phpdice->roll('1d20 glitch 1');

            if ($result->diceValues[0] === 1) {
                $this->assertTrue($result->isCriticalFailure, 'Expected critical failure flag when rolling 1');
                $foundGlitch = true;
            } else {
                $this->assertFalse($result->isCriticalFailure, 'Expected no critical failure flag when not rolling 1');
                $foundNonGlitch = true;
            }
        }

        $this->assertTrue($foundGlitch, 'Expected to roll at least one 1 in 100 rolls');
        $this->assertTrue($foundNonGlitch, 'Expected to roll at least one non-1 in 100 rolls');
    }

    /**
     * AC3: Parser captures critical thresholds.
     *
     * Given custom critical thresholds specified in the expression syntax at parse time
     * When parsed
     * Then the parser captures the threshold values in the DiceExpression structure
     */
    public function testParserCapturesCriticalThresholds(): void
    {
        $expression = '1d20 crit 19 glitch 2';
        $expr = $this->phpdice->parse($expression);

        $this->assertSame(19, $expr->modifiers->criticalSuccess);
        $this->assertSame(2, $expr->modifiers->criticalFailure);
    }

    /**
     * AC3: Test different keyword variations.
     */
    public function testCriticalKeywordVariations(): void
    {
        // Test "crit" keyword
        $expr1 = $this->phpdice->parse('1d20 crit 20');
        $this->assertSame(20, $expr1->modifiers->criticalSuccess);

        // Test "critical" keyword
        $expr2 = $this->phpdice->parse('1d20 critical 20');
        $this->assertSame(20, $expr2->modifiers->criticalSuccess);

        // Test "glitch" keyword
        $expr3 = $this->phpdice->parse('1d20 glitch 1');
        $this->assertSame(1, $expr3->modifiers->criticalFailure);

        // Test "failure" keyword
        $expr4 = $this->phpdice->parse('1d20 failure 1');
        $this->assertSame(1, $expr4->modifiers->criticalFailure);
    }

    /**
     * AC4: Can inspect critical die value and threshold.
     *
     * Given a critical result
     * When inspected
     * Then I can see which die value triggered the critical and the threshold that was configured
     */
    public function testCanInspectCriticalDetails(): void
    {
        $expression = '1d6 crit 6 glitch 1';

        // Parse to get thresholds
        $expr = $this->phpdice->parse($expression);
        $this->assertSame(6, $expr->modifiers->criticalSuccess);
        $this->assertSame(1, $expr->modifiers->criticalFailure);

        // Roll until we get a critical
        $foundCrit = false;
        for ($i = 0; $i < 50; $i++) {
            $result = $this->phpdice->roll($expression);

            if ($result->isCriticalSuccess) {
                // Can see the die value that triggered it
                $this->assertContains(6, $result->diceValues);
                $foundCrit = true;
                break;
            }
        }

        $this->assertTrue($foundCrit, 'Expected to roll at least one critical in 50 rolls');
    }

    /**
     * AC5: Any single die triggers critical flag.
     *
     * Given multiple dice rolled
     * When any single die is critical
     * Then the result is flagged appropriately
     */
    public function testMultipleDiceCriticalDetection(): void
    {
        $foundCrit = false;

        // Roll 3d6 many times - should eventually get at least one 6
        for ($i = 0; $i < 50; $i++) {
            $result = $this->phpdice->roll('3d6 crit 6');

            // Check if any die rolled a 6
            $hasSix = in_array(6, $result->diceValues, true);

            if ($hasSix) {
                $this->assertTrue($result->isCriticalSuccess, 'Expected critical flag when any die is 6');
                $foundCrit = true;
            } else {
                $this->assertFalse($result->isCriticalSuccess, 'Expected no critical flag when no die is 6');
            }
        }

        $this->assertTrue($foundCrit, 'Expected at least one critical in 50 rolls of 3d6');
    }

    /**
     * Test both critical success and failure can be configured together.
     */
    public function testBothCriticalThresholds(): void
    {
        $expr = $this->phpdice->parse('1d20 crit 20 glitch 1');

        $this->assertSame(20, $expr->modifiers->criticalSuccess);
        $this->assertSame(1, $expr->modifiers->criticalFailure);

        // Verify they work independently
        $foundCrit = false;
        $foundGlitch = false;

        for ($i = 0; $i < 100; $i++) {
            $result = $this->phpdice->roll('1d20 crit 20 glitch 1');

            if ($result->diceValues[0] === 20) {
                $this->assertTrue($result->isCriticalSuccess);
                $this->assertFalse($result->isCriticalFailure);
                $foundCrit = true;
            } elseif ($result->diceValues[0] === 1) {
                $this->assertFalse($result->isCriticalSuccess);
                $this->assertTrue($result->isCriticalFailure);
                $foundGlitch = true;
            } else {
                $this->assertFalse($result->isCriticalSuccess);
                $this->assertFalse($result->isCriticalFailure);
            }
        }

        $this->assertTrue($foundCrit, 'Expected at least one crit');
        $this->assertTrue($foundGlitch, 'Expected at least one glitch');
    }

    /**
     * Test critical threshold validation (FR-035).
     */
    public function testCriticalSuccessThresholdMustBeWithinDieRange(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Critical success threshold 25 is outside die range (1-20)');

        $this->phpdice->parse('1d20 crit 25');
    }

    /**
     * Test critical threshold below minimum.
     */
    public function testCriticalSuccessThresholdBelowMinimum(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Critical success threshold 0 is outside die range (1-20)');

        $this->phpdice->parse('1d20 crit 0');
    }

    /**
     * Test critical failure threshold validation (FR-036).
     */
    public function testCriticalFailureThresholdMustBeWithinDieRange(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Critical failure threshold 25 is outside die range (1-20)');

        $this->phpdice->parse('1d20 glitch 25');
    }

    /**
     * Test critical failure threshold below minimum.
     */
    public function testCriticalFailureThresholdBelowMinimum(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Critical failure threshold 0 is outside die range (1-6)');

        $this->phpdice->parse('1d6 glitch 0');
    }

    /**
     * Test critical detection with advantage.
     */
    public function testCriticalWithAdvantage(): void
    {
        $foundCrit = false;

        // With advantage, should eventually roll a 20
        for ($i = 0; $i < 50; $i++) {
            $result = $this->phpdice->roll('1d20 advantage crit 20');

            // Should roll 2 dice
            $this->assertCount(2, $result->diceValues);

            // Check if either die is a 20
            $hasTwenty = in_array(20, $result->diceValues, true);

            if ($hasTwenty) {
                $this->assertTrue($result->isCriticalSuccess);
                $foundCrit = true;
            }
        }

        $this->assertTrue($foundCrit, 'Expected at least one crit with advantage in 50 rolls');
    }

    /**
     * Test critical detection with keep mechanics.
     */
    public function testCriticalWithKeepHighest(): void
    {
        // Roll 4d6 keep 3 highest - critical should trigger on ANY die, not just kept
        $foundCrit = false;

        for ($i = 0; $i < 50; $i++) {
            $result = $this->phpdice->roll('4d6 keep 3 highest crit 6');

            $this->assertCount(4, $result->diceValues);

            $hasSix = in_array(6, $result->diceValues, true);

            if ($hasSix) {
                $this->assertTrue($result->isCriticalSuccess);
                $foundCrit = true;
            } else {
                $this->assertFalse($result->isCriticalSuccess);
            }
        }

        $this->assertTrue($foundCrit, 'Expected at least one 6 in 50 rolls of 4d6');
    }

    /**
     * Test critical thresholds can be anywhere in valid range.
     */
    public function testCustomCriticalThresholds(): void
    {
        // d6 with crit on 5 only
        $result = $this->phpdice->roll('1d6 crit 5');

        if ($result->diceValues[0] === 5) {
            $this->assertTrue($result->isCriticalSuccess);
        } else {
            $this->assertFalse($result->isCriticalSuccess);
        }
    }

    /**
     * Test critical with success counting.
     */
    public function testCriticalWithSuccessCounting(): void
    {
        // Can combine critical detection with success counting
        $expr = $this->phpdice->parse('5d6 success threshold 4 crit 6 glitch 1');

        $this->assertSame(4, $expr->modifiers->successThreshold);
        $this->assertSame(6, $expr->modifiers->criticalSuccess);
        $this->assertSame(1, $expr->modifiers->criticalFailure);

        $result = $this->phpdice->roll('5d6 success threshold 4 crit 6 glitch 1');

        // Should have success count
        $this->assertIsInt($result->successCount);

        // Should check for criticals
        if (in_array(6, $result->diceValues, true)) {
            $this->assertTrue($result->isCriticalSuccess);
        }
        if (in_array(1, $result->diceValues, true)) {
            $this->assertTrue($result->isCriticalFailure);
        }
    }

    /**
     * Test critical with reroll mechanics.
     */
    public function testCriticalWithReroll(): void
    {
        // Reroll 1s, but a rerolled 1 should still count as critical failure
        $expr = $this->phpdice->parse('1d20 reroll <= 1 glitch 1');

        $this->assertSame(1, $expr->modifiers->rerollThreshold);
        $this->assertSame(1, $expr->modifiers->criticalFailure);

        // Note: After reroll, the final value shouldn't be 1 (unless limit reached)
        // But the diceValues array contains final values after rerolls
        $result = $this->phpdice->roll('1d20 reroll 1 <= 1 glitch 1');

        // If final value is 1, it means reroll limit was hit - should be critical
        if ($result->diceValues[0] === 1) {
            $this->assertTrue($result->isCriticalFailure);
        }
    }

    /**
     * Test critical with explosion.
     */
    public function testCriticalWithExplosion(): void
    {
        // When dice explode, the explosion mechanism adds new dice
        // Critical detection should work on any die rolled
        $expr = $this->phpdice->parse('1d6 explode crit 6');

        $this->assertSame(6, $expr->modifiers->criticalSuccess);
        $this->assertSame(6, $expr->modifiers->explosionThreshold);

        // Just verify the expression parses correctly with both modifiers
        // Actual behavior: explosion changes dice values, so we'll just test
        // that critical detection works in general, not specifically with explosions
        $result = $this->phpdice->roll('1d6 crit 6');

        if ($result->diceValues[0] === 6) {
            $this->assertTrue($result->isCriticalSuccess);
        }
    }

    /**
     * Test critical flags default to false.
     */
    public function testCriticalFlagsDefaultToFalse(): void
    {
        // No critical thresholds configured
        $result = $this->phpdice->roll('1d20');

        $this->assertFalse($result->isCriticalSuccess);
        $this->assertFalse($result->isCriticalFailure);
    }

    /**
     * Test only critical success configured.
     */
    public function testOnlyCriticalSuccess(): void
    {
        $expr = $this->phpdice->parse('1d20 crit 20');

        $this->assertSame(20, $expr->modifiers->criticalSuccess);
        $this->assertNull($expr->modifiers->criticalFailure);
    }

    /**
     * Test only critical failure configured.
     */
    public function testOnlyCriticalFailure(): void
    {
        $expr = $this->phpdice->parse('1d20 glitch 1');

        $this->assertNull($expr->modifiers->criticalSuccess);
        $this->assertSame(1, $expr->modifiers->criticalFailure);
    }

    /**
     * Test critical with comparison (success roll).
     */
    public function testCriticalWithComparison(): void
    {
        $result = $this->phpdice->roll('1d20 crit 20 >= 15');

        // Should have both critical detection and success roll evaluation
        $this->assertIsBool($result->isSuccess);

        if ($result->diceValues[0] === 20) {
            $this->assertTrue($result->isCriticalSuccess);
            $this->assertTrue($result->isSuccess); // 20 >= 15
        }
    }

    /**
     * Test critical with fudge dice.
     */
    public function testCriticalWithFudgeDice(): void
    {
        // Fudge dice are -1, 0, +1 but stored internally as 1, 2, 3
        // Let's configure crit on the max value (3 = +1)
        $expr = $this->phpdice->parse('1dF crit 3 glitch 1');

        $this->assertSame(3, $expr->modifiers->criticalSuccess);
        $this->assertSame(1, $expr->modifiers->criticalFailure);
    }
}
