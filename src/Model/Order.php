<?php

namespace NextEvent\PHPSDK\Model;

use NextEvent\PHPSDK\Rest\Client as RESTClient;
use NextEvent\PHPSDK\Exception\TicketNotFoundException;

/**
 * Order model
 *
 * Represents an order record retrieved from the API.
 *
 * @package NextEvent\PHPSDK\Model
 */
class Order extends Model
{
  /**
   * Container for cached Ticket Collection
   *
   * @var Ticket[]
   */
  protected $tickets = [];

  /**
   * Container for order document Collection
   *
   * @var OrderDocument[]
   */
  protected $documents = [];

  /**
   * Cached array list of OrderItem entities
   *
   * @var OrderItem[]
   */
  protected $items_cache = [];

  /**
   * RESTClient instance used to fetch related data
   *
   * @var RESTClient
   */
  protected $httpClient;

  /**
   * Order constructor
   *
   * @param array $source
   * @param \NextEvent\PHPSDK\Rest\Client|null $restClient Rest client reference for fetching base prices.
   */
  public function __construct(array $source, $restClient = null)
  {
    parent::__construct($source);

    if (isset($source['tickets'])) {
      $this->tickets = array_map(
        function ($source) {
          return new Ticket($source);
        },
        $this->source['tickets']
      );
    }

    if (!empty($source['documents'])) {
      $this->documents = array_map(
        function ($source) {
          return new OrderDocument($source);
        },
        $this->source['documents']
      );
    }

    if (!empty($restClient)) {
      $this->restClient = $restClient;
    }
  }


  /**
   * Setter for self::restClient
   *
   * @internal
   * @param RESTClient $client
   * @return self
   */
  public function setRestClient(RESTClient $client)
  {
    $this->restClient = $client;
    return $this;
  }


  /**
   * @inheritdoc
   */
  public function isValid()
  {
    return isset($this->source['order_id']) &&
      isset($this->source['state']) &&
      (!isset($this->source['tickets']) || is_array($this->source['tickets']));
  }


  /**
   * Getter for the order identifier
   *
   * @return int orderId
   */
  public function getId()
  {
    return $this->source['order_id'];
  }


  /**
   * Get the globally unique identifier of this item
   *
   * @return string UUID
   */
  public function getUuid()
  {
    return isset($this->source['uuid']) ? $this->source['uuid'] : null;
  }


  /**
   * Getter for the order booking date
   *
   * @return DateTime|null
   */
  public function getOrderDate()
  {
    return isset($this->source['orderdate']) ? DateTime::fromJson($this->source['orderdate']) : null;
  }


  /**
   * Getter for items assigned to this order
   *
   * @return OrderItem[]
   * @throws
   */
  public function getOrderItems()
  {
    // lazy-fetch items for this order
    if (empty($this->items_cache) && !isset($this->source['items']) && $this->restClient) {
      $response = $this->restClient->get('/order_items_by?order_id=' . intval($this->source['order_id']));
      $this->source['items'] = $response->getEmbedded()['order_item'];
    }

    // convert raw API data into a list of OrderItem objects
    if (empty($this->items_cache) && !empty($this->source['items'])) {
      // build a list of OrderItem models
      $items = [];
      $order_id = $this->source['order_id'];
      array_walk(
        $this->source['items'],
        function($source) use (&$items, $order_id) {
          $source['order_id'] = $order_id;
          $items[$source['order_item_id']] = new OrderItem($source);
        }
      );

      // group items by their parent_ticket_id references
      $this->items_cache = Basket::groupItems($items);
    }

    return $this->items_cache;
  }


  /**
   * Get tickets booked with this order
   *
   * @return Ticket[]
   * @throws TicketNotFoundException
   */
  public function getTickets($force = false)
  {
    if (!$this->hasTickets() || !($force || $this->isComplete())) {
      throw new TicketNotFoundException();
    }

    return $this->tickets;
  }


  /**
   * Get the state of this order
   *
   * Possible values are: payment, reservation, completed,
   * aborted, replaced, cancelled
   *
   * @return string
   */
  public function getState()
  {
    return $this->source['state'];
  }


  /**
   * Getter for the total amount of this order
   *
   * @return float
   */
  public function getTotal()
  {
    if (isset($this->source['amount'])) {
      return $this->source['amount'];
    }

    // aggegate total amount from items
    $totalPrice = 0;
    $orderCurrency = null;
    foreach ($this->getOrderItems() as $item) {
      $price = $item->getPrice();
      $totalPrice += $price->getPrice();
      if (!$orderCurrency) {
        $orderCurrency = $price->getCurrency();
      }
    }

    $this->source['amount'] = $totalPrice;
    $this->source['currency'] = $orderCurrency;

    return $this->source['amount'];
  }


  /**
   * Getter for the currency code of this order
   *
   * @return string ISO 4217 currency code
   */
  public function getCurrency()
  {
    return isset($this->source['currency']) ? $this->source['currency'] : null;
  }


  /**
   * Getter for the orders's expiration date
   *
   * Can be null if the orders has no specific expiration date
   *
   * @return DateTime|null
   */
  public function getExpires()
  {
    return isset($this->source['expires']) ? DateTime::fromJson($this->source['expires']) : null;
  }


  /**
   * Check order if tickets are available
   *
   * @return bool
   */
  public function hasTickets()
  {
    return !empty($this->tickets);
  }


  /**
   * Check if tickets have all be issued
   *
   * As opposed to `hasTickets()`, this only returns true
   * if tickets for this order are available AND have been issued
   * as PDF documents.
   *
   * @return bool
   */
  public function allTicketsIssued()
  {
    foreach ($this->tickets as $ticket) {
      if ($ticket->isIssued() === false) {
        return false;
      }
    }
    return !empty($this->tickets);
  }


  /**
   * Check if documents/tickets can be expected to be issued for this order
   *
   * @return boolean
   */
  public function expectDocuments()
  {
    return !$this->isComplete() || !empty($this->source['expect_documents']);
  }

  /**
   * Check order if documents are available
   * 
   * This chech includes tickets
   *
   * @return bool
   */
  public function hasDocuments()
  {
    return !empty($this->documents) || $this->hasTickets();
  }


  /**
   * Getter for order documents
   *
   * @return array of OrderDocument models
   */
  public function getDocuments()
  {
    return $this->documents;
  }


  /**
   * Check order status to be 'completed'
   *
   * @return bool
   */
  public function isComplete()
  {
    return $this->source['state'] === 'completed';
  }

}
