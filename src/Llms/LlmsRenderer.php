<?php

namespace Statamic\SeoPro\Llms;

use InvalidArgumentException;
use Statamic\Facades\Antlers;
use Statamic\Sites\Site as SiteObject;
use Statamic\View\Cascade;

class LlmsRenderer
{
    public const VERSION = 1;

    public const MAX_BYTES = 512000;

    public function render(LlmsDocument|array $document, string|SiteObject|null $site = null): string
    {
        $values = $document instanceof LlmsDocument
            ? $document->all()
            : Llms::withDefaults($document);
        $site = Llms::site($site);
        $context = $this->context($site);

        if ($values['mode'] === 'custom') {
            $contents = $this->finalize($this->parse($values['custom_source'], $context));
            $this->validateCustomSource($contents);

            return $contents;
        }

        $title = trim($this->parse($values['title'], $context));
        $this->validateSingleLine($title, 'The llms.txt title');

        if ($title === '') {
            throw new InvalidArgumentException('The llms.txt title is required.');
        }

        $lines = ['# '.$title];

        if ($summary = trim($this->parse($values['summary'], $context))) {
            $lines[] = '';
            $lines = [...$lines, ...collect(explode("\n", $this->normalizeLines($summary)))
                ->map(fn (string $line) => '> '.$line)
                ->all()];
        }

        if ($details = trim($this->parse($values['details'], $context))) {
            $this->validateDetails($details);
            $lines[] = '';
            $lines[] = $this->normalizeLines($details);
        }

        foreach ($values['sections'] as $section) {
            $sectionTitle = trim($this->parse($section['title'], $context));
            $links = collect($section['links'])
                ->map(function (array $link) use ($context) {
                    return [
                        'title' => trim($this->parse($link['title'], $context)),
                        'url' => trim($this->parse($link['url'], $context)),
                        'description' => trim($this->parse($link['description'], $context)),
                    ];
                })
                ->filter(fn (array $link) => $link['title'] !== '' || $link['url'] !== '')
                ->values();

            if ($sectionTitle === '' && $links->isEmpty()) {
                continue;
            }

            $this->validateSingleLine($sectionTitle, 'An llms.txt section title');

            if ($sectionTitle === '') {
                throw new InvalidArgumentException('Every non-empty llms.txt section requires a title.');
            }

            $lines[] = '';
            $lines[] = '## '.$sectionTitle;
            $lines[] = '';

            foreach ($links as $link) {
                $this->validateLink($link);
                $description = preg_replace('/\s+/u', ' ', $this->normalizeLines($link['description']));
                $lines[] = '- ['.$this->escapeLinkTitle($link['title']).']('.$link['url'].')'
                    .($description !== '' ? ': '.$description : '');
            }
        }

        return $this->finalize(implode("\n", $lines));
    }

    public function contextFingerprint(string|SiteObject|null $site = null): string
    {
        $site = Llms::site($site);

        return hash('sha256', (string) json_encode([
            'renderer' => self::VERSION,
            'context' => $this->context($site),
        ], JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
    }

    private function parse(string $value, array $context): string
    {
        return (string) Antlers::parse($value, $context);
    }

    private function context(SiteObject $site): array
    {
        return [
            'config' => Cascade::config(),
            'site' => $this->siteVariables($site),
        ];
    }

    private function siteVariables(SiteObject $site): array
    {
        return [
            'handle' => $site->handle(),
            'name' => $site->name(),
            'locale' => $site->locale(),
            'short_locale' => $site->shortLocale(),
            'lang' => $site->lang(),
            'url' => $site->url(),
            'permalink' => $site->absoluteUrl(),
            'direction' => (string) $site->direction(),
            'attributes' => $site->attributes(),
        ];
    }

    private function validateCustomSource(string $contents): void
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        $firstLine = collect(explode("\n", $contents))->first(fn (string $line) => trim($line) !== '');

        if (! is_string($firstLine) || ! preg_match('/^#\s+\S/u', $firstLine)) {
            throw new InvalidArgumentException('Enabled custom llms.txt source must begin with a non-empty H1 heading.');
        }
    }

    private function validateDetails(string $details): void
    {
        $lines = explode("\n", $this->normalizeLines($details));

        foreach ($lines as $index => $line) {
            if (preg_match('/^\s{0,3}#{1,6}\s+/u', $line)
                || ($index > 0 && trim($lines[$index - 1]) !== '' && preg_match('/^\s*(?:=+|-+)\s*$/u', $line))) {
                throw new InvalidArgumentException('Additional llms.txt context cannot contain Markdown headings. Use a file-list section instead.');
            }
        }
    }

    private function validateLink(array $link): void
    {
        $this->validateSingleLine($link['title'], 'An llms.txt link title');
        $this->validateSingleLine($link['url'], 'An llms.txt link URL');

        if ($link['title'] === '' || $link['url'] === '') {
            throw new InvalidArgumentException('Every llms.txt link requires a title and URL.');
        }

        if (! filter_var($link['url'], FILTER_VALIDATE_URL)
            || ! in_array(strtolower((string) parse_url($link['url'], PHP_URL_SCHEME)), ['http', 'https'], true)) {
            throw new InvalidArgumentException("The llms.txt link URL [{$link['url']}] must be an absolute HTTP or HTTPS URL.");
        }
    }

    private function validateSingleLine(string $value, string $label): void
    {
        if (str_contains($value, "\n") || str_contains($value, "\r")) {
            throw new InvalidArgumentException("{$label} must be a single line.");
        }
    }

    private function escapeLinkTitle(string $title): string
    {
        return str_replace(['\\', '[', ']'], ['\\\\', '\\[', '\\]'], $title);
    }

    private function normalizeLines(string $contents): string
    {
        return str_replace(["\r\n", "\r"], "\n", $contents);
    }

    private function normalize(string $contents): string
    {
        return rtrim($this->normalizeLines($contents))."\n";
    }

    private function finalize(string $contents): string
    {
        $contents = $this->normalize($contents);

        if (! mb_check_encoding($contents, 'UTF-8')) {
            throw new InvalidArgumentException('The resolved llms.txt document must be valid UTF-8.');
        }

        if (strlen($contents) > self::MAX_BYTES) {
            throw new InvalidArgumentException('The resolved llms.txt document must not be greater than 500 KiB.');
        }

        return $contents;
    }
}
