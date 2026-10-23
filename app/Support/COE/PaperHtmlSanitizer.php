<?php

namespace App\Support\COE;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Cleans a question paper written in the COE editor (Summernote) before it is
 * stored (CWE-79). Allowlist, not blocklist: only the tags the editor toolbar
 * and the paper template produce survive; other tags are unwrapped (their text
 * is kept) and script-like tags are dropped with their content. Attributes are
 * allowlisted per tag, and URLs must be http(s), mailto, or an inline image.
 */
class PaperHtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'hr', 'div', 'span', 'h2', 'h3', 'h4', 'strong', 'b', 'em', 'i', 'u', 's', 'strike',
        'ul', 'ol', 'li', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'a', 'img',
    ];

    /** Removed together with everything inside them. */
    private const DROPPED_TAGS = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select',
        'link', 'meta', 'base', 'svg', 'math', 'template', 'noscript', 'frame', 'frameset',
    ];

    public function clean(string $html): string
    {
        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        // The encoding prolog makes libxml read the fragment as UTF-8 (Hindi text included).
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="qp-root">'.$html.'</div>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('qp-root');
        if (! $root) {
            return '';
        }
        $this->cleanChildren($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }

    /** True when the cleaned paper has no visible text (e.g. only <p>&nbsp;</p>). */
    public function isBlank(string $cleanHtml): bool
    {
        $text = html_entity_decode(strip_tags($cleanHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_replace('/[\s\x{00A0}]+/u', '', $text) === '';
    }

    /** The cleaned paper as a standalone .html file (opens in a browser or Word). */
    public function toDocument(string $title, string $cleanHtml): string
    {
        return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>'.e($title).'</title></head>'
            .'<body>'.$cleanHtml.'</body></html>';
    }

    /** Back from toDocument() to the paper itself, cleaned again, to edit it. */
    public function fromDocument(string $document): string
    {
        return $this->clean(preg_match('#<body[^>]*>(.*)</body>#is', $document, $m) ? $m[1] : $document);
    }

    private function cleanChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                continue;
            }
            if (! $child instanceof DOMElement) {      // comments, CDATA, processing instructions
                $node->removeChild($child);

                continue;
            }

            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROPPED_TAGS, true)) {
                $node->removeChild($child);

                continue;
            }

            $this->cleanChildren($child);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            foreach (iterator_to_array($child->attributes) as $attr) {
                if (! $this->keepAttribute($tag, strtolower($attr->name), trim($attr->value))) {
                    $child->removeAttributeNode($attr);
                }
            }
        }
    }

    private function keepAttribute(string $tag, string $name, string $value): bool
    {
        return match (true) {
            $name === 'class' => true,
            $name === 'style' => (bool) preg_match('/^text-align\s*:\s*(left|right|center|justify)\s*;?$/i', $value),
            in_array($name, ['colspan', 'rowspan', 'width', 'height'], true) => ctype_digit($value),
            $tag === 'img' && $name === 'alt' => true,
            $tag === 'img' && $name === 'src' => (bool) preg_match('#^(https?://|data:image/(png|jpe?g|gif);base64,)#i', $value),
            $tag === 'a' && $name === 'href' => (bool) preg_match('#^(https?:|mailto:)#i', $value),
            default => false,
        };
    }
}
