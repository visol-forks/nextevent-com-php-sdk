<?php

namespace NextEvent\PHPSDK\Model;

/**
 * Basket model
 *
 * Represents a basket model retrieved from the API
 *
 * @package NextEvent\PHPSDK\Model
 */
class Basket extends Model
{
  /**
   * Cached array list of BasketItem entities
   *
   * @var BasketItem[]
   */
  protected $items_cache;


  /**
   * @inheritdoc
   */
  public function isValid()
  {
    return isset($this->source['order_id']) &&
      array_key_exists('expires', $this->source) &&
      isset($this->source['tickets']) &&
      is_array($this->source['tickets']);
  }


  /**
   * Getter for the basket identifier
   *
   * @return int basketId
   */
  public function getId()
  {
    return $this->source['order_id'];
  }


  /**
   * Getter for the basket's expiration date
   *
   * Can be null if the basket has no specific expiration date
   *
   * @return DateTime|null
   */
  public function getExpires()
  {
    return isset($this->source['expires']) ? DateTime::fromJson($this->source['expires']) : null;
  }


  /**
   * Getter for items assigned to this basket
   *
   * @return BasketItem[]
   */
  public function getBasketItems()
  {
    if (empty($this->items_cache)) {
      $this->items_cache = array_map(
        function ($source) {
          $source['order_id'] = $this->source['order_id'];
          return new BasketItem($source);
        },
        $this->source['tickets']
      );
    }
    return $this->items_cache;
  }


  /**
   * Determine whether the basket has items assigned
   *
   * Use this method to check whether the basket is empty or not.
   *
   * @return bool True if basket contains at least one item
   */
  public function hasBasketItems()
  {
    return count($this->source['tickets']) > 0;
  }
}
