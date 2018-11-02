<?php

namespace NextEvent\PHPSDK\Model;

use NextEvent\PHPSDK\Store\StoreInterface;
use NextEvent\PHPSDK\Exception\InvalidModelDataException;
use NextEvent\PHPSDK\Exception\DeviceLoginException;
use NextEvent\PHPSDK\Exception\DeviceLogoutException;

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
   * A reference to the rest client.
   *
   * @var \NextEvent\PHPSDK\Rest\Client
   */
  protected $restClient;

  /**
   * A reference to the gate at which it is currently logged in.
   *
   * @var Gate
   */
  protected $gate;

  /**
   * A reference to the optional cache.
   *
   * @var StoreInterface;
   */
  protected $cache;

  /**
   * @inheritdoc
   * @param \NextEvent\PHPSDK\Rest\Client|null $restClient Rest client reference for logging in.
   * @param StoreInterface|null $cache Optional cache for storing this device.
   */
  public function __construct($source, $restClient = null, $cache = null)
  {
    if (isset($source['device_id'])) {
      parent::__construct($source);
    } else {
      if (!is_array($source)) {
        throw new InvalidModelDataException('Given $source for ' . get_class($this) . ' creation is invalid');
      }
      $this->source = $source;
    }
    $this->restClient = $restClient;
    $this->gate = null;
    $this->cache = $cache;
  }


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
   * Get the creation data of this device
   *
   * @return DateTime|null
   */
  public function getCreatedDate()
  {
    return isset($this->source['created']) ? DateTime::fromJson($this->source['created']) : null;
  }


  /**
   * Get the changed date of this device
   *
   * @return DateTime|null
   */
  public function getChangedDate()
  {
    return isset($this->source['changed']) ? DateTime::fromJson($this->source['changed']) : null;
  }


  /**
   * Get the universal unique identifier for this device
   *
   * @return string
   */
  public function getUUID()
  {
    return $this->source['uuid'];
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
    return isset($this->source['gate_id']) ? $this->source['gate_id'] : null;
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


  /**
   * Get the gate of this device
   *
   * @return Gate
   */
  public function getGate()
  {
    return $this->gate;
  }


  /**
   * Logs this device in on the given gate, if not done yet.
   *
   * @param Gate $gate The gate on which this device should be logged in.
   * @throws DeviceLoginException If no rest client has been set on this model.
   * @return Device
   */
  public function login($gate)
  {
    if (isset($this->cache) && $this->getUUID()) {
      $found = $this->cache->get($this->getUUID());
      if ($found) {
        $this->unserialize($found);
      }
      if ($this->getGateId() === $gate->getId()) {
        $this->gate = $gate;
      }
    }
    if ($this->getGate() && $this->getGate()->getId() === $gate->getId()) {
      $this->gate = $gate;
      return $this;
    }
    if (isset($this->restClient)) {
      $query = http_build_query(array(
        'device' => $this->getUUID(),
        'platform' => $this->getPlatform(),
        'version' => $this->getVersion(),
        'name' => $this->getName(),
      ));
      $url = '/access/login/device/gate-' . $gate->getHash() . '?' . $query;
      $re = $this->restClient->get($url)->getContent();
      if (count($re) > 0) {
        $this->source = array_merge($this->source, $re);
      }
      if (isset($this->cache)) {
        $this->cache->set($this->getUUID(), $this->toString());
      }
      $this->gate = $gate;
      return $this;
    } else {
      throw new DeviceLoginException('Call setRestClient first!');
    }
  }


  /**
   * Logs this device out from the gate it is logged in at.
   *
   * @throws DeviceLogoutException If no rest client has been set on this model.
   * @return Device
   */
  public function logout()
  {
    if (isset($this->restClient)) {
      $url = '/access/logout/device/' . $this->getUUID();
      $re = $this->restClient->get($url)->getContent();
      if (count($re) > 0) {
        $this->source = array_merge($this->source, $re);
      }
      if (isset($this->source['gate_id'])) {
        unset($this->source['gate_id']);
      }
      if (isset($this->cache)) {
        $this->cache->set($this->getUUID(), $this->toString());
      }
      $this->gate = null;
      return $this;
    } else {
      throw new DeviceLogoutException('Call setRestClient first!');
    }
  }
}
