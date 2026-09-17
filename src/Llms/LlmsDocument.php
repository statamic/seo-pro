<?php

namespace Statamic\SeoPro\Llms;

class LlmsDocument
{
    public function __construct(private array $data) {}

    public function all(): array
    {
        return Llms::withDefaults($this->data);
    }

    public function enabled(): bool
    {
        return $this->all()['enabled'];
    }
}
