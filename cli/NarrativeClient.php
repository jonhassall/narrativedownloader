<?php

namespace Narrative;

use GuzzleHttp;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;

use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise;

class NarrativeClient {

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * Constructor
     *
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client) {
        $this->client = $client;

    }

    public function getMoments() {
//        $response = $this->client->get('moments', ['limit' => 1] );
//        $response->getStatusCode();
        $moments = array();

        $response = $this->client->request('GET', 'moments?limit=1500&page=1');
        $response_array = json_decode($response->getBody(), true);
        array_push($moments, $response_array['results']);

        if ($response_array['next'] !== null)
        {
            echo(".");
            $response = $this->client->request('GET', $response_array['next']);
            $response_array = json_decode($response->getBody(), true);
            array_push($moments, $response_array['results']);
        }
        return json_decode($response->getBody(), true);
//        return $moments;
    }

    public function deleteMoment($uuid) {
        //Narrative limits moments to a maximum of 1500 photos
        $response = $this->client->request('DELETE', 'moments/' . $uuid . '/');
        $response->getStatusCode();
        return json_decode($response->getBody(), true);
    }

    public function deleteVideo($uuid) {
        //Narrative limits moments to a maximum of 1500 photos
        $response = $this->client->request('DELETE', 'videos/' . $uuid . '/');
        $response->getStatusCode();
        return json_decode($response->getBody(), true);
    }
    
    public function getPhotos($uuid) {
        //Narrative limits moments to a maximum of 1500 photos
        $response = $this->client->request('GET', 'moments/' . $uuid . '/photos/?limit=1500');
        $response->getStatusCode();
        return json_decode($response->getBody(), true);
    }

    public function getPositions($uuid) {
        //Narrative limits moments to a maximum of 1500 photos
        $response = $this->client->request('GET', 'moments/' . $uuid . '/positions/?limit=1500');
        $response->getStatusCode();
        return json_decode($response->getBody(), true);
    }
    
    public function getTimeline() {
        //For getting videos
        $response = $this->client->request('GET', 'timeline/?limit=3000');
        $response->getStatusCode();
        return json_decode($response->getBody(), true);
    }
    
    public function sanitizeFilename($filename) {
        // Remove anything which isn't a word, whitespace, number
        // or any of the following caracters -_~,;[]().
        // If you don't need to handle multi-byte characters
        // you can use preg_replace rather than mb_ereg_replace
        // Thanks @Łukasz Rysiak!
        $filename = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $file);
        // Remove any runs of periods (thanks falstro!)
        $filename = mb_ereg_replace("([\.]{2,})", '', $file);
        return($filename);
    }

    function downloadFile($url, $filename) {
//        file_get_contents($filename);
        if (!file_exists($filename))
        {
            file_put_contents($filename, file_get_contents($url));
        } else
        {
//            echo("File exists.\n");
        }
    }
    
    //Download multiple files concurrently using Guzzle
    //Array of URL and Filename pairs
    //Throw error if any don't work
    function downloadFiles($files)
    {
        
        $guzzle_client_noauth = new Client(
            [
        'base_uri' => 'https://narrativeapp.com/api/v2/',
            ]
        );
        
        $promises = array();

        echo("Doing lots");

        foreach ($files as $file)
        {
            array_push($promises, $guzzle_client_noauth->getAsync($file['url']));
        }
        
        $results = Promise\unwrap($promises);

        echo("End");
        
    }

}
