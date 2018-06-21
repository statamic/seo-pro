<?php

namespace Statamic\Addons\SeoPro\Reporting\Rules;

use Statamic\Addons\SeoPro\Reporting\Page;

class Rule
{
    public function id()
    {
        return class_basename(static::class);
    }

    public function comment()
    {
        return $this->passes() ? $this->passingComment() : $this->failingComment();
    }

    protected function passingComment()
    {
        return;
    }

    protected function failingComment()
    {
        return;
    }
}
