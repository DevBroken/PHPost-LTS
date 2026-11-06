<?php

namespace JBBCode\visitors;

/**
 * This visitor escapes html content of all strings and attributes
 *
 * @author Alexander Polyanskikh
 */
class HTMLSafeVisitor implements \JBBCode\NodeVisitor
{
    public function visitDocumentElement(\JBBCode\DocumentElement $documentElement): void
    {
        foreach ($documentElement->getChildren() as $child) {
            $child->accept($this);
        }
    }

    public function visitTextNode(\JBBCode\TextNode $textNode): void
    {
        $textNode->setValue($this->htmlSafe($textNode->getValue()));
    }

    public function visitElementNode(\JBBCode\ElementNode $elementNode): void
    {
        $attrs = $elementNode->getAttribute();
        if (is_array($attrs) && !empty($attrs)) {
            $escapedAttrs = [];
            foreach ($attrs as $key => $value) {
                $escapedAttrs[$key] = $this->htmlSafe($value);
            }
            $elementNode->setAttribute($escapedAttrs);
        }

        foreach ($elementNode->getChildren() as $child) {
            $child->accept($this);
        }
    }

    protected function htmlSafe(string $str, ?int $options = null): string
    {
        if ($options === null) {
            $options = ENT_QUOTES | ENT_HTML401;
            if (defined('ENT_DISALLOWED')) {
                $options |= ENT_DISALLOWED;
            }
        }
        return htmlspecialchars($str, $options, 'UTF-8');
    }
}
