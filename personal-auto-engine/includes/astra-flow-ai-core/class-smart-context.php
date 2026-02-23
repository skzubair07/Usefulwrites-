<?php
/**
 * Token-safe content trimmer.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class Smart_Context {
    public function prepare(string $content): string {
        $sanitized = trim(wp_strip_all_tags($content));
        if ($sanitized === '') {
            return '';
        }

        $words = preg_split('/\s+/', $sanitized) ?: array();
        $word_count = count($words);

        if ($word_count <= 1500) {
            return $sanitized;
        }

        $start = array_slice($words, 0, 500);
        $end = array_slice($words, -200);
        return implode(' ', array_merge($start, $end));
    }
}
