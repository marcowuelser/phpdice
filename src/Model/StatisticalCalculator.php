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
        // Calculate base statistics
        $baseStats = $ast !== null 
            ? $this->calculateFromAst($ast, $spec, $modifiers)
            : $this->calculateBasicDice($spec);

        // Apply keep modifiers if present
        if ($modifiers->advantageCount !== null && ($modifiers->keepHighest !== null || $modifiers->keepLowest !== null)) {
            // Determine total dice to roll
            $totalDice = $spec->count + $modifiers->advantageCount;
            $sides = $spec->sides;

            if ($modifiers->keepHighest !== null) {
                $minimum = $modifiers->keepHighest * 1;
                $maximum = $modifiers->keepHighest * $sides;
                $expected = $this->calculateKeepHighestExpected($sides, $totalDice, $modifiers->keepHighest);
            } else { // keepLowest
                $minimum = $modifiers->keepLowest * 1;
                $maximum = $modifiers->keepLowest * $sides;
                $expected = $this->calculateKeepLowestExpected($sides, $totalDice, $modifiers->keepLowest);
            }

            // If there's an AST, we need to adjust for the arithmetic operations
            if ($ast !== null) {
                // For expressions like "1d20 advantage + 5", we need to apply the arithmetic to the keep stats
                $keepStats = new StatisticalData($minimum, $maximum, round($expected, 3));
                return $this->applyAstOperations($ast, $keepStats);
            }

            return new StatisticalData($minimum, $maximum, round($expected, 3));
        }

        // Apply arithmetic modifier if no AST
        if ($ast === null) {
            $minimum = $baseStats->minimum + $modifiers->arithmeticModifier;
            $maximum = $baseStats->maximum + $modifiers->arithmeticModifier;
            $expected = $baseStats->expected + $modifiers->arithmeticModifier;
            return new StatisticalData($minimum, $maximum, round($expected, 3));
        }

        return $baseStats;
    }

    /**
     * Calculate statistics for basic dice without modifiers
     *
     * @param DiceSpecification $spec Dice specification
     * @return StatisticalData Statistical data
     */
    private function calculateBasicDice(DiceSpecification $spec): StatisticalData
    {
        $minPerDie = 1;
        $maxPerDie = $spec->sides;
        $expectedPerDie = ($minPerDie + $maxPerDie) / 2;

        $minimum = $spec->count * $minPerDie;
        $maximum = $spec->count * $maxPerDie;
        $expected = $spec->count * $expectedPerDie;

        return new StatisticalData($minimum, $maximum, round($expected, 3));
    }

    /**
     * Apply AST operations to keep statistics
     * For "1d20 advantage + 5", replaces the dice node value with keep stats
     *
     * @param Node $node AST node
     * @param StatisticalData $diceStats Statistics for the dice after keep
     * @return StatisticalData Final statistics
     */
    private function applyAstOperations(Node $node, StatisticalData $diceStats): StatisticalData
    {
        if ($node instanceof DiceNode) {
            return $diceStats;
        }

        if ($node instanceof NumberNode) {
            $value = $node->getValue();
            return new StatisticalData($value, $value, (float)$value);
        }

        if ($node instanceof BinaryOpNode) {
            $left = $this->applyAstOperations($node->getLeft(), $diceStats);
            $right = $this->applyAstOperations($node->getRight(), $diceStats);

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
            $arg = $this->applyAstOperations($node->getArgument(), $diceStats);

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

        return $diceStats;
    }

    /**
     * Calculate expected value for keeping highest N dice from M rolls
     *
     * @param int $sides Die sides
     * @param int $totalDice Total dice rolled
     * @param int $keepCount Number to keep
     * @return float Expected value
     */
    private function calculateKeepHighestExpected(int $sides, int $totalDice, int $keepCount): float
    {
        // For d20 advantage (2d20 keep 1 highest): expected ≈ 13.825
        // General formula uses order statistics, but we approximate
        // E[kth highest of n dice] ≈ (sides + 1) * (n - k + 1) / (n + 1)
        
        $expected = 0.0;
        for ($k = 1; $k <= $keepCount; $k++) {
            // E[kth highest] ≈ (sides + 1) * (totalDice - k + 1) / (totalDice + 1)
            $expected += ($sides + 1) * ($totalDice - $k + 1) / ($totalDice + 1);
        }
        
        return $expected;
    }

    /**
     * Calculate expected value for keeping lowest N dice from M rolls
     *
     * @param int $sides Die sides
     * @param int $totalDice Total dice rolled
     * @param int $keepCount Number to keep
     * @return float Expected value
     */
    private function calculateKeepLowestExpected(int $sides, int $totalDice, int $keepCount): float
    {
        // For d20 disadvantage (2d20 keep 1 lowest): expected ≈ 7.175
        // E[kth lowest of n dice] ≈ (sides + 1) * k / (n + 1)
        
        $expected = 0.0;
        for ($k = 1; $k <= $keepCount; $k++) {
            $expected += ($sides + 1) * $k / ($totalDice + 1);
        }
        
        return $expected;
    }

    /**
     * Calculate statistics from AST
     *
     * @param Node $node AST node
     * @param DiceSpecification $spec Dice specification for dice nodes
     * @param RollModifiers $modifiers Roll modifiers
     * @return StatisticalData Statistical data
     */
    private function calculateFromAst(Node $node, DiceSpecification $spec, RollModifiers $modifiers): StatisticalData
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
            $left = $this->calculateFromAst($node->getLeft(), $spec, $modifiers);
            $right = $this->calculateFromAst($node->getRight(), $spec, $modifiers);

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
            $arg = $this->calculateFromAst($node->getArgument(), $spec, $modifiers);

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
