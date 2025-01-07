<?php

namespace Statamic\SeoPro\Linking\Suggestions;

use Statamic\Support\Traits\FluentlyGetsAndSets;

class PhraseContext
{
    use FluentlyGetsAndSets;

    protected string $fieldHandle = '';

    protected string $context = '';

    protected bool $canReplace = false;

    /**
     * @return ($handle is null ? string : null))
     */
    public function fieldHandle(?string $handle = null)
    {
        return $this->fluentlyGetOrSet('fieldHandle')
            ->args(func_get_args());
    }

    /**
     * @return ($context is null ? string : null)
     */
    public function context(?string $context = null)
    {
        return $this->fluentlyGetOrSet('context')
            ->args(func_get_args());
    }

    /**
     * @return ($canReplace is null ? bool : null)
     */
    public function canReplace(?bool $canReplace = null)
    {
        return $this->fluentlyGetOrSet('canReplace')
            ->args(func_get_args());
    }

    /**
     * @return array{field_handle:string,context:string,can_replace:bool}
     */
    public function toArray()
    {
        return [
            'field_handle' => $this->fieldHandle,
            'context' => $this->context,
            'can_replace' => $this->canReplace,
        ];
    }
}
