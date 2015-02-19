<?php
namespace Backlog\OAuth2;

/**
 * Class Consumer
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
     * @var array $config
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
            'grant_type'    => 'authorization_code', // 'refresh_token'
            'code'          => $_REQUEST['code'],
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        );

        if ($this->redirectUri) {
            $params['redirect_uri'] = $this->redirectUri;
        }

        $response = $this->client
            ->oauth2->token
            ->post($params)
            ->getBody();

        $_SESSION['ACCESS_TOKEN'] = serialize(new AccessToken($response));
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
