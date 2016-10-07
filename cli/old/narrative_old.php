<?php

$access_token = "PAufPOnhryLMMiNoXThU4FKxdBhRbF";

// curl "https://narrativeapp.com/api/v2/moments/?limit=1" -H "Authorization: Bearer PAufPOnhryLMMiNoXThU4FKxdBhRbF"
require __DIR__ . '/vendor/autoload.php';

$log = new Monolog\Logger('log');
$log->pushHandler(new Monolog\Handler\StreamHandler('log/app.log', Monolog\Logger::WARNING));
$log->addWarning('Start');

use GuzzleHttp\Client;

include('NarrativeClient.php');

use Narrative\NarrativeClient as Narrative;

$guzzle = new Client(
        [
    'base_uri' => 'https://narrativeapp.com/api/v2/',
    'headers' => [
        'Content-Type' => 'application/json',
//            'X-Client-ID' => $client_id,
//            'X-Access-Token' => $access_token,
        'Authorization' => 'Bearer ' . $access_token
    ]
        ]
);

$narrative = new Narrative($guzzle);
$moments = $narrative->getMoments();
//var_dump($moments);
file_put_contents('moments.json', json_encode($moments, JSON_PRETTY_PRINT));

foreach ($moments['results'] as $moment)
{
    var_dump($moment['uuid']);
    var_dump($moment['start_timestamp_local']);
    $moment_timestamp_start = strtotime($moment['start_timestamp_local']);
    $moment_timestamp_end = strtotime($moment['end_timestamp_local']);

    $moment_dirname = 'moments/' . date("U Y-m-d h-i-s", $moment_timestamp_start) . ' to ' . date("Y-m-d h-i-s", $moment_timestamp_end) . '/';
    var_dump($moment_dirname);
    if (!is_dir($moment_dirname))
    {
        mkdir($moment_dirname);
    }

    echo("Getting photos for moment " . $moment['uuid'] . "...\n");
    $photos = $narrative->getPhotos($moment['uuid']);
    file_put_contents('photos_' . $moment['uuid'] . '.json', json_encode($photos, JSON_PRETTY_PRINT));

    $i = 1;
    foreach ($photos['results'] as $photo)
    {
        if ($photo['type'] == 'photo')
        {
            echo("Photo\n");
            $filename = $moment_dirname . $i . '.jpg';
            var_dump($photo['renders']['g1_hd']['url']);
//            var_dump($photo);
            var_dump($filename);
            $narrative->downloadFile($photo['renders']['g1_hd']['url'], $filename);
        }
        else
//        if ($photo['type'] == 'video')
        {
            //Store this video, and skip to next
            echo("Video?\n");
            var_dump($photo);
            die();
            //Delete the folder
            rmdir($moment_dirname);
            //Download the video
            $filename = date("U Y-m-d h-i-s", $moment_timestamp_start) . '.mp4';
            die("OH LOOK A VIDEO JON LOOK AT THIS DATA:\n");
            var_dump($photo);
        }
        
        $i++;
    }
}
//$response = $guzzle->get('moments');
//$response = $guzzle->requset('GET', 'moments');
//$guzzle->checkResponseStatusCode($response, 200);
//var_dump(json_decode($response->getBody(), true));
