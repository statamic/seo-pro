<?php

namespace Statamic\SeoPro\TextProcessing\Content;

class RetrievedConfig
{
    public function __construct(
        public array $config,
        public array $fieldNames,
    ) {}

    public function toArray()
    {
        return [
            'config' => $this->config,
            'field_names' => $this->fieldNames,
        ];
    }
}