<?php

namespace Statamic\SeoPro\Http\Controllers\CP;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Statamic\Facades\Site;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\Robots\Robots;
use Statamic\SeoPro\Robots\RobotsPolicy;
use Statamic\SeoPro\Robots\RobotsRenderer;
use Statamic\SeoPro\Robots\RobotsTxtGenerator;
use Throwable;

class RobotsController extends CpController
{
    public function __construct(
        private RobotsRenderer $renderer,
        private RobotsTxtGenerator $generator,
    ) {}

    public function edit(Request $request)
    {
        $this->authorize('edit seo robots');

        $policy = Robots::get();

        $viewData = [
            'values' => $policy->all(),
            'preview' => $this->renderer->render($policy),
            'action' => cp_route('seo-pro.robots.update'),
            'previewUrl' => cp_route('seo-pro.robots.preview'),
            'liveUrl' => Robots::authority(Site::selected()).'/robots.txt',
            'file' => $this->generator->status(),
        ];

        if ($request->wantsJson()) {
            return $viewData;
        }

        return Inertia::render('seo-pro::Robots/Edit', $viewData);
    }

    public function update(Request $request)
    {
        $this->authorize('edit seo robots');

        $data = $this->validated($request);
        $policy = new RobotsPolicy($data);

        try {
            $generated = $this->generator->generate($policy);
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'file' => __('Unable to generate :path. Make sure the public directory is writable.', [
                    'path' => $this->generator->path(),
                ]),
            ]);
        }

        return [
            'generated' => true,
            'preview' => $generated['contents'],
            'file' => $this->generator->status(),
        ];
    }

    public function preview(Request $request)
    {
        $this->authorize('edit seo robots');

        $policy = new RobotsPolicy($this->validated($request));

        return ['preview' => $this->renderer->render($policy)];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'mode' => ['required', Rule::in(['managed', 'custom'])],
            'preset' => ['required', Rule::in(['neutral', 'discoverable', 'full', 'search_only', 'private', 'custom'])],
            'allow' => ['present', 'array'],
            'allow.*' => ['required', 'string', 'max:2048', 'regex:/^\//', 'not_regex:/[\r\n]/'],
            'disallow' => ['present', 'array'],
            'disallow.*' => ['required', 'string', 'max:2048', 'regex:/^\//', 'not_regex:/[\r\n]/'],
            'ai' => ['required', 'array'],
            'ai.search' => ['required', Rule::in(['neutral', 'allow', 'disallow'])],
            'ai.agent' => ['required', Rule::in(['neutral', 'allow', 'disallow'])],
            'ai.training' => ['required', Rule::in(['neutral', 'allow', 'disallow'])],
            'content_signals' => ['required', 'array'],
            'content_signals.search' => ['nullable', Rule::in(['yes', 'no'])],
            'content_signals.ai_input' => ['nullable', Rule::in(['yes', 'no'])],
            'content_signals.ai_train' => ['nullable', Rule::in(['yes', 'no'])],
            'content_signals.use' => ['nullable', Rule::in(['immediate', 'reference', 'full'])],
            'include_sitemap' => ['required', 'boolean'],
            'custom_source' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (strlen($value) > RobotsTxtGenerator::MAX_IMPORT_BYTES) {
                        $fail(__('The :attribute must not be greater than 500 KiB.', ['attribute' => $attribute]));
                    }

                    if (! mb_check_encoding($value, 'UTF-8')) {
                        $fail(__('The :attribute must be valid UTF-8.', ['attribute' => $attribute]));
                    }
                },
            ],
        ]);
    }
}
