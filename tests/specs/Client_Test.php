<?php
class Client_Test extends PHPUnit_Framework_TestCase
{
    protected $adapter = null;

    protected $client = null;

    public function setUp()
    {
        $adapter = new Zend\Http\Client\Adapter\Test();
        $this->adapter = $adapter;

        $client = new \Backlog\Client(array(
            'adapter' => $adapter,
        ));

        $this->client = $client;
    }

    public function tearDown()
    {
    }

    protected function setMockResponse($file = '200ok')
    {
        $adapter  = $this->adapter;
        $response = file_get_contents(__DIR__.'/httpresponse/'.$file.'.txt');

        $adapter->setResponse($response);
    }

    public function testBasicFunctions()
    {
        $client = $this->client;
        $httpClient = $client->getHttpClient();

        $this->assertEquals('Zend\Http\Client', get_class($httpClient));
    }

    public function testSpacesAndBaseUri()
    {
        $this->setMockResponse();

        $client = $this->client;

        $client->setBaseUri('http://%s.example.com/')
            ->setSpace('dummyspace')
            ->setToken('dummytoken');

        $client->projects()->get();

        $uri = $client->getHttpClient()->getUri();

        $this->assertEquals('http',                   $uri->getScheme());
        $this->assertEquals('dummyspace.example.com', $uri->getHost());
        $this->assertEquals('/projects',              $uri->getPath());

        $request = $client->getHttpClient()->getRequest();

        $this->assertEquals('dummytoken', $request->getQuery('apiKey'));
    }

    public function testBuildQuery()
    {
        $this->setMockResponse();

        $client = $this->client;

        $client->projects->get();
        $path = $client->getHttpClient()->getUri()->getPath();
        $this->assertEquals('/api/v2/projects', $path);

        $client->space->notification->get();
        $path = $client->getHttpClient()->getUri()->getPath();
        $this->assertEquals('/api/v2/space/notification', $path);

        $client->issues('TEST-1')->comments->get();
        $path = $client->getHttpClient()->getUri()->getPath();
        $this->assertEquals('/api/v2/issues/TEST-1/comments', $path);

        $client->issues->{'TEST-2'}->comments->get();
        $path = $client->getHttpClient()->getUri()->getPath();
        $this->assertEquals('/api/v2/issues/TEST-2/comments', $path);
    }

    public function testMethods()
    {
        $this->setMockResponse();

        $client = $this->client;

        $client->projects->request('GET');
        $method = $client->getHttpClient()->getMethod();
        $this->assertEquals('GET', $method);

        $testCases = array(
            'GET'    => array('p1' => 'v1'),
            'POST'   => array('p2' => 'v2'),
            'PUT'    => array('p3' => 'v3'),
            'PATCH'  => array('p4' => 'v4'),
            'DELETE' => array('p5' => 'v5'),
        );

        foreach ($testCases as $_method => $args) {
            call_user_func_array(array($client->projects, strtolower($_method)), array($args));

            $method = $client->getHttpClient()->getMethod();
            $this->assertEquals($_method, $method);

            $request = $client->getHttpClient()->getRequest();

            if ($_method == 'GET' || $_method == 'DELETE' ) {
                $this->assertEquals(current($args), $request->getQuery(key($args)));
            } else {
                $this->assertEquals(current($args), $request->getPost(key($args)));
            }
        }
    }

    public function testSaceAttachment()
    {
        $this->setMockResponse('200ok_attachment');

        $response = $this->client->dummymethod()->get();

        $this->assertNull($response->getBody());

        $fileName = __DIR__.'/tmp/'.uniqid(__METHOD__);
        $response->save($fileName);

        $this->assertEquals('test http body', file_get_contents($fileName));

        unlink($fileName);
    }

    /**
     * @expectedException Backlog\Exception\ApiErrorException
     */
    public function testApiError400()
    {
        $this->setMockResponse('400badrequest');

        $this->client->dummymethod()->get();
    }

    public function testApiErrorDetails()
    {
        $this->setMockResponse('400badrequest');

        $errors = null;

        try {
            $this->client->dummymethod->get();
        } catch (\Backlog\Exception\ApiErrorException $e) {
            $errors = $e->getErrors();
        }

        $this->assertNotEmpty($errors);
    }

    /**
     * @expectedException DomainException
     */
    public function testErrorResponseHeader()
    {
        $this->setMockResponse('200ok_wrongheader');

        $this->client->dummymethod->get();
    }

    /**
     * @expectedException     Backlog\Exception\HttpErrorException
     * @expectedExceptionCode 404
     */
    public function testHttpError()
    {
        $this->setMockResponse('404notfound');

        $this->client->dummymethod->get();
    }
}
