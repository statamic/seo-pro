<?php

namespace Statamic\SeoPro\Robots;

class BotRegistry
{
    /**
     * AI crawler product tokens grouped by the purpose published by their operator.
     *
     * Keep this list deliberately conservative. A crawler should only be added when
     * its operator documents both the token and a sufficiently clear purpose.
     */
    public static function categories(): array
    {
        return [
            'search' => [
                'OAI-SearchBot',
                'Claude-SearchBot',
                'PerplexityBot',
            ],
            'agent' => [
                'ChatGPT-User',
                'Claude-User',
                'Perplexity-User',
            ],
            'training' => [
                'GPTBot',
                'ClaudeBot',
                'Google-Extended',
                'Applebot-Extended',
                'CCBot',
                'Meta-ExternalAgent',
                'Bytespider',
            ],
        ];
    }
}
