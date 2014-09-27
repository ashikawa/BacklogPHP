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

        $client->projects->get(array('p1' => 'v1'));

        $method = $client->getHttpClient()->getMethod();
        $this->assertEquals('GET', $method);

        $request = $client->getHttpClient()->getRequest();
        $this->assertEquals('v1', $request->getQuery('p1'));

        $client->projects->post(array('p2' => 'v2'));

        $method = $client->getHttpClient()->getMethod();
        $this->assertEquals('POST', $method);

        $request = $client->getHttpClient()->getRequest();
        $this->assertEquals('v2', $request->getPost('p2'));

        $request = $client->getHttpClient()->getRequest();

        $client->projects->put(array('p3' => 'v3'));

        $method = $client->getHttpClient()->getMethod();
        $this->assertEquals('PUT', $method);

        $request = $client->getHttpClient()->getRequest();
        $this->assertEquals('v3', $request->getPost('p3'));

        $client->projects->patch(array('p4' => 'v4'));

        $method = $client->getHttpClient()->getMethod();
        $this->assertEquals('PATCH', $method);

        $request = $client->getHttpClient()->getRequest();
        $this->assertEquals('v4', $request->getPost('p4'));

        $client->projects->delete();
        $method = $client->getHttpClient()->getMethod();
        $this->assertEquals('DELETE', $method);
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
        try {
            $this->client->dummymethod();
        } catch (\Backlog\Exception\ApiErrorException $e) {
            $this->assertNotEmpty($e->getErrors());
        }
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
