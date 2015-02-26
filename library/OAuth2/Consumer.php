<?php
namespace Backlog\OAuth2;

/**
 * Consumer.
 *
 * @SuppressWarnings("Superglobals")
 */
class Consumer
{
    /**
     * @var string
     */
    protected $clientId     = null;

    /**
     * @var string
     */
    protected $clientSecret = null;

    /**
     * @var string
     */
    protected $redirectUri   = null;

    /**
     * @var \Backlog\Client
     */
    protected $client  = null;

    /**
     * @var array
     */
    public function __construct($config = array())
    {
        $client = new \Backlog\Client();

        foreach ($config as $key => $value) {
            switch ($key) {
                case 'clientId':
                case 'clientSecret':
                case 'rediectUri':
                    $this->{$key} = $value;
                    break;
                case 'baseUri':
                    $client->setBaseUri($value);
                    break;
                default:
                    break;
            }
        }

        $this->client = $client;

        if ($accessToken = $this->getAccessToken()) {
            $client->setAccessToken($accessToken);
        }
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    public function requestAccessToken()
    {
        $this->removeAccessToken();
        $this->checkState();

        $params = array(
            'grant_type'    => 'authorization_code',
            'code'          => $_REQUEST['code'],
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        );

        if ($this->redirectUri) {
            $params['redirect_uri'] = $this->redirectUri;
        }

        $this->tokenRequest($params);
    }

    private function checkState()
    {
        $state        = $_REQUEST['state'];
        $stateSaved   = $_SESSION['BACKLOG_OAUTH_STATE'];

        unset($_SESSION['BACKLOG_OAUTH_STATE']);

        if ($stateSaved !== $state) {
            throw new \Exception('state error!');
        }
    }

    public function requestRefreshToken()
    {
        $accessToken = $this->getAccessToken();

        if (is_null($accessToken)) {
            throw new \Exception('access token not found');
        }

        $params = array(
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $accessToken->refresh_token,
        );

        $this->client->setAccessToken(null);

        $this->tokenRequest($params);
    }

    /**
     * @return stdClass jsonResponse
     */
    private function tokenRequest($params)
    {
        $response = $this->client
            ->oauth2->token
            ->post($params)
            ->getBody();

        $this->setAccessToken($response);

        return $response;
    }

    /**
     * @param AccessToken|Object $accessToken
     *
     * @return Consumer
     */
    public function setAccessToken($accessToken)
    {
        if (! $accessToken instanceof AccessToken) {
            $accessToken = new AccessToken($accessToken);
        }

        $_SESSION['ACCESS_TOKEN'] = serialize($accessToken);

        $this->getClient()
            ->setAccessToken($accessToken);

        return $this;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        if (isset($_SESSION['ACCESS_TOKEN'])) {
            return unserialize($_SESSION['ACCESS_TOKEN']);
        }

        return;
    }

    public function removeAccessToken()
    {
        $this->client->setAccessToken(null);
        unset($_SESSION['ACCESS_TOKEN']);
    }

    /**
     * @return string
     */
    public function getAuthorizeUrl()
    {
        $baseUrl = trim($this->client->getBaseUrl(), '/');
        $url     = $baseUrl.'/OAuth2AccessRequest.action';
        $state   = md5(__CLASS__.time());

        $params = array(
            'response_type' => 'code',
            'client_id'     => $this->clientId,
            'state'         => $state,
        );

        if ($this->redirectUri) {
            $params['redirect_uri'] = $this->redirectUri;
        }

        $_SESSION['BACKLOG_OAUTH_STATE'] = $state;

        return $url.'?'.http_build_query($params);
    }
}
