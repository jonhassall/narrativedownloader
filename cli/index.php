<pre><?php

session_start();

require __DIR__ . '/vendor/autoload.php';

$log = new Monolog\Logger('name');
$log->pushHandler(new Monolog\Handler\StreamHandler('log/app.log', Monolog\Logger::WARNING));
$log->addWarning('Foo');

$provider = new \League\OAuth2\Client\Provider\GenericProvider([
    'clientId' => 'bfQ9lc75Wupun6QlYt2H3vQ3NFNqkliF7m7l7ai3', // The client ID assigned to you by the provider
    'clientSecret' => 'dws4XQIZEe8j34kJUc7ItUKfscKuaIK38y0ylbSAbnNJyov7vP6fjNwNVRiPRI0CMZZRKSFEsdMkup6QUVDrY15bs3urb49WkVX0pKFIYdZY62SJbhrpkhunA2fmjxSF', // The client password assigned to you by the provider
    'redirectUri' => 'http://narrative-dev.jonhassall.com',
    'urlAuthorize' => 'https://narrativeapp.com/oauth2/authorize',
    'urlAccessToken' => 'https://narrativeapp.com/oauth2/token',
    'urlResourceOwnerDetails' => 'https://narrativeapp.com/oauth2/lockdin/resource',
    'verify' => false
        ]);

// If we don't have an authorization code then get one
if (!isset($_GET['code']))
{

    // Fetch the authorization URL from the provider; this returns the
    // urlAuthorize option and generates and applies any necessary parameters
    // (e.g. state).
    $authorizationUrl = $provider->getAuthorizationUrl();

    // Get the state generated for you and store it to the session.
    $_SESSION['oauth2state'] = $provider->getState();
    var_dump($_SESSION);

    // Redirect the user to the authorization URL.
    header('Location: ' . $authorizationUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state']))
{

    unset($_SESSION['oauth2state']);
    exit('Invalid state');
} else
{

    try
    {

        // Try to get an access token using the authorization code grant.
        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        // We have an access token, which we may use in authenticated
        // requests against the service provider's API.
        echo $accessToken->getToken() . "\n";
        echo $accessToken->getRefreshToken() . "\n";
        echo $accessToken->getExpires() . "\n";
        echo ($accessToken->hasExpired() ? 'expired' : 'not expired') . "\n";

//        // Using the access token, we may look up details about the
//        // resource owner.
//        $resourceOwner = $provider->getResourceOwner($accessToken);
//
//        var_export($resourceOwner->toArray());
//
//        // The provider provides a way to get an authenticated API request for
//        // the service, using the access token; it returns an object conforming
//        // to Psr\Http\Message\RequestInterface.
//        $request = $provider->getAuthenticatedRequest(
//                'GET', 'http://brentertainment.com/oauth2/lockdin/resource', $accessToken
//        );
    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e)
    {

        // Failed to get the access token or user details.
        exit($e->getMessage());
    }
}
?></pre>