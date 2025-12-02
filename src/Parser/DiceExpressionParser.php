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

        // Parse expression as AST
        $this->astRoot = $this->parseExpression();

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

        // Create modifiers
        $modifiers = new RollModifiers();

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

        // Dice notation (XdY)
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
