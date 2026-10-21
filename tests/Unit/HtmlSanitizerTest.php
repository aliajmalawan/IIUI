<?php
declare(strict_types=1);

define('ROOT_PATH', 'c:/xampp/htdocs/IIUI');
define('APP_PATH', ROOT_PATH . '/app');
require APP_PATH . '/Core/Autoloader.php';
App\Core\Autoloader::register();
App\Core\Autoloader::addNamespace('App', APP_PATH);

use App\Core\HtmlSanitizer;

$cases = [
    'script tag entirely removed' => [
        'in'  => '<p>Hello</p><script>alert(document.cookie)</script><p>World</p>',
        'assertNotContains' => ['<script', 'alert'],
        'assertContains' => ['Hello', 'World'],
    ],
    'onerror attribute stripped, img kept' => [
        'in'  => '<img src="photo.jpg" onerror="alert(1)" alt="test">',
        'assertNotContains' => ['onerror', 'alert(1)'],
        'assertContains' => ['photo.jpg', 'alt="test"'],
    ],
    'javascript: href stripped' => [
        'in'  => '<a href="javascript:alert(1)">click me</a>',
        'assertNotContains' => ['javascript:'],
        'assertContains' => ['click me'],
    ],
    'external link gets safe rel' => [
        'in'  => '<a href="https://evil.com" target="_blank">ext</a>',
        'assertContains' => ['rel="noopener noreferrer"', 'href="https://evil.com"'],
    ],
    'disallowed div unwrapped, onclick gone, text kept' => [
        'in'  => '<div onclick="alert(1)">plain text</div>',
        'assertNotContains' => ['<div', 'onclick', 'alert(1)'],
        'assertContains' => ['plain text'],
    ],
    'iframe removed entirely (incl. content)' => [
        'in'  => '<p>before</p><iframe src="javascript:alert(1)">trapped text</iframe><p>after</p>',
        'assertNotContains' => ['<iframe', 'trapped text', 'javascript'],
        'assertContains' => ['before', 'after'],
    ],
    'legitimate content passes through unchanged in substance' => [
        'in'  => '<p>Normal <strong>bold</strong> text with a <a href="/about">link</a>.</p>',
        'assertContains' => ['Normal', '<strong>bold</strong>', 'href="/about"', 'link'],
    ],
    'svg with onload removed entirely' => [
        'in'  => '<svg onload="alert(1)"><animate attributeName="x"></animate></svg><p>safe</p>',
        'assertNotContains' => ['<svg', 'onload', 'alert(1)'],
        'assertContains' => ['safe'],
    ],
    'malformed unclosed tags handled gracefully' => [
        'in'  => '<p>Unclosed <strong>bold text',
        'assertContains' => ['Unclosed', 'bold text'],
    ],
    'data: URI stripped from img src' => [
        'in'  => '<img src="data:image/png;base64,AAAA" alt="x">',
        'assertNotContains' => ['data:image'],
    ],
    'table structure with colspan preserved' => [
        'in'  => '<table><tbody><tr><td colspan="2">cell</td></tr></tbody></table>',
        'assertContains' => ['<table>', 'colspan="2"', 'cell'],
    ],
    'style attribute stripped everywhere' => [
        'in'  => '<p style="background:url(javascript:alert(1))">text</p>',
        'assertNotContains' => ['style=', 'javascript'],
        'assertContains' => ['text'],
    ],
    'nested script inside allowed tag still removed' => [
        'in'  => '<p>Before<script>alert(2)</script>After</p>',
        'assertNotContains' => ['<script', 'alert(2)'],
        'assertContains' => ['Before', 'After'],
    ],
    'form/input stripped (no phishing widgets)' => [
        'in'  => '<form action="https://evil.com"><input type="text" name="password"></form><p>ok</p>',
        'assertNotContains' => ['<form', '<input', 'evil.com'],
        'assertContains' => ['ok'],
    ],
];

$pass = 0;
$fail = 0;

foreach ($cases as $label => $case) {
    $out = HtmlSanitizer::clean($case['in']);
    $ok = true;
    $reasons = [];

    foreach ($case['assertNotContains'] ?? [] as $needle) {
        if (str_contains($out, $needle)) {
            $ok = false;
            $reasons[] = "should NOT contain '{$needle}'";
        }
    }
    foreach ($case['assertContains'] ?? [] as $needle) {
        if (!str_contains($out, $needle)) {
            $ok = false;
            $reasons[] = "should contain '{$needle}'";
        }
    }

    if ($ok) {
        $pass++;
        echo "PASS: {$label}\n";
    } else {
        $fail++;
        echo "FAIL: {$label}\n";
        echo "   input:  {$case['in']}\n";
        echo "   output: {$out}\n";
        echo "   issues: " . implode('; ', $reasons) . "\n";
    }
}

echo "\n{$pass} passed, {$fail} failed\n";
exit($fail > 0 ? 1 : 0);
