<?php
/**
 * Converts technical provider errors into user-safe messages.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class Error_Translator {
    public function translate(?string $error, int $http_code = 0): string {
        if ($http_code === 401) {
            return 'API key is invalid.';
        }

        if ($http_code === 404) {
            return 'Endpoint not found. Please update Base URL.';
        }

        if ($http_code === 410) {
            return 'This provider link is outdated.';
        }

        $normalized = strtolower((string) $error);

        if (str_contains($normalized, 'timeout') || str_contains($normalized, 'timed out')) {
            return 'Provider not responding.';
        }

        if ($http_code >= 500 && $http_code < 600) {
            return 'Provider service is temporarily unavailable.';
        }

        return 'Provider request failed. Please verify configuration and try again.';
    }
}
