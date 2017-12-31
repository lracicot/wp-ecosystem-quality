<?php

namespace Reporter\PHPVersion;

use Reporter\{PhpFileContentMapper, Reporter};

class PHPVersion extends Reporter
{
    public function report($analyze_dir)
    {
        $plugin = basename($analyze_dir);

        $content = file_get_contents(
            dirname($analyze_dir) . '_results/php_version/' . $plugin
        );

        return substr($content, 0, strpos($content, ' ') !== false ? strpos($content, ' ') : 5);
    }
}
