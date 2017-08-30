<?php

namespace NextEvent\PHPSDK\Exception;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\Response;
use NextEvent\PHPSDK\Util\Log\LogContextInterface;

/**
 * Generic Exception encapsulating an API error response
 *
 * Thrown when a request to the NextEvent API failed with an error response.
 * The exception provides extensive information about the request sent, the
 * response received and the reasons which it didn't succeed.
 *
 * Use the toLogContext() or dumpAsString() methods for logging.
 *
 * @package NextEvent\PHPSDK\Exception
 */
class APIResponseException extends \Exception implements LogContextInterface
{
  /**
   * @var int
   */
  protected $code;

  /**
   * @var Request
   */
  protected $request;

  /**
   * @var Response
   */
  protected $response;

  /**
   * @var string
   */
  protected $description;

  /**
   * @var string
   */
  protected $reason;

  /**
   * @var string
   */
  protected $request_id;


  /**
   * Exception constructor
   *
   * @param string|\Exception $message Error message or source exception
   * @param int $code Error code
   * @param \Exception $ex Previous exception
   */
  public function __construct($message, $code = 0, $ex = null)
  {
    if ($message instanceof \Exception) {
      $ex = $message;
      $message = $ex->getMessage();
      $code = $ex->getCode();
    }

    $this->code = $code;

    // wrap BadResponseException for better error reporting
    if ($ex instanceof BadResponseException) {
      $this->request = $ex->getRequest();
      $this->response = $ex->getResponse();
      $this->request_id = $this->getRequestID();

      try {
        $response_data = $this->response->json();
        if (isset($response_data['description'])) {
          $this->description = $response_data['description'];
        } else if (isset($response_data['detail'])) {
          $this->description = $response_data['detail'];
        }
        if (isset($response_data['reason'])) {
          $this->reason = $response_data['reason'];
        }
      } catch (\Exception $ex) {
        // ignore json error if response body doesn't contain any json
      }

      $message = 'APIResponseException: ' . $message . ($this->request_id ? ' [request id] ' . $this->request_id : '') . ($this->description ? ' [description] ' . $this->description : '') . ($this->reason ? ' [reason] ' . $this->reason : '');
    }

    parent::__construct($message, $code, $ex);
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
    return isset($this->response) ? $this->response->getHeader('x-request-id') : null;
  }


  /**
   * Get HTTP response status code
   *
   * @return int|null|string
   */
  public function getStatusCode()
  {
    return isset($this->response) ? $this->response->getStatusCode() : $this->code;
  }


  /**
   * Get the error description provided by the API
   *
   * @return string
   */
  public function getDescription()
  {
    return $this->description;
  }


  /**
   * Get the sent HTTP request message
   *
   * @return Request
   */
  public function getRequest()
  {
    return $this->request;
  }


  /**
   * Get the received HTTP response message
   *
   * @return Response
   */
  public function getResponse()
  {
    return $this->response;
  }


  /**
   * For convenience provide function to convert Exception to Context for logs
   *
   * @return array
   */
  public function toLogContext()
  {
    $context = ['code' => $this->getCode(), 'message' => $this->getMessage()];

    if ($this->request) {
      $context['requestURL'] = $this->getRequest()->getUrl();
      $context['requestMethod'] = $this->getRequest()->getMethod();
    }

    if ($this->response) {
      $context['requestId'] = $this->getRequestID();
    };

    return $context;
  }


  /**
   * Returns a full string representation of this exception
   *
   * Including HTTP request, response and stack trace (optional)
   *
   * @param boolean $withStackTrace Append stack trace to output
   * @return string
   */
  public function dumpAsString($withStackTrace = false)
  {
    $blocks = [$this->getMessage()];

    if ($this->request instanceof Request) {
      $blocks[] = strval($this->request);
    }
    if ($this->response instanceof Response) {
      $blocks[] = strval($this->response);
    }
    if ($withStackTrace) {
      $blocks = $this->getTraceAsString();
    }

    return join("\n\n", array_filter($blocks));
  }

  /**
   * Produces a string representation of this object
   *
   * @return string
   */
  public function __toString()
  {
    return $this->getMessage();
  }
}
