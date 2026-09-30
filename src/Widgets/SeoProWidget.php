<?php

namespace Statamic\SeoPro\Widgets;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\File;
use Statamic\Facades\Entry;
use Statamic\Facades\Term;
use Statamic\Facades\User;
use Statamic\SeoPro\Reporting\Report;
use Statamic\Widgets\VueComponent;
use Statamic\Widgets\Widget;

/**
 * Dashboard widget for the latest SEO report: when it was generated, the score,
 * and which rules are still open.
 *
 * Config options (config/statamic/cp.php):
 * - title: widget title (default "SEO Pro")
 * - show_rules: list the open rules below the score (default true)
 * - stale_after_days: after how many days the report is considered outdated
 *   and a "Generate Report" button is shown (default 30)
 */
class SeoProWidget extends Widget
{
    /**
     * Short rule labels for the table. The rule descriptions are full sentences
     * and don't fit into a table row. Unknown rules fall back to the description.
     */
    protected const RULE_LABELS = [
        'SiteName' => 'site_name',
        'UniqueTitleTag' => 'unique_title',
        'IdealTitleLength' => 'title_length',
        'UniqueMetaDescription' => 'unique_description',
        'IdealMetaDescriptionLength' => 'description_length',
        'NoUnderscoresInUrl' => 'no_underscores',
        'ThreeSegmentUrls' => 'three_segments',
    ];

    public function component()
    {
        // Without the permission every link in the widget would lead to a 403.
        if (! User::current()?->can('view seo reports')) {
            return;
        }

        return VueComponent::render('seo-pro-widget', [
            'icon' => File::get(__DIR__.'/../../resources/svg/nav-icon.svg'),
            'title' => $this->config('title', __('SEO Pro')),
            'reportsUrl' => cp_route('seo-pro.reports.index'),
            'createUrl' => cp_route('seo-pro.reports.create'),
            'showRules' => (bool) $this->config('show_rules', true),
            'report' => $this->report(),
        ]);
    }

    protected function report(): ?array
    {
        if (! $report = Report::latestGenerated()) {
            return null;
        }

        $data = $report->toArray();

        // Legacy reports have no score until they are opened once
        // (ReportController::show() calls generateIfNecessary()). Treat them like
        // no report instead of showing a misleading 0%.
        if (($data['score'] ?? null) === null) {
            return null;
        }

        $url = cp_route('seo-pro.reports.show', $report->id());
        $rules = $this->rules($report, $data['results'] ?? [], $url);
        $open = $rules->reject(fn ($rule) => $rule['status'] === 'pass')->values();
        $passed = $rules->count() - $open->count();

        return [
            'url' => $url,
            'score' => (int) round((float) $data['score']),
            'date' => __('seo-pro::messages.widget.date', [
                'date' => $this->localDate($report)->isoFormat('LL'),
            ]),
            'freshness' => $this->freshness($report),
            'rulesChecked' => trans_choice('seo-pro::messages.widget.rules_checked', $rules->count(), ['count' => $rules->count()]),
            'openRules' => $open,
            'passed' => $passed
                ? trans_choice('seo-pro::messages.widget.rules_passed', $passed, ['count' => $passed])
                : null,
        ];
    }

    /**
     * Rules in the same order as the report. The badge counts all affected pages
     * (failures and warnings), which matches the length of the page list the
     * report shows when filtered by that rule.
     */
    protected function rules(Report $report, array $results, string $reportUrl)
    {
        $raw = $report->results() ?? [];

        return collect($results)->map(function (array $rule) use ($raw, $reportUrl) {
            $handle = $rule['handle'];
            $value = $raw[$handle] ?? null;

            // Length rules store failures and warnings separately, other page
            // rules a single count. Site rules (bool) have no page count.
            $pages = match (true) {
                is_array($value) => (int) ($value['failures'] ?? 0) + (int) ($value['warnings'] ?? 0),
                is_int($value) => $value,
                default => null,
            };

            $label = static::RULE_LABELS[$handle] ?? null;

            return [
                'handle' => $handle,
                'label' => $label ? __('seo-pro::messages.widget.rules.'.$label) : strip_tags($rule['description']),
                'status' => $rule['status'],
                'badge' => $rule['is_filterable'] && $pages !== null
                    ? trans_choice('seo-pro::messages.widget.pages', $pages, ['count' => $pages])
                    : __('seo-pro::messages.widget.site_wide'),
                'url' => $this->ruleUrl($rule, $reportUrl),
            ];
        })->values();
    }

    /**
     * Page rules open the report filtered by the rule. The site name has no
     * page list, so it links to the site defaults where it is set instead.
     */
    protected function ruleUrl(array $rule, string $reportUrl): ?string
    {
        if ($rule['is_filterable']) {
            return $reportUrl.'?rule='.$rule['handle'];
        }

        if ($rule['handle'] === 'SiteName' && User::current()?->can('edit seo site defaults')) {
            return cp_route('seo-pro.site-defaults.edit');
        }

        return null;
    }

    /**
     * Sentence below the date: how old the report is and how many entries
     * have been edited since. Past stale_after_days, the widget suggests
     * generating a new report instead of counting changes.
     */
    protected function freshness(Report $report): array
    {
        $date = $this->localDate($report);
        $now = now()->setTimezone($date->getTimezone());

        // diffInDays() is signed since Carbon 3. A report dated in the future
        // (skewed server clock) would otherwise never become stale.
        $days = (int) max(0, $date->copy()->startOfDay()->diffInDays($now->copy()->startOfDay()));

        if ($days > (int) $this->config('stale_after_days', 30)) {
            // Non-breaking space between number and unit ("3 months ago").
            $ago = preg_replace('/(\d) /u', "$1\u{00A0}", $date->diffForHumans($now, [
                'syntax' => CarbonInterface::DIFF_RELATIVE_TO_NOW,
                'parts' => 1,
            ]));

            return [
                'stale' => true,
                'text' => __('seo-pro::messages.widget.age_stale', ['ago' => $ago])
                    .' '.__('seo-pro::messages.widget.stale_detail'),
            ];
        }

        $time = $date->isoFormat(__('seo-pro::messages.widget.time_format'));

        $created = match ($days) {
            0 => __('seo-pro::messages.widget.fresh_today', ['time' => $time]),
            1 => __('seo-pro::messages.widget.fresh_yesterday', ['time' => $time]),
            default => trans_choice('seo-pro::messages.widget.age_days', $days, ['count' => $days]),
        };

        $changes = static::changesSince($report);

        return [
            'stale' => false,
            'text' => $changes === null
                ? $created
                : $created.' '.trans_choice('seo-pro::messages.widget.changes', $changes, ['count' => $changes]),
        ];
    }

    /**
     * The report date in the CP's display timezone. app.timezone is usually UTC,
     * which would shift the time and the today/yesterday boundary.
     */
    protected function localDate(Report $report): CarbonInterface
    {
        return $report->date()
            ->setTimezone(config('statamic.system.display_timezone') ?? config('app.timezone'))
            ->locale(app()->getLocale());
    }

    /**
     * Entries (without redirects, like the report) and terms edited after the
     * report was generated. Queries updated_at instead of loading all content.
     *
     * Returns null when track_last_update is disabled: Statamic doesn't write
     * updated_at then, and the count would silently be wrong. File modification
     * times aren't used either, since deploys and checkouts change them.
     */
    public static function changesSince(Report $report): ?int
    {
        if (! config('statamic.system.track_last_update')) {
            return null;
        }

        $since = $report->date()->timestamp;

        return Entry::query()->whereNull('redirect')->where('updated_at', '>', $since)->count()
            + Term::query()->where('updated_at', '>', $since)->count();
    }
}
