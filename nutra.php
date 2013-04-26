<?php

include("./global.php");

$locations = array("http://www.nutraplanet.com/top_100");

/*echo "### read script first ### remove exit;";
exit; */

$count = 0;
$cologneUrls = array();

foreach($locations as $locationUrl) {    
    $page = @file_get_contents($locationUrl);

    preg_match_all("%/product/(.*?)\.html%is", $page, $matches);

    $cologneUrls = array_merge($cologneUrls, $matches[1]);
}

$cologneUrls = array_unique($cologneUrls);

foreach($cologneUrls as $cologneUrl) {

    $page = @file_get_contents("http://www.nutraplanet.com/manufacturer/".$cologneUrl."/reviews");

    preg_match_all("%<td>&nbsp;<i>(.*?)</i></td>%is", $page, $matches);

    echo $cologneUrl . "\t";

    foreach($matches[1] as $votes) {
        echo $votes . "\t";    
    }

    echo "\n";    
}

?>  					
