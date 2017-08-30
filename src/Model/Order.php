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
   */
  public function __construct(array $source)
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
   * Getter for items assigned to this order
   *
   * @return OrderItem[]
   * @throws
   */
  public function getOrderItems()
  {
    // lazy-fetch items for this order
    if (empty($this->items_cache) && !isset($this->source['order_items']) && $this->restClient) {
      $response = $this->restClient->get('/order_items_by?order_id=' . intval($this->source['order_id']));
      $this->source['order_items'] = $response->getEmbedded()['order_item'];
    }

    // convert raw API data into a list of OrderItem objects
    if (empty($this->items_cache) && !empty($this->source['order_items'])) {
      $this->items_cache = array_map(
        function ($source) {
          $source['order_id'] = $this->source['order_id'];
          return new OrderItem($source);
        },
        $this->source['order_items']
      );
    }

    return $this->items_cache;
  }


  /**
   * Get tickets booked with this order
   *
   * @return Ticket[]
   * @throws TicketNotFoundException
   */
  public function getTickets()
  {
    if (!$this->hasTickets() || !$this->isComplete()) {
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
   * Check order status to be 'completed'
   *
   * @return bool
   */
  public function isComplete()
  {
    return $this->source['state'] === 'completed';
  }

}
