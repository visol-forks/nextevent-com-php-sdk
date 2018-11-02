<?php

namespace NextEvent\PHPSDK\Model;

use NextEvent\PHPSDK\Exception\InvalidModelDataException;
use NextEvent\PHPSDK\Exception\AccessCodeValidateException;

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
   * A reference to the rest client.
   *
   * @var NextEvent\PHPSDK\Rest\Client
   */
  protected $restClient;

  /**
   * @inheritdoc
   * @param \NextEvent\PHPSDK\Rest\Client|null $restClient Rest client reference for invalidating this access code.
   */
  public function __construct($source, $restClient = null)
  {
    if (isset($source['access_code_id'])) {
      parent::__construct($source);
    } else {
      if (!is_array($source)) {
        throw new InvalidModelDataException('Given $source for ' . get_class($this) . ' creation is invalid');
      }
      $this->source = $source;
    }
    $this->restClient = $restClient;
  }

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
   * Set the rest client for this access code, it may be validated via the api.
   *
   * @param \NextEvent\PHPSDK\Rest\Client $restClient
   * @return AccessCode
   */
  public function setRestClient($restClient)
  {
    $this->restClient = $restClient;
    return $this;
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
   * Get the creation data of this access code
   *
   * @return DateTime|null
   */
  public function getCreatedDate()
  {
    return isset($this->source['created']) ? DateTime::fromJson($this->source['created']) : null;
  }


  /**
   * Get the changed date of this access code
   *
   * @return DateTime|null
   */
  public function getChangedDate()
  {
    return isset($this->source['changed']) ? DateTime::fromJson($this->source['changed']) : null;
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


  /**
   * Updates the entry state of this access code via the given device at the logged in gate.
   * If the entry state could not be updated, you will receive a validation message from the API.
   *
   * @param Device $device The device on which this code has to be validated.
   * @param int $entryState The new entry state of the code. If the mode of the gate is not <b>both</b>,
   *                        the mode of the gate will be used by default.
   * @see AccessCode::ENTRY_IN and
   * @see AccessCode::ENTRY_OUT when passing <b>$entryState</b>
   * @param string $connection Optional connection parameter. Default is 'online'.
   * @param array|null $categories Optional list of categories to validate. By default the category id of this
   *                               access code will be set.
   * @param string $processed Optional processed time. Default is the current time of the server this code runs on.
   * @throws AccessCodeValidateException If the device is not logged in.<br>
   *                                       If <b>$entryState</b> is not set but the gate mode is <b>both</b>.<br>
   *                                       If an invalid <b>$entryState</b> has been passed.<br>
   *                                       If no rest client has been set on this model.
   * @return AccessCode
   */
  public function setEntryState($device, $entryState = null, $connection = 'online', $categories = null, $processed = null)
  {
    if (!$device->getGate()) {
      throw new AccessCodeValidateException('The device is not logged in!');
    }
    if (isset($this->restClient)) {

      if ($entryState === null) {
        $gate = $device->getGate();
        $entryState = $gate->getMode();
        if ($entryState === Gate::MODE_BOTH) {
          throw new AccessCodeValidateException('The gate is in mode "both"! ' .
                                                  'You have to provide $entryState by your own!');
        }
        $entryState = $entryState === Gate::MODE_OUT ? self::ENTRY_OUT : self::ENTRY_IN;
      }

      if ($entryState !== self::ENTRY_IN && $entryState !== self::ENTRY_OUT) {
        throw new AccessCodeValidateException('An entry state has to be set.' .
                                                ' Either AccessCode::ENTRY_IN or AccessCode::ENTRY_OUT!');
      }
      if ($processed === null) {
        $processed = date(DATE_ATOM);
      }
      $data = array(
        'device' => $device->getUUID(),
        'connection' => $connection,
        'processed' => $processed,
        'entry_state' => $entryState === self::ENTRY_OUT ? 'out' : 'in',
      );
      if ($categories === null) {
        $categories = array($this->getCategoryId());
      }
      if (is_array($categories)) {
        $data['categories'] = $categories;
      }
      $re = $this->restClient->post('/access/invalidate/' . $device->getGate()->getId() . '/' . $this->getCode(), $data);
      $codes = new AccessCodeCollection($re->getContent(), $this->restClient);
      foreach ($codes as $code) {
        if ($code->getId() === $this->getId()) {
          $this->source = array_merge($this->source, $code->toArray());
        }
      }
      return $this;
    } else {
      throw new AccessCodeValidateException('Call setRestClient first!');
    }
  }
}
