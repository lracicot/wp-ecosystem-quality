<?php

namespace Analyzer;

class PhpFileContentMapper
{
    private $dirPath;

    public function __construct($dirPath)
    {
        $this->dirPath = $dirPath;
    }

    private function getIterator()
    {
        $directory = new \RecursiveDirectoryIterator($this->dirPath);
        $filter = new \RecursiveCallbackFilterIterator($directory, function ($current) {
            if ($current->getFilename()[0] === '.'
                || $current->getFilename() == 'vendor'
            ) {
                return false;
            }

            return substr($current->getFilename(), -4) === '.php';
        });

        return new \RecursiveIteratorIterator($filter);
    }

    public function map($func)
    {
        $results = [];

        foreach ($this->getIterator() as $info) {
            $filePath = $info->getPathname();
            $relativeFilePath = substr($filePath, strlen($this->dirPath) + 1);

            $results[$relativeFilePath] = $func(
                file_get_contents($filePath)
            );
        }

        return $results;
    }

    public function reduce($func, $reduced = null)
    {
        foreach ($this->getIterator() as $info) {
            $filePath = $info->getPathname();
            $relativeFilePath = substr($filePath, strlen($this->dirPath) + 1);

            $reduced = $func(
                $reduced,
                file_get_contents($filePath)
            );
        }

        return $reduced;
    }
}
