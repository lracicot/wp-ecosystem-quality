<?php

namespace Reporter\NamespacePollution;

use Reporter\{PhpFileContentMapper, Reporter};

class NamespacePollution extends Reporter
{
    public function report($analyze_dir)
    {
        $plugin = basename($analyze_dir);

        return (int)file_get_contents(
            dirname($analyze_dir) . '_results/namespace_pollution/' . $plugin
        );
    }
}
