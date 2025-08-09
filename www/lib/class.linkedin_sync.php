<?php

/**
 * LinkedInSync handles OAuth authentication and contact synchronization
 * with the LinkedIn API.
 */
class LinkedInSync
{
    /** @var \PDO|mixed */
    protected $db;
    /** @var string */
    protected $clientId;
    /** @var string */
    protected $clientSecret;
    /** @var string */
    protected $redirectUri;

    public function __construct($db, $clientId, $clientSecret, $redirectUri)
    {
        $this->db = $db;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
    }

    /**
     * Build authorization URL for LinkedIn OAuth.
     *
     * @param string $state
     *
     * @return string
     */
    public function getAuthorizationUrl($state)
    {
        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
            'scope' => 'r_liteprofile r_emailaddress w_member_social',
        ]);
        return 'https://www.linkedin.com/oauth/v2/authorization?' . $params;
    }

    /**
     * Exchange authorization code for access token.
     *
     * @param string $code
     *
     * @return array|null
     */
    public function fetchAccessToken($code)
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query([
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $this->redirectUri,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ]),
                'timeout' => 20,
            ],
        ]);
        $response = @file_get_contents('https://www.linkedin.com/oauth/v2/accessToken', false, $context);
        if ($response === false) {
            return null;
        }
        $data = json_decode($response, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Synchronize contacts from LinkedIn into the local address table.
     * The $accessToken must be a valid OAuth token.
     *
     * @param string $accessToken
     */
    public function syncContacts($accessToken)
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Authorization: Bearer ' . $accessToken,
                'timeout' => 20,
            ],
        ]);
        $response = @file_get_contents('https://api.linkedin.com/v2/me', false, $context);
        if ($response === false) {
            return;
        }
        $profile = json_decode($response, true);
        if (!is_array($profile)) {
            return;
        }
        $name = $this->db->real_escape_string($profile['localizedFirstName'] . ' ' . $profile['localizedLastName']);
        $this->db->Insert("INSERT INTO adresse (name) VALUES ('{$name}')");
    }
}
