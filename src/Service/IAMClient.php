<?php

namespace NextEvent\PHPSDK\Service;

use GuzzleHttp\Client as HTTPClient;
use GuzzleHttp\Exception\BadResponseException;
use NextEvent\PHPSDK\Exception\APIResponseException;
use NextEvent\PHPSDK\Exception\InvalidStoreException;
use NextEvent\PHPSDK\Model\APIResponse;
use NextEvent\PHPSDK\Model\Token;
use NextEvent\PHPSDK\Store\StoreInterface;
use NextEvent\PHPSDK\Util\Env;
use NextEvent\PHPSDK\Util\Log\Logger;
use Psr\Log\LoggerInterface;

/**
 * Client for the IAM service API
 *
 * ```php
 * // Get AccessToken by IAM
 * <?php
 * use NextEvent\PHPSDK\Client;
 * use NextEvent\PHPSDK\Auth\IAMClient;
 *
 * $credentials = ['user', 'password', 'scope']; // Your Credentials
 *
 * $client = new Client($appUrl, $credentials, $widgetHash);
 *
 * // stand alone
 * $iam_client = new IAMClient($credentials, $cache);
 * $token = $iam_client->getToken();
 * ```
 *
 * With the Token you can authorize requests to the NextEvent application by
 * setting the <code>Authorization</code> Header.
 *
 * ```php
 * $http->get(
 *   $url,
 *   ['headers' => [
 *     'Authorization' => $token->getAuthorizationHeader()
 *   ]]);
 * ```
 *
 * @package NextEvent\PHPSDK\Service
 */
class IAMClient
{
  const IAM_TOKEN_KEY = 'iam-client-token';
  /**
   * credentials used authenticate with IAM Service
   * @var array
   */
  protected $credentials;
  /**
   * scope for client
   * @var string
   */
  protected $iamScope;
  /**
   * @var HTTPClient
   */
  protected $httpClient;
  /**
   * @var StoreInterface
   */
  protected $cache;
  /**
   * @var LoggerInterface
   */
  protected $logger;


  /**
   * IAMClient constructor.
   *
   * @param array           $credentials
   * @param StoreInterface  $cache
   * @param LoggerInterface $logger
   */
  public function __construct($credentials, $cache, $logger)
  {
    $this->credentials = [$credentials['name'], $credentials['password']];
    $this->iamScope = $credentials['scope'];
    $this->cache = $cache;
    $this->logger = Logger::wrapLogger($logger);

    $verify = true;
    if (Env::getEnv() === 'TEST' || Env::getEnv() === 'DEV') {
      $verify = false;
    }

    $httpClientDefaults = [
      'headers' => ['Accept' => 'application/json'],
      'timeout' => 2,
      'verify' => $verify
    ];
    // reflect user language in SDK requests
    if (Env::getVar('locale')) {
      $httpClientDefaults['headers']['Accept-Language'] = Env::getVar('locale');
    } else if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
      $httpClientDefaults['headers']['Accept-Language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    }

    $this->httpClient = new HTTPClient(
      [
        'base_url' => Env::getVar('iam_service_url'),
        'defaults' => $httpClientDefaults
      ]
    );
  }

  /**
   * Cache setter
   *
   * @param StoreInterface $cache
   * @throws InvalidStoreException
   */
  public function setCache($cache)
  {
    if (!($cache instanceof StoreInterface)) {
      throw new InvalidStoreException();
    }
    $this->cache = $cache;
  }

  /**
   * Authenticate with IAM and get Token
   *
   * @return Token|null
   * @throws APIResponseException
   */
  public function getToken()
  {
    // use cached token if available
    $token = Token::fromString($this->cache->get(self::IAM_TOKEN_KEY));
    if ($token && !$token->isExpired()) {
      $this->logger->debug('Use cached IAMToken');
      return $token;
    }

    // request new token
    $options = [
      'body' => [
        'grant_type' => 'client_credentials',
        'scope' => $this->iamScope
      ],
      'auth' => $this->credentials
    ];

    try {
      $response = $this->httpClient->post('/oauth/basic', $options);
      $data = json_decode($response->getBody(), true);

      $token = new Token(
        $data['access_token'],
        $data['expires_in'],
        $data['scope'],
        $data['token_type']
      );

      $this->logger->info('Use fetched IAMToken from IAMService', (new APIResponse($response))->toLogContext());

      // cache token
      $this->cache->set(self::IAM_TOKEN_KEY, $token->toString());
      return $token;
    } catch (BadResponseException $ex) {
      $responseException = new APIResponseException($ex);
      $this->logger->error('the IAM Client failed fetching a new token', $responseException->toLogContext());
      throw $responseException;
    }
  }


  /**
   * Forces the IAM Client to fetch a new token.
   *
   * @return Token|null
   */
  public function getNewToken()
  {
    // clear cached token
    $this->cache->set(self::IAM_TOKEN_KEY, null);
    $this->logger->info('Force IAM Client to fetch a new token');
    return $this->getToken();
  }
}
