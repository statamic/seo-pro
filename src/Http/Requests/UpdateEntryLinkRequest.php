<?php

namespace Statamic\SeoPro\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEntryLinkRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'can_be_suggested' => 'required|boolean',
            'include_in_reporting' => 'required|boolean',
        ];
    }
}
