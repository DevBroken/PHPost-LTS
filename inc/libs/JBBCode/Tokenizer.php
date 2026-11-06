<?php

namespace JBBCode;

/**
 * This Tokenizer is used while constructing the parse tree. The tokenizer
 * handles splitting the input into brackets and miscellaneous text. The
 * parser is then built as a FSM ontop of these possible inputs.
 *
 * @author jbowens
 */
class Tokenizer
{

    /** @var integer[] the positions of tokens found during parsing */
    protected array $tokens = [];

    /** @var integer the number of the current token */
    protected int $i = -1;

    /**
     * Constructs a tokenizer from the given string. The string will be tokenized
     * upon construction.
     *
     * @param string $str the string to tokenize
     */
    public function __construct($str)
    {
        $strLen = strlen($str ?? '');
        $position = 0;

        while ($position < $strLen) {
            $offset = strcspn($str, '[]', $position);
            $condition = ($offset === 0);
            //Have we hit a single ']' or '['?
            $this->tokens[] = $condition ? $str[$position] : substr($str, $position, $offset);
            if ($condition) {
                $position++;
            } else {
                $position += $offset;
            }
        }
    }

    /**
     * Returns true if there is another token in the token stream.
     * @return boolean
     */
    public function hasNext(): bool
    {
        return isset($this->tokens[$this->i + 1]);
    }

    /**
     * Advances the token stream to the next token and returns the new token.
     * @return null|string
     */
    public function next(): ?string
    {
        return (!$this->hasNext()) ? null : $this->tokens[++$this->i];
    }

    /**
     * Retrieves the current token.
     * @return null|string
     */
    public function current(): ?string
    {
        return ($this->i < 0) ? null : $this->tokens[$this->i];
    }

    /**
     * Moves the token stream back a token.
     */
    public function stepBack(): void
    {
        if ($this->i > -1) {
            $this->i--;
        }
    }

    /**
     * Restarts the tokenizer, returning to the beginning of the token stream.
     */
    public function restart(): void
    {
        $this->i = -1;
    }

    /**
     * toString method that returns the entire string from the current index on.
     * @return string
     */
    public function toString(): string
    {
        return implode('', array_slice($this->tokens, $this->i + 1));
    }
}
