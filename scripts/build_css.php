<?php
// Simple CSS concatenation + minification (naive) without external dependencies
// Usage: php scripts/build_css.php

$baseDir = dirname(__DIR__);
$cssDir = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'asset' . DIRECTORY_SEPARATOR . 'css';
$files = [
    $cssDir . DIRECTORY_SEPARATOR . 'styles.css',
    $cssDir . DIRECTORY_SEPARATOR . 'responsive.css',
];

$buffer = '';
foreach ($files as $path) {
    if (!file_exists($path)) {
        fwrite(STDERR, "CSS file not found: {$path}\n");
        exit(1);
    }
    $buffer .= file_get_contents($path) . "\n";
}

// Remove comments /* ... */
$min = preg_replace('/\/\*[^*]*\*+([^/*][^*]*\*+)*\//', '', $buffer);
// Collapse whitespace
$min = preg_replace('/\s+/', ' ', $min);
// Trim spaces around symbols
$min = preg_replace('/\s*{\s*/', '{', $min);
$min = preg_replace('/\s*}\s*/', '}', $min);
$min = preg_replace('/\s*;\s*/', ';', $min);
$min = preg_replace('/\s*:\s*/', ':', $min);
$min = preg_replace('/\s*,\s*/', ',', $min);
$min = trim($min);

$outPath = $cssDir . DIRECTORY_SEPARATOR . 'styles.min.css';
if (file_put_contents($outPath, $min) === false) {
    fwrite(STDERR, "Failed to write: {$outPath}\n");
    exit(1);
}

$originalSize = strlen($buffer);
$minifiedSize = strlen($min);
$ratio = $originalSize > 0 ? round((1 - $minifiedSize / $originalSize) * 100, 2) : 0;

fwrite(STDOUT, "Generated styles.min.css (saved ~{$ratio}%): {$outPath}\n");
exit(0);
