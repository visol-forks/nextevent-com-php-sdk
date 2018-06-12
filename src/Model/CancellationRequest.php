<?php

namespace NextEvent\PHPSDK\Model;

/**
 * Cancellation request model
 *
 * Represents a cancellation request for a given order.
 * Can be used to confirm cancellation in a second step.
 *
 * @package NextEvent\PHPSDK\Model
 */
class CancellationRequest extends Model
{
  /**
   * @inheritdoc
   */
  public function isValid()
  {
    return isset($this->source['order_id']) &&
      isset($this->source['authorization']) &&
      isset($this->source['refund_amount']);
  }


  /**
   * Get the order identifier of this cancellation
   *
   * @return int
   */
  public function getOrderId()
  {
    return $this->source['order_id'];
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
   * Get the total amount to be refunded when cancelling the order
   *
   * @return float
   */
  public function getRefundAmount()
  {
    return $this->source['refund_amount'];
  }


  /**
   * Get the currency of the refund amount
   *
   * Returns an alphanumeric currency code according to ISO 4217.
   *
   * @return string
   */
  public function getCurrency()
  {
    return isset($this->source['currency']) ? $this->source['currency'] : null;
  }


  /**
   * Get the data to be submitted for completing the cancellation
   *
   * @param string $reason Description why this order is cancelled
   * @return array Hash array to be submitted as POST data
   */
  public function getSettlementData($reason = '')
  {
    return [
      'order_id' => $this->source['order_id'],
      'authorization' => $this->source['authorization'],
      'reason' => $reason,
    ];
  }
}
