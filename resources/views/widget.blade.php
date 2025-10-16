<seo-pro-widget
    icon="{{ $icon }}"
    reports-url="{{ $reportsUrl }}"
    create-report-url="{{ $createReportUrl }}"
    :report='@json($report->toArray())'
></seo-pro-widget>