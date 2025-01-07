<?php

namespace Statamic\SeoPro\Http\Controllers\Linking;

use Illuminate\Http\Request;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\Contracts\Linking\Links\LinksRepository;
use Statamic\SeoPro\Http\Requests\IgnoreSuggestionRequest;
use Statamic\SeoPro\Linking\Links\IgnoredSuggestion;

class IgnoredSuggestionsController extends CpController
{
    public function __construct(
        Request $request,
        protected readonly LinksRepository $linksRepository,
    ) {
        parent::__construct($request);
    }

    public function create(IgnoreSuggestionRequest $request)
    {
        /** @var array{action:string,scope:string,phrase:string,entry:string,ignored_entry:string,site:string} $data */
        $data = $request->only(['action', 'scope', 'phrase', 'entry', 'ignored_entry', 'site']);

        $this->linksRepository->ignoreSuggestion(new IgnoredSuggestion(
            $data['action'],
            $data['scope'],
            $data['phrase'] ?? '',
            $data['entry'],
            $data['ignored_entry'],
            $data['site']
        ));
    }
}
