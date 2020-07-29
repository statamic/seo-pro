<?php

namespace Statamic\SeoPro;

use Statamic\Events;
use Statamic\Facades\File;
use Statamic\Facades\YAML;
use Statamic\Fields\FieldTransformer;
use Statamic\Statamic;
use Statamic\Support\Arr;

class Blueprint
{
    const DATA_PROPERTY = [
        Events\EntryBlueprintFound::class => 'entry',
        Events\TermBlueprintFound::class => 'term',
    ];

    protected $blueprint;
    protected $data;

    protected static $addingField = false;

    /**
     * Instantiate blueprint found event handler.
     *
     * @param mixed $event
     */
    public function __construct($event)
    {
        $this->blueprint = $event->blueprint;
        $this->data = $this->getEventData($event);
    }

    /**
     * Instantiate blueprint found event handler.
     *
     * @param mixed $event
     * @return static
     */
    public static function on($event)
    {
        return new static($event);
    }

    /**
     * Add SEO section and fields to blueprint.
     */
    public function addSeoFields()
    {
        if (static::$addingField) {
            return;
        }

        static::$addingField = true;

        $this->blueprint->ensureFieldInSection('seo', $this->seoField(), __('SEO'));

        static::$addingField = false;
    }

    /**
     * Get event data.
     *
     * @param mixed $event
     * @return mixed
     */
    protected function getEventData($event)
    {
        $eventClass = get_class($event);

        $dataProperty = static::DATA_PROPERTY[$eventClass];

        return $event->{$dataProperty};
    }

    /**
     * SEO field config.
     *
     * @return array
     */
    protected function seoField()
    {
        return [
            'type' => 'seo_pro',
            'fields' => Fields::new($this->data)->getConfig(),
        ];
    }
}
