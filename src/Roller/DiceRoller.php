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
        $diceValues = [];

        // Roll each die
        for ($i = 0; $i < $spec->count; $i++) {
            $diceValues[] = $this->rng->generate(1, $spec->sides);
        }

        // Calculate total
        if ($ast !== null) {
            // Evaluate AST with dice results
            $this->setDiceResults($ast, array_sum($diceValues));
            $total = $ast->evaluate();
        } else {
            $total = array_sum($diceValues) + $expression->modifiers->arithmeticModifier;
        }

        return new RollResult(
            expression: $expression,
            total: $total,
            diceValues: $diceValues
        );
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
