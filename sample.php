<?php
require_once 'vendor/autoload.php';

use Backlog\Client;

if (isset($_ENV['BACKLOG_SPACE'])) {
    $space = $_ENV['BACKLOG_SPACE'];
}
if (isset($_ENV['BACKLOG_API_KEY'])) {
    $token = $_ENV['BACKLOG_API_KEY'];
}

// php sample.php -s BACKLOG_SPACE -k BACKLOG_API_KEY
$options = getopt('s:t:');

if (isset($options['s'])) {
    $space = $options['s'];
}
if (isset($options['k'])) {
    $token = $options['k'];
}

$backlog = new Client();
$backlog->setSpace($space)
    ->setToken($token);

// # GET

$response = $backlog->projects->get();

// $response = $backlog->space->notification->get();

// $response = $backlog
//     ->issues('XXXXXX')
//     ->comments()
//     ->get();

// # POST

// $response = $backlog->issues->post(array(
//     'projectId' => 'xxxxxx',
//     'summary'   => 'xxxxxx',
//     'issueTypeId' => 1,
//     'categoryId' => array(
//         'xxxxxx',
//         'xxxxxx',
//     ),
//     'priorityId'  => 2,
// ));

// # PUT

// $response = $backlog->space->notification->put(array(
//     'content' => 'xxxxxx',
// ));

// # PATCH

// $response = $backlog->issues('TEST-1')->patch(array(
//     'summary' => 'xxxxxx',
//     'categoryId' => array(
//         'xxxxxx',
//         'xxxxxx',
//     ),
// ));

// # DELETE

// $response = $backlog->projects('XXXXXX')
//                 ->categories('xxxxxx')
//                 ->delete();


# Response

// var_dump($response->getBody()->propname); // get Object
// var_dump($response->propname); // short access
