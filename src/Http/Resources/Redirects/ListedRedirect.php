<?php

namespace Statamic\SeoPro\Http\Resources\Redirects;

use Illuminate\Http\Resources\Json\JsonResource;
use Statamic\Facades\User;

class ListedRedirect extends JsonResource
{
    protected $blueprint;

    protected $columns;

    public function blueprint($blueprint)
    {
        $this->blueprint = $blueprint;

        return $this;
    }

    public function columns($columns)
    {
        $this->columns = $columns;

        return $this;
    }

    public function toArray($request)
    {
        $redirect = $this->resource;

        return [
            'id' => $redirect->id(),
            'source' => $redirect->source(),
            'destination' => $redirect->destination(),
            'response_code' => [$redirect->responseCode()],
            'hits' => $redirect->hits(),
            'last_hit_at' => $redirect->lastHitAt(),
            'status' => $redirect->status(),
            'edit_url' => $redirect->editUrl(),
            'delete_url' => $redirect->deleteUrl(),
            'editable' => User::current()->can('edit', $redirect),
            'deletable' => User::current()->can('delete', $redirect),
        ];
    }
}
