<?php

namespace Statamic\Addons\SeoPro\Fieldtypes;

use Statamic\Addons\Assets\AssetsFieldtype;

class AssetFieldtype extends AssetsFieldtype
{
    protected function maxFiles()
    {
        return 1;
    }
}
