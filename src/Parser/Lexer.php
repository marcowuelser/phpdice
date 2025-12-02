<?php

declare(strict_types=1);

namespace PHPDice\Parser;

use PHPDice\Exception\ParseException;

/**
 * Tokenizes dice expressions into a stream of tokens
 */
class Lexer
{
    private int $position = 0;
    private int $length;

    /**
     * Create a new lexer
     *
     * @param string $input Dice expression to tokenize
     */
    public function __construct(private readonly string $input)
    {
        $this->length = strlen($input);
    }

    /**
     * Get all tokens from the input
     *
     * @return array<Token> Array of tokens
     * @throws ParseException If invalid syntax is encountered
     */
    public function tokenize(): array
    {
        $tokens = [];

        while ($this->position < $this->length) {
            $this->skipWhitespace();

            if ($this->position >= $this->length) {
                break;
            }

            $char = $this->input[$this->position];

            // Numbers
            if (ctype_digit($char)) {
                $tokens[] = $this->readNumber();
                continue;
            }

            // Dice notation (d or D)
            if ($char === 'd' || $char === 'D') {
                $tokens[] = new Token(Token::TYPE_DICE, 'd', $this->position);
                $this->position++;
                continue;
            }

            // Operators
            if (in_array($char, ['+', '-', '*', '/'], true)) {
                $tokens[] = new Token(Token::TYPE_OPERATOR, $char, $this->position);
                $this->position++;
                continue;
            }

            // Parentheses
            if ($char === '(') {
                $tokens[] = new Token(Token::TYPE_LPAREN, '(', $this->position);
                $this->position++;
                continue;
            }

            if ($char === ')') {
                $tokens[] = new Token(Token::TYPE_RPAREN, ')', $this->position);
                $this->position++;
                continue;
            }

            // Unknown character
            throw new ParseException("Unexpected character '{$char}'", $this->position);
        }

        $tokens[] = new Token(Token::TYPE_EOF, null, $this->position);

        return $tokens;
    }

    /**
     * Skip whitespace characters
     */
    private function skipWhitespace(): void
    {
        while ($this->position < $this->length && ctype_space($this->input[$this->position])) {
            $this->position++;
        }
    }

    /**
     * Read a number token
     *
     * @return Token Number token
     */
    private function readNumber(): Token
    {
        $start = $this->position;
        $number = '';

        while ($this->position < $this->length && ctype_digit($this->input[$this->position])) {
            $number .= $this->input[$this->position];
            $this->position++;
        }

        return new Token(Token::TYPE_NUMBER, (int)$number, $start);
    }
}
