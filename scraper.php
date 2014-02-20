<?php

// echo 'Remove exit from script to run.'; exit;

define('MINIMUM_NUMBER_OF_VOTES_TO_SAVE_PRODUCT', 2);

$locations = array('http://www.nutraplanet.com/top_100');
$reviewUrlFragments = array_unique(getReviewUrlFragments($locations));
$products = getProducts($reviewUrlFragments, MINIMUM_NUMBER_OF_VOTES_TO_SAVE_PRODUCT);

calculateBayesianEstimate($products);

usort($products, function ($a, $b) {
    if ($a['bayesian_estimate'] == $b['bayesian_estimate'])
    {
        return 0;
    }

    return ($a['bayesian_estimate'] < $b['bayesian_estimate']) ? 1 : -1;
});

printReviews($products);

function getReviewUrlFragments($locations) {
    $reviewUrlFragments = array();

    foreach($locations as $location)
    {
        $page = @file_get_contents($location);
        preg_match_all('%/product/(.*?)\.html%is', $page, $matches);
        $reviewUrlFragments = array_merge($reviewUrlFragments, $matches[1]);
    }

    return $reviewUrlFragments;
}

function getProducts($productReviewUrlFragments, $minimumNumberOfVotesToSaveProduct)
{
    $products = array();

    foreach($productReviewUrlFragments as $productReviewUrlFragment)
    {
        $reviewUrl = 'http://www.nutraplanet.com/manufacturer/'
                         .$productReviewUrlFragment
                         .'/reviews';

        $page = @file_get_contents($reviewUrl);
        preg_match_all('%<td>&nbsp;<i>(.*?)</i></td>%is', $page, $matches);

        $product = array();
        $product['name'] = 'http://www.nutraplanet.com/product/'.$productReviewUrlFragment.'.html';

        $votes = getVotes($matches[1]);

        $product['vote_distribution'] = $votes['votes'];
        $product['review_average'] = $votes['average'];
        $product['num_votes'] = $votes['num_votes'];

        // Only add if there is a vote for the product
        if ($product['num_votes'] >= $minimumNumberOfVotesToSaveProduct)
        {
            $products[] = $product;
        }
    }

    return $products;
}

function getVotes($match)
{
    $votes = array();
    $sum_votes = 0;
    $total_votes = 0;
    $vote_star = 1;

    // In ascending order, 1-star to 5
    foreach($match as $vote_count)
    {
        $votes[] = $vote_count;
        $total_votes += $vote_count;
        $sum_votes += ($vote_count * $vote_star++);
    }

    $product['votes'] = $votes;
    $product['average'] = ($total_votes < 1) ? 0 : ($sum_votes / $total_votes);
    $product['num_votes'] = $total_votes;

    return $product;
}

function calculateBayesianEstimate(&$products)
{
    // See bottom of page: http://www.imdb.com/chart/top
    $m = getMinNumberOfVotes($products);
    $C = getAverageReviewAverage($products);

    for ($i = 0, $len = count($products); $i < $len; $i++)
    {
        $R = $products[$i]['review_average'];
        $v = $products[$i]['num_votes'];
        $products[$i]['bayesian_estimate'] = (($v / ($v+$m)) * $R) + (($m / ($v+$m)) * $C);
    }
}

function getAverageReviewAverage($products)
{
    $total = 0;

    foreach($products as $product)
    {
        $total += $product['review_average'];
    }

    return $total / count($products);
}

function getMinNumberOfVotes($products)
{
    foreach($products as $product) {
        if (!isset($minVote) || ($product['num_votes'] < $minVote))
        {
            $minVote = $product['num_votes'];
        }
    }

    if (!isset($minVote))
    {
        $minVote = 0;
    }

    return $minVote;
}

function printReviews($products)
{
    echo '<table>';
    foreach($products as $product)
    {
        $product['vote_distribution'] = implode('</td><td>', $product['vote_distribution']);
        $product = implode('</td><td>', $product);
        echo '<tr><td>' . $product . '</td></tr>';
    }
    echo '<table>';
}
