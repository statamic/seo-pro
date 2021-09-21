<?php

namespace Tests\Localized;

use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected $siteFixturePath = __DIR__.'/../Fixtures/site-localized';
}
