<?php

namespace JBBCode\visitors;

/**
 * This visitor traverses parse graph, counting the number of times each
 * tag name occurs.
 *
 * @author jbowens
 * @since January 2013
 */
class TagCountingVisitor implements \JBBcode\NodeVisitor
{
    protected array $frequencies = [];

    public function visitDocumentElement(\JBBCode\DocumentElement $documentElement): void
    {
        foreach ($documentElement->getChildren() as $child) {
            $child->accept($this);
        }
    }

    public function visitTextNode(\JBBCode\TextNode $textNode): void
    {
        // Nothing to do here, text nodes do not have tag names or children
    }

    public function visitElementNode(\JBBCode\ElementNode $elementNode): void
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
