<?php

namespace Reporter\WordpressCoupling;

use Reporter\{PhpFileContentMapper, Reporter};

class WordpressCoupling extends Reporter
{
    public function report($analyze_dir)
    {
        $plugin = basename($analyze_dir);

        $couplings = json_decode(file_get_contents(
            dirname($analyze_dir) . '_results/wp_coupling/' . $plugin . '.json'
        ));

        $results = reduce($couplings, function ($sum, $coupling) {
            return $sum + $coupling;
        }, 0);

        return $results;
    }
}
