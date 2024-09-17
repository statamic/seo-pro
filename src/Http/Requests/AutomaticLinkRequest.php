<?php

namespace Statamic\SeoPro\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AutomaticLinkRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'site' => 'required',
            'is_active' => 'boolean',
            'link_text' => 'required',
            'link_target' => 'required',
        ];
    }
}
