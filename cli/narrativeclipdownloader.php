<?php

require __DIR__ . '/vendor/autoload.php';

$log = new Monolog\Logger('log');
$log->pushHandler(new Monolog\Handler\StreamHandler('log/app.log', Monolog\Logger::WARNING));
$log->addWarning('Started');

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Event\CompleteEvent;


require('clitools.inc.php');
require('initools.inc.php');

readfile('README.txt');

echo "
  _   _                      _   _              _____ _ _       
 | \ | |                    | | (_)            / ____| (_)      
 |  \| | __ _ _ __ _ __ __ _| |_ ___   _____  | |    | |_ _ __  
 | . ` |/ _` | '__| '__/ _` | __| \ \ / / _ \ | |    | | | '_ \ 
 | |\  | (_| | |  | | | (_| | |_| |\ V /  __/ | |____| | | |_) |
 |_|_\_|\__,_|_|  |_|  \__,_|\__|_| \_/ \___|  \_____|_|_| .__/ 
 |  __ \                    | |               | |        | |    
 | |  | | _____      ___ __ | | ___   __ _  __| | ___ _ _|_|    
 | |  | |/ _ \ \ /\ / / '_ \| |/ _ \ / _` |/ _` |/ _ \ '__|     
 | |__| | (_) \ V  V /| | | | | (_) | (_| | (_| |  __/ |        
 |_____/ \___/ \_/\_/ |_| |_|_|\___/ \__,_|\__,_|\___|_|        
 
by Jonathan Hassall

";

echo("Checking for software updates...\n");
//Check for latest version
$current_version_number = 0.1;
$guzzle_version_check = new Client(
        [
    'base_uri' => 'https://narrativeapp.com/api/v2/',
    'headers' => [
        'Content-Type' => 'application/json'
    ]
        ]
);
$response = $guzzle_version_check->request('GET', 'http://www.jonhassall.com/narrativeclipdownloader/latestversion.json');
$response->getStatusCode();
$version_check_data = json_decode($response->getBody(), true);

if ($version_check_data['version'] && $current_version_number < $version_check_data['version'])
{
    echo("An updated version of this software is available. You can download it from " . $version_check_data['url'] . "\n");
}
else
{
    echo("You have the latest version of this software.\n");
}

if (file_exists('config.ini')) {
    $ini_array = parse_ini_file("config.ini");
    $access_token = $ini_array['access_token'];
}

if (!$ini_array['output_directory'])
{
    $ini_array['output_directory'] = 'moments';
}
//Trim trailing slash from output directory
$ini_array['output_directory'] = rtrim($ini_array['output_directory'], '/\\');

if (!$access_token)
{
    echo "Please enter your Narrative Platform access token: ";

    $access_token = stream_get_line(STDIN, 1024, PHP_EOL);

    $log->addWarning('Access token entered: ' . $access_token);
    
    $ini_array['access_token'] = $access_token;
    write_php_ini($ini_array, "config.ini");
}



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

use lsolesen\pel\PelJpeg;
use lsolesen\pel\PelTiff;
use lsolesen\pel\PelExif;
use lsolesen\pel\PelIfd;
use lsolesen\pel\PelEntryUserComment;
use lsolesen\pel\PelEntryAscii;
use lsolesen\pel\PelTag;
use lsolesen\pel\PelEntryByte;
use lsolesen\pel\PelEntryRational;

include('gpsfunctions.inc.php');

//$guzzle->setDefaultOption();

$narrative = new Narrative($guzzle);

echo("
Please choose an option:

[1] List all Moments
[2] Download all Moments
[3] Download all Videos
[4] Set output directory
[5] Delete all Moments
[6] Delete all video Moments
[Q] Quit

");

switch (stream_get_line(STDIN, 1024, PHP_EOL)) {
    case 1:
        echo("Getting all moments, please wait...\n");
        $moments = $narrative->getMoments();
        file_put_contents('data/moments.json', json_encode($moments, JSON_PRETTY_PRINT));

        foreach($moments['results'] as $moment)
        {
            echo($moment['start_timestamp_local'] . " to " . $moment['end_timestamp_local'] . "\n");
        }
        break;

    case 4:
        echo("Please enter desired output directory:\n");
        $output_directory = stream_get_line(STDIN, 1024, PHP_EOL);
        $ini_array['output_directory'] = $output_directory;
        write_php_ini($ini_array, "config.ini");
        echo("Output directory set.\n");
    break;

    case 5:
        echo("Type YES if you are sure you want to delete all Moments:\n");
        if (stream_get_line(STDIN, 1024, PHP_EOL) == "YES")
        {
            echo("Deleting all Moments...\n");
            $moments = $narrative->getMoments();
            foreach($moments['results'] as $moment)
            {
                echo("Deleting Moment " . $moment['uuid'] . "...\n");
                $narrative->deleteMoment($moment['uuid']);
            }
            echo("All Moments deleted.\n");
        }
        
    break;

    case 6:
        echo("Type YES if you are sure you want to delete all video Moments:\n");
        if (stream_get_line(STDIN, 1024, PHP_EOL) == "YES")
        {
            echo("Deleting all videos...\n");
            $timeline = $narrative->getTimeline();
            $videos_array = array();
            foreach ($timeline['results'] as $result)
            {
                if ($result['type'] == 'video')
                {
                    echo("Deleting video Moment " . $result['uuid'] . "...\n");
                    $narrative->deleteVideo($result['uuid']);
                }
            }
            echo("All video Moments deleted.\n");
        }
        
    break;

    case 3:
        echo("Finding all videos...\n");
        $timeline = $narrative->getTimeline();
        
        $videos_array = array();
        
        foreach ($timeline['results'] as $result)
        {
            if ($result['type'] == 'video')
            {
                $max_size = null;
                foreach ($result['renders'] as $render)
                {
                    if ($max_size == NULL || $max_size < $render['size']['height'])
                    {
                        //Videos may be landscape or portrait
                        //Find the video with largest height
                        array_push($videos_array, array('url' => $render['url'], 'width' => $render['size']['width'], 'height' => $render['size']['height'], 'timestamp' => $result['start_timestamp_local']));
                        $max_size = $render['size']['height'];
                    }
                }
            }
        }
        
        echo(count($videos_array) . " video files found. Downloading:\n");
        $progress_completed = 0;
        $progress_target = count($videos_array);
        $progressBar = new Console_ProgressBar("  %current% / %max% [%bar%] %percent% - Estimated: %estimate%", '=>', ' ', 110, $progress_target);

        foreach ($videos_array as $video)
        {
            $filename = $ini_array['output_directory'] . '\\' . date('jS M Y H-i-s', strtotime($video['timestamp'])) . " - " . $video['width'] . 'x' . $video['height'] . '.mp4';
            $narrative->downloadFile($video['url'], $filename);
            touch($filename, strtotime($video['timestamp']));
            $progress_completed++;
            $progressBar->update($progress_completed);
        }

        echo("Finished.\n");
        break;
    
    
    case 2:
        echo("Downloading all moments...\n");
        echo("Getting all moments, please wait...\n");
        $moments = $narrative->getMoments();
        file_put_contents('data/moments.json', json_encode($moments, JSON_PRETTY_PRINT));
           
        
        foreach($moments['results'] as $moment)
        {
//            echo($moment['start_timestamp_local'] . " to " . $moment['end_timestamp_local'] . "\n");

            //Folder. If there is a caption, use that in the name
            $moment_dirname = $ini_array['output_directory'] . '\\' . date('jS M Y', strtotime($moment['start_timestamp_local'])) . " to " . date('jS M Y', strtotime($moment['end_timestamp_local']));
            if ($moment['caption'] != "")
            {
                $moment_dirname = $moment_dirname . " - " . $moment['caption'];
            }
            else
            {
                $moment_dirname = $moment_dirname . " - " . $moment['uuid'];
            }
            if (!file_exists($moment_dirname))
            {
                mkdir($moment_dirname);
            }
            
            echo("\nCurrent moment: ");
            echo($moment_dirname . "\n");
  
            echo("Downloading all images from moment and applying GPS/Geolocation...\n");
            $photos = $narrative->getPhotos($moment['uuid']);
            file_put_contents('data/photos-' . stripslashes($moment['uuid']) . '.json', json_encode($photos, JSON_PRETTY_PRINT));

            //Get positions for this moment
            echo("Getting GPS/Geolocation data for this moment...");
            $positions = $narrative->getPositions($moment['uuid']);
            file_put_contents('data/positions-' . stripslashes($moment['uuid']) . '.json', json_encode($positions, JSON_PRETTY_PRINT));
            echo("There are " . count($positions['results']) . " GPS/Geolocation points for this moment\n");
            echo("There are " . count($photos['results']) . " photos in this moment\n");
            

            $progress_completed = 0;
            $progress_target = count($photos['results']);
            $progressBar = new Console_ProgressBar("  %current% / %max% [%bar%] %percent% - Estimated: %estimate%", '=>', ' ', 110, $progress_target);
            
            $i = 1;
            foreach ($photos['results'] as $photo)
            {
//                echo("X");

                
                //Set modification date, and apply GPS/Geolocation information

//                $gps_ifd->addEntry(new PelEntryByte(PelTag::GPS_VERSION_ID, 2, 2, 0, 0));
//                $gps_ifd->addEntry(new PelEntryAscii(PelTag::GPS_LATITUDE_REF, $latitude_ref));
//                $gps_ifd->addEntry(new PelEntryRational(PelTag::GPS_LATITUDE, $hours, $minutes, $seconds));
//                $gps_ifd->addEntry(new PelEntryAscii(PelTag::GPS_LONGITUDE_REF, $longitude_ref));
//                $gps_ifd->addEntry(new PelEntryRational(PelTag::GPS_LONGITUDE, $hours, $minutes, $seconds));
                
                $closest_location = array();
                $location_filename_addition = '';
                if (count($positions['results']) > 0)
                {
//                    echo("Geolocation information present. Finding closest point...\n");
                    foreach ($positions['results'] as $position)
                    {
                        $time_difference = abs( strtotime($position['timestamp']) - strtotime($photo['taken_at_local']) );

                        if (!isset($closest_location['time_difference']))
                        {
                            $closest_location['time_difference'] = $time_difference;
                            $closest_location['location'] = $position['location'];
                        }
                        else
                        {
                            if ($time_difference < $closest_location['time_difference'])
                            {
                                $closest_location['time_difference'] = $time_difference;
                                $closest_location['location'] = $position['location'];
                            }
                        }
                    }
//                    echo("Closest location:\n");
//                    var_dump($closest_location);
//                    echo("\n");
                    
                    $location_filename_addition = ' - ' . $closest_location['location']['properties']['country'] . ' - ' . $closest_location['location']['properties']['city'] . ' - ' . $closest_location['location']['properties']['address'];
                }
                else
                {
//                    echo("Geolocation information not present.\n");
                }

                $filename = $moment_dirname . "/" . str_pad($i, 4, '0000', STR_PAD_LEFT) . $location_filename_addition . '.jpg';
                $narrative->downloadFile($photo['renders']['g1_hd']['url'], $filename);
                

                
                if (file_exists($filename))
                {
                    touch($filename, strtotime($photo['taken_at_local']));
                    
                    if (count($positions['results']) > 0)
                    {
                        //Set GPS/Geolocation information
                        $pelJpeg = new PelJpeg($filename);
                        $pelExif = $pelJpeg->getExif();
                        if ($pelExif == null)
                        {
                            $pelExif = new PelExif();
                            $pelJpeg->setExif($pelExif);
                        }
                        
                        $pelTiff = $pelExif->getTiff();
                        if ($pelTiff == null) {
                            $pelTiff = new PelTiff();
                            $pelExif->setTiff($pelTiff);
                        }
                        
                        $pelIfd0 = $pelTiff->getIfd();
                        if ($pelIfd0 == null) {
                            $pelIfd0 = new PelIfd(PelIfd::IFD0);
                            $pelTiff->setIfd($pelIfd0);
                        }
                        
                        $pelIfd0->addEntry(new PelEntryAscii(
                                PelTag::IMAGE_DESCRIPTION, $closest_location['location']['properties']['country'] . ' - ' . $closest_location['location']['properties']['city'] . ' - ' . $closest_location['location']['properties']['address']));
                        $pelIfd0->addEntry(new PelEntryAscii(
                                PelTag::SOFTWARE, 'Narrative Clip Downloader by Jonathan Hassall'));

                        $pelSubIfdGps = new PelIfd(PelIfd::GPS);
                        $pelIfd0->addSubIfd($pelSubIfdGps);

//                        setGeolocation($pelSubIfdGps, 13.37, 1.337);
                        setGeolocation($pelSubIfdGps, $closest_location['location']['geometry']['coordinates'][1], $closest_location['location']['geometry']['coordinates'][0]);
                        
                        $pelJpeg->saveFile($filename);

//                        addGpsInfo($filename, $filename, 'Description', 'Comment', 'Model', $closest_location['location']['geometry']['coordinates'][0], $closest_location['location']['geometry']['coordinates'][1], 0, strtotime($photo['taken_at_local']));
                    }

                }
                
                
                $i++;
                $progress_completed++;
                $progressBar->update($progress_completed);
            }
            
            //Set directory modification time
            if (file_exists($moment_dirname))
            {
                touch($moment_dirname, strtotime($moment['start_timestamp_local']));
            }
        }
        
        break;

    case "Q":
    case "q":

        break;

    default:

        echo("Invalid option.");
        break;
}