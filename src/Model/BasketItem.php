<?php

namespace NextEvent\PHPSDK\Model;

/**
 * BasketItem model
 *
 * Model class representing a single item (ticket) of a NextEvent basket
 *
 * @package NextEvent\PHPSDK\Model
 */
class BasketItem extends Model
{
  /**
   * @var Category
   */
  protected $category;

  /**
   * @var Price
   */
  protected $price;

  /**
   * @var DiscountCode
   */
  protected $discountCode;

  /**
   * @var Seat
   */
  protected $seat;

  /**
   * @var array
   */
  protected $children = [];


  /**
   * BasketItem constructor
   *
   * @param array $source
   * @throws \Exception
   */
  public function __construct($source)
  {
    parent::__construct($source);
    $this->category = new Category($source['category']);
    $this->price = new Price($source['price']);

    if (!empty($source['discount_code'])) {
      $this->discountCode = new DiscountCode($source['discount_code']);
    }
    if (!empty($source['seat'])) {
      $this->seat = new Seat($source['seat']);
    }
  }


  /**
   * @inheritdoc
   */
  public function isValid()
  {
    return isset($this->source['order_item_id']) &&
      isset($this->source['category']) &&
      isset($this->source['price']) &&
      isset($this->source['type']);
  }


  /**
   * Get the unique identifier of this item
   *
   * @return int
   */
  public function getId()
  {
    return $this->source['order_item_id'];
  }


  /**
   * Get the order/basket identifier this item is associated with
   *
   * @return int orderId
   */
  public function getOrderId()
  {
    return $this->source['order_id'];
  }


  /**
   * Get the event identifier this item refers to
   *
   * @return string eventId
   */
  public function getEventId()
  {
    return $this->category->getEventId();
  }


  /**
   * Get the title of the event this item refers to
   *
   * @return string
   */
  public function getEventTitle()
  {
    return $this->category->getEventTitle();
  }


  /**
   * Get a description for this order item
   *
   * The description is composed from the associated category and price titles
   *
   * @param string $delimiter Character(s) used to separate description segments
   * @return string
   */
  public function getDescription($delimiter = ' - ')
  {
    $segments = [
      $this->category->getDisplayname(),
      $this->price->getTitle(),
    ];
    return join($delimiter, array_filter($segments));
  }


  /**
   * Get the associated category model
   *
   * @return Category
   */
  public function getCategory()
  {
    return $this->category;
  }


  /**
   * Get the model for price information
   *
   * @return Price
   */
  public function getPrice()
  {
    return $this->price;
  }


  /**
   * Get the model for discount information
   *
   * @return DiscountCode|null
   */
  public function getDiscountCode()
  {
    return $this->discountCode;
  }


  /**
   * Get the type of the basket/order item
   *
   * @return string Either 'ticket' or 'addition'
   */
  public function getType()
  {
    return $this->source['type'];
  }


  /**
   * Getter for the deleted flag
   *
   * Rebookig items have the deleted flag set if they are
   * subject to be cancelled from the order.
   *
   * @return bool True if this item has been flagged for deletion
   */
  public function isDeleted()
  {
    return !empty($this->source['deleted']);
  }


  /**
   * Determine whether this basket item has seat information assigned
   * 
   * @return bool True if this is a seated ticket and seat information is available
   */
  public function hasSeat()
  {
    return !empty($this->seat);
  }


  /**
   * Getter for seat information
   *
   * @return Seat|null
   */
  public function getSeat()
  {
    return $this->seat;
  }


  /**
   * Register the given BasketItem as a child element
   * 
   * @param BasketItem item
   */
  public function addChildItem(BasketItem $item)
  {
    // add child item price
    $priceData = $this->getPrice()->toArray();
    $priceData['real_price'] += $item->getPrice()->getPrice();
    $this->price = new Price($priceData);

    $this->children[] = $item;
  }


  /**
   * Getter for child items
   *
   * Child items represent additional basket items like ticket options, side events, etc.
   * which are coupled with the given basket item and cannot be booked individually.
   *
   * They also contribute to the overall price and may or may not be listed in a basket summary.
   *
   * @return array List of nested BasketItems
   */
  public function getChildren()
  {
    return $this->children;
  }
}
