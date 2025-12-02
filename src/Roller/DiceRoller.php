<?php

declare(strict_types=1);

namespace PHPDice\Roller;

use PHPDice\Model\DiceExpression;
use PHPDice\Model\RollResult;
use PHPDice\Parser\AST\BinaryOpNode;
use PHPDice\Parser\AST\DiceNode;
use PHPDice\Parser\AST\FunctionNode;
use PHPDice\Parser\AST\Node;

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
     * @param Node|null $ast Optional AST for complex expressions
     * @return RollResult Roll result with values and total
     */
    public function roll(DiceExpression $expression, ?Node $ast = null): RollResult
    {
        $spec = $expression->specification;
        $modifiers = $expression->modifiers;
        $diceValues = [];

        // Determine total dice to roll (base + advantage)
        $totalDiceToRoll = $spec->count;
        if ($modifiers->advantageCount !== null) {
            $totalDiceToRoll += $modifiers->advantageCount;
        }

        // Roll each die
        for ($i = 0; $i < $totalDiceToRoll; $i++) {
            $diceValues[] = $this->rng->generate(1, $spec->sides);
        }

        // Handle keep highest/lowest
        $keptIndices = null;
        $discardedIndices = null;
        $finalValues = $diceValues;

        if ($modifiers->keepHighest !== null) {
            [$finalValues, $keptIndices, $discardedIndices] = $this->keepHighest($diceValues, $modifiers->keepHighest);
        } elseif ($modifiers->keepLowest !== null) {
            [$finalValues, $keptIndices, $discardedIndices] = $this->keepLowest($diceValues, $modifiers->keepLowest);
        }

        // Calculate total
        if ($ast !== null) {
            // Evaluate AST with dice results
            $this->setDiceResults($ast, array_sum($finalValues));
            $total = $ast->evaluate();
        } else {
            $total = array_sum($finalValues) + $modifiers->arithmeticModifier;
        }

        return new RollResult(
            expression: $expression,
            total: $total,
            diceValues: $diceValues,
            keptDice: $keptIndices,
            discardedDice: $discardedIndices
        );
    }

    /**
     * Keep the highest N dice
     *
     * @param array<int> $diceValues All dice values
     * @param int $count Number to keep
     * @return array{0: array<int>, 1: array<int>, 2: array<int>} [kept values, kept indices, discarded indices]
     */
    private function keepHighest(array $diceValues, int $count): array
    {
        // Create array of [index => value]
        $indexed = [];
        foreach ($diceValues as $index => $value) {
            $indexed[$index] = $value;
        }

        // Sort by value descending, maintaining indices
        arsort($indexed);

        // Take top N
        $keptIndices = array_slice(array_keys($indexed), 0, $count, true);
        $discardedIndices = array_slice(array_keys($indexed), $count, null, true);

        $keptValues = [];
        foreach ($keptIndices as $index) {
            $keptValues[] = $diceValues[$index];
        }

        return [$keptValues, array_values($keptIndices), array_values($discardedIndices)];
    }

    /**
     * Keep the lowest N dice
     *
     * @param array<int> $diceValues All dice values
     * @param int $count Number to keep
     * @return array{0: array<int>, 1: array<int>, 2: array<int>} [kept values, kept indices, discarded indices]
     */
    private function keepLowest(array $diceValues, int $count): array
    {
        // Create array of [index => value]
        $indexed = [];
        foreach ($diceValues as $index => $value) {
            $indexed[$index] = $value;
        }

        // Sort by value ascending, maintaining indices
        asort($indexed);

        // Take bottom N
        $keptIndices = array_slice(array_keys($indexed), 0, $count, true);
        $discardedIndices = array_slice(array_keys($indexed), $count, null, true);

        $keptValues = [];
        foreach ($keptIndices as $index) {
            $keptValues[] = $diceValues[$index];
        }

        return [$keptValues, array_values($keptIndices), array_values($discardedIndices)];
    }

    /**
     * Set dice roll results in the AST
     *
     * @param Node $node Node to update
     * @param int|float $result Roll result
     */
    private function setDiceResults(Node $node, int|float $result): void
    {
        if ($node instanceof DiceNode) {
            $node->setRollResult($result);
        } elseif ($node instanceof BinaryOpNode) {
            $this->setDiceResults($node->getLeft(), $result);
            $this->setDiceResults($node->getRight(), $result);
        } elseif ($node instanceof FunctionNode) {
            $this->setDiceResults($node->getArgument(), $result);
        }
    }
}
