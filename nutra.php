<?php

$locations = array("http://www.nutraplanet.com/top_100");

$productUrls = array();

foreach($locations as $locationUrl) {    
    $page = @file_get_contents($locationUrl);

    preg_match_all("%/product/(.*?)\.html%is", $page, $matches);

    $productUrlFragments = array_merge($productUrlFragments, $matches[1]);
}

$productUrlFragments = array_unique($productUrlFragments);

foreach($productUrlFragments as $productUrlFragment) {

    $page = @file_get_contents("http://www.nutraplanet.com/manufacturer/".$productUrlFragment."/reviews");

    preg_match_all("%<td>&nbsp;<i>(.*?)</i></td>%is", $page, $matches);

    echo $productUrlFragment . "\t";

    foreach($matches[1] as $votes) {
        echo $votes . "\t";    
    }

    echo "\n";    
}

?>  					
