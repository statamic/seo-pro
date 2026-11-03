<?php

namespace Tests\BrokenLinks;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\BrokenLinks\Reference;
use Tests\TestCase;

class ReferenceTest extends TestCase
{
    #[Test]
    #[DataProvider('keyProvider')]
    public function it_can_be_made_from_its_key(string $type, string $id, string $site)
    {
        $reference = Reference::fromKey((new Reference($type, $id, $site))->key());

        $this->assertEquals($type, $reference->type);
        $this->assertEquals($id, $reference->id);
        $this->assertEquals($site, $reference->site);
    }

    public static function keyProvider(): array
    {
        return [
            'entry' => ['entry', '0c5f4a6e-1b2d-4c3e-9f8a-7b6c5d4e3f2a', 'default'],
            'term' => ['term', 'tags::news', 'fr'],
        ];
    }
}
