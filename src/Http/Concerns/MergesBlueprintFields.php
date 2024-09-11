<?php

namespace Statamic\SeoPro\Http\Concerns;

use Statamic\Fields\Blueprint;

trait MergesBlueprintFields
{
    protected function mergeBlueprintIntoContext(Blueprint $blueprint, array $target = [], callable $callback = null): array
    {
        $fields = $blueprint->fields();
        $values = $fields->values()->all();

        if ($callback) {
            $callback($values, $blueprint);
        }

        return array_merge($target, [
            'blueprint' => $blueprint->toPublishArray(),
            'meta' => (object) $fields->meta()->all(),
            'fields' => $fields->toPublishArray(),
            'values' => $values,
        ]);
    }
}