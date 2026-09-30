<?php

namespace Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\User;
use Statamic\SeoPro\SiteDefaults\SiteDefaults;

class UpdateSiteDefaultsTest extends TestCase
{
    #[Test]
    public function it_updates_json_ld_entity_fields()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patchJson('/cp/seo-pro/site-defaults', [
                'json_ld_entity' => 'local_business',
                'json_ld_entity_name' => 'Cool Runnings Bobsled',
                'json_ld_entity_url' => 'https://cool-runnings.com',
                'json_ld_entity_email' => 'hello@cool-runnings.com',
                'json_ld_entity_same_as' => ['https://twitter.com/coolrunnings'],
                'json_ld_entity_latitude' => '51.0447',
                'json_ld_entity_longitude' => '-114.0719',
                'json_ld_entity_opening_hours' => [
                    'monday' => ['opening' => '09:00', 'closing' => '17:00'],
                    'tuesday' => ['opening' => null, 'closing' => null],
                ],
            ])
            ->assertOk();

        $values = SiteDefaults::in('default')->all();

        $this->assertEquals('Cool Runnings Bobsled', $values['json_ld_entity_name']);
        $this->assertEquals([
            'monday' => ['opening' => '09:00', 'closing' => '17:00'],
        ], $values['json_ld_entity_opening_hours']);
    }

    #[Test]
    #[DataProvider('invalidJsonLdEntityFieldsProvider')]
    public function it_validates_json_ld_entity_fields($field, $value)
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patchJson('/cp/seo-pro/site-defaults', [
                'json_ld_entity' => 'local_business',
                'json_ld_entity_name' => 'Cool Runnings Bobsled',
                $field => $value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    public static function invalidJsonLdEntityFieldsProvider()
    {
        return [
            'url' => ['json_ld_entity_url', 'not a url'],
            'email' => ['json_ld_entity_email', 'not an email'],
            'same as' => ['json_ld_entity_same_as', ['https://twitter.com/coolrunnings', 'not a url']],
            'non-numeric latitude' => ['json_ld_entity_latitude', 'north'],
            'out of range latitude' => ['json_ld_entity_latitude', '91'],
            'non-numeric longitude' => ['json_ld_entity_longitude', 'west'],
            'out of range longitude' => ['json_ld_entity_longitude', '-181'],
            'opening time without closing time' => ['json_ld_entity_opening_hours', ['monday' => ['opening' => '09:00', 'closing' => null]]],
            'closing time without opening time' => ['json_ld_entity_opening_hours', ['monday' => ['opening' => null, 'closing' => '17:00']]],
        ];
    }
}
