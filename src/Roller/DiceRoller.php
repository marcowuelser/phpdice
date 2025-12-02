<?php

declare(strict_types=1);

namespace PHPDice\Roller;

use PHPDice\Model\DiceExpression;
use PHPDice\Model\RollResult;

/**
 * Rolls dice based on parsed expressions
 */
class DiceRoller
{
    public function __construct(
        private readonly RandomNumberGenerator $rng = new RandomNumberGenerator()
    ) {
    }

    /**
     * Roll dice based on an expression
     *
     * @param DiceExpression $expression Parsed dice expression
     * @return RollResult Roll result with values and total
     */
    public function roll(DiceExpression $expression): RollResult
    {
        $spec = $expression->specification;
        $diceValues = [];

        // Roll each die
        for ($i = 0; $i < $spec->count; $i++) {
            $diceValues[] = $this->rng->generate(1, $spec->sides);
        }

        // Calculate total
        $total = array_sum($diceValues) + $expression->modifiers->arithmeticModifier;

        return new RollResult(
            expression: $expression,
            total: $total,
            diceValues: $diceValues
        );
    }
}
