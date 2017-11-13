<?php

namespace NextEvent\PHPSDK\Rest;

use GuzzleHttp\Client as HTTPClient;
use GuzzleHttp\Exception\RequestException;
use NextEvent\PHPSDK\Exception\APIResponseException;
use NextEvent\PHPSDK\Model\APIResponse;
use NextEvent\PHPSDK\Model\HALResponse;
use NextEvent\PHPSDK\Util\Log\Logger;
use Psr\Log\LoggerInterface;

/**
 * Utility class for connecting to a REST API
 *
 * @internal
 * @package NextEvent\PHPSDK\REST
 */
class Client
{
  /**
   * @var HTTPClient
   */
  protected $httpClient;
  /**
   * @var LoggerInterface
   */
  protected $logger;
  /**
   * @var string
   */
  protected $authorizationHeader;


  /**
   * Client constructor
   *
   * Wraps HTTP client adding REST-specific headers and handling
   * response or errors.
   *
   * @param HTTPClient      $httpClient
   * @param LoggerInterface $logger optional logger
   */
  public function __construct($httpClient, $logger = null)
  {
    $this->httpClient = $httpClient;
    $this->logger = Logger::wrapLogger($logger);
  }


  /**
   * Setter for the Authorization header to be used in HTTP requests
   *
   * @param string $header
   * @return self
   */
  public function setAuthorizationHeader($header)
  {
    $this->authorizationHeader = $header;
    return $this;
  }


  /**
   * Send a GET request to $url
   *
   * @param string $url
   * @param string $authorizationHeader
   * @return HALResponse
   * @throws APIResponseException
   */
  public function get($url, $authorizationHeader = null)
  {
    $options = ['headers' => ['Accept' => 'application/json']];
    if ($authorizationHeader) {
      $options['headers']['Authorization'] = $authorizationHeader;
    } else if ($this->authorizationHeader) {
      $options['headers']['Authorization'] = $this->authorizationHeader;
    }
    try {
      $response = $this->httpClient->get($url, $options);
      $hal = new HALResponse($response);
      $this->logger->debug('REST GET', (new APIResponse($response))->toLogContext());
      return $hal;
    } catch (RequestException $ex) {
      $ex = new APIResponseException($ex);
      $this->logger->error('Rest request failed', $ex->toLogContext());
      throw $ex;
    }
  }


  /**
   * Send a POST request to $url
   *
   * @param string $url request url
   * @param array  $payload optional, send payload as json
   * @param string $authorizationHeader optional, authorizationHeader
   * @return HALResponse
   * @throws APIResponseException
   */
  public function post($url, $payload = null, $authorizationHeader = null)
  {
    $options = ['headers' => ['Accept' => 'application/json']];
    if ($authorizationHeader) {
      $options['headers']['Authorization'] = $authorizationHeader;
    } else if ($this->authorizationHeader) {
      $options['headers']['Authorization'] = $this->authorizationHeader;
    }

    if (isset($payload) && is_array($payload)) {
      $options['json'] = $payload;
    }

    $options['timeout'] = 0;

    try {
      $response = $this->httpClient->post($url, $options);
      $hal = new HALResponse($response);
      $this->logger->debug('REST POST', (new APIResponse($response))->toLogContext());
      return $hal;
    } catch (RequestException $ex) {
      $ex = new APIResponseException($ex);
      $this->logger->error('Rest request failed', $ex->toLogContext());
      throw $ex;
    }
  }


  /**
   * Send a PUT request to $url
   *
   * @param string $url request url
   * @param array  $payload optional, send payload as json
   * @param string $authorizationHeader optional, authorizationHeader
   * @return HALResponse
   * @throws APIResponseException
   */
  public function put($url, $payload = null, $authorizationHeader = null)
  {
    $options = ['headers' => ['Accept' => 'application/json']];
    if ($authorizationHeader) {
      $options['headers']['Authorization'] = $authorizationHeader;
    } else if ($this->authorizationHeader) {
      $options['headers']['Authorization'] = $this->authorizationHeader;
    }

    if (isset($payload) && is_array($payload)) {
      $options['json'] = $payload;
    }

    try {
      $response = $this->httpClient->put($url, $options);
      $hal = new HALResponse($response);
      $this->logger->debug('REST PUT', (new APIResponse($response))->toLogContext());
      return $hal;
    } catch (RequestException $ex) {
      $ex = new APIResponseException($ex);
      $this->logger->error('Rest request failed', $ex->toLogContext());
      throw $ex;
    }
  }


  /**
   * Send a DELETE request to $url
   *
   * @param string $url
   * @param string $authorizationHeader
   * @return bool success
   * @throws APIResponseException
   */
  public function delete($url, $authorizationHeader = null)
  {
    $options = ['headers' => ['Accept' => 'application/json']];
    if ($authorizationHeader) {
      $options['headers']['Authorization'] = $authorizationHeader;
    } else if ($this->authorizationHeader) {
      $options['headers']['Authorization'] = $this->authorizationHeader;
    }
    try {
      $response = $this->httpClient->delete($url, $options);
      $this->logger->debug('REST DELETE', (new APIResponse($response))->toLogContext());
      return $response->getStatusCode() >= 200 && $response->getStatusCode() < 299;
    } catch (RequestException $ex) {
      $ex = new APIResponseException($ex);
      $this->logger->error('Rest request failed', $ex->toLogContext());
      throw $ex;
    }
  }
}
