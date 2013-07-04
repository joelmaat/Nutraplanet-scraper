<?php

$locations = array("http://www.nutraplanet.com/top_100");

/*echo "### read script first ### remove exit;";
exit; */

$count = 0;
$productUrls = array();

foreach($locations as $locationUrl) {    
    $page = @file_get_contents($locationUrl);

    preg_match_all("%/product/(.*?)\.html%is", $page, $matches);

    $productUrls = array_merge($productUrls, $matches[1]);
}

$productUrls = array_unique($productUrls);

foreach($productUrls as $productUrl) {

    $page = @file_get_contents("http://www.nutraplanet.com/manufacturer/".$productUrl."/reviews");

    preg_match_all("%<td>&nbsp;<i>(.*?)</i></td>%is", $page, $matches);

    echo $productUrl . "\t";

    foreach($matches[1] as $votes) {
        echo $votes . "\t";    
    }

    echo "\n";    
}

?>  					
