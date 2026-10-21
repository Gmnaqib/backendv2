<?php

namespace App\Helpers;

class HtmlSanitizer
{
    
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's',
        'ul', 'ol', 'li', 'a', 'blockquote',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'img', 'span', 'sub', 'sup', 'pre', 'code',
    ];

    
    private const ALLOWED_ATTRIBUTES = [
        'a'   => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height'],
    ];

    
    private const SAFE_PROTOCOLS = ['http', 'https', 'mailto'];

    
    public static function sanitize(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        
        
        $dangerousTags = [
            'script', 'style', 'iframe', 'object', 'embed',
            'applet', 'link', 'meta', 'base', 'form', 'input',
            'button', 'select', 'textarea', 'svg', 'math',
        ];

        foreach ($dangerousTags as $tag) {
            $html = preg_replace(
                '#<' . $tag . '\b[^>]*>.*?</' . $tag . '>#si',
                '',
                $html
            );
            
            $html = preg_replace(
                '#<' . $tag . '\b[^>]*/?\s*>#si',
                '',
                $html
            );
        }

        
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $wrapped = '<div>' . mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8') . '</div>';
        $dom->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $wrapped . '</body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR
        );

        libxml_clear_errors();

        
        $wrapper = $dom->getElementsByTagName('div')->item(0);
        if ($wrapper) {
            self::sanitizeNode($wrapper);
        }

        
        $output = '';
        if ($wrapper) {
            foreach ($wrapper->childNodes as $child) {
                $output .= $dom->saveHTML($child);
            }
        }

        return trim($output);
    }

    
    public static function stripAll(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        return trim(
            html_entity_decode(
                strip_tags($html),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            )
        );
    }

    
    
    

    private static function sanitizeNode(\DOMNode $node): void
    {
        $allowedTagSet = array_flip(self::ALLOWED_TAGS);

        
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                
                $tag = strtolower($child->nodeName);

                if (!isset($allowedTagSet[$tag])) {
                    
                    self::unwrapNode($child);
                    continue;
                }

                
                self::sanitizeAttributes($child, $tag);

                
                self::sanitizeNode($child);
            } elseif ($child->nodeType === XML_COMMENT_NODE) {
                
                $child->parentNode->removeChild($child);
            }
        }
    }

    private static function sanitizeAttributes(\DOMElement $element, string $tag): void
    {
        $allowed = self::ALLOWED_ATTRIBUTES[$tag] ?? [];

        $toRemove = [];
        foreach ($element->attributes as $attr) {
            $name = strtolower($attr->name);

            
            if (str_starts_with($name, 'on')) {
                $toRemove[] = $attr->name;
                continue;
            }

            
            if (in_array($name, ['style', 'class', 'id'], true)) {
                $toRemove[] = $attr->name;
                continue;
            }

            if (!in_array($name, $allowed, true)) {
                $toRemove[] = $attr->name;
                continue;
            }

            
            if (in_array($name, ['href', 'src'], true)) {
                if (!self::isSafeUrl($attr->value)) {
                    $toRemove[] = $attr->name;
                }
            }
        }

        foreach ($toRemove as $attrName) {
            $element->removeAttribute($attrName);
        }

        
        if ($tag === 'a') {
            $element->setAttribute('target', '_blank');
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function isSafeUrl(string $url): bool
    {
        $url = trim($url);

        if ($url === '' || $url === '#') {
            return true;
        }

        
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return true;
        }

        
        if (preg_match('#^([a-z][a-z0-9+\-.]*):/?#i', $url, $matches)) {
            $protocol = strtolower($matches[1]);
            return in_array($protocol, self::SAFE_PROTOCOLS, true);
        }

        
        if (str_starts_with($url, '//')) {
            return true;
        }

        
        if (str_starts_with($url, 'data:image/')) {
            return true;
        }

        
        return true;
    }

    private static function unwrapNode(\DOMElement $element): void
    {
        $parent = $element->parentNode;
        if (!$parent) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }
}
