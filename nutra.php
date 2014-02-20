<?php
/**
 * Scrapes NutraPlanet and gets review information for the top 100 products.
 *
 * PHP version 5.3
 *
 * @package   NutraPlanetScraper
 * @author    Joel Johnson <me@joelster.com>
 * @copyright 2013-2014 Joel Johnson
 * @license   http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @version   0.1
 */

$page = file_get_contents('http://www.nutraplanet.com/top_100');
preg_match_all('%/product/(.*?)\.html%is', $page, $matches);

$productUrlFragments = array_unique($matches[1]);

foreach ($productUrlFragments as $productUrlFragment)
{

    $page = file_get_contents('http://www.nutraplanet.com/manufacturer/'
                                  .$productUrlFragment
                                  .'/reviews');

    preg_match_all('%<td>&nbsp;<i>(.*?)</i></td>%is', $page, $matches);

    echo $productUrlFragment . '\t';

    foreach ($matches[1] as $votes)
    {
        echo $votes . '\t';
    }

    echo '\n';
}
