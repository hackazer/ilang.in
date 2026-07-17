<?php

declare(strict_types=1);

namespace Helpers;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

final class HtmlSanitizer
{
    private const MAX_INPUT_BYTES = 1_048_576;

    private const ALLOWED_ELEMENTS = [
        'a', 'b', 'i', 'iframe', 'img', 'li', 'ol', 'p', 'strong', 'u', 'ul',
    ];

    private const DROP_WITH_CONTENT = [
        'applet', 'audio', 'base', 'button', 'canvas', 'embed', 'form', 'frame',
        'frameset', 'input', 'link', 'math', 'meta', 'noscript', 'object',
        'option', 'plaintext', 'script', 'select', 'source', 'style', 'svg',
        'template', 'textarea', 'track', 'video', 'xmp',
    ];

    private const IFRAME_HOSTS = [
        'embed.music.apple.com',
        'open.spotify.com',
        'player.vimeo.com',
        'w.soundcloud.com',
        'www.youtube-nocookie.com',
        'www.youtube.com',
        'youtube-nocookie.com',
        'youtube.com',
    ];

    public static function sanitizeBioHtml(string $html): string
    {
        if ($html === '') {
            return '';
        }

        if (!class_exists(DOMDocument::class)) {
            return '';
        }

        if (strlen($html) > self::MAX_INPUT_BYTES || str_contains($html, "\0") || preg_match('//u', $html) !== 1) {
            return '';
        }

        $html = preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/u', '', $html);

        if (!is_string($html) || $html === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrorMode = libxml_use_internal_errors(true);

        try {
            $loaded = $document->loadHTML(
                '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>'
                .'<div id="bio-html-sanitizer-root">'.$html.'</div></body></html>',
                LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT
            );

            if ($loaded !== true) {
                return '';
            }

            $roots = (new DOMXPath($document))->query('//div[@id="bio-html-sanitizer-root"]');
            $root = $roots === false ? null : $roots->item(0);

            if (!$root instanceof DOMElement) {
                return '';
            }

            foreach (self::nodes($root) as $child) {
                self::sanitizeNode($child);
            }

            $output = '';

            foreach (self::nodes($root) as $child) {
                $serialized = $document->saveHTML($child);

                if (is_string($serialized)) {
                    $output .= $serialized;
                }
            }

            return $output;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorMode);
        }
    }

    public static function sanitizeBioBlock(array $block): array
    {
        if (!isset($block['type']) || !is_string($block['type']) || !in_array($block['type'], ['html', 'text'], true)) {
            return $block;
        }

        foreach ($block as $key => $value) {
            if ($key !== 'type' && is_string($value)) {
                $block[$key] = self::sanitizeBioHtml($value);
            }
        }

        return $block;
    }

    public static function sanitizeBioProfileData(array $profileData): array
    {
        if (!isset($profileData['links']) || !is_array($profileData['links'])) {
            return $profileData;
        }

        foreach ($profileData['links'] as $key => $block) {
            if (is_array($block)) {
                $profileData['links'][$key] = self::sanitizeBioBlock($block);
            }
        }

        return $profileData;
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        if ($node->nodeType === XML_COMMENT_NODE || $node->nodeType === XML_PI_NODE) {
            $node->parentNode?->removeChild($node);

            return;
        }

        if (!$node instanceof DOMElement) {
            return;
        }

        $tag = strtolower($node->tagName);

        if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
            $node->parentNode?->removeChild($node);

            return;
        }

        if (!in_array($tag, self::ALLOWED_ELEMENTS, true)) {
            foreach (self::nodes($node) as $child) {
                self::sanitizeNode($child);
            }

            self::unwrap($node);

            return;
        }

        self::sanitizeAttributes($node, $tag);

        if (($tag === 'img' || $tag === 'iframe') && !$node->hasAttribute('src')) {
            $node->parentNode?->removeChild($node);

            return;
        }

        foreach (self::nodes($node) as $child) {
            self::sanitizeNode($child);
        }
    }

    private static function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $attributes = [];

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $attributes[strtolower($attribute->nodeName)] = $attribute->nodeValue;
            $element->removeAttributeNode($attribute);
        }

        if ($tag === 'a') {
            self::copyTextAttribute($element, $attributes, 'title');

            if (isset($attributes['href']) && ($href = self::safeLinkUrl($attributes['href'])) !== null) {
                $element->setAttribute('href', $href);
                $target = isset($attributes['target']) && in_array(strtolower(trim($attributes['target'])), ['_blank', '_self'], true)
                    ? strtolower(trim($attributes['target']))
                    : null;

                if ($target !== null) {
                    $element->setAttribute('target', $target);
                }

                $element->setAttribute('rel', $target === '_blank' ? 'nofollow noopener noreferrer' : 'nofollow');
            }

            return;
        }

        if ($tag === 'img') {
            if (isset($attributes['src']) && ($src = self::safeResourceUrl($attributes['src'])) !== null) {
                $element->setAttribute('src', $src);
                $element->setAttribute('loading', 'lazy');
                $element->setAttribute('referrerpolicy', 'no-referrer');
            }

            self::copyTextAttribute($element, $attributes, 'alt');
            self::copyTextAttribute($element, $attributes, 'title');
            self::copyDimension($element, $attributes, 'width');
            self::copyDimension($element, $attributes, 'height');

            return;
        }

        if ($tag === 'iframe') {
            if (isset($attributes['src']) && ($src = self::safeIframeUrl($attributes['src'])) !== null) {
                $element->setAttribute('src', $src);
                $element->setAttribute('loading', 'lazy');
                $element->setAttribute('referrerpolicy', 'no-referrer');
            }

            self::copyTextAttribute($element, $attributes, 'title');
            self::copyDimension($element, $attributes, 'width');
            self::copyDimension($element, $attributes, 'height');

            if (array_key_exists('allowfullscreen', $attributes)) {
                $element->setAttribute('allowfullscreen', '');
            }
        }
    }

    private static function safeLinkUrl(string $url): ?string
    {
        $url = self::normalizeUrl($url);

        if ($url === null) {
            return null;
        }

        if (str_starts_with($url, '#') || str_starts_with($url, '?') || (str_starts_with($url, '/') && !str_starts_with($url, '//'))) {
            return $url;
        }

        $parts = parse_url($url);

        if (!is_array($parts) || !isset($parts['scheme'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);

        if (in_array($scheme, ['http', 'https'], true)) {
            return self::hasSafeNetworkAuthority($parts) ? $url : null;
        }

        if ($scheme === 'mailto') {
            $address = strtok(substr($url, 7), '?');

            return is_string($address) && filter_var($address, FILTER_VALIDATE_EMAIL) !== false ? $url : null;
        }

        if ($scheme === 'tel') {
            return preg_match('/\Atel:\+?[0-9(). -]{3,32}\z/i', $url) === 1 ? $url : null;
        }

        return null;
    }

    private static function safeResourceUrl(string $url): ?string
    {
        $url = self::normalizeUrl($url);

        if ($url === null) {
            return null;
        }

        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return $url;
        }

        $parts = parse_url($url);

        return is_array($parts)
            && isset($parts['scheme'])
            && in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            && self::hasSafeNetworkAuthority($parts)
                ? $url
                : null;
    }

    private static function safeIframeUrl(string $url): ?string
    {
        $url = self::normalizeUrl($url);
        $parts = $url === null ? false : parse_url($url);

        if (!is_array($parts)
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || !self::hasSafeNetworkAuthority($parts)
            || isset($parts['port']) && $parts['port'] !== 443) {
            return null;
        }

        $host = strtolower(rtrim($parts['host'], '.'));

        return in_array($host, self::IFRAME_HOSTS, true) ? $url : null;
    }

    private static function normalizeUrl(string $url): ?string
    {
        $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $url = trim($url);

        if ($url === ''
            || strlen($url) > 2_048
            || preg_match('/[\x00-\x20\x7f]/', $url)
            || str_contains($url, '\\')) {
            return null;
        }

        return $url;
    }

    private static function hasSafeNetworkAuthority(array $parts): bool
    {
        return isset($parts['host'])
            && is_string($parts['host'])
            && $parts['host'] !== ''
            && !isset($parts['user'])
            && !isset($parts['pass']);
    }

    private static function copyTextAttribute(DOMElement $element, array $attributes, string $name): void
    {
        if (!isset($attributes[$name]) || !is_string($attributes[$name])) {
            return;
        }

        $value = trim($attributes[$name]);

        if ($value !== '' && strlen($value) <= 512 && preg_match('/[\x00-\x1f\x7f]/', $value) !== 1) {
            $element->setAttribute($name, $value);
        }
    }

    private static function copyDimension(DOMElement $element, array $attributes, string $name): void
    {
        if (!isset($attributes[$name]) || preg_match('/\A[1-9][0-9]{0,3}\z/', $attributes[$name]) !== 1) {
            return;
        }

        $dimension = (int) $attributes[$name];

        if ($dimension <= 4_096) {
            $element->setAttribute($name, (string) $dimension);
        }
    }

    private static function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if ($parent === null) {
            return;
        }

        while ($element->firstChild !== null) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    /** @return list<DOMNode> */
    private static function nodes(DOMNode $node): array
    {
        return iterator_to_array($node->childNodes);
    }
}
