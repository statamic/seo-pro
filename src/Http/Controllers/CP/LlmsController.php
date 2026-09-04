<?php

namespace Statamic\SeoPro\Http\Controllers\CP;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\Llms\Llms;
use Statamic\SeoPro\Llms\LlmsDocument;
use Statamic\SeoPro\Llms\LlmsRenderer;
use Statamic\SeoPro\Llms\LlmsTxtGenerator;
use Statamic\StaticCaching\Cacher;
use Throwable;

class LlmsController extends CpController
{
    public function __construct(
        private LlmsRenderer $renderer,
        private LlmsTxtGenerator $generator,
        private Cacher $staticCache,
    ) {}

    public function edit(Request $request)
    {
        $this->authorize('edit seo robots');
        $site = $this->site($request);
        $document = Llms::get($site);

        return [
            'values' => $document->all(),
            'preview' => $this->previewContents($document, $site),
            'action' => cp_route('seo-pro.llms.update'),
            'generateUrl' => cp_route('seo-pro.llms.generate'),
            'previewUrl' => cp_route('seo-pro.llms.preview'),
            'liveUrl' => Llms::url($site),
            'site' => $site->handle(),
            'sites' => Site::authorized()->map(fn ($site) => [
                'handle' => $site->handle(),
                'name' => $site->name(),
            ])->values()->all(),
            'collectionOptions' => $this->collectionOptions($site),
            'entryOptions' => $this->entryOptions($site),
            'file' => $this->generator->status($site),
        ];
    }

    public function update(Request $request)
    {
        $this->authorize('edit seo robots');
        $site = $this->site($request);
        $document = new LlmsDocument($this->validated($request, $site));

        try {
            $result = $this->generator->sync($document, $site);
            $this->staticCache->invalidateUrls([Llms::url($site)]);
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'settings' => __('seo-pro::messages.llms_txt.unable_to_save_settings_writable'),
            ]);
        }

        return [
            'saved' => true,
            'preview' => $document->enabled() ? $this->renderer->render($document, $site) : '',
            'file' => $this->generator->status($site),
            'fileChanged' => $result['changed'],
            'fileRemoved' => $result['removed'],
        ];
    }

    public function generate(Request $request)
    {
        $this->authorize('edit seo robots');
        $site = $this->site($request);
        $document = new LlmsDocument($this->validated($request, $site));

        try {
            $generated = $this->generator->generate($document, $site);
            $this->staticCache->invalidateUrls([Llms::url($site)]);
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'file' => $exception->getMessage(),
            ]);
        }

        return [
            'generated' => true,
            'changed' => $generated['changed'],
            'preview' => $generated['contents'],
            'file' => $this->generator->status($site),
        ];
    }

    public function preview(Request $request)
    {
        $this->authorize('edit seo robots');
        $site = $this->site($request);
        $document = new LlmsDocument($this->validated($request, $site, false));

        try {
            return ['preview' => $this->renderer->render($document, $site)];
        } catch (Throwable $exception) {
            throw ValidationException::withMessages(['document' => $exception->getMessage()]);
        }
    }

    private function validated(Request $request, $site, bool $validateRenderedDocument = true): array
    {
        $enabled = $request->boolean('enabled');
        $managed = $request->input('mode') === 'managed';
        $custom = $request->input('mode') === 'custom';

        $values = $request->validate([
            'enabled' => ['required', 'boolean'],
            'mode' => ['required', Rule::in(['managed', 'custom'])],
            'title' => [Rule::requiredIf($enabled && $managed), 'nullable', 'string', 'max:5000'],
            'summary' => ['nullable', 'string', 'max:20000'],
            'details' => ['nullable', 'string', 'max:100000'],
            'collections' => ['sometimes', 'array', 'max:100'],
            'collections.*' => [
                'bail',
                'string',
                'max:255',
                'distinct',
                function (string $attribute, mixed $value, \Closure $fail) use ($site) {
                    $collection = Collection::find((string) $value);

                    if (! $collection
                        || ! $collection->sites()->contains($site->handle())
                        || User::current()->cannot('view', $collection)) {
                        $fail(__('The selected :attribute is invalid.', ['attribute' => $attribute]));
                    }
                },
            ],
            'entries' => ['sometimes', 'array', 'max:1000'],
            'entries.*' => [
                'bail',
                'string',
                'max:255',
                'distinct',
                function (string $attribute, mixed $value, \Closure $fail) use ($site) {
                    $entry = Entry::find((string) $value)?->in($site->handle());

                    if (! $entry || User::current()->cannot('view', $entry)) {
                        $fail(__('The selected :attribute is invalid.', ['attribute' => $attribute]));
                    }
                },
            ],
            'sections' => ['present', 'array', 'max:50'],
            'sections.*.title' => ['nullable', 'string', 'max:5000'],
            'sections.*.links' => ['present', 'array', 'max:100'],
            'sections.*.links.*.title' => ['nullable', 'string', 'max:5000'],
            'sections.*.links.*.url' => ['nullable', 'string', 'max:5000'],
            'sections.*.links.*.description' => ['nullable', 'string', 'max:20000'],
            'custom_source' => [
                Rule::requiredIf($enabled && $custom),
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (strlen((string) $value) > LlmsTxtGenerator::MAX_BYTES) {
                        $fail(__('seo-pro::messages.llms_txt.custom_source_too_large'));
                    }

                    if (! mb_check_encoding((string) $value, 'UTF-8')) {
                        $fail(__('seo-pro::messages.llms_txt.custom_source_invalid_utf8'));
                    }
                },
            ],
        ]);

        $values['title'] ??= '';
        $values['summary'] ??= '';
        $values['details'] ??= '';
        $values['collections'] ??= [];
        $values['entries'] ??= [];
        $values['custom_source'] ??= '';

        if ($validateRenderedDocument && $enabled) {
            try {
                $this->renderer->render(new LlmsDocument($values), $site);
            } catch (Throwable $exception) {
                throw ValidationException::withMessages(['document' => $exception->getMessage()]);
            }
        }

        return $values;
    }

    private function site(Request $request)
    {
        $handle = $request->input('site', Site::selected()->handle());
        $site = Site::get($handle);

        abort_unless($site, 404);
        $this->authorize('view', $site);

        return $site;
    }

    private function previewContents(LlmsDocument $document, $site): string
    {
        try {
            return $this->renderer->render($document, $site);
        } catch (Throwable) {
            return '';
        }
    }

    private function collectionOptions($site): array
    {
        return Collection::all()
            ->filter(fn ($collection) => $collection->sites()->contains($site->handle())
                && User::current()->can('view', $collection))
            ->map(fn ($collection) => [
                'label' => $collection->title(),
                'value' => $collection->handle(),
            ])
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    private function entryOptions($site): array
    {
        return Entry::query()
            ->where('site', $site->handle())
            ->whereNotNull('uri')
            ->whereStatus('published')
            ->get()
            ->filter(fn ($entry) => User::current()->can('view', $entry))
            ->map(function ($entry) {
                $title = $entry->value('title');
                $title = is_scalar($title) || $title instanceof \Stringable
                    ? trim((string) preg_replace('/\s+/u', ' ', (string) $title))
                    : '';

                return [
                    'label' => sprintf('%s — %s', $title ?: ($entry->slug() ?? $entry->id()), $entry->collection()->title()),
                    'value' => $entry->id(),
                ];
            })
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
