# narrativedownloader
Downloader for the Narrative AB Clip Platform

# Explanation

The Narrative Clip and Narrative Clip 2 were devices created by Narrative AB, to be used as a wearable lifelogging camera.

In 2016 the future of the hosted cloud platform was in doubt, so I created this downloader to create a local copy of images and videos. In September 2016 the Narrative AB company effectively went bankrupt.

# Geolocation

This script will also mangle the geolocation data from the platform to a useful EXIF header. The Clip 2 uses GPS and WiFi geolocation data sources.

# Contributions

Contributions to the project are welcome, although it is likely the hosted cloud platform from Narrative AB will no longer exist by November 2016, making this downloader useless.

# Notes

**public_html** contains a HTML page allowing you to retrieve an access token from the Narrative Platform.
**cli** contains PHP commandline scripts. Open a command prompt, change to the cli directory, and run `php narrativeclipdownloader.php`