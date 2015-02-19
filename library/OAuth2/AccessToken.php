<?php
namespace Backlog\OAuth2;

class AccessToken
{
    /**
     * @var stdClass
     */
    protected $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function __get($name)
    {
        return $this->token->{$name};
    }

    public function __toString()
    {
        return $this->token->access_token;
    }
}
