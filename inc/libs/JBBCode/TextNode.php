<?php

namespace JBBCode;

require_once 'Node.php';

/**
 * Represents a piece of text data. TextNodes never have children.
 *
 * @author jbowens
 */
class TextNode extends Node
{
    /** @var string The value of this text node */
    protected string $value;

    /**
     * Constructs a text node from its text string
     *
     * @param string $val
     */
    public function __construct(string $val)
    {
        $this->value = $val;
    }

    public function accept(NodeVisitor $visitor): void
    {
        $visitor->visitTextNode($this);
    }

    /**
     * (non-PHPdoc)
     * @see JBBCode.Node::isTextNode()
     *
     * @returns boolean true
     */
    public function isTextNode(): bool
    {
        return true;
    }

    /**
     * Returns the text string value of this text node.
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * (non-PHPdoc)
     * @see JBBCode.Node::getAsText()
     *
     * Returns the text representation of this node.
     *
     * @return string this node represented as text
     */
    public function getAsText(): string
    {
        return $this->getValue();
    }

    /**
     * (non-PHPdoc)
     * @see JBBCode.Node::getAsBBCode()
     *
     * Returns the bbcode representation of this node. (Just its value)
     *
     * @return string this node represented as bbcode
     */
    public function getAsBBCode(): string
    {
        return $this->getValue();
    }

    /**
     * (non-PHPdoc)
     * @see JBBCode.Node::getAsHTML()
     *
     * Returns the html representation of this node. (Just its value)
     *
     * @return string this node represented as HTML
     */
    public function getAsHTML(): string
    {
        return $this->getValue();
    }

    /**
     * Edits the text value contained within this text node.
     *
     * @param string $newValue  the new text value of the text node
     */
    public function setValue(string $newValue): void
    {
        $this->value = $newValue;
    }
}
