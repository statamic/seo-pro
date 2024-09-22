<?php

namespace Statamic\SeoPro\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InsertLinkRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'entry' => 'required',
            'phrase' => 'required',
            'target' => 'required',
            'field' => 'required',
            'auto_link' => 'boolean',
        ];
    }
}
