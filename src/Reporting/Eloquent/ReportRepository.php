<?php

namespace Statamic\SeoPro\Reporting\Eloquent;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Statamic\SeoPro\Reporting\Chunk;
use Statamic\SeoPro\Reporting\Page;
use Statamic\SeoPro\Reporting\Report;
use Statamic\SeoPro\Reporting\ReportRepository as RepositoryContract;

class ReportRepository implements RepositoryContract
{
    public function all(): Collection
    {
        return ReportModel::query()
            ->orderByDesc('id')
            ->get()
            ->map(fn ($model) => static::fromModel($model));
    }

    public function find($id): ?Report
    {
        if (! is_numeric($id) || ! $model = ReportModel::find((int) $id)) {
            return null;
        }

        return static::fromModel($model);
    }

    public function nextId(): int
    {
        return (int) ReportModel::max('id') + 1;
    }

    public function save(Report $report): void
    {
        $raw = $report->raw();

        ReportModel::updateOrCreate(['id' => $report->id()], [
            'date' => $raw['date'],
            'status' => $raw['status'],
            'score' => $raw['score'],
            'pages_crawled' => $raw['pages_crawled'],
            'pages_actionable' => $raw['pages_actionable'],
            'results' => $raw['results'],
        ]);
    }

    public function delete(Report $report): void
    {
        DB::transaction(function () use ($report) {
            ReportPageModel::query()->where('report_id', $report->id())->delete();
            ReportChunkModel::query()->where('report_id', $report->id())->delete();
            ReportModel::query()->where('id', $report->id())->delete();
        });
    }

    public function pages(Report $report): Collection
    {
        return ReportPageModel::query()
            ->where('report_id', $report->id())
            ->get()
            ->map(fn ($model) => (new Page($model->page_id, $model->data, $report))->setResults($model->results));
    }

    public function savePage(Page $page): void
    {
        // One query per page: a report saves every page twice (the chunk, then finalize()).
        ReportPageModel::upsert([[
            'report_id' => $page->report()->id(),
            'page_id' => $page->id(),
            'data' => json_encode($page->data()),
            'results' => json_encode($page->results()),
        ]], ['report_id', 'page_id'], ['data', 'results']);
    }

    public function saveChunk(Chunk $chunk): void
    {
        ReportChunkModel::updateOrCreate([
            'report_id' => $chunk->report()->id(),
            'chunk_id' => $chunk->id(),
        ], [
            'content_ids' => $chunk->contentIds(),
        ]);
    }

    public function deleteChunk(Chunk $chunk): void
    {
        ReportChunkModel::query()
            ->where('report_id', $chunk->report()->id())
            ->where('chunk_id', $chunk->id())
            ->delete();
    }

    public function hasChunks(Report $report): bool
    {
        return ReportChunkModel::query()->where('report_id', $report->id())->exists();
    }

    public function deleteChunks(Report $report): void
    {
        ReportChunkModel::query()->where('report_id', $report->id())->delete();
    }

    public static function bindings(): array
    {
        return [];
    }

    public static function fromModel(ReportModel $model): Report
    {
        return Report::create($model->id)->hydrate([
            'date' => $model->date->timestamp,
            'status' => $model->status,
            'score' => $model->score,
            'pages_crawled' => $model->pages_crawled,
            'pages_actionable' => $model->pages_actionable,
            'results' => $model->results,
        ]);
    }
}
