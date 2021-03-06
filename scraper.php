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

$numRatingsRequired = 2;

$locations = array('http://www.nutraplanet.com/top_100');
$productIds = array_unique(getProductIds($locations));
$products = getProducts($productIds, $numRatingsRequired);

calculateBayesianEstimate($products);

usort($products, function (array $a, array $b) {
    if ($a['bayesian_average_rating'] === $b['bayesian_average_rating'])
    {
        return 0;
    }
    elseif ($a['bayesian_average_rating'] < $b['bayesian_average_rating'])
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
 * @param array<string> $locations List of urls to visit.
 *
 * @return array<string> List of product ids.
 */
function getProductIds(array $locations)
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
 * Visits each product page and extracts product information.
 *
 * @param array<string> $productIds         List of product ids to fetch.
 * @param integer       $numRatingsRequired Minimum number of ratings for product to be considered.
 *
 * @return array<integer,mixed[]> List of products.
 */
function getProducts(array $productIds, $numRatingsRequired)
{
    $products = array();

    foreach($productIds as $productId)
    {
        $productUrlPrefix = 'http://www.nutraplanet.com/manufacturer/' . $productId;
        $reviewUrl = $productUrlPrefix . '/reviews';

        $page = file_get_contents($reviewUrl);
        preg_match_all('%<td>&nbsp;<i>(.*?)</i></td>%is', $page, $matches);

        $product = createProduct($productUrlPrefix . '.html', $matches[1]);

        if ($product['num_ratings'] >= $numRatingsRequired)
        {
            $products[] = $product;
        }
    }

    return $products;
}


/**
 * Creates a product with the given name and rating distribution.
 *
 * @param string        $productName        Name of product to create.
 * @param array<string> $ratingDistribution Number of 1-5 star ratings (13 1 star, 7 2 star, etc).
 *
 * @return array<string,string|string[]|double> A product.
 */
function createProduct($productName, array $ratingDistribution)
{
    $sumRatings = 0.0;
    $numRatings = 0.0;
    $ratingStar = 1.0;

    // In ascending order, 1-star to 5.
    foreach($ratingDistribution as $ratingCount)
    {
        $ratingCount = intval($ratingCount);
        $numRatings += $ratingCount;
        $sumRatings += ($ratingCount * $ratingStar++);
    }

    $product = array();
    $product['name'] = $productName;
    $product['rating_distribution'] = $ratingDistribution;
    $product['average_rating'] = ($numRatings < 1) ? 0.0 : ($sumRatings / $numRatings);
    $product['num_ratings'] = $numRatings;

    return $product;
}


/**
 * Adds bayesian estimate (of product rating) to each product.
 *
 * @param array<integer,mixed[]> &$products List of products to add bayesian estimate to.
 *
 * @return void
 */
function calculateBayesianEstimate(array &$products)
{
    // See bottom of page: http://www.imdb.com/chart/top .
    $min = getLowestNumberOfRatings($products);
    $averageAcrossProducts = getAverageRatingAcrossProducts($products);

    for ($i = 0, $length = count($products); $i < $length; $i++)
    {
        $averageRating = $products[$i]['average_rating'];
        $numRatings = $products[$i]['num_ratings'];

        $adjustedRating = ($numRatings / ($numRatings + $min)) * $averageRating;
        $offset = ($min / ($numRatings + $min)) * $averageAcrossProducts;

        $products[$i]['bayesian_average_rating'] = $adjustedRating + $offset;
    }
}


/**
 * Returns the average product rating across all products.
 *
 * @param array<integer,mixed[]> $products List of products.
 *
 * @return double Average rating across products.
 */
function getAverageRatingAcrossProducts(array $products)
{
    $total = 0.0;

    foreach($products as $product)
    {
        $total += $product['average_rating'];
    }

    $average = $total / count($products);

    return $average;
}


/**
 * Returns the number of ratings for the product with the least number of ratings.
 *
 * @param array<integer,mixed[]> $products List of products.
 *
 * @return integer Lowest number of ratings.
 */
function getLowestNumberOfRatings(array $products)
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
 * @param array<integer,mixed[]> $products List of products.
 *
 * @return void
 */
function printReviews(array $products)
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

