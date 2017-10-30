<?php

namespace NextEvent\PHPSDK\Model;

/**
 * Device model
 *
 * A device entity represents a real world device.
 * It holds information such as the device UUID, platform(os) and it's version and the device name.
 * A device can also tell on which gate it is logged in and when it has been logged in the last time.
 *
 * @package NextEvent\PHPSDK\Model
 */
class Device extends Model
{
  /**
   * @inheritdoc
   */
  public function isValid()
  {
    return isset($this->source['device_id']) &&
            isset($this->source['uuid']);
  }


  /**
   * Get the internal unique identifier for this device
   *
   * @return int
   */
  public function getId()
  {
    return $this->source['device_id'];
  }


  /**
   * Get the universal unique identifier for this device
   *
   * @return string
   */
  public function getUUID()
  {
    return $this->source['device_id'];
  }


  /**
   * Get the platform for this device, e.g. Android or iOS
   *
   * @return string|null
   */
  public function getPlatform()
  {
    return $this->source['platform'];
  }


  /**
   * Get the os version for this device
   *
   * @return string|null
   */
  public function getVersion()
  {
    return $this->source['version'];
  }


  /**
   * Get the gate id for this device, which tells whether the device is logged in or not
   *
   * @return int|null
   */
  public function getGateId()
  {
    return $this->source['gate_id'];
  }


  /**
   * Get the last login date for this device
   *
   * @return DateTime|null
   */
  public function getLastLogin()
  {
    return isset($this->source['last_login']) ? DateTime::fromJson($this->source['last_login']) : null;
  }


  /**
   * Get the name for this device
   *
   * @return string
   */
  public function getName()
  {
    return $this->source['name'];
  }
}
