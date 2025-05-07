<?php

namespace Statamic\SeoPro;

use Statamic\Events;

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
     * @param  mixed  $event
     */
    public function __construct($event)
    {
        $this->blueprint = $event->blueprint;
        $this->data = $this->getEventData($event);
    }

    /**
     * Instantiate blueprint found event handler.
     *
     * @param  mixed  $event
     * @return static
     */
    public static function on($event)
    {
        return new static($event);
    }

    /**
     * Ensure SEO section and fields are added to (or removed from) blueprint.
     *
     * @param  bool  $isEnabled
     */
    public function ensureSeoFields($isEnabled = true)
    {
        $isEnabled
            ? $this->addSeoFields()
            : $this->removeSeoFields();
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

        $this->blueprint->ensureFieldInTab('seo', [
            'type' => 'seo_pro',
            'listable' => false,
            'display' => 'SEO',
            'localizable' => true,
        ], 'SEO');

        static::$addingField = false;
    }

    /**
     * Remove SEO section and fields from blueprint.
     */
    public function removeSeoFields()
    {
        $this->blueprint->removeTab('SEO');
    }

    /**
     * Get event data.
     *
     * @param  mixed  $event
     * @return mixed
     */
    protected function getEventData($event)
    {
        $eventClass = get_class($event);

        $dataProperty = static::DATA_PROPERTY[$eventClass];

        return $event->{$dataProperty};
    }
}
