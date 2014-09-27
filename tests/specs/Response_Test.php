<?php
class Response_Test extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
    }

    public function tearDown()
    {
    }

    protected function buildHttpResponse($file = '200ok')
    {
        $responseString = file_get_contents(__DIR__.'/httpresponse/'.$file.'.txt');

        return Zend\Http\Response::fromString($responseString);
    }

    public function testResponseMethods()
    {
        $httpresponse = $this->buildHttpResponse();
        $response = new Backlog\Response($httpresponse);

        $this->assertTrue(isset($response->content));
        $this->assertEquals('dummy', $response->content);

        $this->assertEquals('dummy', $response->getBody()->content);
        $this->assertInternalType('string', $response->getRawResponse());
    }

    /**
     * @expectedException DomainException
     */
    public function testErrorJsonParseError()
    {
        $httpresponse = $this->buildHttpResponse('200ok_wrongformat');

        $response = new Backlog\Response($httpresponse);
    }
}
