<?php

namespace JBBCode\visitors;

use JBBCode\{
    DocumentElement,
    ElementNode,
    NodeVisitor,
    TextNode
};

/**
 * This visitor is an example of how to implement smiley parsing on the JBBCode
 * parse graph. It converts :) into image tags pointing to /smiley.png.
 *
 * @author jbowens
 * @since April 2013
 */
class SmileyVisitor implements NodeVisitor
{
    public function visitDocumentElement(DocumentElement $documentElement): void
    {
        foreach ($documentElement->getChildren() as $child) {
            $child->accept($this);
        }
    }

    public function visitTextNode(TextNode $textNode): void
    {
        /* Convert :) into an image tag. */
        $textNode->setValue(str_replace(':)', '<img src="/smiley.png" alt=":)" />', $textNode->getValue()));
    }

    public function visitElementNode(ElementNode $elementNode): void
    {
        /* We only want to visit text nodes within elements if the element's
         * code definition allows for its content to be parsed.
         */
        if ($elementNode->getCodeDefinition()->parseContent()) {
            foreach ($elementNode->getChildren() as $child) {
                $child->accept($this);
            }
        }
    }
}
