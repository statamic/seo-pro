<?php

namespace Statamic\SeoPro\Reporting\File;

use Illuminate\Support\Collection;
use Statamic\Facades\File;
use Statamic\Facades\Folder;
use Statamic\Facades\YAML;
use Statamic\SeoPro\Reporting\Chunk;
use Statamic\SeoPro\Reporting\Page;
use Statamic\SeoPro\Reporting\Report;
use Statamic\SeoPro\Reporting\ReportRepository as RepositoryContract;

class ReportRepository implements RepositoryContract
{
    public function all(): Collection
    {
        return Folder::getFolders($this->prepareDirectory())
            ->map(fn ($path) => (int) pathinfo($path)['filename'])
            ->map(fn ($id) => $this->find($id))
            ->filter()
            ->sortByDesc
            ->id()
            ->values();
    }

    public function find($id): ?Report
    {
        if (! File::exists($path = $this->reportPath($id))) {
            return null;
        }

        return Report::create($id)->hydrate(YAML::parse(File::get($path)));
    }

    public function latest(): ?Report
    {
        return $this->all()->first();
    }

    public function nextId(): int
    {
        return (int) Folder::getFolders($this->prepareDirectory())
            ->map(fn ($path) => (int) pathinfo($path)['filename'])
            ->max() + 1;
    }

    public function save(Report $report): void
    {
        File::put($this->reportPath($report->id()), YAML::dump($report->raw()));
    }

    public function delete(Report $report): void
    {
        File::delete($this->reportFolder($report->id()));
    }

    public function pages(Report $report): Collection
    {
        return Folder::getFilesRecursively($this->reportFolder($report->id()).'/pages')
            ->map(fn ($file) => YAML::parse(File::get($file)))
            ->map(fn ($yaml) => (new Page($yaml['id'], $yaml['data'], $report))->setResults($yaml['results']));
    }

    public function savePage(Page $page): void
    {
        File::put($this->pagePath($page), YAML::dump([
            'id' => $page->id(),
            'data' => $page->data(),
            'results' => $page->results(),
        ]));
    }

    public function saveChunk(Chunk $chunk): void
    {
        File::put($this->chunkFolder($chunk).'/chunk.yaml', YAML::dump([
            'ids' => $chunk->contentIds(),
        ]));
    }

    public function deleteChunk(Chunk $chunk): void
    {
        File::delete($this->chunkFolder($chunk));
    }

    public function hasChunks(Report $report): bool
    {
        return File::exists($dir = $this->chunksFolder($report->id()))
            && Folder::getFolders($dir)->isNotEmpty();
    }

    public function deleteChunks(Report $report): void
    {
        File::delete($this->chunksFolder($report->id()));
    }

    public static function bindings(): array
    {
        return [];
    }

    private function directory(): string
    {
        return config('statamic.seo-pro.reports.directory', storage_path('statamic/seopro/reports'));
    }

    private function prepareDirectory(): string
    {
        if (! File::exists($directory = $this->directory())) {
            File::makeDirectory($directory, 0755, true);
        }

        return $directory;
    }

    private function reportFolder($id): string
    {
        return $this->directory().'/'.$id;
    }

    private function reportPath($id): string
    {
        return $this->reportFolder($id).'/report.yaml';
    }

    private function chunksFolder($id): string
    {
        return $this->reportFolder($id).'/chunks';
    }

    private function chunkFolder(Chunk $chunk): string
    {
        return $this->chunksFolder($chunk->report()->id()).'/'.$chunk->id();
    }

    private function pagePath(Page $page): string
    {
        $key = md5($page->id());
        $parts = array_slice(str_split($key, 2), 0, 2);

        return $this->reportFolder($page->report()->id()).vsprintf('/pages/%s/%s.yaml', [
            implode('/', $parts), $key,
        ]);
    }
}
