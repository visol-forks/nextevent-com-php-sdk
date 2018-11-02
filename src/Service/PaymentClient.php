<?php

namespace NextEvent\PHPSDK\Service;

use GuzzleHttp\Client as HTTPClient;
use GuzzleHttp\Exception\ClientException;
use NextEvent\PHPSDK\Exception\APIResponseException;
use NextEvent\PHPSDK\Exception\InvalidArgumentException;
use NextEvent\PHPSDK\Exception\PaymentNotFoundException;
use NextEvent\PHPSDK\Model\APIResponse;
use NextEvent\PHPSDK\Model\Payment;
use NextEvent\PHPSDK\Model\Token;
use NextEvent\PHPSDK\Util\Env;
use NextEvent\PHPSDK\Util\Log\Logger;
use Psr\Log\LoggerInterface;

/**
 *
 * Utility class for connecting to NextEvent's Payment Service API
 *
 * @internal
 * @package NextEvent\PHPSDK\Service
 */
class PaymentClient
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
   * PaymentClient constructor
   *
   * @param LoggerInterface $logger
   */
  public function __construct($logger)
  {
    $this->logger = Logger::wrapLogger($logger);

    $verify = true;
    if (Env::getEnv() === 'TEST' || Env::getEnv() === 'DEV') {
      $verify = false;
    }

    $httpClientDefaults = [
      'headers' => ['Accept' => 'application/json'],
      'timeout' => 10,
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
        'base_uri' => Env::getVar('payment_service_url'),
        'defaults' => $httpClientDefaults
      ]
    );
  }


  /**
   * Settle payment
   *
   * Confirm successful payment for the previously requested authorization
   *
   * @param Token   $paymentToken Payment service access token
   * @param Payment $payment Payment authorization object
   * @param array   $customer customer information
   * @param string  $transactionId Reference transaction-id if you have one
   * @return array  Hash array with Payment transaction data
   * @throws PaymentNotFoundException
   * @throws APIResponseException
   * @throws \Exception
   */
  public function settlePayment(
    $paymentToken,
    $payment,
    $customer,
    $transactionId = null
  )
  {
    if (!($payment instanceof Payment && $payment->isValid() && !$payment->isExpired())) {
      throw new InvalidArgumentException('require $payment argument to be instance of \NextEvent\PHPSDK\Model\Payment');
    }

    $options = [
      'headers' => ['Authorization' => $paymentToken->getAuthorizationHeader()],
      'json' => [
        'uuid' => $payment->getUUID(),
        'reference' => $payment->getReference(),
        'authorization' => $payment->getAuthorization(),
        'status' => 'settled',
        'transaction-id' => $transactionId,
        'customer' => $customer
      ]
    ];

    try {
      $response = $this->httpClient->post('/payment/ipn/external', $options);
      if ($response->getStatusCode() !== 200)
        throw new APIResponseException('Unexpected response', $response->getStatusCode());

      $apiResponse = new APIResponse($response);
      $this->logger->info('Payment successfully settled',
        array_merge(
          ['invoiceUUID' => $payment->getUUID(), 'transactionId' => $transactionId, 'result' => $apiResponse->getContent()],
          $apiResponse->toLogContext()
        )
      );
      return (array)$apiResponse->getContent() + ['requestId' => $apiResponse->getRequestID()];
    } catch (ClientException $ex) {
      if ($ex->getResponse()->getStatusCode() === 404) {
        $this->logger->error('Payment settlement failed: PaymentNotFound', ['invoiceUUID' => $payment->getUUID(), 'transactionId' => $transactionId]);
        throw new PaymentNotFoundException($ex);
      } else {
        $this->logger->error('Payment settlement failed', ['invoiceUUID' => $payment->getUUID(), 'transactionId' => $transactionId, 'exception' => $ex->getMessage()]);
        throw new APIResponseException($ex);
      }
    }
  }


  /**
   * Abort payment with reason
   *
   * @param Token   $paymentToken Payment service access token
   * @param Payment $payment Payment authorization object
   * @param string  $reason Reason why payment is aborted
   * @return bool True if service accepted the cancellation
   * @throws PaymentNotFoundException
   * @throws APIResponseException
   * @throws \Exception
   */
  public function abortPayment($paymentToken, $payment, $reason)
  {
    if (!($payment instanceof Payment && $payment->isValid())) {
      throw new InvalidArgumentException('require $payment argument to be instance of \NextEvent\PHPSDK\Model\Payment');
    }

    $options = [
      'headers' => ['Authorization' => $paymentToken->getAuthorizationHeader()],
      'json' => [
        'uuid' => $payment->getUUID(),
        'reference' => $payment->getReference(),
        'authorization' => $payment->getAuthorization(),
        'status' => 'aborted',
        'reason' => $reason
      ]
    ];

    try {
      $response = $this->httpClient->post('/payment/ipn/external', $options);
      $success = $response->getStatusCode() === 200;
      $apiResponse = new APIResponse($response);
      $this->logger->info(
        $success ? 'Payment aborted' : 'Payment not aborted',
        array_merge(
          ['success' => $success, 'invoiceUUID' => $payment->getUUID(), 'result' => $apiResponse->getContent()],
          $apiResponse->toLogContext()
        )
      );
      return $success;
    } catch (ClientException $ex) {
      if ($ex->getResponse()->getStatusCode() === 404) {
        $this->logger->error('Payment abortion failed: PaymentNotFound', ['invoiceUUID' => $payment->getUUID()]);
        throw new PaymentNotFoundException($ex);
      } else {
        $this->logger->error('Payment abortion failed', ['invoiceUUID' => $payment->getUUID(), 'exception' => $ex->getMessage()]);
        throw new APIResponseException($ex);
      }
    }
  }
}
