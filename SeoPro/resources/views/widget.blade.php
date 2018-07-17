<div class="card flush">
    <div class="head">
        <h1>SEO Pro</h1>
        <a href="{{ route('seopro.reports.index') }}" class="btn btn-primary">
            {{ translate('addons.SeoPro::messages.reports') }}
        </a>
    </div>
    <div class="card-body pad-16">
        @if ($report)

            <div class="text-center p-5 ">
                <p class="text-grey text-sm">
                    {{ translate('addons.SeoPro::messages.latest_report_score') }}
                </p>
                <div class="text-3xl mb-2
                    @if ($report->score() < 50) text-red
                    @elseif ($report->score() < 90) text-blue
                    @else text-green @endif
                ">
                    {{ $report->score() }}%
                </div>
            </div>

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
