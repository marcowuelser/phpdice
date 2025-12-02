<?php

declare(strict_types=1);

namespace PHPDice\Parser\AST;

/**
 * Represents a dice roll node in the AST
 */
class DiceNode extends Node
{
    private int|float $rollResult = 0;

    public function __construct(
        private readonly int $count,
        private readonly int $sides
    ) {
    }

    public function evaluate(): int|float
    {
        return $this->rollResult;
    }

    public function setRollResult(int|float $result): void
    {
        $this->rollResult = $result;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getSides(): int
    {
        return $this->sides;
    }
}
