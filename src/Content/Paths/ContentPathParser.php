<?php

namespace Statamic\SeoPro\Content\Paths;

class ContentPathParser
{
    public function parse(string $path)
    {
        $chars = mb_str_split($path);
        $parts = [];
        $buffer = [];
        $metaBuffer = [];
        $inMeta = false;
        $count = count($chars);
        $metaData = [];

        for ($i = 0; $i < $count; $i++) {
            $cur = $chars[$i];
            $next = null;

            if ($i + 1 < $count) {
                $next = $chars[$i + 1];
            }

            if ($inMeta) {
                if ($cur === '\\') {
                    $metaBuffer[] = $next;
                    $i += 1;

                    continue;
                }

                $metaBuffer[] = $cur;

                if ($cur === '}') {
                    $metaData[] = implode('', $metaBuffer);
                    $metaBuffer = [];
                    $inMeta = false;

                    if ($next === null || $next === '[') {
                        $part = [
                            'name' => implode($buffer),
                            'meta' => $metaData,
                            'type' => 'named',
                        ];

                        $metaData = [];

                        $parts[] = $part;
                        $buffer = [];
                    }
                }

                continue;
            }

            if ($cur === '{') {
                $inMeta = true;
                $metaBuffer[] = $cur;

                continue;
            } elseif ($next === null || $next === '[') {
                $buffer[] = $cur;

                $part = [
                    'name' => implode($buffer),
                    'meta' => $metaData,
                    'type' => 'named',
                ];

                $metaData = [];

                $parts[] = $part;
                $buffer = [];

                continue;
            } elseif ($cur === ']') {
                array_shift($buffer);

                $parts[] = [
                    'name' => implode($buffer),
                    'meta' => $metaData,
                    'type' => 'index',
                ];

                $metaData = [];
                $buffer = [];

                continue;
            }

            $buffer[] = $cur;
        }

        if (count($metaBuffer) > 0) {
            $metaData[] = implode('', $metaBuffer);
        }

        if (count($buffer) > 0) {
            $part = implode($buffer);

            $part = [
                'name' => $part,
                'meta' => $metaData,
                'type' => 'named',
            ];

            $parts[] = $part;
        }

        $chars = null;
        $root = array_shift($parts);
        $contentParts = [];

        foreach ($parts as $part) {
            $contentParts[] = new ContentPathPart(
                $part['name'],
                $part['type'],
                $part['meta'] ?? [],
            );
        }

        return new ContentPath(
            $contentParts,
            new ContentPathPart(
                $root['name'],
                $root['type'],
                $root['meta']
            ),
        );
    }
}
