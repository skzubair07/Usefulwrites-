<?php
/**
 * Fallback generator when all providers fail.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class Ghost_Fallback {
    /**
     * @return array{caption:string,tags:array<int,string>}
     */
    public function generate(string $content, string $platform): array {
        $clean = trim(wp_strip_all_tags($content));
        $paragraphs = preg_split('/\n\s*\n/', $clean) ?: array();
        $first = trim((string) ($paragraphs[0] ?? ''));

        if ($first === '') {
            $sentences = preg_split('/(?<=[.!?])\s+/', $clean) ?: array();
            $first = trim((string) ($sentences[0] ?? 'Your content is ready to share.'));
        }

        if (strlen($first) > 240) {
            $first = substr($first, 0, 237) . '...';
        }

        $tags = $this->extract_tags($clean);

        $caption = sprintf(
            '[%s] %s\n\n#%s',
            ucfirst($platform),
            $first !== '' ? $first : 'Your content is ready to share.',
            implode(' #', $tags)
        );

        return array(
            'caption' => $caption,
            'tags'    => $tags,
        );
    }

    /**
     * @return array<int,string>
     */
    private function extract_tags(string $content): array {
        $content = strtolower($content);
        $content = preg_replace('/[^a-z0-9\s]/', ' ', $content) ?: '';
        $words = preg_split('/\s+/', $content) ?: array();

        $stopwords = array('the', 'and', 'for', 'with', 'that', 'this', 'from', 'into', 'your', 'have', 'will', 'about', 'when', 'where', 'what', 'been', 'were', 'their', 'they', 'them', 'then');
        $frequencies = array();

        foreach ($words as $word) {
            if (strlen($word) < 4 || in_array($word, $stopwords, true)) {
                continue;
            }

            if (!isset($frequencies[$word])) {
                $frequencies[$word] = 0;
            }
            $frequencies[$word]++;
        }

        arsort($frequencies);
        $tags = array_slice(array_keys($frequencies), 0, 5);

        if (count($tags) < 5) {
            $defaults = array('content', 'marketing', 'business', 'growth', 'social');
            foreach ($defaults as $default) {
                if (!in_array($default, $tags, true)) {
                    $tags[] = $default;
                }
                if (count($tags) === 5) {
                    break;
                }
            }
        }

        return $tags;
    }
}
