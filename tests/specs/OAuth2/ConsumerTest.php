<?php
namespace BacklogTest\OAuth2;

use PHPUnit_Framework_TestCase as TestCase;
use Backlog\OAuth2\Consumer as Consumer;

/**
 * @SuppressWarnings("Superglobals")
 */
class ConsumerTest extends TestCase
{
    use \BacklogTest\mockRequest;

    protected $consumer = null;

    public function setUp()
    {
        $this->setupClient();

        $consumer = new Consumer(array(
            'client'      => $this->client,
            'baseUri'     => 'http://example.com',
            'redirectUri' => 'http://localhost.com/callback',
        ));

        $this->consumer = $consumer;
    }

    public function tearDown()
    {
    }

    public function testClient()
    {
        $consumer = $this->consumer;
        $client   = $consumer->getClient();

        $this->assertEquals('Backlog\Client', get_class($client));
        $this->assertEquals('http://example.com', $client->getBaseUrl());
    }

    public function testAccessToken()
    {
        $consumer = $this->consumer;

        $accessToken = (object) array(
            'access_token' => 'xxxxx',
        );

        $consumer->setAccessToken($accessToken);
        $accessToken = $consumer->getAccessToken();

        $this->assertEquals('Backlog\OAuth2\AccessToken', get_class($accessToken));
        $this->assertEquals('xxxxx', $accessToken.'');

        $this->assertEquals('xxxxx', $accessToken->access_token);

        $consumer->removeAccessToken();
        $accessToken = $consumer->getAccessToken();

        $this->assertNull($accessToken);
    }

    public function testGetAuthorizeUrl()
    {
        $consumer = $this->consumer;

        $authorizeUri = $consumer->getAuthorizeUrl();
        $this->assertTrue(filter_var($authorizeUri, FILTER_VALIDATE_URL) !== false);

        $parsed = parse_url($authorizeUri);

        $this->assertEquals('example.com', $parsed['host']);
        $this->assertEquals('/OAuth2AccessRequest.action', $parsed['path']);

        parse_str($parsed['query'], $query);

        $this->assertEquals('code', $query['response_type']);
        $this->assertTrue(in_array($query['state'], array_values($_SESSION)));
        $this->assertEquals('http://localhost.com/callback', $query['redirect_uri']);
    }

    public function testRequestAccessToken()
    {
        $this->setMockResponse('200ok_token');

        $consumer = $this->consumer;

        $_REQUEST['state'] = 'xxxxx';
        $_REQUEST['code']  = 'zzzzz';
        $_SESSION['BACKLOG_OAUTH_STATE']  = 'xxxxx';

        $accessToken = $consumer->requestAccessToken();
        $request = $this->getHttpRequest();

        $this->assertRequestPath('/api/v2/oauth2/token');
        $this->assertRequestMethod('POST');
        $this->assertEquals('authorization_code', $request->getPost()->grant_type);
        $this->assertEquals('xxxxxxxxxx', $accessToken->access_token);

        $this->assertFalse(isset($_SESSION['BACKLOG_OAUTH_STATE']));
    }

    public function testRequestRefreshToken()
    {
        $this->setMockResponse('200ok_token');

        $consumer = $this->consumer;

        $accessToken = array(
            'refresh_token' => 'zzzzz',
        );
        $consumer->setAccessToken((object) $accessToken);

        $accessToken = $consumer->requestRefreshToken();
        $request = $this->getHttpRequest();

        $this->assertRequestPath('/api/v2/oauth2/token');
        $this->assertRequestMethod('POST');
        $this->assertEquals('refresh_token', $request->getPost()->grant_type);
        $this->assertEquals('xxxxxxxxxx', $accessToken->access_token);
    }

    /**
     * @expectedException Exception
     */
    public function testRequestRefreshTokenWithoutToken()
    {
        $consumer = $this->consumer;
        $consumer->requestRefreshToken();
    }
}
