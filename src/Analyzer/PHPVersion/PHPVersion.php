<?php
/*
function f($req, $opt = null, ...$params)
function  f ( $req ,$opt = null, ...$params )
*/



namespace Analyzer\PHPVersion;

use Analyzer\{PhpFileContentMapper, Analyzer};


class PHPVersion extends Analyzer
{
    public function analyze($analyze_dir, $results_dir = false)
    {
        $plugin = basename($analyze_dir);
        $this->logger->info('Analysing php version for ' . $plugin . "...\n");

        $phpNamePattern = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';
        $variablePattern = '\$' . $phpNamePattern;
        $funcParamPattern = $variablePattern . ' *(= *' . $phpNamePattern . ')? *';
        $funcParamsPattern = '( *(' . $funcParamPattern . '), *)* *(' . $funcParamPattern . ')?';
        $variadicPattern = '\.\.\.' . $variablePattern;

        $versionIdentifiyers = [
            '5.3' => [
                '^namespace', // Namespace support
                '(__callStatic\ ?\()', // new magic functions
                '(__invoke\ ?\()', // new magic functions
                ' function[ *\n]*\(', // Annonymous functions
                $variablePattern . '::' . $variablePattern, // Dynamic access to static content
                "<<<'", // Nowdoc syntax
                '<<<"', // Heredoc doublequotes syntax
            ],
            '5.4' => [
                '^trait ' . $phpNamePattern . ' *(\n|{)', // Traits
                $variablePattern . ' *= * \[', // Array declaration
                '\( *new ' . $phpNamePattern . ' * (\(' . $funcParamsPattern . ' *\))? *\)(\n| )* *(->)', // Class member access on instantiation
            ],
            '5.5' => [
                ' yield ?', // Generators
                '\}(\n| )* *finally(\n| )* *\{', // Finally
                '(\'\") *\[', // String literal deferencing
                ' as +list *\(', // foreach supports list
            ],
            '5.6' => [
                $variadicPattern,
            ],
            '7.1' => [
                '\) *: *void', // Function return void
                '\) *: *\?' . $phpNamePattern, // Function return nullable types
                '(public|protected|private) * const', // Symmetric array destructuring
            ],
            '7.0' => [
                '<=>', // Spaceship operator
                'new class ', // Annonymouse class
                ' yield from ', // Generator delegation
            ],
        ];

        $mapper = new PhpFileContentMapper($analyze_dir);

        return max($mapper->map(function ($str) use ($versionIdentifiyers) {
          foreach ($versionIdentifiyers as $version => $identifiers) {
              foreach ($identifiers as $identifier) {
                  if (preg_match('/' . $identifier . '/', $str)
                    && strpos($str, '<script ') === false
                    && strpos($str, 'jQuery') === false
                  ) {
                    return $version . ' : ' . $identifier;
                  }
              }
          }

          // Assuming php 5.2.4 when no version detected
          return '5.2.4';
        }));
    }
}
