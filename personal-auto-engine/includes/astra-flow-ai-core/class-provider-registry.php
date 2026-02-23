<?php
/**
 * Dynamic provider registry.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class Provider_Registry {
    /** @var array<string,Astra_Provider_Interface> */
    private array $providers = array();

    /** @var array<string,bool> */
    private array $enabled_map = array();

    /** @var array<string,array<string,mixed>> */
    private array $provider_configs = array();

    /**
     * @param array<string,array<string,mixed>> $provider_configs
     */
    public function __construct(array $provider_configs = array()) {
        $this->provider_configs = $provider_configs;
    }

    public function register(Astra_Provider_Interface $provider, bool $enabled = true): void {
        $key = $provider->get_key();
        $this->providers[$key] = $provider;
        $this->enabled_map[$key] = $enabled;
    }

    public function enable(string $provider_key): void {
        $this->enabled_map[$provider_key] = true;
    }

    public function disable(string $provider_key): void {
        $this->enabled_map[$provider_key] = false;
    }

    public function is_enabled(string $provider_key): bool {
        return (bool) ($this->enabled_map[$provider_key] ?? false);
    }

    public function has(string $provider_key): bool {
        return isset($this->providers[$provider_key]);
    }

    public function get(string $provider_key): ?Astra_Provider_Interface {
        return $this->providers[$provider_key] ?? null;
    }

    /**
     * @return array<string,mixed>
     */
    public function get_config(string $provider_key): array {
        $config = $this->provider_configs[$provider_key] ?? array();
        return is_array($config) ? $config : array();
    }

    /**
     * @param array<string,mixed> $config
     */
    public function set_config(string $provider_key, array $config): void {
        $this->provider_configs[$provider_key] = $config;
    }
}
