<?php

declare(strict_types=1);

namespace PHPDice\Parser;

use PHPDice\Exception\ParseException;
use PHPDice\Model\DiceExpression;
use PHPDice\Model\DiceSpecification;
use PHPDice\Model\DiceType;
use PHPDice\Model\RollModifiers;
use PHPDice\Model\StatisticalCalculator;

/**
 * Parses dice expressions into structured DiceExpression objects
 */
class DiceExpressionParser
{
    private array $tokens = [];
    private int $current = 0;

    public function __construct(
        private readonly Validator $validator = new Validator(),
        private readonly StatisticalCalculator $calculator = new StatisticalCalculator()
    ) {
    }

    /**
     * Parse a dice expression
     *
     * @param string $expression Dice expression to parse (e.g., "3d6", "1d20+5")
     * @param array<string, int> $variables Optional placeholder variables
     * @return DiceExpression Parsed expression
     * @throws ParseException If parsing fails
     */
    public function parse(string $expression, array $variables = []): DiceExpression
    {
        // Validate expression format
        $this->validator->validateExpression($expression);

        // Tokenize
        $lexer = new Lexer($expression);
        $this->tokens = $lexer->tokenize();
        $this->current = 0;

        // Parse basic XdY notation
        $count = $this->consumeNumber();
        $this->consume(Token::TYPE_DICE);
        $sides = $this->consumeNumber();

        // Create dice specification
        $spec = new DiceSpecification(
            count: $count,
            sides: $sides,
            type: DiceType::STANDARD
        );

        // Validate specification
        $this->validator->validateDiceSpecification($spec);

        // Create modifiers (basic for now)
        $modifiers = new RollModifiers();

        // Calculate statistics
        $statistics = $this->calculator->calculate($spec, $modifiers);

        // Build expression
        return new DiceExpression(
            specification: $spec,
            modifiers: $modifiers,
            statistics: $statistics,
            originalExpression: $expression
        );
    }

    /**
     * Consume a number token
     *
     * @return int Number value
     * @throws ParseException If current token is not a number
     */
    private function consumeNumber(): int
    {
        $token = $this->tokens[$this->current] ?? null;

        if ($token === null || $token->type !== Token::TYPE_NUMBER) {
            throw new ParseException('Expected number', $token->position ?? 0);
        }

        $this->current++;
        return (int)$token->value;
    }

    /**
     * Consume a specific token type
     *
     * @param string $type Expected token type
     * @throws ParseException If current token doesn't match expected type
     */
    private function consume(string $type): void
    {
        $token = $this->tokens[$this->current] ?? null;

        if ($token === null || $token->type !== $type) {
            throw new ParseException("Expected {$type}", $token->position ?? 0);
        }

        $this->current++;
    }
}
