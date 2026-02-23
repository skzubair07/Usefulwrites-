<?php
/**
 * Runs provider sequence with failover.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class Failover_Manager {
    public function __construct(
        private Provider_Registry $registry,
        private Health_Monitor $health_monitor,
        private Error_Translator $translator,
        private Astra_Debugger $debugger
    ) {
    }

    /**
     * @param array<int,string> $provider_order
     * @return array{success:bool,output:string,provider:?string,error:?string}
     */
    public function execute(string $prompt, array $provider_order): array {
        foreach ($provider_order as $provider_key) {
            if (!$this->registry->has($provider_key) || !$this->registry->is_enabled($provider_key)) {
                continue;
            }

            if ($this->health_monitor->is_in_cooldown($provider_key)) {
                $this->debugger->log(array(
                    'event'   => 'provider_cooldown_triggered',
                    'context' => array('provider' => $provider_key),
                ));
                continue;
            }

            $this->debugger->log(array(
                'event'   => 'provider_attempted',
                'context' => array('provider' => $provider_key),
            ));

            $provider = $this->registry->get($provider_key);
            if ($provider === null) {
                continue;
            }

            $result = $provider->generate($prompt, $this->registry->get_config($provider_key));
            $success = (bool) ($result['success'] ?? false);
            $http_code = (int) ($result['http_code'] ?? 0);
            $raw_error = isset($result['error']) ? (string) $result['error'] : null;

            if ($success) {
                $this->health_monitor->mark_success($provider_key);
                $this->debugger->log(array(
                    'event'   => 'provider_success',
                    'context' => array(
                        'provider'  => $provider_key,
                        'http_code' => $http_code,
                    ),
                ));

                return array(
                    'success'  => true,
                    'output'   => (string) ($result['output'] ?? ''),
                    'provider' => $provider_key,
                    'error'    => null,
                );
            }

            $translated = $this->translator->translate($raw_error, $http_code);
            $this->health_monitor->mark_failure($provider_key, $translated);

            $this->debugger->log(array(
                'event'   => 'provider_failed',
                'context' => array(
                    'provider'         => $provider_key,
                    'http_code'        => $http_code,
                    'translated_error' => $translated,
                    'failover'         => true,
                ),
            ));
        }

        return array(
            'success'  => false,
            'output'   => '',
            'provider' => null,
            'error'    => 'All configured providers failed.',
        );
    }
}
