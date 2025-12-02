<?php

declare(strict_types=1);

namespace PHPDice\Model;

use PHPDice\Parser\AST\BinaryOpNode;
use PHPDice\Parser\AST\DiceNode;
use PHPDice\Parser\AST\FunctionNode;
use PHPDice\Parser\AST\Node;
use PHPDice\Parser\AST\NumberNode;

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
     * @param Node|null $ast Optional AST for complex expressions
     * @return StatisticalData Statistical data
     */
    public function calculate(DiceSpecification $spec, RollModifiers $modifiers, ?Node $ast = null): StatisticalData
    {
        if ($ast !== null) {
            return $this->calculateFromAst($ast, $spec);
        }

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

    /**
     * Calculate statistics from AST
     *
     * @param Node $node AST node
     * @param DiceSpecification $spec Dice specification for dice nodes
     * @return StatisticalData Statistical data
     */
    private function calculateFromAst(Node $node, DiceSpecification $spec): StatisticalData
    {
        if ($node instanceof NumberNode) {
            $value = $node->getValue();
            return new StatisticalData($value, $value, (float)$value);
        }

        if ($node instanceof DiceNode) {
            $minPerDie = 1;
            $maxPerDie = $node->getSides();
            $expectedPerDie = ($minPerDie + $maxPerDie) / 2;

            $min = $node->getCount() * $minPerDie;
            $max = $node->getCount() * $maxPerDie;
            $expected = $node->getCount() * $expectedPerDie;

            return new StatisticalData($min, $max, round($expected, 3));
        }

        if ($node instanceof BinaryOpNode) {
            $left = $this->calculateFromAst($node->getLeft(), $spec);
            $right = $this->calculateFromAst($node->getRight(), $spec);

            return match ($node->getOperator()) {
                '+' => new StatisticalData(
                    $left->minimum + $right->minimum,
                    $left->maximum + $right->maximum,
                    round($left->expected + $right->expected, 3)
                ),
                '-' => new StatisticalData(
                    $left->minimum - $right->maximum,
                    $left->maximum - $right->minimum,
                    round($left->expected - $right->expected, 3)
                ),
                '*' => new StatisticalData(
                    min(
                        $left->minimum * $right->minimum,
                        $left->minimum * $right->maximum,
                        $left->maximum * $right->minimum,
                        $left->maximum * $right->maximum
                    ),
                    max(
                        $left->minimum * $right->minimum,
                        $left->minimum * $right->maximum,
                        $left->maximum * $right->minimum,
                        $left->maximum * $right->maximum
                    ),
                    round($left->expected * $right->expected, 3)
                ),
                '/' => new StatisticalData(
                    $left->minimum / max($right->maximum, 1),
                    $left->maximum / max($right->minimum, 1),
                    round($left->expected / max($right->expected, 1), 3)
                ),
                default => new StatisticalData(0, 0, 0.0),
            };
        }

        if ($node instanceof FunctionNode) {
            $arg = $this->calculateFromAst($node->getArgument(), $spec);

            return match (strtolower($node->getName())) {
                'floor' => new StatisticalData(
                    floor($arg->minimum),
                    floor($arg->maximum),
                    round(floor($arg->expected), 3)
                ),
                'ceil', 'ceiling' => new StatisticalData(
                    ceil($arg->minimum),
                    ceil($arg->maximum),
                    round(ceil($arg->expected), 3)
                ),
                'round' => new StatisticalData(
                    round($arg->minimum),
                    round($arg->maximum),
                    round($arg->expected, 3)
                ),
                default => $arg,
            };
        }

        return new StatisticalData(0, 0, 0.0);
    }
}
