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
   * Get the type of the basket/order item
   *
   * @return string Either 'ticket' or 'addition'
   */
  public function getType()
  {
    return $this->source['type'];
  }
}
