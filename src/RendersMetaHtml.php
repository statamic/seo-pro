<?php

namespace Statamic\SeoPro;

trait RendersMetaHtml
{
    /**
     * Render normalized meta view HTML.
     *
     * @param  array  $data
     * @return string
     */
    protected function renderMetaHtml($data)
    {
        // Render view.
        $html = view('seo-pro::meta', $data)->render();

        // Remove new lines.
        $html = str_replace(["\n", "\r"], '', $html);

        // Remove whitespace between elements.
        $html = preg_replace('/(>)\s*(<)/', '$1$2', $html);

        // Add cleaner line breaks.
        $html = preg_replace('/(<[^\/])/', "\n$1", $html);

        return trim($html);
    }
}
