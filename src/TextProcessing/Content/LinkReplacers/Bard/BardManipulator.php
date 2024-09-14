<?php

namespace Statamic\SeoPro\TextProcessing\Content\LinkReplacers\Bard;

class BardManipulator
{
    protected function indexParagraph(array $content): array
    {
        $index = [];

        for ($i = 0; $i < count($content); $i++) {
            $node = $content[$i];
            $text = $node['text'] ?? '';
            $words = explode(' ', $text);

            foreach ($words as $word) {
                if (mb_strlen($word) == 0) {
                    continue;
                }

                $index[] = [
                    'text' => $word,
                    'origin' => $i,
                    'node' => $node,
                ];
            }
        }

        return $index;
    }

    protected function findPositionInParagraph(array $content, string $searchString): ?array
    {
        if (! array_key_exists('type', $content) || $content['type'] != 'paragraph') {
            return null;
        }

        if (! array_key_exists('content', $content)) {
            return null;
        }

        $pos = null;

        $index = $this->indexParagraph($content['content']);
        $indexCount = count($index);

        $searchWords = explode(' ', $searchString);
        $searchSpaceCount = count($searchWords);

        for ($i = 0; $i < count($index); $i++) {
            if ($i + $searchSpaceCount > $indexCount) {
                break;
            }

            $section = array_slice($index, $i, $searchSpaceCount);
            $sectionWords = collect($section)->pluck('text')->implode(' ');

            if ($sectionWords != $searchString) {
                continue;
            }

            // Prevent stomping on existing links.
            foreach ($section as $item) {
                if ($this->hasLinkMark($item['node'])) {
                    return null;
                }
            }

            $pos = $i;
            break;
        }

        return [$pos, $searchSpaceCount];
    }

    public function findPositionIn(array $content, string $searchString): ?array
    {
        if (array_key_exists('type', $content)) {
            return $this->findPositionInParagraph($content, $searchString);
        }

        for ($i = 0; $i < count($content); $i++) {
            $paragraph = $content[$i];

            if ($pos = $this->findPositionInParagraph($paragraph, $searchString)) {
                if (is_null($pos[0])) {
                    continue;
                }

                return [$i, $pos[0], $pos[1]];
            }
        }

        return null;
    }

    public function insertLinkAt(array $content, ?array $pos, BardLink $link): array
    {
        if (! $pos) {
            return $content;
        }

        $locOffset = 0;

        if (count($pos) === 3) {
            $repContent = $content[$pos[0]]['content'];
        } else {
            $repContent = $content['content'];
            $locOffset = 1;
        }

        $index = $this->indexParagraph($repContent);
        $replacementStarts = $pos[1 - $locOffset];
        $replacementLength = $pos[2 - $locOffset];

        $before = array_slice($index, 0, $replacementStarts);
        $middle = array_slice($index, $replacementStarts, $replacementLength);
        $after = array_slice($index, $replacementStarts + $replacementLength);

        $textMerger = function ($group)
        {
            $first = $group->first();
            $firstNode = $first['node'];

            if ($group->count() == 1) {

                $firstNode['text'] = $first['text'] ?? '';

                return $firstNode;
            }

            $groupWords = $group->pluck('text')->implode(' ');
            $groupWords .= $this->findTrailingWhitespace($firstNode['text'], $groupWords);

            $firstNode['text'] = $groupWords;

            return $firstNode;
        };

        $result = collect($before)->groupBy('origin')->map($textMerger)
            ->concat(
                collect($this->mergeNodes($middle))->map(function ($node) use ($link) {
                    $newNode = $node['node'];

                    $newNode['text'] = $node['text']. $this->findTrailingWhitespace($newNode['text'], $node['text']);

                    return $this->setLinkMark($newNode, $link);
                })->all()
            )
            ->concat(
                collect($after)->groupBy('origin')->map($textMerger)
            )->all();

        if (count($pos) === 3) {
            $content[$pos[0]]['content'] = $result;
        } else {
            $content['content'] = $result;
        }

        return $content;
    }

    protected function findTrailingWhitespace(string $haystack, string $needle): string
    {
        $lastPos = strrpos($haystack, $needle);

        if ($lastPos !== false) {
            $afterSearch = substr($haystack, $lastPos + strlen($needle));

            if (preg_match('/^\s+/', $afterSearch, $matches)) {
                return $matches[0];
            }
        }

        return '';
    }

    public function canInsertLink(array $content, string $search): bool
    {
        return $this->findPositionIn($content, $search) != null;
    }

    public function replaceFirstWithLink(array $content, string $search, BardLink $link): array
    {
        return $this->insertLinkAt(
            $content,
            $this->findPositionIn($content, $search),
            $link
        );
    }

    protected function mergeNodes(array $nodes): array
    {
        if (count($nodes) <= 1) {
            return $nodes;
        }

        $newNodes = [];

        $lastNode = $nodes[0];
        $lastMark = [];

        if (array_key_exists('marks', $lastNode['node'])) {
            $lastMark = $lastNode['node']['marks'];
        }

        for ($i = 1; $i < count($nodes); $i++) {
            $node = $nodes[$i];
            $currentMark = [];

            if (array_key_exists('marks', $node['node'])) {
                $currentMark = $node['node']['marks'];
            }

            if ($currentMark != $lastMark) {
                $newNodes[] = $lastNode;

                $lastNode = $node;
                $lastMark = $currentMark;
                continue;
            }

            $lastNode['text'] .= ' '.$node['text'];
        }

        if (count($newNodes) > 0) {
            if ($newNodes[count($newNodes) - 1] != $lastNode) {
                $newNodes[] = $lastNode;
            }
        } else {
            $newNodes[] = $lastNode;
        }

        return $newNodes;
    }

    protected function setLinkMark(array $node, BardLink $link): array
    {
        $marks = collect($node['marks'] ?? [])->where(function ($mark) {
            return $mark['type'] != 'link';
        })->all();

        $marks[] = [
            'type' => 'link',
            'attrs' => [
                'href' => $link->href,
                'rel' => $link->rel,
                'target' => $link->target,
                'title' => $link->title,
            ],
        ];

        $node['marks'] = $marks;

        return $node;
    }

    protected function hasLinkMark(array $node): bool
    {
        return collect($node['marks'] ?? [])->where(fn($node) => $node['type'] === 'link')->count() > 0;
    }
}