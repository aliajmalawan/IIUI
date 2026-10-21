<?php

declare(strict_types=1);

namespace App\Core;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Allowlist-based HTML sanitizer for TinyMCE output — docs/05-security.md §4:
 * "TinyMCE output is the only unescaped content, and it passes through
 * HTMLPurifier... on save, then again on render." No Composer is installed
 * here, so HTMLPurifier isn't vendored; this is a deliberately narrow,
 * auditable allowlist built on PHP's own DOMDocument instead of a general-
 * purpose HTML cleaner. Every module that stores rich text calls
 * HtmlSanitizer::clean() before INSERT/UPDATE, and the render path calls it
 * again — a bug in one pass is still caught by the other.
 */
final class HtmlSanitizer
{
    /** @var string[] */
    private const ALLOWED_TAGS = [
        'p', 'br', 'hr', 'h2', 'h3', 'h4', 'h5', 'blockquote',
        'ul', 'ol', 'li',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th',
        'figure', 'figcaption',
        'pre', 'code',
        'strong', 'b', 'em', 'i', 'u', 's',
        'a', 'img',
    ];

    /** @var array<string,string[]> tag => allowed attribute names */
    private const ALLOWED_ATTRIBUTES = [
        'a'   => ['href', 'target', 'title'],
        'img' => ['src', 'alt', 'width', 'height', 'title'],
        'td'  => ['colspan', 'rowspan'],
        'th'  => ['colspan', 'rowspan'],
    ];

    /** URL schemes allowed in href/src — javascript:, data:, vbscript:, file: are all excluded on purpose. */
    private const ALLOWED_URL_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /** Tags whose entire subtree (not just the wrapper) must be discarded. */
    private const STRIP_ENTIRELY = [
        'script', 'style', 'iframe', 'object', 'embed', 'form',
        'input', 'button', 'svg', 'link', 'meta', 'noscript', 'template',
    ];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $priorSetting = libxml_use_internal_errors(true);

        // The <?xml encoding="UTF-8"> prefix is the standard trick to make
        // DOMDocument respect UTF-8 without the deprecated mb_convert_encoding
        // 'HTML-ENTITIES' target (removed behaviour in PHP 8.2).
        $wrapped = '<?xml encoding="UTF-8"><!DOCTYPE html><html><body>' . $html . '</body></html>';
        $loaded = $dom->loadHTML($wrapped, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET);

        libxml_clear_errors();
        libxml_use_internal_errors($priorSetting);

        if (!$loaded) {
            return '';
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body === null) {
            return '';
        }

        self::sanitizeChildren($dom, $body);

        $output = '';
        foreach (iterator_to_array($body->childNodes) as $child) {
            $output .= $dom->saveHTML($child);
        }

        return trim($output);
    }

    private static function sanitizeChildren(DOMDocument $dom, DOMNode $parent): void
    {
        // Snapshot the child list first — nodes get removed/reparented below,
        // which would corrupt a live NodeList iterated in place.
        $children = iterator_to_array($parent->childNodes);

        foreach ($children as $node) {
            if ($node instanceof DOMText) {
                continue; // plain text is always safe as-is
            }

            if (!($node instanceof DOMElement)) {
                $parent->removeChild($node); // comments, PIs, etc.
                continue;
            }

            $tagName = strtolower($node->tagName);

            if (in_array($tagName, self::STRIP_ENTIRELY, true)) {
                $parent->removeChild($node);
                continue;
            }

            // Recurse before unwrapping so nested disallowed tags are
            // cleaned no matter what happens to this node afterward.
            self::sanitizeChildren($dom, $node);

            if (!in_array($tagName, self::ALLOWED_TAGS, true)) {
                // Disallowed wrapper: keep its already-cleaned contents,
                // discard only the tag itself (drops formatting, not words).
                while ($node->firstChild !== null) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);
                continue;
            }

            self::sanitizeAttributes($node, $tagName);
        }
    }

    private static function sanitizeAttributes(DOMElement $node, string $tagName): void
    {
        $allowed = self::ALLOWED_ATTRIBUTES[$tagName] ?? [];

        $attributeNames = [];
        foreach ($node->attributes as $attr) {
            $attributeNames[] = $attr->name;
        }

        foreach ($attributeNames as $name) {
            $lower = strtolower($name);

            // Blocks style="" too — no inline CSS is allowlisted anywhere,
            // and every on* handler (onerror, onload, onclick...) is
            // rejected here since none of them appear in any tag's list.
            if (!in_array($lower, $allowed, true)) {
                $node->removeAttribute($name);
                continue;
            }

            if (($lower === 'href' || $lower === 'src') && !self::isSafeUrl($node->getAttribute($name))) {
                $node->removeAttribute($name);
            }
        }

        if ($tagName === 'a') {
            if ($node->getAttribute('target') === '_blank') {
                $node->setAttribute('rel', 'noopener noreferrer');
            } else {
                $node->removeAttribute('target');
            }
        }
    }

    private static function isSafeUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }

        // No scheme at all ("/gallery", "photo.jpg", "#section") is fine —
        // only an explicit scheme needs checking against the allowlist.
        if (preg_match('#^([a-zA-Z][a-zA-Z0-9+.\-]*):#', $url, $m) !== 1) {
            return true;
        }

        return in_array(strtolower($m[1]), self::ALLOWED_URL_SCHEMES, true);
    }
}
