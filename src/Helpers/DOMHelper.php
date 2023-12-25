<?php

namespace App\Helpers;

class DOMHelper
{
    function getFirstElementValueByQuery($element, $query)
    {
        $doc = new \DomDocument();
        @$doc->loadHTMLFile($element);
        $xpath = new \DomXpath($doc);
        $xq = $xpath->query($query);

        if ($xq->length <= 0) {
            return null;
        }

        return $xq[0]->value;
    }
}
