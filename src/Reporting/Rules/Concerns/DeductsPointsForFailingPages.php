<?php

namespace Statamic\SeoPro\Reporting\Rules\Concerns;

trait DeductsPointsForFailingPages
{
    abstract protected function points();

    public function maxPoints()
    {
        return $this->points() * $this->report->pages()->count();
    }

    public function demerits()
    {
        return $this->points() * $this->failures;
    }
}
