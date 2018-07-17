<div class="card flush">
    <div class="head">
        <h1>SEO Pro</h1>
        <a href="{{ route('seopro.reports.index') }}" class="btn btn-primary">
            {{ translate('addons.SeoPro::messages.reports') }}
        </a>
    </div>
    <div class="card-body flex flex-col h-full -mt-5 items-center justify-center p-2">
        @if ($report)

                <div class="text-4xl leading-tight font-light
                    @if ($report->score() < 70) text-red
                    @elseif ($report->score() < 90) text-yellow-dark
                    @else text-green @endif
                ">
                    {{ $report->score() }}%
                </div>
                <p class="text-grey text-sm">
                    {{ translate('addons.SeoPro::messages.latest_report_score') }}
                </p>

        @else

            <div class="text-center p-5 ">
                <p class="text-grey text-sm mb-3">
                    {{ translate('addons.SeoPro::messages.report_no_results_text') }}
                </p>
                <a href="{{ route('seopro.reports.index') }}" class="btn btn-default">
                    {{ translate('addons.SeoPro::messages.generate_your_first_report') }}
                </a>
            </div>

        @endif
    </div>
</div>
