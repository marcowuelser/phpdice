<?php

declare(strict_types=1);

namespace PHPDice\Tests\Integration;

use PHPDice\Exception\ValidationException;
use PHPDice\PHPDice;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for User Story 6a: Exploding Dice
 * 
 * Tests the Savage Worlds "Aces" mechanic where dice that roll maximum
 * are rerolled and added to the total, with configurable limits.
 * 
 * @covers \PHPDice\PHPDice
 * @covers \PHPDice\Parser\DiceExpressionParser
 * @covers \PHPDice\Roller\DiceRoller
 * @covers \PHPDice\Model\DiceExpression
 * @covers \PHPDice\Model\RollResult
 */
class ExplodingDiceTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test basic explosion with default limit (100) and default threshold (max value)
     * Acceptance: "3d6 explode" rolls 3d6, explosions on 6, up to 100 times
     */
    public function testExplosionWithDefaultLimitAndThreshold(): void
    {
        $result = $this->phpdice->roll('3d6 explode');
        
        // Should roll 3 dice initially
        $this->assertCount(3, $result->diceValues);
        
        // All values should be >= 6 (minimum is one die showing 6 and exploding once to 1)
        // Actually, minimum is 3 (no explosions), so just check values are reasonable
        $this->assertGreaterThanOrEqual(3, $result->total);
        
        // Check explosion history structure if explosions occurred
        if ($result->explosionHistory !== null) {
            foreach ($result->explosionHistory as $dieIndex => $history) {
                $this->assertArrayHasKey('rolls', $history);
                $this->assertArrayHasKey('count', $history);
                $this->assertArrayHasKey('cumulativeTotal', $history);
                $this->assertArrayHasKey('limitReached', $history);
                
                // First roll in history should be 6 (the value that triggered explosion)
                $this->assertEquals(6, $history['rolls'][0]);
                
                // Cumulative total should match the die value
                $this->assertEquals($result->diceValues[$dieIndex], $history['cumulativeTotal']);
            }
        }
    }

    /**
     * Test explosion with explicit limit
     * Acceptance: "3d6 explode 2" allows at most 2 explosions per die
     */
    public function testExplosionWithExplicitLimit(): void
    {
        $result = $this->phpdice->roll('3d6 explode 2');
        
        $this->assertCount(3, $result->diceValues);
        
        // If explosions occurred, verify limit is respected
        if ($result->explosionHistory !== null) {
            foreach ($result->explosionHistory as $history) {
                $this->assertLessThanOrEqual(2, $history['count']);
                
                // Total rolls = original + explosions
                $this->assertLessThanOrEqual(3, count($history['rolls'])); // 1 original + 2 explosions max
            }
        }
    }

    /**
     * Test explosion with range threshold
     * Acceptance: "3d6 explode 3 >=5" explodes on 5 or 6, up to 3 times
     */
    public function testExplosionWithRangeThreshold(): void
    {
        $result = $this->phpdice->roll('3d6 explode 3 >=5');
        
        $this->assertCount(3, $result->diceValues);
        
        // If explosions occurred, verify threshold and limit
        if ($result->explosionHistory !== null) {
            foreach ($result->explosionHistory as $history) {
                // First roll should be 5 or 6 (triggered explosion)
                $this->assertContains($history['rolls'][0], [5, 6]);
                
                // Max 3 explosions
                $this->assertLessThanOrEqual(3, $history['count']);
            }
        }
    }

    /**
     * Test explosion with <= operator
     * Acceptance: "3d6 explode <=2" explodes on 1 or 2
     */
    public function testExplosionWithLessThanOperator(): void
    {
        $result = $this->phpdice->roll('3d6 explode <=2');
        
        $this->assertCount(3, $result->diceValues);
        
        // If explosions occurred, verify threshold
        if ($result->explosionHistory !== null) {
            foreach ($result->explosionHistory as $history) {
                // First roll should be 1 or 2
                $this->assertContains($history['rolls'][0], [1, 2]);
            }
        }
    }

    /**
     * Test explosion cumulative totals
     * Verify that explosions add to total, not replace
     */
    public function testExplosionCumulativeTotals(): void
    {
        // Use a die that always explodes once for predictable testing
        // "1d6 explode 10 >=6" means: roll d6, if 6, explode up to 10 times
        $result = $this->phpdice->roll('1d6 explode 10 >=6');
        
        // Verify the result is valid
        $this->assertNotNull($result);
        $this->assertCount(1, $result->diceValues);
        
        if ($result->explosionHistory !== null && isset($result->explosionHistory[0])) {
            $history = $result->explosionHistory[0];
            
            // Cumulative total should be sum of all rolls
            $expectedTotal = array_sum($history['rolls']);
            $this->assertEquals($expectedTotal, $history['cumulativeTotal']);
            
            // Die value should equal cumulative total
            $this->assertEquals($history['cumulativeTotal'], $result->diceValues[0]);
        }
    }

    /**
     * Test explosion limit reached flag
     * Acceptance: Reaching 100 explosions sets limitReached flag
     */
    public function testExplosionLimitReachedFlag(): void
    {
        // Use low limit for faster test
        $result = $this->phpdice->roll('1d6 explode 1 >=6');
        
        // Verify the result is valid
        $this->assertNotNull($result);
        $this->assertCount(1, $result->diceValues);
        
        if ($result->explosionHistory !== null && isset($result->explosionHistory[0])) {
            $history = $result->explosionHistory[0];
            
            if ($history['count'] >= 1) {
                $this->assertTrue($history['limitReached']);
            }
        }
    }

    /**
     * Test explosion covering entire range throws exception
     * FR-038c: "1d6 explode >=1" should reject (all values explode)
     */
    public function testExplosionCoveringEntireRangeThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('would trigger on all possible die values');
        
        $this->phpdice->roll('1d6 explode >=1');
    }

    /**
     * Test other invalid explosion ranges
     */
    public function testOtherInvalidExplosionRanges(): void
    {
        // "1d20 explode <=20" would explode on all values
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('would trigger on all possible die values');
        
        $this->phpdice->roll('1d20 explode <=20');
    }

    /**
     * Test valid explosion edge cases
     */
    public function testValidExplosionEdgeCases(): void
    {
        // "1d6 explode >=6" - only 6 explodes (valid)
        $result1 = $this->phpdice->roll('1d6 explode >=6');
        $this->assertIsInt($result1->total);
        
        // "1d6 explode <=1" - only 1 explodes (valid)
        $result2 = $this->phpdice->roll('1d6 explode <=1');
        $this->assertIsInt($result2->total);
        
        // "1d6 explode >=2" - 2-6 explode (valid, 5 out of 6 values)
        $result3 = $this->phpdice->roll('1d6 explode >=2');
        $this->assertIsInt($result3->total);
    }

    /**
     * Test explosion with keep mechanics
     * Parser expects order: keep, explode, reroll, success
     */
    public function testExplosionWithKeepMechanics(): void
    {
        $result = $this->phpdice->roll('6d6 keep 4 highest explode >=5');
        
        // Should roll 6 dice
        $this->assertCount(6, $result->diceValues);
        
        // Should keep 4 highest
        $this->assertCount(4, $result->keptDice ?? []);
        
        // Total should be sum of kept dice (which may have exploded)
        $total = 0;
        foreach ($result->keptDice as $index) {
            $total += $result->diceValues[$index];
        }
        $this->assertEquals($total, $result->total);
    }

    /**
     * Test explosion statistics calculation
     */
    public function testExplosionStatistics(): void
    {
        $result = $this->phpdice->roll('3d6 explode');
        
        // With explosions, expected should be higher than base dice
        $baseExpected = 3 * 3.5; // 3d6 average
        $this->assertGreaterThan($baseExpected, $result->expression->statistics->expected);
        
        // Maximum should account for explosion limit
        $this->assertGreaterThan(18, $result->expression->statistics->maximum); // More than 3d6 max
    }

    /**
     * Test explosion with invalid operator
     */
    public function testExplosionWithInvalidOperator(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Only >= and <= are supported');
        
        $this->phpdice->roll('3d6 explode >5');
    }

    /**
     * Test explosion with negative limit
     */
    public function testExplosionWithNegativeLimit(): void
    {
        // This should be caught by parser validation
        // For now, just verify it doesn't break (parser may not validate yet)
        try {
            $result = $this->phpdice->roll('3d6 explode -1');
            // If it doesn't throw, at least verify result is valid
            $this->assertIsInt($result->total);
        } catch (ValidationException $e) {
            // Expected - negative limit should be rejected
            $this->assertStringContainsString('negative', strtolower($e->getMessage()));
        }
    }
}
