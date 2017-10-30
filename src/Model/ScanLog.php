<?php

namespace NextEvent\PHPSDK\Model;

/**
 * ScanLog model
 *
 * A scan log holds entry information of an access code.
 * Those information contain process time, gate which scanned the code and which device has done it.
 * Furthermore it holds connection state information and the validation message.
 * This model can be used to analyze the entrance checks.
 *
 * @package NextEvent\PHPSDK\Model
 */
class ScanLog extends Model
{

  /**
   * Constant for indicating an online connection.
   *
   * @var int
   */
  const CONNECTION_ONLINE = 1;

  /**
   * Constant for indicating an offline connection.
   *
   * @var int
   */
  const CONNECTION_OFFLINE = 2;

  /**
   * Entry states which a scan log can have.
   *
   * @static
   * @var array $possibleEntryStates
   */
  static protected $possibleEntryStates = array('in', 'out');

  /**
   * Connection states which a scan log can have.
   *
   * @static
   * @var array
   */
  static protected $possibleConnections = array('online', 'offline');

  /**
   * @inheritdoc
   */
  public function isValid()
  {
    return isset($this->source['scan_log_id']) &&
            isset($this->source['code']) &&
            isset($this->source['category_id']) &&
            isset($this->source['entry_state']) &&
            isset($this->source['processed']) &&
            isset($this->source['gate_id']) &&
            isset($this->source['device_id']) &&
            isset($this->source['validation']) &&
            isset($this->source['connection']) &&
            array_search($this->source['entry_state'], self::$possibleEntryStates, true) >= 0 &&
            array_search($this->source['connection'], self::$possibleConnections, true) >= 0;
  }


  /**
   * Get the unique identifier for this scan log
   *
   * @return int
   */
  public function getId()
  {
    return $this->source['scan_log_id'];
  }


  /**
   * Get the code, i.e barcode, for this scan log
   *
   * @return string
   */
  public function getCode()
  {
    return $this->source['code'];
  }


  /**
   * Get the category id for this scan log
   *
   * @return int
   */
  public function getCategoryId()
  {
    return $this->source['category_id'];
  }


  /**
   * Get the category id for this scan log
   *
   * @return int|null
   */
  public function getPriceId()
  {
    return $this->source['price_id'];
  }


  /**
   * Get the entry state for this scan log
   *
   * @see AccessCode::ENTRY_IN For the in state
   * @see AccessCode::ENTRY_OUT For the out state
   * @return int
   */
  public function getEntryState()
  {
    switch ($this->source['entry_state']) {
      case 'in': return AccessCode::ENTRY_IN;
      case 'out': return AccessCode::ENTRY_OUT;
    }
  }


  /**
   * Get the processed time for this scan log
   *
   * @return DateTime
   */
  public function getProcessed()
  {
    return isset($this->source['processed']) ? DateTime::fromJson($this->source['processed']) : null;
  }


  /**
   * Get the gate id for this scan log
   *
   * @return int
   */
  public function getGateId()
  {
    return $this->source['gate_id'];
  }


  /**
   * Get the device id for this scan log
   *
   * @return int
   */
  public function getDeviceId()
  {
    return $this->source['device_id'];
  }


  /**
   * Get the validation message for this scan log
   *
   * @return string
   */
  public function getValidation()
  {
    return $this->source['validation'];
  }


  /**
   * Get the validation message for this scan log
   *
   * @see ScanLog::CONNECTION_ONLINE For the online connection
   * @see ScanLog::CONNECTION_OFFLINE For the offline connection
   * @return int
   */
  public function getConnection()
  {
    switch ($this->source['connection']) {
      case 'in': return self::CONNECTION_ONLINE;
      case 'out': return self::CONNECTION_OFFLINE;
    }
  }
}
