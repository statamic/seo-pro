<seo-pro-widget
    icon="{{ $icon }}"
    reports-url="{{ $reportsUrl }}"
    create-url="{{ $createUrl }}"
    :report='@json($report->toArray())'
></seo-pro-widget>