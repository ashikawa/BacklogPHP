<?php
require_once 'vendor/autoload.php';

use Backlog\Client;

if (isset($_ENV['BACKLOG_BASE_URI'])) {
    $baseUri = $_ENV['BACKLOG_BASE_URI'];
}
if (isset($_ENV['BACKLOG_API_KEY'])) {
    $token = $_ENV['BACKLOG_API_KEY'];
}

// php sample.php -u https://exmple.backlog.jp/ -k XXXXXXX
$options = getopt('u:t:');

if (isset($options['u'])) {
    $baseUri = $options['u'];
}
if (isset($options['k'])) {
    $token = $options['k'];
}

$backlog = new Client();
$backlog->setBaseUri($baseUri)
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

// # Post File Attachment

// $backlog->getHttpClient()
//     ->setFileUpload('/tmp/dymmy.txt', 'file');

// $response = $backlog->space->attachment->post();

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
