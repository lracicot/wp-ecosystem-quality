<?php

namespace Utils;

use SimpleXMLElement;

class XMLParser
{
    public function load($filePath)
    {
        return new SimpleXMLElement(file_get_contents($filePath));
    }
}
