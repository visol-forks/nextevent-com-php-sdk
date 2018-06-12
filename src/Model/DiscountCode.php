<?php

namespace NextEvent\PHPSDK\Model;

/**
 * Discount code model
 *
 * Struct for a discount code record assigned to an order/basket item.
 *
 * @package NextEvent\PHPSDK\Model
 */
class DiscountCode extends Model
{
  /**
   * @inheritdoc
   */
  public function isValid()
  {
    return isset($this->source['discount_code_id']) &&
      isset($this->source['title']);
  }


  /**
   * Get the unique identifier for this discount code
   *
   * @return int
   */
  public function getId()
  {
    return $this->source['discount_code_id'];
  }


  /**
   * Get the title/name of this category
   *
   * @return string
   */
  public function getTitle()
  {
    return $this->source['title'];
  }


  /**
   * Get the display name of this discount code
   *
   * This is basically an alias for self::getTitle()
   *
   * @return string
   */
  public function getDisplayname()
  {
    return $this->source['title'];
  }

  /**
   * Get the code entered for this discount
   *
   * @return string
   */
  public function getCode()
  {
    return $this->source['code'];
  }

}
