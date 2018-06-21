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
        switch ($this->status()) {
            case 'pass':
                return $this->passingComment();
            case 'fail':
                return $this->failingComment();
            case 'warning':
                return $this->warningComment();
        }
    }

    protected function passingComment()
    {
        return;
    }

    protected function failingComment()
    {
        return;
    }

    protected function warningComment()
    {
        return;
    }
}
