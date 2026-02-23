<?php
/**
 * Provider health and cooldown manager.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class Health_Monitor {
    private const OPTION_KEY = 'astraflow_ai_core_provider_health';
    private const FAILURE_LIMIT = 3;
    private const COOLDOWN_SECONDS = 1800;

    public function mark_success(string $provider_key): void {
        $health = $this->load_health();
        $health[$provider_key] = array(
            'consecutive_failures' => 0,
            'last_success'         => time(),
            'last_error'           => null,
            'disabled_until'       => 0,
        );
        $this->save_health($health);
    }

    public function mark_failure(string $provider_key, string $error): void {
        $health = $this->load_health();
        $existing = $health[$provider_key] ?? array(
            'consecutive_failures' => 0,
            'last_success'         => 0,
            'last_error'           => null,
            'disabled_until'       => 0,
        );

        $failures = (int) $existing['consecutive_failures'] + 1;
        $disabled_until = (int) $existing['disabled_until'];

        if ($failures >= self::FAILURE_LIMIT) {
            $disabled_until = time() + self::COOLDOWN_SECONDS;
        }

        $health[$provider_key] = array(
            'consecutive_failures' => $failures,
            'last_success'         => (int) $existing['last_success'],
            'last_error'           => $error,
            'disabled_until'       => $disabled_until,
        );

        $this->save_health($health);
    }

    public function is_in_cooldown(string $provider_key): bool {
        $health = $this->load_health();
        $state = $health[$provider_key] ?? null;

        if (!is_array($state)) {
            return false;
        }

        return time() < (int) ($state['disabled_until'] ?? 0);
    }

    /**
     * @return array<string,mixed>
     */
    public function getProviderHealth(string $provider_key): array {
        $health = $this->load_health();
        return $health[$provider_key] ?? array(
            'consecutive_failures' => 0,
            'last_success'         => 0,
            'last_error'           => null,
            'disabled_until'       => 0,
        );
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function load_health(): array {
        $health = get_option(self::OPTION_KEY, array());
        return is_array($health) ? $health : array();
    }

    /**
     * @param array<string,array<string,mixed>> $health
     */
    private function save_health(array $health): void {
        update_option(self::OPTION_KEY, $health, false);
    }
}
