<?php

namespace NextEvent\PHPSDK;

use GuzzleHttp\Client as HTTPClient;
use NextEvent\PHPSDK\Exception\AccessCodesNotFoundException;
use NextEvent\PHPSDK\Exception\APIResponseException;
use NextEvent\PHPSDK\Exception\BasketEmptyException;
use NextEvent\PHPSDK\Exception\DeviceNotFoundException;
use NextEvent\PHPSDK\Exception\InvalidArgumentException;
use NextEvent\PHPSDK\Exception\InvalidModelDataException;
use NextEvent\PHPSDK\Exception\InvalidStoreException;
use NextEvent\PHPSDK\Exception\GateNotFoundException;
use NextEvent\PHPSDK\Exception\MissingDocumentException;
use NextEvent\PHPSDK\Exception\NotAuthenticatedException;
use NextEvent\PHPSDK\Exception\NotAuthorizedException;
use NextEvent\PHPSDK\Exception\OrderItemNotFoundException;
use NextEvent\PHPSDK\Exception\OrderNotFoundException;
use NextEvent\PHPSDK\Exception\ScanLogsNotFoundException;
use NextEvent\PHPSDK\Model\AccessCode;
use NextEvent\PHPSDK\Model\BaseCategory;
use NextEvent\PHPSDK\Model\BasePrice;
use NextEvent\PHPSDK\Model\Gate;
use NextEvent\PHPSDK\Model\Category;
use NextEvent\PHPSDK\Model\Collection;
use NextEvent\PHPSDK\Model\Device;
use NextEvent\PHPSDK\Model\ScanLog;
use NextEvent\PHPSDK\Model\Basket;
use NextEvent\PHPSDK\Model\Event;
use NextEvent\PHPSDK\Model\Order;
use NextEvent\PHPSDK\Model\Payment;
use NextEvent\PHPSDK\Model\Price;
use NextEvent\PHPSDK\Model\TicketDocument;
use NextEvent\PHPSDK\Model\Token;
use NextEvent\PHPSDK\Model\CancellationRequest;
use NextEvent\PHPSDK\Rest\Client as RESTClient;
use NextEvent\PHPSDK\Service\IAMClient;
use NextEvent\PHPSDK\Service\PaymentClient;
use NextEvent\PHPSDK\Store\OpcacheStore;
use NextEvent\PHPSDK\Store\StoreInterface;
use NextEvent\PHPSDK\Util\Env;
use NextEvent\PHPSDK\Util\Filter;
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
 * 2. <code>getWidget($hash)->generateEmbedCode($eventId)</code> Embed widget for specific event
 * 3. Get orderId from the widget by postMessage
 * 4. <code>getBasket($orderId)</code> checkout the basket
 * 5. <code>authorizeOrder($orderId)</code> start payment process
 * 6. <code>settlePayment($payment, $customer, $transactionId)</code> settle payment
 * 7. <code>getTicketDocuments($orderId)</code> get TicketDocuments with download urls
 *
 * ### Rebooking
 *
 * 1. <code>rebookOrder()</code> create rebooking basket
 * 2. <code>getWidget($hash)->generateEmbedCode(['basket' => $basket])</code> Embed widget with rebooking basket.
 * 3. Complete order as in regular booking process
 *
 * ### Cancellation
 *
 * 1. <code>requestCancellation()</code> request cancellation authorization for a given order
 * 2. <code>settleCancellation()</code> settle cancellation with the authorization data
 *
 * ### Entrance check information
 *
 * 1. <code>getAccessCodes($filter)</code> get a collection of AccessCodes
 * 2. <code>getGate($gateId)</code> get a Gate
 * 3. <code>getGates($filter)</code> get a collection Gates
 * 4. <code>getDevice($deviceId)</code> get a Device
 * 5. <code>getDevices($filter)</code> get a collection of Devices
 * 6. <code>getScanLogs($filter)</code> get a collection of ScanLogs
 *
 * ### Price and category information
 *
 * 1. <code>getBaseCategories($filter)</code> get a collection of BaseCategories
 * 2. <code>getCategories($filter)</code> get a collection of Categories
 * 3. <code>getBasePrices($filter)</code> get a collection of BasePrices
 * 4. <code>getPrices($filter)</code> get a collection of Prices
 *
 * ### Persistance
 *
 * 1. <code>createEvent($event)</code> create a new Event
 * 2. <code>createBaseCategory($categories)</code> create new BaseCategories and Categories
 * 3. <code>createBasePrice($prices)</code> create new BasePrices and Prices
 * 4. <code>updateBaseCategory($categories)</code> update BaseCategories and its Categories
 * 5. <code>updateBasePrice($prices)</code> update BasePrices and its Prices
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
   * Define constants used as key for accessing the cache
   */
  const PAYMENT_TOKEN_KEY = 'sdk:paymentTokenKey';


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

    // generate version from options
    $omitKeys = ['logger' => null, 'cache' => null];
    $optionsVersion = hash('crc32', serialize(array_diff_key($options, $omitKeys)));

    // initialize Cache and Logger
    if (isset($options['cache']) && $options['cache'] instanceof StoreInterface) {
      $this->cache = $options['cache'];
    } else {
      // don't use same cache for different options
      $this->cache = new OpcacheStore($optionsVersion);
    }

    if (!isset($options['logger'])) {
      $options['logger'] = null;
    }
    $this->logger = Logger::wrapLogger($options['logger'], $this->loggerContext);

    // set ENV by options
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
      'timeout' => 10,
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
   * @throws APIResponseException in case fetching the Token failed
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
        $this->logger->warning('Authentication Token is expired');
        throw new NotAuthenticatedException('Authentication Token is expired');
      }
    } catch (APIResponseException $ex) {
      $this->logger->error('Could not authenticate SDK Client with IAM', $ex->toLogContext());
      throw new NotAuthenticatedException(
        'Could not authenticate SDK Client, request failed: ' . $ex->getMessage(),
        $ex->getCode(),
        $ex
      );
    }
  }


  /**
   * Do $method request. In case of 401 Unauthorized, retry request with new token
   *
   * @internal
   * @param string $method one of get, post, delete
   * @param string $url request url
   * @param array  $options optional request payload
   * @param bool   $retry retry as long as true
   * @return Model\HALResponse|bool
   * @throws APIResponseException
   * @throws InvalidArgumentException
   */
  protected function authenticatedRequest($method, $url, $options = array(), $retry = true)
  {
    $this->authenticate();
    try {
      switch ($method) {
        case 'get':
          return $this->restClient->get($url);
        case 'post':
          return $this->restClient->post($url, $options);
        case 'put':
          return $this->restClient->put($url, $options);
        case 'delete':
          return $this->restClient->delete($url);
        default:
          throw new InvalidArgumentException('Requires $method argument to be one of [get, post, delete]');
      }
    } catch (APIResponseException $ex) {
      if ($retry && $ex->getCode() === 401) {
        $this->logger->warning('Retry ' . $method . ' ' . $url . ' request with new token', $ex->toLogContext());
        $this->iamClient->getNewToken();
        return $this->authenticatedRequest($method, $url, $options, false);
      }
      throw $ex;
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
    try {
      $response = $this->authenticatedRequest('get', '/jsonld/event');
      $events = $response->getEmbedded()['itemListElement'];
      $this->logger->debug('Fetched events', ['count' => count($events)]);
      $data = array(
        '_links' => array(
          'self' => '/jsonld/event',
          'next' => null,
          'prev' => null,
          'last' => '/jsonld/event'
        ),
        '_embedded' => array(
          'event' => $events
        ),
        'total_items' => count($events),
        'page_size' => count($events),
        'page' => 1,
        'page_count' => 1,
      );
      return new Collection('NextEvent\PHPSDK\Model\Event', array(), $data, $this->restClient);
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
    try {
      $response = $this->authenticatedRequest('get', '/jsonld/event/' . $eventId);
      $event = $response->getEmbedded();
      $this->logger->debug('Fetched event ' . $eventId, ['eventId' => $eventId]);
      return new Event($event);
    } catch (APIResponseException $ex) {
      $this->logger->error('Failed fetching event ' . $eventId, $ex->toLogContext());
      throw $ex;
    }
  }


  /**
   * Persists, i.e. creates, the new given event.
   * Make sure your created the event with {@link NextEvent\PHPSDK\Model\Event::spawn()}.
   *
   * On success the new event identifier will be stored in the given instance.
   *
   * @param Event $event
   * @return Event
   */
  public function createEvent(Event $event)
  {
    try {
      $response = $this->authenticatedRequest('post', '/event', $event->toArray());
      $newEvent = $this->getEvent($response->getContent()['event_id']);
      $event->setSource($newEvent->toArray());
      $this->logger->debug('Event created', ['event' => $newEvent]);
      return $event;
    } catch (APIResponseException $ex) {
      $this->logger->error('Failed creating event', $ex->toLogContext());
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
    try {
      $response = $this->authenticatedRequest('get', '/basket/' . $orderId);
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
      throw new BasketEmptyException('Basket does not exist');
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
    $this->logger->info('Delete basket', ['orderId' => $orderId]);
    try {
      return $this->authenticatedRequest('delete', '/basket/' . $orderId . '/item');
    } catch (APIResponseException $ex) {
      if ($ex->getCode() === 404) {
        $this->logger->error('Failed delete basket', $ex->toLogContext());
        throw new OrderNotFoundException('Order/Basket not found for deletion', $ex->getCode(), $ex);
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
    $this->logger->info('Delete basket item', ['orderId' => $orderId, 'orderItemId' => $orderItemId]);
    try {
    return $this->authenticatedRequest('delete', '/basket/' . $orderId . '/item/' . $orderItemId);
    } catch (APIResponseException $ex) {
      if ($ex->getCode() === 404) {
        throw new OrderItemNotFoundException('Order/Basket item not found for deletion', $ex->getCode(), $ex);
      } else {
        throw $ex;
      }
    }
  }


  /**
   * Start payment with authorizing the order
   *
   * Payment in NextEvent is a two-step process starting with authorizing
   * a given order for payment and a later settlement. Authorization freezes the
   * basket to make sure the reserved tickets do not expire while the shopping
   * application processes payment.
   *
   * @param int $orderId The order ID
   * @return Payment Payment authorization data used for settlement
   * @throws APIResponseException
   * @throws InvalidModelDataException if authorization was valid but not the returned data
   * @throws OrderNotFoundException
   */
  public function authorizeOrder($orderId)
  {
    $this->logger->debug('Authorize Order', ['orderId' => $orderId]);
    $options = [
      'headers' => [
        'Authorization' => $this->iamClient->getToken()->getAuthorizationHeader(),
        'Accept' => 'application/json'
      ],
      'timeout' => 20 // may take longer than 5 seconds
    ];
    try {
      $response = $this->authenticatedRequest('post', '/checkout/' . $orderId, $options);
      $this->logger->info('Order authorized', ['orderId' => $orderId]);
      return new Payment($response->getEmbedded());
    } catch (InvalidModelDataException $ex) {
      $this->logger->error('Authorized but failed to initialize the Payment from order authorization', $response->getEmbedded());
      throw $ex;
    } catch (APIResponseException $ex) {
      if ($ex->getResponse()->getStatusCode() === 404) {
        $this->logger->error('Failed to authorize order', ['orderId' => $orderId, 'errorCode' => $ex->getCode()]);
        throw new OrderNotFoundException($ex);
      }
      $this->logger->error('Failed to authorize order', $ex->toLogContext());
      throw $ex;
    }
  }


  /**
   * Settle payment
   * 
   * This confirms a previously obtained payment authorization and
   * completes the NextEvent order. The tickets will be finally booked
   * for the supplied customer and will be issued afterwards.
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
   * @param Payment $payment Payment authorization data
   * @param array   $customer Customer data
   * @param null    $transactionId
   * @return array Hash array with payment transaction data
   * @throws APIResponseException
   */
  public function settlePayment($payment, $customer, $transactionId = null)
  {
    try {
      return $this->paymentClient->settlePayment(
        $this->getPaymentToken(),
        $payment,
        $customer,
        $transactionId
      );
    } catch (APIResponseException $ex) {
      if ($ex->getCode() === 401) {
        $this->getNewPaymentToken();
        return $this->paymentClient->settlePayment(
          $this->getPaymentToken(),
          $payment,
          $customer,
          $transactionId
        );
      }
      throw $ex;
    }
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
    // use cached token if available
    $paymentToken = Token::fromString($this->cache->get(self::PAYMENT_TOKEN_KEY));
    if ($paymentToken && !$paymentToken->isExpired()) {
      $this->logger->debug('Use PaymentToken from cache');
      return $paymentToken;
    }

    try {
      $response = $this->authenticatedRequest('post', '/payment/token');
      $this->logger->info('Fetched PaymentToken from API', $response->toLogContext());

      $data = $response->getResponse()->json();
      $paymentToken = new Token(
        $data['access_token'], $data['expires_in'], $data['scope'], $data['token_type']
      );

      $this->cache->set(self::PAYMENT_TOKEN_KEY, $paymentToken->toString());

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
   * Forces refresh payment token
   *
   * @return Token
   * @throws NotAuthorizedException
   * @throws APIResponseException
   */
  public function getNewPaymentToken()
  {
    $this->cache->set(self::PAYMENT_TOKEN_KEY, null);
    return $this->getPaymentToken();
  }


  /**
   * Abort payment
   *
   * Cancels the payment process previously started with `authorizeOrder()`
   * using the payment authorization data. This will unfreeze the reservation
   * and restore the basket linked with the payment authorization. To be called
   * when the user aborts the payment process in the shopping application.
   *
   * @param Payment $payment Payment authorization data
   * @param string  $reason why the payment is aborted
   * @return bool
   * @throws APIResponseException
   */
  public function abortPayment($payment, $reason)
  {
    try {
      return $this->paymentClient->abortPayment(
        $this->getPaymentToken(),
        $payment,
        $reason
      );
    } catch (APIResponseException $ex) {
      if ($ex->getCode() === 401) {
        $this->getNewPaymentToken();
        return $this->paymentClient->abortPayment(
          $this->getPaymentToken(),
          $payment,
          $reason
        );
      }
      throw $ex;
    }
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
    $order = $this->getOrder($orderId, ['tickets','document']);

    // repeat fetching if tickets are not yet available
    while (microtime(true) < $timeout && !$order->allTicketsIssued()) {
      usleep(300000);
      $order = $this->getOrder($orderId, ['tickets','document']);
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
   * Get full order data
   *
   * use order->invoice->status == 'paid' for checking invoice status
   *
   * @param int $orderId The order ID
   * @param array $embed List of associations to embed in the response (any of 'tickets','document','invoice','items', 'user', 'sales_channel')
   * @return Order Order model
   * @throws OrderNotFoundException
   * @throws APIResponseException
   */
  public function getOrder($orderId, $embed = ['tickets','document','invoice'])
  {
    try {
      if (!is_array($embed)) {
        $embed = [$embed];
      }
      $query = !empty($embed) ? sprintf('?_embed=%s', join(',', $embed)) : '';
      $response = $this->authenticatedRequest('get', '/order/' . $orderId . $query);
      $this->logger->debug('Order fetched', ['orderId' => $orderId, 'embed' => $embed, 'result' => $response->getContent()]);
      return new Order($response->getEmbedded(), $this->restClient);
    } catch (APIResponseException $ex) {
      if ($ex->getCode() === 404) {
        $this->logger->error('Order not found', array_merge(['orderId' => $orderId], $ex->toLogContext()));
        throw new OrderNotFoundException('Order not found', $ex->getCode(), $ex);
      }
      throw $ex;
    }
  }


  /**
   * Fetches (completed) orders using the given filter.
   *
   * @param array $filter A list of filters, supported by the API.
   *                      Supported filters are:
   *                        * `state` = ['reservation','completed','replaced','cancelled','aborted']
   *                        * `page_size`
   *                        * `order` = 'asc|desc'
   * @see NextEvent\PHPSDK\Util\Filter
   * @return NextEvent\PHPSDK\Model\Collection
   */
  public function getOrders($filter)
  {
    try {
      $response = $this->authenticatedRequest('get', '/order?' . Filter::toString($filter));
      $this->logger->debug('Orders fetched', ['filter' => $filter, 'result' => $response->getContent()]);
      return new Collection('NextEvent\PHPSDK\Model\Order', array($this->restClient), $response->getContent(), $this->restClient);
    } catch (APIResponseException $ex) {
      $this->logger->error('Failed fetching orders', $ex->toLogContext());
      throw $ex;
    }
  }


  /**
   * Rebook/modify a completed order
   *
   * Starts the rebooking process for the given order.
   * As a result, a new "rebooking" basket will be created which can be
   * processed like regular orders. When completed, this basket will replace
   * the original order and invalidate the previously issued tickets.
   *
   * @param int $orderId
   * @return Basket Rebooking basket model
   * @throws OrderNotFoundException
   * @throws APIResponseException
   */
  public function rebookOrder($orderId)
  {
    $this->logger->debug('Rebook order', ['orderId' => $orderId]);

    try {
      $response = $this->authenticatedRequest('post', '/order/' . $orderId . '/rebook', []);
      $result = $response->getEmbedded();
      $this->logger->info('Rebook order created', ['orderId' => $orderId, 'basketId' => $result['order_id']]);
      // fetch rebooking basket right away
      return $this->getBasket($result['order_id']);
    } catch (InvalidModelDataException $ex) {
      $this->logger->error('Authorized but failed to initialize the Payment from order authorization', $response->getEmbedded());
      throw $ex;
    } catch (APIResponseException $ex) {
      if ($ex->getResponse()->getStatusCode() === 404) {
        $this->logger->error('Failed to rebook order', ['orderId' => $orderId, 'errorCode' => $ex->getCode()]);
        throw new OrderNotFoundException($ex);
      }
      $this->logger->error('Failed to rebook order', $ex->toLogContext());
      throw $ex;
    }
  }


  /**
   * Request the cancellation of a completed order
   *
   * Like payment, canceling orders in NextEvent is a two-step process starting
   * with sending a request for cancellation. This is a pre-check to verify
   * whether the given order is actually eligible for cancellation and returns
   * an authorization object to be used for later settlement.
   *
   * @param int $orderId
   * @return CancellationRequest Cancellation authorization used for settlement
   * @throws OrderNotFoundException
   * @throws APIResponseException
   */
  public function requestCancellation($orderId)
  {
    $this->logger->debug('Request cancellation', ['orderId' => $orderId]);

    try {
      $response = $this->authenticatedRequest('post', '/order/' . $orderId . '/cancel', []);
      return new CancellationRequest($response->getEmbedded());
    } catch (InvalidModelDataException $ex) {
      $this->logger->error('Cancellation request failed with invalid response data', $response->getContent());
      throw $ex;
    } catch (APIResponseException $ex) {
      if ($ex->getResponse()->getStatusCode() === 404) {
        $this->logger->error('Cancellation request failed', ['orderId' => $orderId, 'errorCode' => $ex->getCode()]);
        throw new OrderNotFoundException($ex);
      }
      $this->logger->error('Cancellation request failed', $ex->toLogContext());
      throw $ex;
    }
  }


  /**
   * Complete cancellation of an order
   *
   * DANGER ZONE: This confirms a previously obtained cancellation request
   * and finally cancels the given order in the NextEvent system which will
   * invalidate all tickets and deny access for entrance checks.
   *
   * @param CancellationRequest $request Cancellation authorization obtained from `requestCancellation()` 
   * @param string $reason Optional message describing the reason why this order was cancelled
   * @return void
   * @throws OrderNotFoundException
   * @throws APIResponseException
   */
  public function settleCancellation($request, $reason = null)
  {
    if (!($request instanceof CancellationRequest && $request->isValid())) {
      throw new InvalidArgumentException('Requires CancellationRequest object');
    }

    $this->logger->debug('Settle cancellation', $request->toArray());

    try {
      $orderId = $request->getOrderId();
      $requestData = $request->getSettlementData($reason ?: 'Cancelled via PHP SDK');
      $response = $this->authenticatedRequest('post', '/order/' . $orderId . '/cancel', $requestData);
      $this->logger->info('Cancellation completed', ['orderId' => $orderId, 'result' => $response->getEmbedded()]);
    } catch (APIResponseException $ex) {
      if ($ex->getResponse()->getStatusCode() === 404) {
        $this->logger->error('Cancellation failed', ['orderId' => $orderId, 'errorCode' => $ex->getCode()]);
        throw new OrderNotFoundException($ex);
      }
      $this->logger->error('Cancellation failed', $ex->toLogContext());
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


  /**
   * Fetches all access codes for the given filter.
   *
   * @param array $filter A list of filters, supported by the API.
   *                      Supported filter are:
   *                        * `code`
   *                        * `category_id`
   *                        * `price_id`
   *                        * `access_code_id`
   * @see NextEvent\PHPSDK\Util\Filter
   * @return NextEvent\PHPSDK\Model\Collection
   */
  public function getAccessCodes($filter)
  {
    try {
      $response = $this->authenticatedRequest('get', '/access_code?' . Filter::toString($filter));
      $this->logger->debug('Access codes fetched', ['filter' => $filter]);
      $codes = $response->getContent();
      return new Collection('NextEvent\PHPSDK\Model\AccessCode', array(), $codes, $this->restClient);
    } catch (APIResponseException $ex) {
      if ($ex->getCode() !== 404) {
        throw $ex;
      }
      $this->logger->error('Access codes not found', array_merge(['filter' => $filter], $ex->toLogContext()));
      throw new AccessCodesNotFoundException('Access codes not found', $ex->getCode(), $ex);
    }
  }


  /**
   * Fetches all scan logs for the given filter.
   *
   * @param array $filter A list of filters, supported by the API.
   *                      Only `code` is supported for now.
   * @see NextEvent\PHPSDK\Util\Filter
   * @return array A collection of NextEvent\PHPSDK\Model\ScanLog
   */
  public function getScanLogs($filter)
  {
    try {
      $response = $this->authenticatedRequest('get', '/scan_log?' . Filter::toString($filter));
      $this->logger->debug('Scan logs fetched', ['filter' => $filter]);
      $logs = $response->getContent();
      return new Collection('NextEvent\PHPSDK\Model\ScanLog', array(), $logs, $this->restClient);
    } catch (APIResponseException $ex) {
      if ($ex->getCode() !== 404) {
        throw $ex;
      }
      $this->logger->error('Scan logs not found', array_merge(['filter' => $filter], $ex->toLogContext()));
      throw new ScanLogsNotFoundException('Scan logs not found', $ex->getCode(), $ex);
    }
  }


  /**
   * Fetches the gate for the given gate identifier.
   *
   * @param int $gateId
   * @return NextEvent\PHPSDK\Model\Gate
   */
  public function getGate($gateId)
  {
    try {
      $response = $this->authenticatedRequest('get', '/gate/' . $gateId);
      $this->logger->debug('Gate fetched', ['gateId' => $gateId]);
      return new Gate($response->getEmbedded(), $this->restClient);
    } catch (APIResponseException $ex) {
      if ($ex->getCode() !== 404) {
        throw $ex;
      }
      $this->logger->error('Gate not found', array_merge(['gateId' => $gateId], $ex->toLogContext()));
      throw new GateNotFoundException('Gate not found', $ex->getCode(), $ex);
    }
  }


  /**
   * Fetches all gates for the given filter.
   *
   * @param array $filter A list of filters, supported by the API.
   *                      Supported filters are:
   *                        * `gate_id`
   *                        * `hash`
   * @see NextEvent\PHPSDK\Util\Filter
   * @return NextEvent\PHPSDK\Model\Collection
   */
  public function getGates($filter)
  {
    try {
      $response = $this->authenticatedRequest('get', '/gate?' . Filter::toString($filter));
      $this->logger->debug('Gates fetched', ['filter' => $filter]);
      $gates = $response->getContent();
      return new Collection('NextEvent\PHPSDK\Model\Gate', array($this->restClient), $gates, $this->restClient);
    } catch (APIResponseException $ex) {
      $this->logger->error('Failed fetching gates', $ex->toLogContext());
      throw $ex;
    }
  }


  /**
   * Fetches the device for the given device identifier.
   *
   * @param int $deviceId
   * @return NextEvent\PHPSDK\Model\Device
   */
  public function getDevice($deviceId)
  {
    try {
      $response = $this->authenticatedRequest('get', '/device/' . $deviceId);
      $this->logger->debug('Device fetched', ['deviceId' => $deviceId]);
      return new Device($response->getEmbedded());
    } catch (APIResponseException $ex) {
      if ($ex->getCode() !== 404) {
        throw $ex;
      }
      $this->logger->error('Device not found', array_merge(['deviceId' => $deviceId], $ex->toLogContext()));
      throw new DeviceNotFoundException('Device not found', $ex->getCode(), $ex);
    }
  }


  /**
   * Fetches all devices for the given filter.
   *
   * @param array $filter A list of filters, supported by the API.
   *                      Supported filters are:
   *                      * `device_id`
   *                      * `uuid`
   *                      * `gate_id`
   * @see NextEvent\PHPSDK\Util\Filter
   * @return NextEvent\PHPSDK\Model\Collection
   */
  public function getDevices($filter)
  {
    try {
      $response = $this->authenticatedRequest('get', '/device?' . Filter::toString($filter));
      $this->logger->debug('Devices fetched', ['filter' => $filter]);
      $devices = $response->getContent();
      return new Collection('NextEvent\PHPSDK\Model\Device', array(), $devices, $this->restClient);
    } catch (APIResponseException $ex) {
      $this->logger->error('Failed fetching devices', $ex->toLogContext());
      throw $ex;
    }
  }


  /**
   * Fetches all base categories for the given filter.
   *
   * @param array $filter A list of filters, supported by the API.
   *                      Supported filters are:
   *                        * `base_category_id`
   *                        * `event_id`
   * @see NextEvent\PHPSDK\Util\Filter
   * @return NextEvent\PHPSDK\Model\Collection
   */
  public function getBaseCategories($filter)
  {
    try {
      $response = $this->authenticatedRequest('get', '/base_category?' . Filter::toString($filter));
      $this->logger->debug('BaseCategories fetched', ['filter' => $filter]);
      $baseCategories = $response->getContent();
      return new Collection('NextEvent\PHPSDK\Model\BaseCategory', array($this->restClient), $baseCategories, $this->restClient);
    } catch (APIResponseException $ex) {
      $this->logger->error('Failed fetching base categories', $ex->toLogContext());
      throw $ex;
    }
  }


  /**
   * Fetches all categories for the given filter.
   *
   * @param array $filter A list of filters, supported by the API.
   *                      Supported filters are:
   *                        * `category_id`
   *                        * `base_category_id`
   *                        * `event_id`
   * @see NextEvent\PHPSDK\Util\Filter
   * @return NextEvent\PHPSDK\Model\Collection
   */
  public function getCategories($filter)
  {
    try {
      $response = $this->authenticatedRequest('get', '/category?' . Filter::toString($filter));
      $this->logger->debug('Categories fetched', ['filter' => $filter]);
      $categories = $response->getContent();
      return new Collection('NextEvent\PHPSDK\Model\Category', array(), $categories, $this->restClient);
    } catch (APIResponseException $ex) {
      $this->logger->error('Failed fetching categories', $ex->toLogContext());
      throw $ex;
    }
  }


  /**
   * Fetches all base prices for the given filter.
   *
   * @param array $filter A list of filters, supported by the API.
   *                      Supported filters are:
   *                        * `base_price_id`
   *                        * `base_category_id`
   *                        * `event_id`
   * @see NextEvent\PHPSDK\Util\Filter
   * @return NextEvent\PHPSDK\Model\Collection
   */
  public function getBasePrices($filter)
  {
    try {
      $response = $this->authenticatedRequest('get', '/base_price?' . Filter::toString($filter));
      $this->logger->debug('BasePrices fetched', ['filter' => $filter]);
      $basePrices = $response->getContent();
      return new Collection('NextEvent\PHPSDK\Model\BasePrice', array(), $basePrices, $this->restClient);
    } catch (APIResponseException $ex) {
      $this->logger->error('Failed fetching base prices', $ex->toLogContext());
      throw $ex;
    }
  }


  /**
   * Fetches all prices for the given filter.
   *
   * @param array $filter A list of filters, supported by the API.
   *                      Supported filters are:
   *                        * `price_id`
   *                        * `category_id`
   *                        * `base_price_id`
   *                        * `event_id`
   * @see NextEvent\PHPSDK\Util\Filter
   * @return NextEvent\PHPSDK\Model\Collection
   */
  public function getPrices($filter)
  {
    try {
      $response = $this->authenticatedRequest('get', '/price?' . Filter::toString($filter));
      $this->logger->debug('Prices fetched', ['filter' => $filter]);
      $prices = $response->getContent();
      return new Collection('NextEvent\PHPSDK\Model\Price', array(), $prices, $this->restClient);
    } catch (APIResponseException $ex) {
      $this->logger->error('Failed fetching prices', $ex->toLogContext());
      throw $ex;
    }
  }


  /**
   * Creates the base category(ies) provided in the $categories argument.
   * Each base category has to hold at least one base price.
   * The attached base prices will also be created automatically.
   * Make sure you created the base category instances with {@link NextEvent\PHPSDK\Model\BaseCategory::spawn()}.
   *
   * On success the new base category ids will be stored in the given instance(s).
   *
   * @throws NextEvent\PHPSDK\Exception\InvalidModelDataException If a base category happens to have no price
   * @param NextEvent\PHPSDK\Model\BaseCategory|array $categories Single or list of
   *                                                              {@link NextEvent\PHPSDK\Model\BaseCategory}
   * @return NextEvent\PHPSDK\Model\Collection
   */
  public function createBaseCategory($categories)
  {
    if (!is_array($categories)) {
      $categories = array($categories);
    }
    try {
      $pricesToCreate = array();
      $data = array();
      // First check for categories which have no price before sending requests
      foreach ($categories as $category) {
        $prices = $category->getBasePrices();
        if (!isset($prices) || ($prices->count() === 0)) {
          throw new InvalidModelDataException('You have to specify at least one base price for the base category! Use setBasePrices');
        }
        $data[] = $category->toArray();
      }
      $response = $this->authenticatedRequest('post', '/base_category', $data);
      $collection = new Collection('NextEvent\PHPSDK\Model\BaseCategory', array($this->restClient), $response->getContent());
      foreach ($collection as $i => $newCategory) {
        $category = $categories[$i];
        $category->setRestClient($this->restClient);
        $category->setSource($newCategory->toArray());
        $prices = $category->getBasePrices();
        foreach ($prices as $price) {
          $pricesToCreate[] = $price->setBaseCategoryId($category->getId());
        }
        $collection[$i] = $category;
      }
      $createdPrices = $this->createBasePrice($pricesToCreate);
      // Let the base prices collection point to the correct base prices which have now ids
      foreach ($collection as $baseCategory) {
        $prices = $createdPrices->filter(function($price) use ($baseCategory) {
          return $price->getBaseCategoryId() === $baseCategory->getId();
        });
        $baseCategory->setBasePrices($prices);
      }
      $this->logger->debug('BaseCategories created', ['categories' => $collection]);
      return $collection;
    } catch (APIResponseException $ex) {
      $this->logger->error('Failed creating base categories', $ex->toLogContext());
      throw $ex;
    }
  }


  /**
   * Updates the base category(ies) provided in the $categories array.
   *
   * @param NextEvent\PHPSDK\Model\BaseCategory|array $categories Single or list of NextEvent\PHPSDK\Model\BaseCategory
   * @return void
   */
  public function updateBaseCategory($categories)
  {
    if (!is_array($categories)) {
      $categories = array($categories);
    }
    try {
      foreach ($categories as $category) {
        $data = $category->toArray(true);
        $this->authenticatedRequest('put', '/base_category/' . $category->getId(), $data);
      }
      $eventIds = array_unique(array_map(function($category) {
        return $category->getEventId();
      }, $categories));

      // Sync the current categories and prices for each event
      foreach ($eventIds as $id) {
        $this->authenticatedRequest('post', '/sync_categories/' . $id);
      }
      $this->logger->debug('BaseCategories updated', ['categories' => $categories]);
    } catch (APIResponseException $ex) {
      $this->logger->error('Failed updating base categories', $ex->toLogContext());
      throw $ex;
    }
  }


  /**
   * Creates the base price(s) provided in the $prices argument.
   * Make sure you created the price instances with {@link NextEvent\PHPSDK\Model\BasePrice::spawn()}.
   * Each provided base price have to be assigned to a base category. Otherwise an exception will be thrown.
   *
   * On success the new base price ids will be stored in the given instance(s).
   *
   * @throws NextEvent\PHPSDK\Exception\InvalidModelDataException If a base price happens to have no assigned base category.
   * @param NextEvent\PHPSDK\Model\BasePrice|array $prices Single or list of NextEvent\PHPSDK\Model\BasePrice
   * @return NextEvent\PHPSDK\Model\Collection
   */
  public function createBasePrice($prices)
  {
    if (!is_array($prices)) {
      $prices = array($prices);
    }
    try {
      $data = array();
      foreach ($prices as $price) {
        if (!$price->getBaseCategory() === null ) {
          throw new InvalidModelDataException('You have to specify a base category for the base price! Use setBaseCategory');
        }
        $data[] = $price->toArray();
      }
      $response = $this->authenticatedRequest('post', '/base_price', $data);
      $collection = new Collection('NextEvent\PHPSDK\Model\BasePrice', null, $response->getContent());
      foreach ($collection as $i => $newPrice) {
        $prices[$i]->setSource($newPrice->toArray());
        $collection[$i] = $prices[$i];
      }
      $eventIds = array_unique(array_map(function($price) {
        return $price->getEventId();
      }, $prices));

      // Sync the current categories and prices for each event
      foreach ($eventIds as $id) {
        $this->authenticatedRequest('post', '/sync_categories/' . $id);
      }
      $this->logger->debug('BasePrices created', ['prices' => $collection]);
      return $collection;
    } catch (APIResponseException $ex) {
      $this->logger->error('Failed creating base prices', $ex->toLogContext());
      throw $ex;
    }
  }


  /**
   * Updates the base price(s) provided in the $prices array.
   *
   * @param NextEvent\PHPSDK\Model\BasePrice|array $prices Single or list of NextEvent\PHPSDK\Model\BasePrice
   * @return void
   */
  public function updateBasePrice($prices)
  {
    if (!is_array($prices)) {
      $prices = array($prices);
    }
    try {
      foreach ($prices as $price) {
        $data = $price->toArray(true);
        $this->authenticatedRequest('put', '/base_price/' . $price->getId(), $data);
      }
      $eventIds = array_unique(array_map(function($price) {
        return $price->getEventId();
      }, $prices));
      // Sync the current categories and prices for each event
      foreach ($eventIds as $id) {
        $this->authenticatedRequest('post', '/sync_categories/' . $id);
      }
      $this->logger->debug('BasePrices updated', ['prices' => $prices]);
    } catch (APIResponseException $ex) {
      $this->logger->error('Failed updating base prices', $ex->toLogContext());
      throw $ex;
    }
  }
}
