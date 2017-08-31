<?php

namespace NextEvent\PHPSDK\Model;

/**
 * Payment model
 *
 * Represents a payment authorization record issued by the API.
 * Can be used to process payment and finally settle orders.
 *
 * With the Serializable interface implemented, payment models
 * can easily be serialized and stored in session data.
 *
 * @package NextEvent\PHPSDK\Model
 */
class Payment extends Model implements \Serializable
{
  /**
   * @inheritdoc
   */
  public function isValid()
  {
    return isset($this->source['id']) &&
      isset($this->source['uuid']) &&
      isset($this->source['reference']) &&
      isset($this->source['authorization']) &&
      isset($this->source['expires']);
  }


  /**
   * Check if this payment authorization has expired
   *
   * @return boolean
   */
  public function isExpired()
  {
    return isset($this->source['expires']) && $this->getExpires() < new \DateTime();
  }


  /**
   * Get the numeric identifier of this payment
   *
   * @return int
   */
  public function getId()
  {
    return $this->source['id'];
  }


  /**
   * Get the unique identifier of this payment
   *
   * Used for order settlement
   *
   * @return string
   */
  public function getUUID()
  {
    return $this->source['uuid'];
  }


  /**
   * Get the payment reference
   *
   * This value can be used on documents as it's a human readable
   * identifier of a NextEvent payment transaction.
   *
   * @return string
   */
  public function getReference()
  {
    return $this->source['reference'];
  }


  /**
   * Get the authorization code
   *
   * Used for order settlement
   *
   * @return string
   */
  public function getAuthorization()
  {
    return $this->source['authorization'];
  }


  /**
   * Get the expiration date/time of this payment authorization
   *
   * @return \DateTime
   */
  public function getExpires()
  {
    return $this->dateFromJson($this->source['expires']);
  }

  /**
   * Get the amount due to be payed
   *
   * @return float
   */
  public function getAmount()
  {
    return $this->source['amount'];
  }


  /**
   * Get the currency of this payment
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
   * String representation of object
   *
   * Implements Serializable interface
   *
   * @return string
   */
  public function serialize()
  {
    return $this->toString();
  }

  /**
   * Constructs the object from a string
   *
   * Implements Serializable interface
   *
   * @param string
   */
  public function unserialize($serialized)
  {
    $this->source = json_decode($serialized, true);
  }
}
