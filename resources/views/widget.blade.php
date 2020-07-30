<div class="card p-0 overflow-hidden">
    <div class="flex justify-between items-center p-2 mb-2">
        <h2>SEO Pro</h2>
        <a href="{{ cp_route('seo-pro.reports.index') }}" class="text-blue hover:text-blue-dark text-sm">
            {{ __('seo-pro::messages.reports') }}
        </a>
    </div>
    <div class="flex flex-col h-full -mt-5 items-center justify-center p-2 mb-2">
        @if ($report)
            <div class="text-4xl leading-tight font-light
                @if ($report->score() < 70) text-red
                @elseif ($report->score() < 90) text-yellow-dark
                @else text-green @endif
            ">
                {{ $report->score() }}%
            </div>
            <p class="text-grey text-sm">
                {{ __('seo-pro::messages.latest_report_score') }}
            </p>
        @else
            <div class="text-center pt-3 pb-1">
                <p class="text-grey text-sm mb-3">
                    {{ __('seo-pro::messages.report_no_results_text') }}
                </p>
                <a href="{{ cp_route('seo-pro.reports.index') }}" class="btn btn-default">
                    {{ __('seo-pro::messages.generate_your_first_report') }}
                </a>
            </div>
        @endif
    </div>
</div>
