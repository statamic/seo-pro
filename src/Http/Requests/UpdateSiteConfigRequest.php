<?php

namespace Statamic\SeoPro\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteConfigRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'ignored_phrases' => 'array',
            'keyword_threshold' => 'required|int',
            'min_internal_links' => 'required|int',
            'max_internal_links' => 'required|int',
            'min_external_links' => 'required|int',
            'max_external_links' => 'required|int',
        ];
    }
}