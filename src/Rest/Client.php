<?php

namespace NextEvent\PHPSDK\Rest;

use GuzzleHttp\Client as HTTPClient;
use GuzzleHttp\Exception\RequestException;
use NextEvent\PHPSDK\Exception\APIResponseException;
use NextEvent\PHPSDK\Exception\InvalidArgumentException;
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
    return $this->sendHttp('GET', $url, $authorizationHeader);
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
    $options = $this->getRequestOptions($authorizationHeader);

    if (isset($payload) && is_array($payload)) {
      $options['json'] = $payload;
    }

    $options['timeout'] = 0;

    return $this->sendHttp('POST', $url, $options);
  }


  /**
   * Send a PUT request to $url
   *
   * @param string $url request url
   * @param array  $payload send payload as json
   * @param string $authorizationHeader optional, authorizationHeader
   * @return HALResponse
   * @throws APIResponseException
   */
  public function put($url, $payload, $authorizationHeader = null)
  {
    $options = $this->getRequestOptions($authorizationHeader);

    if (isset($payload) && is_array($payload)) {
      $options['json'] = $payload;
    } else {
      throw new InvalidArgumentException('Invalid $payload argument supplied. Hash array expected');
    }

    return $this->sendHttp('PUT', $url, $options);
  }


  /**
   * Send a PATCH request to $url
   *
   * @param string $url request url
   * @param array  $payload send payload as json
   * @param string $authorizationHeader optional, authorizationHeader
   * @return HALResponse
   * @throws APIResponseException
   */
  public function patch($url, $payload, $authorizationHeader = null)
  {
    $options = $this->getRequestOptions($authorizationHeader);

    if (isset($payload) && is_array($payload)) {
      $options['json'] = $payload;
    } else {
      throw new InvalidArgumentException('Invalid $payload argument supplied. Hash array expected');
    }

    return $this->sendHttp('PATCH', $url, $options);
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
    return $this->sendHttp('DELETE', $url, $authorizationHeader);
  }


  /**
   * Wrapper for GuzzleHttp\Client::request()
   * 
   * @param string $method HTTP request method
   * @param string $url The request url
   * @param array|string $optionsOrAuthorization Hash array with request options like `headers` and `json` payload or authorization header value
   * @return HALResponse|bool
   * @throws APIResponseException
   */
  protected function sendHttp($method, $url, $optionsOrAuthorization = null)
  {
    if (is_array($optionsOrAuthorization)) {
      $options = $optionsOrAuthorization;
    } else {
      $options = $this->getRequestOptions($optionsOrAuthorization);
    }

    try {
      $response = $this->httpClient->request($method, $url, $options);
      $this->logger->debug('REST ' . $method, (new APIResponse($response))->toLogContext());

      // return boolean value for DELETE requests
      if ($method === 'DELETE') {
        return $response->getStatusCode() >= 200 && $response->getStatusCode() < 299;
      }

      // parse response into a HAL response
      return new HALResponse($response);
    } catch (RequestException $ex) {
      $ex = new APIResponseException($ex);
      $this->logger->error("REST ${method} request failed", $ex->toLogContext());
      throw $ex;
    }
  }


  /**
   * Helper method to compose HTTP request headers
   *
   * @param string $authorizationHeader
   * @return array
   */
  protected function getRequestOptions($authorizationHeader = null)
  {
    $options = ['headers' => ['Accept' => 'application/json']];
    if (!empty($authorizationHeader)) {
      $options['headers']['Authorization'] = $authorizationHeader;
    } else if ($this->authorizationHeader) {
      $options['headers']['Authorization'] = $this->authorizationHeader;
    }

    return $options;
  }
}
