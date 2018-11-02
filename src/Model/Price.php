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
   * Get the creation data of this price
   *
   * @return DateTime|null
   */
  public function getCreatedDate()
  {
    return isset($this->source['created']) ? DateTime::fromJson($this->source['created']) : null;
  }


  /**
   * Get the changed date of this price
   *
   * @return DateTime|null
   */
  public function getChangedDate()
  {
    return isset($this->source['changed']) ? DateTime::fromJson($this->source['changed']) : null;
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


  /**
   * Check if this price represents a sideevent price
   *
   * @return boolean
   */
  public function isSideeventPrice()
  {
    return isset($this->source['side_event']['parent_category_id']) && !empty($this->source['side_event']['parent_category_id']);
  }


  /**
   * Check if this price represents a package price
   *
   * @return boolean
   */
  public function isPackagePrice()
  {
    return isset($this->source['side_event']['parent_category_id']) && !empty($this->source['side_event']['preselected_items']);
  }


  /**
   * Check if this price represents a discount price
   *
   * Discount prices cannot be selected directly but are automatically
   * chosen if a matching discount code was entered.
   *
   * @return boolean
   */
  public function isDiscountPrice()
  {
    return !empty($this->source['parent_price_id']);
  }


  /**
   * Check if this price is a hidden item
   *
   * Hidden prices are not directly selectable for customers but
   * represent the price that is charged when a ticket is added
   * indirectly via sideevent selection or when booked as a package.
   *
   * @return boolean
   */
  public function isHidden()
  {
    return $this->isSideeventPrice() || $this->isPackagePrice() || $this->isDiscountPrice();
  }


  /**
   * Getter for the deleted flag
   *
   * Denotes that the price has been deleted and is no longer
   * available for booking.
   *
   * @return bool True if this price has been deleted
   */
  public function isDeleted()
  {
    return !empty($this->source['deleted']);
  }
}
