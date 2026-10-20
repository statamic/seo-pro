<?php

namespace Statamic\SeoPro\Robots;

class RobotsPolicy
{
    public function __construct(private array $data) {}

    public function all(): array
    {
        return Robots::withDefaults($this->data);
    }

    public function set(array $data): self
    {
        $this->data = $data;

        return $this;
    }
}
