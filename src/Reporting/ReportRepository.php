<?php

namespace Statamic\SeoPro\Reporting;

use Illuminate\Support\Collection;

interface ReportRepository
{
    public function all(): Collection;

    public function find($id): ?Report;

    public function nextId(): int;

    public function save(Report $report): void;

    public function delete(Report $report): void;

    public function pages(Report $report): Collection;

    public function savePage(Page $page): void;

    public function saveChunk(Chunk $chunk): void;

    public function deleteChunk(Chunk $chunk): void;

    public function hasChunks(Report $report): bool;

    public function deleteChunks(Report $report): void;

    public static function bindings(): array;
}
