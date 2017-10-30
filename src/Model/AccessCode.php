<?php

namespace NextEvent\PHPSDK\Model;

/**
 * Access code model
 *
 * An access code is used in the entrance check process.
 * Each access code owns a (bar)code, a category id and a state.
 * A {@link Gate} is responsible for invalidating,
 * i.e. modifying the `entry_state`, of such an access code.
 *
 * @package NextEvent\PHPSDK\Model
 */
class AccessCode extends Model
{
  /**
   * Constant for indicating the state 'valid'.
   *
   * @var int
   */
  const STATE_VALID = 1;

  /**
   * Constant for indicating the state 'cancelled'.
   *
   * @var int
   */
  const STATE_CANCELLED = 2;

  /**
   * Constant for indicating the state 'external'.
   *
   * @var int
   */
  const STATE_EXTERNAL = 3;

  /**
   * Constant for indicating the entry state 'in'.
   *
   * @var int
   */
  const ENTRY_IN = 1;

  /**
   * Constant for indicating the entry state 'out'.
   *
   * @var int
   */
  const ENTRY_OUT = 2;

  /**
   * Constant for indicating no entry state.
   *
   * @var int
   */
  const ENTRY_NONE = 3;

  /**
   * Possible state values an access code can have.
   *
   * @static
   * @var array
   */
  static protected $possibleStates = array('valid', 'cancelled', 'extern');

  /**
   * @inheritdoc
   */
  public function isValid()
  {
    return isset($this->source['access_code_id']) &&
            isset($this->source['code']) &&
            isset($this->source['category_id']) &&
            isset($this->source['state']) &&
            array_search($this->source['state'], self::$possibleStates, true) >= 0;
  }


  /**
   * Get the unique identifier for this access code
   *
   * @return int
   */
  public function getId()
  {
    return $this->source['access_code_id'];
  }


  /**
   * Get the code, i.e barcode, for this access code
   *
   * @return string
   */
  public function getCode()
  {
    return $this->source['code'];
  }


  /**
   * Get the category id for this access code
   *
   * @return int
   */
  public function getCategoryId()
  {
    return $this->source['category_id'];
  }


  /**
   * Get the price id for this access code
   *
   * @return int|null
   */
  public function getPriceId()
  {
    return $this->source['price_id'];
  }


  /**
   * Get the state for this access code
   *
   * @see AccessCode::STATE_VALID For the valid state
   * @see AccessCode::STATE_CANCELLED For the cancelled state
   * @see AccessCode::STATE_EXTERNAL For the external state
   * @return int
   */
  public function getState()
  {
    switch ($this->source['state']) {
      case 'valid': return self::STATE_VALID;
      case 'cancelled': return self::STATE_CANCELLED;
      case 'extern': return self::STATE_EXTERNAL;
    }
  }


  /**
   * Get the entry state for this access code
   *
   * @see AccessCode::ENTRY_IN For the in state
   * @see AccessCode::ENTRY_OUT For the out state
   * @see AccessCode::ENTRY_NONE For no entry state
   * @return int
   */
  public function getEntryState()
  {
    switch ($this->source['entry_state']) {
      case 'in': return self::ENTRY_IN;
      case 'out': return self::ENTRY_OUT;
      default: return self::ENTRY_NONE;
    }
  }


  /**
   * Get the first processed time for this access code
   *
   * @return DateTime|null
   */
  public function getProcessed()
  {
    return isset($this->source['processed']) ? DateTime::fromJson($this->source['processed']) : null;
  }


  /**
   * Get the gate id for the first gate this access code has been processed at
   *
   * @return int|null
   */
  public function getGateId()
  {
    return $this->source['gate_id'];
  }


  /**
   * Get the device id for the first device this access code has been processed at
   *
   * @return int
   */
  public function getDeviceId()
  {
    return $this->source['device_id'];
  }


  /**
   * Get the access from date for this access code
   *
   * @return DateTime|null
   */
  public function getAccessFrom()
  {
    return isset($this->source['access_from']) ? DateTime::fromJson($this->source['access_from']) : null;
  }


  /**
   * Get the access to date for this access code
   *
   * @return DateTime|null
   */
  public function getAccessTo()
  {
    return isset($this->source['access_to']) ? DateTime::fromJson($this->source['access_to']) : null;
  }


  /**
   * Get the amount of entries for this access code
   *
   * @return int|null
   */
  public function getEntries()
  {
    return $this->source['entries'];
  }


  /**
   * Get the time this access code has previously been processed
   *
   * @return DateTime|null
   */
  public function getLastStateChange()
  {
    return isset($this->source['last_state_change']) ? DateTime::fromJson($this->source['last_state_change']) : null;
  }


  /**
   * Get the name of the gate this access code has previously been processed at
   *
   * @return string|null
   */
  public function getLastGate()
  {
    return $this->source['last_gate_change'];
  }
}
