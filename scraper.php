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

$minimumNumberOfRatingsToSaveProduct = 2;

$locations = array('http://www.nutraplanet.com/top_100');
$productIds = array_unique(getProductIds($locations));
$products = getProducts($productIds, $minimumNumberOfRatingsToSaveProduct);

calculateBayesianEstimate($products);

usort($products, function ($a, $b) {
    if ($a['bayesian_average_rating'] === $b['bayesian_average_rating'])
    {
        return 0;
    }
    else if ($a['bayesian_average_rating'] < $b['bayesian_average_rating'])
    {
        return 1;
    }
    else
    {
        return -1;
    }
});

printReviews($products);


/**
 * Visits each location and extracts any productIds it can find.
 *
 * @param array $locations
 * @return array
 */
function getProductIds($locations)
{
    $productIds = array();

    foreach($locations as $location)
    {
        $page = file_get_contents($location);
        preg_match_all('%/product/(.*?)\.html%is', $page, $matches);
        $productIds = array_merge($productIds, $matches[1]);
    }

    return $productIds;
}


/**
 * Visits each product page and extracts rating information.
 *
 * @param array $productIds
 * @param int $minimumNumberOfRatingsToSaveProduct
 * @return array
 */
function getProducts($productIds, $minimumNumberOfRatingsToSaveProduct)
{
    $products = array();

    foreach($productIds as $productId)
    {
        $productUrlPrefix = 'http://www.nutraplanet.com/manufacturer/' . $productId;
        $reviewUrl = $productUrlPrefix . '/reviews';

        $page = file_get_contents($reviewUrl);
        preg_match_all('%<td>&nbsp;<i>(.*?)</i></td>%is', $page, $matches);

        $product = createProduct($productUrlPrefix . '.html', $matches[1]);

        if ($product['num_ratings'] >= $minimumNumberOfRatingsToSaveProduct)
        {
            $products[] = $product;
        }
    }

    return $products;
}


/**
 * Creates a product with the given name and rating distribution.
 *
 * @param string $productName
 * @param array $ratingDistribution
 * @return array
 */
function createProduct($productName, $ratingDistribution)
{
    $sumRatings = 0;
    $numRatings = 0;
    $ratingStar = 1;

    // In ascending order, 1-star to 5
    foreach($ratingDistribution as $ratingCount)
    {
        $numRatings += $ratingCount;
        $sumRatings += ($ratingCount * $ratingStar++);
    }

    $product = array();
    $product['name'] = $productName;
    $product['rating_distribution'] = $ratingDistribution;
    $product['average_rating'] = ($numRatings < 1) ? 0 : ($sumRatings / $numRatings);
    $product['num_ratings'] = $numRatings;

    return $product;
}


/**
 * Adds bayesian estimate (of product rating) to each product.
 *
 * @param array $products
 */
function calculateBayesianEstimate(&$products)
{
    // See bottom of page: http://www.imdb.com/chart/top
    $m = getLowestNumberOfRatings($products);
    $C = getAverageRatingAcrossProducts($products);

    for ($i = 0, $length = count($products); $i < $length; $i++)
    {
        $R = $products[$i]['average_rating'];
        $v = $products[$i]['num_ratings'];
        $products[$i]['bayesian_average_rating'] = (($v / ($v + $m)) * $R) + (($m / ($v + $m)) * $C);
    }
}


/**
 * Returns the average product rating across all products.
 *
 * @param array $products
 * @return float
 */
function getAverageRatingAcrossProducts($products)
{
    $total = 0;

    foreach($products as $product)
    {
        $total += $product['average_rating'];
    }

    return $total / count($products);
}


/**
 * Returns the number of ratings for the product with the least number of ratings.
 *
 * @param array $products
 * @return int
 */
function getLowestNumberOfRatings($products)
{
    $lowest = 0;

    foreach($products as $product)
    {
        if ($product['num_ratings'] < $lowest)
        {
            $lowest = $product['num_ratings'];
        }
    }

    return $lowest;
}

/**
 * Prints review/rating information for each product.
 *
 * @param array $products
 */
function printReviews($products)
{
    echo '<table>';
    foreach($products as $product)
    {
        $product['rating_distribution'] = implode('</td><td>', $product['rating_distribution']);
        $product = implode('</td><td>', $product);
        echo '<tr><td>' . $product . '</td></tr>';
    }
    echo '<table>';
}
