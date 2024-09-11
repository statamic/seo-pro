<?php

namespace Statamic\SeoPro\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IgnoreSuggestionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'action' => [
                'required',
                Rule::in(['ignore_entry', 'ignore_phrase']),
            ],
            'scope' => [
                'required',
                Rule::in(['entry', 'all_entries']),
            ],
            'phrase' => 'required_if:action,ignore_phrase',
            'entry' => 'required_if:scope,entry',
            'ignored_entry' => 'required_if:action,ignore_entry',
            'site' => 'required',
        ];
    }
}