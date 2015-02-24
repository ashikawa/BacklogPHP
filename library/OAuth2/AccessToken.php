<?php
namespace Backlog\OAuth2;

/**
 * @property string $access_token
 * @property string $token_type
 * @property string $expires_in
 * @property string $refresh_token
 */
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
