<?php

namespace JBBCode\visitors;

use JBBCode\{
    DocumentElement,
    ElementNode,
    NodeVisitor,
    TextNode
};

/**
 * This visitor traverses parse graph, counting the number of times each
 * tag name occurs.
 *
 * @author jbowens
 * @since January 2013
 */
class TagCountingVisitor implements NodeVisitor
{
    protected array $frequencies = [];

    public function visitDocumentElement(DocumentElement $documentElement): void
    {
        foreach ($documentElement->getChildren() as $child) {
            $child->accept($this);
        }
    }

    public function visitTextNode(TextNode $textNode): void
    {
        // Nothing to do here, text nodes do not have tag names or children
    }

    public function visitElementNode(ElementNode $elementNode): void
    {
        $tagName = strtolower($elementNode->getTagName());

        // Update this tag name's frequency
        if (isset($this->frequencies[$tagName])) {
            $this->frequencies[$tagName]++;
        } else {
            $this->frequencies[$tagName] = 1;
        }

        // Visit all the node's childrens
        foreach ($elementNode->getChildren() as $child) {
            $child->accept($this);
        }
    }

    /**
     * Retrieves the frequency of the given tag name.
     *
     * @param string $tagName the tag name to look up
     *
     * @return integer
     */
    public function getFrequency(string $tagName): int
    {
        if (!isset($this->frequencies[$tagName])) {
            return 0;
        } else {
            return (int)$this->frequencies[$tagName];
        }
    }
}
