<?php

namespace App\Services\Wikidata;

use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Server\Server;
use League\OAuth1\Client\Signature\SignatureInterface;

class MediaWikiOAuth extends Server
{
    /**
     * @var string
     */
    protected $baseUri;

    /**
     * @var string
     */
    protected $requestTokenEndpoint;

    /**
     * @var string
     */
    protected $authorizationEndpoint;

    /**
     * @var string
     */
    protected $accessTokenEndpoint;

    /**
     * MediaWikiOAuth constructor.
     * 
     * @param array $clientCredentials
     * @param SignatureInterface|null $signature
     */
    public function __construct(array $clientCredentials, SignatureInterface $signature = null)
    {
        parent::__construct($clientCredentials, $signature);
        
        // URL für die test.wikidata.org-Instanz
        $this->baseUri = 'https://test.wikidata.org';
        
        // Endpunkte für OAuth
        $this->requestTokenEndpoint = $this->baseUri . '/w/index.php?title=Special:OAuth/initiate';
        $this->authorizationEndpoint = $this->baseUri . '/w/index.php?title=Special:OAuth/authorize';
        $this->accessTokenEndpoint = $this->baseUri . '/w/index.php?title=Special:OAuth/token';
    }

    /**
     * Get the request token endpoint for the provider.
     *
     * @return string
     */
    public function urlRequestToken()
    {
        return $this->requestTokenEndpoint;
    }

    /**
     * Get the authorization endpoint for the provider.
     *
     * @return string
     */
    public function urlAuthorization()
    {
        return $this->authorizationEndpoint;
    }

    /**
     * Generate the OAuth1 authorization URL.
     *
     * @param TokenCredentials $temporaryCredentials
     * @param array $options Additional options for the URL
     * @return string
     */
    public function getAuthorizationUrl($temporaryCredentials, array $options = [])
    {
        $parameters = [
            'oauth_token' => $temporaryCredentials->getIdentifier(),
        ];

        // Add any custom options
        foreach ($options as $key => $value) {
            $parameters[$key] = $value;
        }

        return $this->urlAuthorization() . '&' . http_build_query($parameters);
    }

    /**
     * Get the access token endpoint for the provider.
     *
     * @return string
     */
    public function urlAccessToken()
    {
        return $this->accessTokenEndpoint;
    }

    /**
     * (Re)formats the response from the server.
     *
     * @param string $body
     * @return array
     */
    protected function parseResponse($body)
    {
        parse_str($body, $data);
        
        return $data;
    }

    /**
     * Get the user information for a token.
     *
     * @param TokenCredentials $tokenCredentials
     * @return array
     */
    public function getUserDetails($tokenCredentials)
    {
        // MediaWiki doesn't support the standard userDetails endpoint
        // We'll need to make custom API calls to get user info
        return [];
    }

    /**
     * Get the user's ID for a token.
     *
     * @param TokenCredentials $tokenCredentials
     * @return string|int
     */
    public function getUserUid($tokenCredentials)
    {
        return '';
    }

    /**
     * Get the user's email for a token.
     *
     * @param TokenCredentials $tokenCredentials
     * @return string
     */
    public function getUserEmail($tokenCredentials)
    {
        return '';
    }

    /**
     * Get the user's screen name for a token.
     *
     * @param TokenCredentials $tokenCredentials
     * @return string
     */
    public function getUserScreenName($tokenCredentials)
    {
        return '';
    }
}