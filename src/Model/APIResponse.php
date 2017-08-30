<?php

namespace NextEvent\PHPSDK\Model;

use GuzzleHttp\Message\ResponseInterface;
use NextEvent\PHPSDK\Exception\InvalidArgumentException;
use NextEvent\PHPSDK\Util\Log\LogContextInterface;

/**
 * Class APIResponse
 *
 * A wrapper class for responses returned by GuzzleHttp.
 * Adds utility functions used by the SDK
 *
 * @package NextEvent\PHPSDK\Model
 */
class APIResponse implements LogContextInterface
{
  /**
   * @var ResponseInterface
   */
  protected $response;

  /**
   * @var array
   */
  protected $content;


  /**
   * APIResponse constructor.
   *
   * Wrapping a ResponseInterface for use in SDK
   *
   * @param ResponseInterface $response
   * @throws InvalidArgumentException
   */
  public function __construct($response)
  {
    if (!($response instanceof ResponseInterface)) {
      throw new InvalidArgumentException('Expect a ResponseInterface as argument');
    }

    $this->response = $response;
  }


  /**
   * Get the underlying HTTP response message
   *
   * @return ResponseInterface
   */
  public function getResponse()
  {
    return $this->response;
  }


  /**
   * Get JSON content from Request
   *
   * @return array
   */
  public function getContent()
  {
    if ($this->content === null) {
      $this->content = json_decode($this->response->getBody(), true);
    }
    return $this->content;
  }


  /**
   * Get the unique identifier of the API request
   *
   * Use this in support inquiries to let us track the request in our logs
   *
   * @return string
   */
  public function getRequestID()
  {
    return $this->response->getHeader('x-request-id');
  }


  /**
   * Return object representation for log context
   *
   * @return array
   */
  public function toLogContext()
  {
    $context = [
      'url' => $this->response->getEffectiveUrl(),
      'statusCode' => $this->response->getStatusCode(),
      'requestId' => $this->getRequestID(),
    ];
    return $context;
  }
}
