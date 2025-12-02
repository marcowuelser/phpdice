<?php

declare(strict_types=1);

namespace PHPDice\Parser;

use PHPDice\Exception\ValidationException;
use PHPDice\Model\DiceSpecification;
use PHPDice\Model\RollModifiers;

/**
 * Validates dice expressions and their components
 */
class Validator
{
    /**
     * Validate a dice specification
     *
     * @param DiceSpecification $spec Dice specification to validate
     * @throws ValidationException If validation fails
     */
    public function validateDiceSpecification(DiceSpecification $spec): void
    {
        // FR-027: Dice count must be >= 1
        if ($spec->count < 1) {
            throw new ValidationException(
                "Dice count must be at least 1, got {$spec->count}",
                'count'
            );
        }

        // FR-028: Sides must be >= 2 (updated from original spec)
        if ($spec->sides < 2) {
            throw new ValidationException(
                "Dice must have at least 2 sides, got {$spec->sides}",
                'sides'
            );
        }

        // FR-029: Maximum 100 dice total
        if ($spec->count > 100) {
            throw new ValidationException(
                "Cannot roll more than 100 dice, got {$spec->count}",
                'count'
            );
        }

        // FR-030: Maximum 100 sides per die
        if ($spec->sides > 100) {
            throw new ValidationException(
                "Dice cannot have more than 100 sides, got {$spec->sides}",
                'sides'
            );
        }
    }

    /**
     * Validate that an expression is not malformed
     *
     * @param string $expression Expression to validate
     * @throws ValidationException If expression is invalid
     */
    public function validateExpression(string $expression): void
    {
        // FR-026: Reject invalid syntax
        $trimmed = trim($expression);

        if (empty($trimmed)) {
            throw new ValidationException('Expression cannot be empty', 'expression');
        }
    }

    /**
     * Validate parenthesis matching (FR-033)
     *
     * @param string $expression Expression to validate
     * @throws ValidationException If parentheses don't match
     */
    public function validateParentheses(string $expression): void
    {
        $count = 0;

        for ($i = 0; $i < strlen($expression); $i++) {
            if ($expression[$i] === '(') {
                $count++;
            } elseif ($expression[$i] === ')') {
                $count--;
                if ($count < 0) {
                    throw new ValidationException('Unmatched closing parenthesis', 'parentheses');
                }
            }
        }

        if ($count > 0) {
            throw new ValidationException('Unmatched opening parenthesis', 'parentheses');
        }
    }

    /**
     * Validate modifiers don't conflict (FR-034)
     *
     * @param RollModifiers $modifiers Modifiers to validate
     * @throws ValidationException If modifiers conflict
     */
    public function validateModifiers(RollModifiers $modifiers): void
    {
        // FR-034: Cannot have both keepHighest and keepLowest
        if ($modifiers->keepHighest !== null && $modifiers->keepLowest !== null) {
            throw new ValidationException(
                'Cannot have both keep highest and keep lowest',
                'modifiers'
            );
        }
    }
}
