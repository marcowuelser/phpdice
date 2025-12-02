<?php

declare(strict_types=1);

namespace PHPDice\Model;

/**
 * Calculates statistical properties of dice expressions
 */
class StatisticalCalculator
{
    /**
     * Calculate statistics for a dice specification
     *
     * @param DiceSpecification $spec Dice specification
     * @param RollModifiers $modifiers Roll modifiers
     * @return StatisticalData Statistical data
     */
    public function calculate(DiceSpecification $spec, RollModifiers $modifiers): StatisticalData
    {
        // For basic standard dice: XdY
        $minPerDie = 1;
        $maxPerDie = $spec->sides;
        $expectedPerDie = ($minPerDie + $maxPerDie) / 2;

        $minimum = $spec->count * $minPerDie;
        $maximum = $spec->count * $maxPerDie;
        $expected = $spec->count * $expectedPerDie;

        // Apply arithmetic modifier
        $minimum += $modifiers->arithmeticModifier;
        $maximum += $modifiers->arithmeticModifier;
        $expected += $modifiers->arithmeticModifier;

        // Round expected to 3 decimal places per SC-004
        $expected = round($expected, 3);

        return new StatisticalData(
            minimum: $minimum,
            maximum: $maximum,
            expected: $expected
        );
    }
}
