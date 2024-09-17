<?php

namespace Statamic\SeoPro\TextProcessing\Content;

use Statamic\SeoPro\Contracts\TextProcessing\Content\Tokenizer as TokenizerContract;

class Tokenizer implements TokenizerContract
{
    public function tokenize(string $content): array
    {
        // Normalize white space.
        $text = preg_replace('/\s+/', ' ', $content);

        $pattern = '/\b\w+\'?\w*\b|[.,!?;:()\[\]{}]/';

        preg_match_all($pattern, $text, $matches);

        if (! is_array($matches) || ! isset($matches[0])) {
            return [];
        }

        return $matches[0];
    }

    public function chunk(string $content, int $tokenLimit): array
    {
        $chunks = [];
        $currentChunk = [];
        $currentTokenCount = 0;

        foreach ($this->tokenize($content) as $token) {
            $tokenLength = mb_strlen($token);

            if ($currentTokenCount + $tokenLength > $tokenLimit) {
                $chunks[] = implode('', $currentChunk);
                $currentChunk = [];
                $currentTokenCount = 0;
            }

            $currentChunk[] = $token;
            $currentTokenCount += $tokenLength;
        }

        if (! empty($currentChunk)) {
            $chunks[] = implode('', $currentChunk);
        }

        return $chunks;
    }
}
