<?php

namespace NextEvent\PHPSDK\Model;

/**
 * Price model
 *
 * Model class exposing price properties like title, price, currency
 *
 * @package NextEvent\PHPSDK\Model
 */
class Price extends Model
{
  /**
   * @inheritdoc
   */
  public function isValid()
  {
    return isset($this->source['price_id']) &&
      array_key_exists('title', $this->source) &&
      array_key_exists('price', $this->source) &&
      is_numeric($this->source['price']) &&
      isset($this->source['currency']);
  }


  /**
   * Get the unique identifier of this price record
   *
   * @return int
   */
  public function getId()
  {
    return $this->source['price_id'];
  }


  /**
   * Get the title for this price
   *
   * @return string
   */
  public function getTitle()
  {
    return $this->source['title'];
  }


  /**
   * Get the net price without additions and taxes
   *
   * @return float
   */
  public function getNetPrice()
  {
    return $this->source['price'];
  }


  /**
   * Get the gross price after calculated additions
   *
   * @return float
   */
  public function getPrice()
  {
    return $this->source['real_price'];
  }


  /**
   * Get the currency of this price
   *
   * Returns an alphanumeric currency code according to ISO 4217.
   *
   * @return string
   */
  public function getCurrency()
  {
    return $this->source['currency'];
  }
}
