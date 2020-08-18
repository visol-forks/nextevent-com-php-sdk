<?php

namespace NextEvent\PHPSDK\Model;

/**
 * Discount group model
 *
 * Struct for a discount group registered in the system.
 *
 * @package NextEvent\PHPSDK\Model
 */
class DiscountGroup extends Model
{
  /**
   * @inheritdoc
   */
  public function isValid()
  {
    return isset($this->source['discount_group_id']) &&
      isset($this->source['title']);
  }


  /**
   * Get the unique identifier for this discount group
   *
   * @return int
   */
  public function getId()
  {
    return $this->source['discount_group_id'];
  }


  /**
   * Get the title/name of this discount group
   *
   * @return string
   */
  public function getTitle()
  {
    return $this->source['title'];
  }


  /**
   * Get the description text of this discount group
   *
   * @return string
   */
  public function getDescription()
  {
    return $this->get('description');
  }


  /**
   * Get the display name of this discount group
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
   * Get the absolute discount amount
   *
   * @return float or null if a realtive factor is set
   */
  public function getAmount()
  {
    return $this->get('amount');
  }


  /**
   * Get the relative discount factor (i.e. the percentage)
   *
   * @return float or null if an absolute amount is set
   */
  public function getFactor()
  {
    return $this->get('factor');
  }

}
