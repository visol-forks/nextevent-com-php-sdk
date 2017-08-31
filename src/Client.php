<?php

namespace NextEvent\PHPSDK;

use GuzzleHttp\Client as HTTPClient;
use GuzzleHttp\Exception\BadResponseException;
use NextEvent\PHPSDK\Exception\APIResponseException;
use NextEvent\PHPSDK\Exception\BasketEmptyException;
use NextEvent\PHPSDK\Exception\InvalidArgumentException;
use NextEvent\PHPSDK\Exception\InvalidStoreException;
use NextEvent\PHPSDK\Exception\MissingDocumentException;
use NextEvent\PHPSDK\Exception\NotAuthenticatedException;
use NextEvent\PHPSDK\Exception\NotAuthorizedException;
use NextEvent\PHPSDK\Exception\OrderItemNotFoundException;
use NextEvent\PHPSDK\Exception\OrderNotFoundException;
use NextEvent\PHPSDK\Model\Basket;
use NextEvent\PHPSDK\Model\Event;
use NextEvent\PHPSDK\Model\Order;
use NextEvent\PHPSDK\Model\Payment;
use NextEvent\PHPSDK\Model\TicketDocument;
use NextEvent\PHPSDK\Model\Token;
use NextEvent\PHPSDK\Rest\Client as RESTClient;
use NextEvent\PHPSDK\Service\IAMClient;
use NextEvent\PHPSDK\Service\PaymentClient;
use NextEvent\PHPSDK\Store\OpcacheStore;
use NextEvent\PHPSDK\Store\StoreInterface;
use NextEvent\PHPSDK\Util\Env;
use NextEvent\PHPSDK\Util\Log\Logger;
use NextEvent\PHPSDK\Util\Widget;
use Psr\Log\LoggerInterface;

/**
 * SDK Client - main class
 *
 * Entry point for the SDK exposing functions to interact with the NextEvent API.
 *
 *
 * ### Booking Process and Payment Process
 *
 * 0. Authorize SDK by creating Client instance with credentials
 * 1. <code>getEvents()</code> List events
 * 2. <code>getWidget($hash)->generateEmbedCode($eventId)</code> Embed widget for specific event.
 * 3. Get orderId from the widget by postMessage.
 * 4. <code>getBasket($orderId)</code> checkout the basket
 * 5. <code>authorizeOrder($orderId)</code> start payment process
 * 6. <code>settlePayment($payment, $customer, $transactionId)</code> settle payment
 * 7. <code>getTicketDocuments($orderId)</code> get TicketDocuments with download uls
 *
 * @package NextEvent\PHPSDK
 */
class Client
{
  /**
   * Options used for creating the Client
   *
   * @var array
   */
  protected $options;
  /**
   * Client internal cache instance
   *
   * @var StoreInterface
   */
  protected $cache;
  /**
   * IAMClient instance used by the Client
   *
   * @var IAMClient
   */
  protected $iamClient;
  /**
   * HTTPClient instance used by the Client
   *
   * @var HTTPClient
   */
  protected $httpClient;
  /**
   * RESTClient instance used by the Client
   *
   * @var RESTClient
   */
  protected $restClient;
  /**
   * PaymentClient instance used by the Client
   *
   * @var PaymentClient
   */
  protected $paymentClient;
  /**
   * Internal LoggerInterface instance
   *
   * @var LoggerInterface
   */
  protected $logger;
  /**
   * Default logger context
   *
   * @var array
   */
  protected $loggerContext = [];


  /**
   * Client constructor.
   *
   * Initializes a Client for using the NextEvent Api.
   *
   * $options[]
   * -  required:
   *   -  'appId'         string NextEvent App Id
   *   -  'appUrl'        string Url to the App
   *   -  'authUsername'  string Username for authentication with IAM
   *   -  'authPassword'  string Password for authentication with IAM
   * -  optional:
   *   -  'env'           string Which NextEvent environment to use: 'PROD', 'INT' or 'TEST'
   *   -  'cache'         StoreInterface Cache instance
   *   -  'logger'        LoggerInterface PSR-3 Logger instance
   *
   * @see StoreInterface
   * @see LoggerInterface
   * @param array $options expects array with appId, appUrl, authUsername, authPassword
   * @throws InvalidArgumentException
   */
  public function __construct($options)
  {
    // check Arguments
    $requiredOptions = ['appId', 'appUrl', 'authUsername', 'authPassword'];
    foreach ($requiredOptions as $optionKey) {
      if (!isset($options[$optionKey])) {
        throw new InvalidArgumentException('Require ' . $optionKey . ' in $options');
      }
    }
    $options['appUrl'] = rtrim($options['appUrl'], '/');
    $this->options = $options;

    // initialize Cache and Logger
    if (isset($options['cache']) && $options['cache'] instanceof StoreInterface) {
      $this->cache = $options['cache'];
    } else {
      $this->cache = new OpcacheStore();
    }
    if (!isset($options['logger'])) {
      $options['logger'] = null;
    }
    $this->logger = Logger::wrapLogger($options['logger'], $this->loggerContext);

    // set ENV
    if (isset($options['env'])) {
      Env::setEnv($options['env']);
    }

    // initialize IAM Client
    $credentials = [
      'name' => $options['authUsername'],
      'password' => $options['authPassword'],
      'scope' => 'identity_info ' . $options['appId']
    ];
    $this->iamClient = new IAMClient($credentials, $this->cache, $this->logger);

    // initialize httpClients
    $httpClientDefaults = [
      'timeout' => 5,
      'headers' => []
    ];
    // reflect user language in SDK requests
    if (Env::getVar('locale')) {
      $httpClientDefaults['headers']['Accept-Language'] = Env::getVar('locale');
    } else if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
      $httpClientDefaults['headers']['Accept-Language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    }
    $this->httpClient = new HTTPClient(
      [
        'base_url' => $this->options['appUrl'],
        'defaults' => $httpClientDefaults
      ]
    );
    $this->paymentClient = new PaymentClient($this->logger);
    $this->restClient = new RESTClient($this->httpClient, $this->logger);
  }


  /**
   * Cache getter
   *
   * @return StoreInterface
   */
  public function getCache()
  {
    return $this->cache;
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
    $this->iamClient->setCache($cache);
  }


  /**
   * Set Logger which will be used for the SDK
   *
   * @param LoggerInterface $logger
   */
  public function setLogger($logger = null)
  {
    $this->logger = Logger::wrapLogger($logger, $this->loggerContext);
  }


  /**
   * Get the internal RESTClient instance
   *
   * @return RESTClient
   */
  public function getRestClient()
  {
    return $this->restClient;
  }


  /**
   * Get the internal IAMClient instance
   *
   * @return IAMClient
   */
  public function getIamClient()
  {
    return $this->iamClient;
  }


  /**
   * Get token for accessing the application api
   *
   * @return Token
   */
  public function getApiToken()
  {
    $this->authenticate();

    return $this->iamClient->getToken();
  }


  /**
   * Authenticate this SDK Client
   *
   * @return bool successfully authenticated
   * @throws NotAuthenticatedException in case client couldn't be authenticated
   */
  public function authenticate()
  {
    $this->logger->debug('Authenticate SDKClient');
    try {
      $token = $this->iamClient->getToken();
      if ($token && !$token->isExpired()) {
        $this->restClient->setAuthorizationHeader($token->getAuthorizationHeader());
        return true;
      } else {
        $this->logger->warning('Authentication Token is expired', []);
        throw new NotAuthenticatedException('Authentication Token is expired');
      }
    } catch (APIResponseException $ex) {
      $this->logger->error('Authentication Token is expired');
      throw new NotAuthenticatedException(
        'Could not authorize, request failed: ' . $ex->getMessage(),
        $ex->getCode(),
        $ex
      );
    }
  }


  /**
   * Fetch all Events available
   *
   * @return Event[]
   * @throws APIResponseException
   */
  public function getEvents()
  {
    $this->authenticate();
    try {
      $response = $this->restClient->get('/jsonld/event');
      $events = $response->getEmbedded()['itemListElement'];
      $this->logger->debug('Fetched events', ['count' => count($events)]);
      return array_map(
        function ($source) {
          return new Event($source);
        },
        $events
      );
    } catch (APIResponseException $ex) {
      $this->logger->error('Failed fetching events', $ex->toLogContext());
      throw $ex;
    }
  }


  /**
   * Fetch single Event by $eventId
   *
   * @param string $eventId
   * @return Event
   * @throws APIResponseException
   */
  public function getEvent($eventId)
  {
    $this->authenticate();
    try {
      $response = $this->restClient->get('/jsonld/event/' . $eventId);
      $event = $response->getEmbedded();
      $this->logger->debug('Fetched event ' . $eventId, ['eventId' => $eventId]);
      return new Event($event);
    } catch (APIResponseException $ex) {
      $this->logger->error('Failed fetching event ' . $eventId, $ex->toLogContext());
      throw $ex;
    }
  }


  /**
   * Fetch Basket by orderId or basketId
   *
   * @param int $orderId
   * @return Basket order data
   * @throws APIResponseException
   * @throws BasketEmptyException
   */
  public function getBasket($orderId)
  {
    $this->authenticate();
    try {
      $response = $this->restClient->get('/basket/' . $orderId);
      $this->logger->debug('Fetched basket', ['orderId' => $orderId]);
      $basket = $response->getEmbedded();
    } catch (APIResponseException $ex) {
      if ($ex->getCode() === 404) {
        throw new BasketEmptyException('Basket does not exist', $ex->getCode(), $ex);
      } else {
        $this->logger->error('Basket could not be fetched', $ex->toLogContext());
        throw $ex;
      }
    }

    if (isset($basket['order_id'])) {
      $basketEntity = new Basket($basket);
      if ($basketEntity->hasBasketItems()) {
        return $basketEntity;
      } else {
        $this->logger->info('Basket is empty', ['orderId' => $orderId]);
        throw new BasketEmptyException('Basket is empty');
      }
    } else {
      $this->logger->info('Basket is empty', ['orderId' => $orderId]);
      throw new BasketEmptyException('Basket is does not exist');
    }
  }


  /**
   * Delete OrderItem from Basket
   *
   * @param int $orderId
   * @return bool successfully deleted
   * @throws APIResponseException
   * @throws OrderNotFoundException if basket could not be found
   */
  public function deleteBasket($orderId)
  {
    $this->authenticate();
    $this->logger->info('Delete basket', ['orderId' => $orderId]);
    try {
      return $this->restClient->delete('/basket/' . $orderId . '/item');
    } catch (APIResponseException $ex) {
      if ($ex->getCode() === 404) {
        $this->logger->error('Failed delete basket', $ex->toLogContext());
        throw new OrderNotFoundException('Order/Basket not found for delete', $ex->getCode(), $ex);
      } else {
        throw $ex;
      }
    }
  }


  /**
   * Delete OrderItem from Basket
   *
   * @param int $orderId
   * @param int $orderItemId
   * @return bool successfully deleted
   * @throws APIResponseException
   * @throws OrderNotFoundException
   */
  public function deleteBasketItem($orderId, $orderItemId)
  {
    $this->authenticate();
    $this->logger->info('Delete basket item', ['orderId' => $orderId, 'orderItemId' => $orderItemId]);
    try {
    return $this->restClient->delete('/basket/' . $orderId . '/item/' . $orderItemId);
    } catch (APIResponseException $ex) {
      if ($ex->getCode() === 404) {
        throw new OrderItemNotFoundException('Order/Basket item not found for delete', $ex->getCode(), $ex);
      } else {
        throw $ex;
      }
    }
  }


  /**
   * Start payment process with authorizing the order
   *
   * @param int $orderId
   * @return Payment invoice data
   * @throws OrderNotFoundException
   * @throws APIResponseException
   */
  public function authorizeOrder($orderId)
  {
    $this->authenticate();
    $this->logger->debug('Authorize Order', ['orderId' => $orderId]);
    $options = [
      'headers' => [
        'Authorization' => $this->iamClient->getToken()->getAuthorizationHeader(
        ),
        'Accept' => 'application/json'
      ],
      'timeout' => 20 // may take longer than 5 seconds
    ];
    try {
      $response = $this->httpClient->post('/checkout/' . $orderId, $options);
      $this->logger->info('Order authorized', ['orderId' => $orderId]);
      return new Payment(json_decode($response->getBody(), true));
    } catch (BadResponseException $ex) {
      if ($ex->getResponse()->getStatusCode() === 404) {
        $this->logger->error('Failed to authorize order', ['orderId' => $orderId, 'errorCode' => $ex->getCode()]);
        throw new OrderNotFoundException($ex);
      } else {
        throw new APIResponseException($ex);
      }
    }
  }


  /**
   * Settle payment
   *
   * $customer example
   *
   * ```json
   * "customer": {
   *  "email": "thomas.muster@example.com",
   *  "name": "Thomas Muster",
   *  "company": "Musterfirma",
   *  "address": {
   *    "street": "Musterstr. 1",
   *    "pobox": "",
   *    "zip": "3001",
   *    "city": "Bern",
   *    "country": "CH"
   *  }
   * }
   * ```
   *
   * @param Payment $payment invoice data
   * @param array   $customer customer data
   * @param null    $transactionId
   * @return array  Hash array with payment transaction data
   */
  public function settlePayment($payment, $customer, $transactionId = null)
  {
    return $this->paymentClient->settlePayment(
      $this->getPaymentToken(),
      $payment,
      $customer,
      $transactionId
    );
  }


  /**
   * Get token for accessing payment service
   *
   * @return Token
   * @throws NotAuthorizedException
   * @throws APIResponseException
   */
  public function getPaymentToken()
  {
    $PAYMENT_TOKEN_KEY = 'payment-token-key';

    // use cached token if available
    $paymentToken = Token::fromString($this->cache->get($PAYMENT_TOKEN_KEY));
    if ($paymentToken && !$paymentToken->isExpired()) {
      $this->logger->debug('Use PaymentToken from cache');
      return $paymentToken;
    }

    $this->authenticate();

    try {
      $response = $this->restClient->post('/payment/token');
      $this->logger->info('Fetched PaymentToken from API', $response->toLogContext());

      $data = $response->getResponse()->json();
      $paymentToken = new Token(
        $data['access_token'], $data['expires_in'], $data['scope'], $data['token_type']
      );

      $this->cache->set($PAYMENT_TOKEN_KEY, $paymentToken->toString());

      return $paymentToken;
    } catch (APIResponseException $ex) {
      $this->logger->error('Failed fetching PaymentToken', $ex->toLogContext());
      if ($ex->getStatusCode() === 401) {
        throw new NotAuthorizedException($ex);
      }
      throw $ex;
    }
  }


  /**
   * Abort payment
   *
   * @param Payment $payment invoice data
   * @param string  $reason why the payment is aborted
   * @return bool
   */
  public function abortPayment($payment, $reason)
  {
    return $this->paymentClient->abortPayment(
      $this->getPaymentToken(),
      $payment,
      $reason
    );
  }


  /**
   * Get TicketDocuments from orderId
   *
   * When the order is paid, tickets are generated automatically and can be
   * downloaded.
   *
   * **Attention**
   * By default tickets from the same Event are merged into one single document.
   *
   * @param int $orderId ID of the order record to fetch tickets for
   * @param int $waitFor Number of seconds to wait for tickets to be issued
   * @return TicketDocument[]
   * @throws MissingDocumentException
   */
  public function getTicketDocuments($orderId, $waitFor=0)
  {
    $timeout = microtime(true) + $waitFor - 0.35;
    $order = $this->getOrder($orderId, 'tickets,document');

    // repeat fetching if tickets are not yet available
    while (microtime(true) < $timeout && !$order->allTicketsIssued()) {
      usleep(300000);
      $order = $this->getOrder($orderId, 'tickets,document');
    }

    if (!$order->allTicketsIssued()) {
      $this->logger->info('Ticket documents not issued yet', ['orderId' => $orderId]);
      throw new MissingDocumentException('Ticket documents not yet issued for order ' . $orderId);
    }

    $used = [];
    $documents = [];

    foreach ($order->getTickets() as $ticket) {
      $document = $ticket->getDocument();
      $url = $document->getDownloadUrl();
      if (!isset($used[$url])) {
        $documents[] = $document;
        $used[$url] = 1;
      }
    }

    return $documents;
  }


  /**
   * Get full order
   *
   * use order->invoice->status == 'paid' for checking invoice status
   *
   * @param int $orderId
   * @param string $embed
   * @return Order Order model
   * @throws OrderNotFoundException
   * @throws APIResponseException
   */
  public function getOrder($orderId, $embed = 'tickets,document')
  {
    $this->authenticate();
    try {
      $query = $embed ? '?_embed=tickets,document' : '';
      $response = $this->restClient->get('/order/' . $orderId . $query);
      $this->logger->debug('Order fetched', ['orderId' => $orderId]);
      $order = new Order($response->getEmbedded());
      $order->setRestClient($this->restClient);
      return $order;
    } catch (APIResponseException $ex) {
      if ($ex->getCode() === 404) {
        $this->logger->error('Order not found', array_merge(['orderId' => $orderId], $ex->toLogContext()));
        throw new OrderNotFoundException('Order not found', $ex->getCode(), $ex);
      }
      throw $ex;
    }
  }


  /**
   * Returns a widget object with utility functions for embedding a NextEvent Widget
   *
   * @param string $hash The hash of the widget to be embedded
   * @return Widget
   * @throws InvalidArgumentException
   */
  public function getWidget($hash)
  {
    if (!isset($hash)) {
      throw new InvalidArgumentException('Required $hash of the widget');
    }
    return new Widget($this->options['appUrl'], $hash);
  }
}
