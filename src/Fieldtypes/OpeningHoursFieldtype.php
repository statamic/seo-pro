<?php

namespace Statamic\SeoPro\Fieldtypes;

use Statamic\Fields\Fieldtype;
use Statamic\SeoPro\Fieldtypes\Rules\CompleteOpeningHours;

class OpeningHoursFieldtype extends Fieldtype
{
    public const DAYS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    public static $handle = 'seo_pro_opening_hours';

    protected $selectable = false;

    public function preProcess($data)
    {
        return collect(self::DAYS)
            ->mapWithKeys(fn (string $day): array => [$day => [
                'opening' => $data[$day]['opening'] ?? null,
                'closing' => $data[$day]['closing'] ?? null,
            ]])
            ->all();
    }

    public function process($data)
    {
        $hours = collect($this->preProcess($data))
            ->filter(fn (array $times): bool => $times['opening'] && $times['closing'])
            ->all();

        return empty($hours) ? null : $hours;
    }

    public function rules(): array
    {
        return [new CompleteOpeningHours];
    }

    public function preload(): array
    {
        return [
            'days' => collect(self::DAYS)
                ->mapWithKeys(fn (string $day): array => [$day => __('seo-pro::messages.'.$day)])
                ->all(),
        ];
    }
}
