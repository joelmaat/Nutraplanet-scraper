<?php

$page = file_get_contents('http://www.nutraplanet.com/top_100');
preg_match_all('%/product/(.*?)\.html%is', $page, $matches);

$product_url_fragments = array_unique($matches[1]);

foreach ($product_url_fragments as $product_url_fragment) 
{

    $page = file_get_contents('http://www.nutraplanet.com/manufacturer/'
                                   .$product_url_fragment
                                   .'/reviews');

    preg_match_all('%<td>&nbsp;<i>(.*?)</i></td>%is', $page, $matches);

    echo $product_url_fragment . '\t';

    foreach ($matches[1] as $votes) 
    {
        echo $votes . '\t';
    }

    echo '\n';
}
