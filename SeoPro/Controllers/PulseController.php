<?php

namespace Statamic\Addons\SeoPro\Controllers;

use Statamic\Addons\SeoPro\Pulse;

class PulseController extends Controller
{
    protected $pulse;

    public function __construct(Pulse $pulse)
    {
        $this->pulse = $pulse;
    }

    public function index()
    {
        return $this->view('pulse', [
            'title' => 'Pulse'
        ]);
    }

    public function summary()
    {
        return $this->pulse->summary();
    }
}
