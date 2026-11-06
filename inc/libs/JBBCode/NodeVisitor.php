<?php

namespace JBBCode;

/**
 * Defines an interface for a visitor to traverse the node graph.
 *
 * @author jbowens
 * @since January 2013
 */
interface NodeVisitor
{
    public function visitDocumentElement(DocumentElement $documentElement): void;

    public function visitTextNode(TextNode $textNode): void;

    public function visitElementNode(ElementNode $elementNode): void;
}
