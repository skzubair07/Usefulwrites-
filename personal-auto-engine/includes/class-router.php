<?php

if (! defined('ABSPATH')) {
    exit;
}

class PADE_Router {
    private PADE_Settings $settings;
    private PADE_Logger $logger;

    public function __construct(PADE_Settings $settings, PADE_Logger $logger) {
        $this->settings = $settings;
        $this->logger = $logger;
    }

    public function dispatch(string $platform, array $payload): array {
        $handler = match ($platform) {
            'telegram' => new PADE_Platform_Telegram($this->settings, $this->logger),
            'pinterest' => new PADE_Platform_Pinterest($this->settings, $this->logger),
            'facebook' => new PADE_Platform_Facebook($this->settings, $this->logger),
            'twitter' => new PADE_Platform_Twitter($this->settings, $this->logger),
            'linkedin' => new PADE_Platform_LinkedIn($this->settings, $this->logger),
            default => null,
        };

        if (null === $handler) {
            return ['success' => false, 'http_code' => 0, 'raw_response' => __('Unsupported platform.', 'personal-auto-engine')];
        }

        return $handler->send($payload);
    }

    public function platforms(): array {
        return ['telegram', 'pinterest', 'facebook', 'twitter', 'linkedin'];
    }
}
