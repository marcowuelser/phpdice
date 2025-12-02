<?php

declare(strict_types=1);

namespace PHPDice\Parser;

use PHPDice\Exception\ParseException;
use PHPDice\Model\DiceExpression;
use PHPDice\Model\DiceSpecification;
use PHPDice\Model\DiceType;
use PHPDice\Model\RollModifiers;
use PHPDice\Model\StatisticalCalculator;
use PHPDice\Parser\AST\BinaryOpNode;
use PHPDice\Parser\AST\DiceNode;
use PHPDice\Parser\AST\FunctionNode;
use PHPDice\Parser\AST\Node;
use PHPDice\Parser\AST\NumberNode;

/**
 * Parses dice expressions into structured DiceExpression objects
 */
class DiceExpressionParser
{
    private array $tokens = [];
    private int $current = 0;
    private ?Node $astRoot = null;

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
        // Tokenize
        $lexer = new Lexer($expression);
        $this->tokens = $lexer->tokenize();
        $this->current = 0;

        // Parse initial dice notation to get base AST
        $this->astRoot = $this->parseTerm(); // Start with term to get just the dice

        // Extract dice specification from AST
        $diceNode = $this->findDiceNode($this->astRoot);
        if ($diceNode === null) {
            // Fallback: try simple validation for backward compatibility
            $this->validator->validateExpression($expression);
            throw new ParseException('No dice notation found in expression', 0);
        }

        // Create dice specification
        $spec = new DiceSpecification(
            count: $diceNode->getCount(),
            sides: $diceNode->getSides(),
            type: DiceType::STANDARD
        );

        // Validate specification
        $this->validator->validateDiceSpecification($spec);

        // Parse modifiers (advantage, disadvantage, keep) - these consume KEYWORD tokens
        $modifiers = $this->parseModifiers($spec);
        
        // Validate modifiers for conflicts
        $this->validator->validateModifiers($modifiers);

        // Continue parsing the rest of the expression (arithmetic operators)
        // At this point, current token might be +, -, *, /, or EOF
        while ($this->match(Token::TYPE_OPERATOR, ['+', '-'])) {
            $operator = $this->previous()->value;
            $right = $this->parseTerm();
            $this->astRoot = new BinaryOpNode($this->astRoot, (string)$operator, $right);
        }

        // Ensure all tokens are consumed
        if (!$this->isAtEnd()) {
            $remaining = $this->peek();
            // Check if it's a duplicate modifier keyword
            if ($remaining->type === Token::TYPE_KEYWORD) {
                throw new \PHPDice\Exception\ValidationException(
                    "Modifier conflict: cannot specify multiple or conflicting keep modifiers",
                    'modifiers'
                );
            }
            throw new ParseException(
                "Unexpected token: {$remaining->type} '{$remaining->value}'",
                $this->getCurrentPosition()
            );
        }

        // Calculate statistics from AST
        $statistics = $this->calculator->calculate($spec, $modifiers, $this->astRoot);

        // Build expression
        return new DiceExpression(
            specification: $spec,
            modifiers: $modifiers,
            statistics: $statistics,
            originalExpression: $expression
        );
    }

    /**
     * Get the AST root for evaluation
     *
     * @return Node|null AST root node
     */
    public function getAstRoot(): ?Node
    {
        return $this->astRoot;
    }

    /**
     * Parse an expression (handles +, -)
     *
     * @return Node Expression node
     */
    private function parseExpression(): Node
    {
        $node = $this->parseTerm();

        while ($this->match(Token::TYPE_OPERATOR, ['+', '-'])) {
            $operator = $this->previous()->value;
            $right = $this->parseTerm();
            $node = new BinaryOpNode($node, (string)$operator, $right);
        }

        return $node;
    }

    /**
     * Parse a term (handles *, /)
     *
     * @return Node Term node
     */
    private function parseTerm(): Node
    {
        $node = $this->parseFactor();

        while ($this->match(Token::TYPE_OPERATOR, ['*', '/'])) {
            $operator = $this->previous()->value;
            $right = $this->parseFactor();
            $node = new BinaryOpNode($node, (string)$operator, $right);
        }

        return $node;
    }

    /**
     * Parse a factor (handles numbers, dice, parentheses, functions)
     *
     * @return Node Factor node
     */
    private function parseFactor(): Node
    {
        // Function call
        if ($this->match(Token::TYPE_FUNCTION)) {
            return $this->parseFunction();
        }

        // Parenthesized expression
        if ($this->match(Token::TYPE_LPAREN)) {
            $expr = $this->parseExpression();
            $this->consume(Token::TYPE_RPAREN, 'Expected closing parenthesis');
            return $expr;
        }

        // Dice notation (XdY) - DON'T consume modifiers here, they're parsed separately
        if ($this->check(Token::TYPE_NUMBER) && $this->checkNext(Token::TYPE_DICE)) {
            $count = $this->consumeNumber();
            $this->consume(Token::TYPE_DICE);
            $sides = $this->consumeNumber();
            return new DiceNode($count, $sides);
        }

        // Plain number
        if ($this->match(Token::TYPE_NUMBER)) {
            return new NumberNode((int)$this->previous()->value);
        }

        throw new ParseException('Expected number, dice, or expression', $this->getCurrentPosition());
    }

    /**
     * Parse a function call
     *
     * @return FunctionNode Function node
     */
    private function parseFunction(): FunctionNode
    {
        $functionName = (string)$this->previous()->value;

        $this->consume(Token::TYPE_LPAREN, 'Expected opening parenthesis after function name');
        $argument = $this->parseExpression();
        $this->consume(Token::TYPE_RPAREN, 'Expected closing parenthesis after function argument');

        return new FunctionNode($functionName, $argument);
    }

    /**
     * Parse modifiers like advantage, disadvantage, keep
     *
     * @param DiceSpecification $spec Dice specification
     * @return RollModifiers Roll modifiers
     */
    private function parseModifiers(DiceSpecification $spec): RollModifiers
    {
        $advantageCount = null;
        $keepHighest = null;
        $keepLowest = null;
        $successThreshold = null;
        $successOperator = null;

        // Check for advantage keyword
        if ($this->match(Token::TYPE_KEYWORD, ['advantage'])) {
            // Roll spec->count extra dice, keep spec->count highest
            $advantageCount = $spec->count;
            $keepHighest = $spec->count;
        }

        // Check for disadvantage keyword
        if ($this->match(Token::TYPE_KEYWORD, ['disadvantage'])) {
            if ($advantageCount !== null) {
                throw new \PHPDice\Exception\ValidationException(
                    'Cannot have both advantage and disadvantage',
                    'modifiers'
                );
            }
            // Roll spec->count extra dice, keep spec->count lowest
            $advantageCount = $spec->count;
            $keepLowest = $spec->count;
        }

        // Check for keep X highest/lowest
        if ($this->match(Token::TYPE_KEYWORD, ['keep'])) {
            $count = $this->consumeNumber();
            
            if ($this->match(Token::TYPE_KEYWORD, ['highest'])) {
                if ($keepHighest !== null || $keepLowest !== null) {
                    throw new \PHPDice\Exception\ValidationException(
                        'Cannot specify keep multiple times',
                        'modifiers'
                    );
                }
                $keepHighest = $count;
            } elseif ($this->match(Token::TYPE_KEYWORD, ['lowest'])) {
                if ($keepHighest !== null || $keepLowest !== null) {
                    throw new \PHPDice\Exception\ValidationException(
                        'Cannot specify keep multiple times',
                        'modifiers'
                    );
                }
                $keepLowest = $count;
            } else {
                throw new ParseException('Expected "highest" or "lowest" after keep count', $this->getCurrentPosition());
            }

            // Calculate total dice to roll (base + advantage)
            $totalDiceToRoll = $spec->count;
            if ($advantageCount !== null) {
                $totalDiceToRoll += $advantageCount;
            }

            // Validate keep count doesn't exceed total dice
            if ($keepHighest !== null && $keepHighest > $totalDiceToRoll) {
                throw new \PHPDice\Exception\ValidationException(
                    "Cannot keep {$keepHighest} dice when only rolling {$totalDiceToRoll}",
                    'keep'
                );
            }
            if ($keepLowest !== null && $keepLowest > $totalDiceToRoll) {
                throw new \PHPDice\Exception\ValidationException(
                    "Cannot keep {$keepLowest} dice when only rolling {$totalDiceToRoll}",
                    'keep'
                );
            }
        }

        // Check for success counting: "success threshold N" or ">=N" or ">N"
        if ($this->match(Token::TYPE_KEYWORD, ['success'])) {
            // Expect "threshold N"
            if (!$this->match(Token::TYPE_KEYWORD, ['threshold'])) {
                throw new ParseException('Expected "threshold" after "success"', $this->getCurrentPosition());
            }
            $successThreshold = $this->consumeNumber();
            $successOperator = '>='; // Default to >= for "success threshold N" syntax
        } elseif ($this->match(Token::TYPE_KEYWORD, ['threshold'])) {
            // Just "threshold N" (shorthand for "success threshold N")
            $successThreshold = $this->consumeNumber();
            $successOperator = '>=';
        } elseif ($this->check(Token::TYPE_COMPARISON)) {
            // Direct comparison: ">=N" or ">N"
            $comparison = $this->advance();
            $operator = (string)$comparison->value;
            
            // Only allow >= and > for success counting
            if (!in_array($operator, ['>=', '>'], true)) {
                throw new \PHPDice\Exception\ValidationException(
                    "Invalid success operator '{$operator}'. Only >= and > are supported for success counting.",
                    'success'
                );
            }
            
            $successOperator = $operator;
            $successThreshold = $this->consumeNumber();
        }

        return new RollModifiers(
            advantageCount: $advantageCount,
            keepHighest: $keepHighest,
            keepLowest: $keepLowest,
            successThreshold: $successThreshold,
            successOperator: $successOperator
        );
    }

    /**
     * Find the first dice node in the AST
     *
     * @param Node $node Node to search
     * @return DiceNode|null Dice node if found
     */
    private function findDiceNode(Node $node): ?DiceNode
    {
        if ($node instanceof DiceNode) {
            return $node;
        }

        if ($node instanceof BinaryOpNode) {
            $left = $this->findDiceNode($node->getLeft());
            if ($left !== null) {
                return $left;
            }
            return $this->findDiceNode($node->getRight());
        }

        if ($node instanceof FunctionNode) {
            return $this->findDiceNode($node->getArgument());
        }

        return null;
    }

    /**
     * Check if current token matches type and optional values
     *
     * @param string $type Token type to match
     * @param array<string>|null $values Optional values to match
     * @return bool True if matches
     */
    private function match(string $type, ?array $values = null): bool
    {
        if (!$this->check($type)) {
            return false;
        }

        if ($values !== null) {
            $currentValue = $this->peek()->value;
            if (!in_array($currentValue, $values, true)) {
                return false;
            }
        }

        $this->advance();
        return true;
    }

    /**
     * Check if current token is of given type
     *
     * @param string $type Token type
     * @return bool True if matches
     */
    private function check(string $type): bool
    {
        if ($this->isAtEnd()) {
            return false;
        }

        return $this->peek()->type === $type;
    }

    /**
     * Check if next token is of given type
     *
     * @param string $type Token type
     * @return bool True if matches
     */
    private function checkNext(string $type): bool
    {
        if ($this->current + 1 >= count($this->tokens)) {
            return false;
        }

        return $this->tokens[$this->current + 1]->type === $type;
    }

    /**
     * Advance to next token
     *
     * @return Token Previous token
     */
    private function advance(): Token
    {
        if (!$this->isAtEnd()) {
            $this->current++;
        }

        return $this->previous();
    }

    /**
     * Check if at end of tokens
     *
     * @return bool True if at end
     */
    private function isAtEnd(): bool
    {
        return $this->peek()->type === Token::TYPE_EOF;
    }

    /**
     * Get current token
     *
     * @return Token Current token
     */
    private function peek(): Token
    {
        return $this->tokens[$this->current];
    }

    /**
     * Get previous token
     *
     * @return Token Previous token
     */
    private function previous(): Token
    {
        return $this->tokens[$this->current - 1];
    }

    /**
     * Consume a number token
     *
     * @return int Number value
     * @throws ParseException If current token is not a number
     */
    private function consumeNumber(): int
    {
        if (!$this->match(Token::TYPE_NUMBER)) {
            throw new ParseException('Expected number', $this->getCurrentPosition());
        }

        return (int)$this->previous()->value;
    }

    /**
     * Consume a specific token type
     *
     * @param string $type Expected token type
     * @param string|null $message Optional error message
     * @throws ParseException If current token doesn't match expected type
     */
    private function consume(string $type, ?string $message = null): void
    {
        if (!$this->match($type)) {
            $msg = $message ?? "Expected {$type}";
            throw new ParseException($msg, $this->getCurrentPosition());
        }
    }

    /**
     * Get current position in the expression
     *
     * @return int Position
     */
    private function getCurrentPosition(): int
    {
        return $this->peek()->position ?? 0;
    }
}
