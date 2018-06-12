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
      // build a list of BasketItem models
      $items = [];
      $order_id = $this->source['order_id'];
      array_walk(
        $this->source['tickets'],
        function($source) use (&$items, $order_id) {
          $source['order_id'] = $order_id;
          $items[$source['order_item_id']] = new BasketItem($source);
        }
      );

      // group items by their parent_ticket_id references
      $this->items_cache = self::groupItems($items);;
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


  /**
   * Indicates whether this basket is a rebooking basket
   *
   * Rebooking baskets will replace the linked order once
   * completed. Use Basket::getReplacedOrderId() to get
   * the ID of the previous order this basket will replace.
   *
   * @return bool True if this is a rebooking basket, false otherwise
   */
  public function isRebookOrder()
  {
    return !empty($this->source['replaced_order_id']);
  }


  /**
   * Getter for rebooking order reference
   *
   * @return int The ID of the order to be replaced by this basket
   */
  public function getReplacedOrderId()
  {
    return isset($this->source['replaced_order_id']) ? $this->source['replaced_order_id'] : null;
  }


  /**
   * Getter for the cancellation total of a rebooking order
   *
   * This represents the total amount paid for the previous order
   * this basket is supposed to replace.
   *
   * @return float
   */
  public function getCancellationTotal()
  {
    return isset($this->source['cancellation_total']) ? $this->source['cancellation_total'] : 0;
  }


  /**
   * Getter for the unique hash that can be passed to widgets
   * to assign this basket to a users booking session.
   *
   * @return string|null
   */
  public function getWidgetParameter()
  {
    if (!empty($this->source['resume_hash'])) {
      return sprintf('%d-%s', $this->getId(), $this->source['resume_hash']);
    }

    return null;
  }

  /**
   * Group the list of given BasketItem by their parent_ticket_id references
   *
   * @param array $items
   * @return array
   */
  public static function groupItems(array $items)
  {
    $result = [];

    foreach ($items as $item) {
      if (($parent_id = $item->get('parent_ticket_id')) && isset($items[$parent_id])) {
        $parent = $items[$parent_id];
        $parent->addChildItem($item);
      } else {
        $result[] = $item;
      }
    }

    return $result;
  }
}
