<?php
/**
 * Provider contract for AstraFlow AI Core.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

interface Astra_Provider_Interface {
    /**
     * Generate content using provider-specific configuration.
     *
     * @param string $prompt
     * @param array<string,mixed> $config
     * @return array{success:bool,output:string,error:?string,http_code?:int}
     */
    public function generate(string $prompt, array $config): array;

    /**
     * Provider unique key.
     */
    public function get_key(): string;
}
