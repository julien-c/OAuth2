<?php

namespace OAuth2;

use OutOfBoundsException;
use OAuth2\Storage\SessionInterface;
use OAuth2\Storage\SessionScopeInterface;
use OAuth2\Util\RequestInterface;
use OAuth2\Util\Request;

class ResourceServer
{
    protected $accessToken = null;

    protected $sessionId = null;

    protected $ownerType = null;

    protected $ownerId = null;

    protected $sessionScopes = array();

    protected $storages = array();

    protected $request = null;

    protected $tokenKey = 'oauth_token';

    /**
     * Sets up the Resource
     *
     * @param  SessionInterface  The Session Storage Object
     * @param  SessionScopeInterface  The Session Scope Storage Object
     */
    public function __construct(SessionInterface $session)
    {
        $this->storages['session'] = $session;
    }

    /**
     * Sets the Request Object
     *
     * @param  RequestInterface The Request Object
     */
    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Gets the Request object.  It will create one from the globals if one is not set.
     *
     * @return  RequestInterface
     */
    public function getRequest()
    {
        if ($this->request === null) {
            // @codeCoverageIgnoreStart
            $this->request = Request::buildFromGlobals();
        }
        // @codeCoverageIgnoreEnd

        return $this->request;
    }

    public function getTokenKey()
    {
        return $this->tokenKey;
    }

    public function setTokenKey($key)
    {
        $this->tokenKey = $key;
    }

    /**
     * Gets the Owner ID.
     *
     * @return  int
     */
    public function getOwnerId()
    {
        return $this->ownerId;
    }

    /**
     * Gets the Owner Type.
     *
     * @return  string
     */
    public function getOwnerType()
    {
        return $this->ownerType;
    }

    /**
     * Gets the Access Token.
     *
     * @return  string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Checks if the Access Token is valid or not.
     *
     * @return bool
     */
    public function isValid()
    {
        $access_token = $this->determineAccessToken();

        $result = $this->storages['session']->validateAccessToken($access_token);

        if ( ! $result) {
            throw new Exception\InvalidAccessTokenException('Access token is not valid');
        }

        $this->accessToken = $access_token;
        $this->sessionId = $result['id'];
        $this->ownerType = $result['owner_type'];
        $this->ownerId = $result['owner_id'];

        $this->sessionScopes = $this->storages['session']->getScopes($this->sessionId);

        return true;
    }

    /**
     * Checks if the current session has the given scope(s).
     *
     * @param   array
     */
    public function hasScope($scopes)
    {
        if (is_string($scopes)) {
            if (in_array($scopes, $this->sessionScopes)) {
                return true;
            }
            return false;
        } elseif (is_array($scopes)) {
            foreach ($scopes as $scope) {
                if ( ! in_array($scope, $this->sessionScopes)) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Reads in the Access Token from the headers.
     *
     * @return string
     * @throws Exception\MissingAccessTokenException
     */
    protected function determineAccessToken()
    {
        if ($header = $this->getRequest()->header('Authorization')) {
            $access_token = base64_decode(trim(str_replace('Bearer', '', $header)));
        } else {
            $method = $this->getRequest()->server('REQUEST_METHOD');
            $access_token = $this->getRequest()->{$method}($this->tokenKey);
        }

        if (empty($access_token)) {
            throw new Exception\InvalidAccessTokenException('Access token is missing');
        }

        return $access_token;
    }

}