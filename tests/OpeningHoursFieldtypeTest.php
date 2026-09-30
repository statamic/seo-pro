<?php

namespace Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Fields\Field;
use Statamic\SeoPro\Fieldtypes\OpeningHoursFieldtype;

class OpeningHoursFieldtypeTest extends TestCase
{
    private function fieldtype(): OpeningHoursFieldtype
    {
        return (new OpeningHoursFieldtype)->setField(new Field('opening_hours', ['type' => 'seo_pro_opening_hours']));
    }

    #[Test]
    public function it_pre_processes_every_day_of_the_week()
    {
        $this->assertEquals([
            'monday' => ['opening' => '09:00', 'closing' => '17:00'],
            'tuesday' => ['opening' => null, 'closing' => null],
            'wednesday' => ['opening' => null, 'closing' => null],
            'thursday' => ['opening' => null, 'closing' => null],
            'friday' => ['opening' => null, 'closing' => null],
            'saturday' => ['opening' => null, 'closing' => null],
            'sunday' => ['opening' => null, 'closing' => null],
        ], $this->fieldtype()->preProcess([
            'monday' => ['opening' => '09:00', 'closing' => '17:00'],
        ]));
    }

    #[Test]
    #[DataProvider('malformedDataProvider')]
    public function it_pre_processes_malformed_data_as_empty_days(mixed $data)
    {
        $processed = $this->fieldtype()->preProcess($data);

        $this->assertEquals(OpeningHoursFieldtype::DAYS, array_keys($processed));
        $this->assertEquals(['opening' => null, 'closing' => null], $processed['monday']);
    }

    #[Test]
    public function it_processes_open_days()
    {
        $this->assertEquals([
            'monday' => ['opening' => '09:00', 'closing' => '17:00'],
            'saturday' => ['opening' => '10:00', 'closing' => '14:00'],
        ], $this->fieldtype()->process([
            'monday' => ['opening' => '09:00', 'closing' => '17:00'],
            'tuesday' => ['opening' => null, 'closing' => null],
            'saturday' => ['opening' => '10:00', 'closing' => '14:00'],
        ]));
    }

    #[Test]
    public function it_drops_unknown_days_when_processing()
    {
        $this->assertEquals([
            'monday' => ['opening' => '09:00', 'closing' => '17:00'],
        ], $this->fieldtype()->process([
            'monday' => ['opening' => '09:00', 'closing' => '17:00'],
            'caturday' => ['opening' => '09:00', 'closing' => '17:00'],
        ]));
    }

    #[Test]
    public function it_processes_no_open_days_as_null()
    {
        $this->assertNull($this->fieldtype()->process([
            'monday' => ['opening' => null, 'closing' => null],
        ]));
    }

    #[Test]
    #[DataProvider('malformedDataProvider')]
    public function it_processes_malformed_data_as_null(mixed $data)
    {
        $this->assertNull($this->fieldtype()->process($data));
    }

    public static function malformedDataProvider(): array
    {
        return [
            'null' => [null],
            'string' => ['monday'],
            'list' => [['09:00', '17:00']],
            'day as string' => [['monday' => '09:00-17:00']],
        ];
    }
}
