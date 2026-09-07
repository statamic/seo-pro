<?php

namespace Statamic\SeoPro\Fieldtypes;

use Statamic\Fields\Fieldtype;

class OpeningHoursFieldtype extends Fieldtype
{
    public static $handle = 'seo_pro_opening_hours';

    protected $selectable = false;

    protected static array $days = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    public function preProcess($data)
    {
        return collect(static::$days)
            ->mapWithKeys(fn (string $day): array => [$day => [
                'opening' => $data[$day]['opening'] ?? null,
                'closing' => $data[$day]['closing'] ?? null,
            ]])
            ->all();
    }

    public function process($data)
    {
        $hours = collect(static::$days)
            ->mapWithKeys(fn (string $day): array => [$day => [
                'opening' => $data[$day]['opening'] ?? null,
                'closing' => $data[$day]['closing'] ?? null,
            ]])
            ->filter(fn (array $times): bool => $times['opening'] && $times['closing'])
            ->all();

        return empty($hours) ? null : $hours;
    }

    public function preload(): array
    {
        return [
            'days' => collect(static::$days)
                ->mapWithKeys(fn (string $day): array => [$day => __('seo-pro::messages.'.$day)])
                ->all(),
        ];
    }
}
