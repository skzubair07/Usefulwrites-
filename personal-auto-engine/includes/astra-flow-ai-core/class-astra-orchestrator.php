<?php
/**
 * Main AI orchestration entry point.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class Astra_Orchestrator {
    public function __construct(
        private Smart_Context $smart_context,
        private Failover_Manager $failover_manager,
        private Ghost_Fallback $ghost_fallback,
        private Astra_Debugger $debugger
    ) {
    }

    /**
     * @param array<int,string> $provider_order
     * @return array{success:bool,caption:string,provider:string,fallback:bool,error:?string}
     */
    public function generate_caption(string $post_content, string $platform, array $provider_order): array {
        $safe_content = $this->smart_context->prepare($post_content);

        $prompt = $this->build_prompt($safe_content, $platform);
        $this->debugger->log(array(
            'event'   => 'orchestration_started',
            'context' => array(
                'platform'       => $platform,
                'provider_count' => count($provider_order),
            ),
        ));

        $result = $this->failover_manager->execute($prompt, $provider_order);

        if ((bool) $result['success'] === true && trim((string) $result['output']) !== '') {
            return array(
                'success'  => true,
                'caption'  => (string) $result['output'],
                'provider' => (string) $result['provider'],
                'fallback' => false,
                'error'    => null,
            );
        }

        $fallback = $this->ghost_fallback->generate($safe_content !== '' ? $safe_content : $post_content, $platform);
        $this->debugger->log(array(
            'event'   => 'ghost_fallback_used',
            'context' => array(
                'platform' => $platform,
                'reason'   => (string) ($result['error'] ?? 'unknown'),
            ),
        ));

        return array(
            'success'  => true,
            'caption'  => $fallback['caption'],
            'provider' => 'ghost_fallback',
            'fallback' => true,
            'error'    => (string) ($result['error'] ?? ''),
        );
    }

    private function build_prompt(string $content, string $platform): string {
        return sprintf(
            "Create a high-quality social caption for %s. Keep it concise, clear, and engaging.\n\nContent:\n%s",
            $platform,
            $content
        );
    }
}
