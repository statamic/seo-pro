<?php

namespace Statamic\SeoPro\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCollectionBehaviorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'allow_cross_site_linking' => 'required|boolean',
            'allow_cross_collection_suggestions' => 'required|boolean',
            'allowed_collections' => 'array'
        ];
    }
}