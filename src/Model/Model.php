<?php

namespace NextEvent\PHPSDK\Model;

use NextEvent\PHPSDK\Exception\InvalidModelDataException;
use NextEvent\PHPSDK\Util\Log\LogContextInterface;
use Serializable;

/**
 * Base class for model classes
 *
 * Data structures retrieved from the NextEvent API are wrapped
 * into dependants of this class in order to provide a well-defined
 * struct for accessing these informations.
 *
 * With the Serializable interface implemented, all models
 * can easily be serialized and stored in session data.
 *
 * @package NextEvent\PHPSDK\Model
 */
abstract class Model implements LogContextInterface, Serializable
{
  /**
   * Mapping of JSON data keys to match the expected source structure
   *
   * @var array|null
   */
  protected $mapJson = null;

  /**
   * Container for wrapped model data
   *
   * @var array
   */
  protected $source;


  /**
   * Model constructor
   *
   * Parse source data.
   *
   * @param array $source The source data as received from the API
   * @throws InvalidModelDataException
   */
  public function __construct($source)
  {
    $this->source = $source;
    if (!is_array($source)) {
      throw new InvalidModelDataException('Given $source for ' . get_class($this) . ' creation is invalid');
    }
    if (!$this->isValid() && !empty($this->mapJson)) {
      $this->source = self::mapSource($source, $this->mapJson);
    }
    if (!$this->isValid()) {
      throw new InvalidModelDataException('Given $source for ' . get_class($this) . ' creation is invalid');
    }
  }


  /**
   * Check model state if it is valid
   *
   * @return bool
   */
  abstract public function isValid();


  /**
   * Convert model to an Array
   *
   * @return array
   */
  public function toArray()
  {
    return $this->source;
  }


  /**
   * Convert the model for logging
   *
   * Implements the LogContextInterface interface
   *
   * @return array
   */
  public function toLogContext()
  {
    return $this->toArray();
  }


  /**
   * Represent the model as string
   *
   * @return string
   */
  public function toString()
  {
    return json_encode($this->source);
  }


  /**
   * Get the value for the given variable.
   *
   * Should be used, if you expect the model to have custom properties which are not known/covered by the known getters
   *
   * @param string $var
   * @return mixed
   */
  public function get($var)
  {
    return isset($this->source[$var]) ? $this->source[$var] : null;
  }


  /**
   * Supports getX or hasX Methods for properties which are yet unknown.
   *
   * If the source of your model contains, e.g. a property named 'my_property',
   * you can call `getMyProperty()` to retrieve it's value.
   *
   * @param string $name
   * @param array $args
   * @return mixed
   */
  public function __call($name, $args)
  {
    if (strpos($name, 'get') !== 0 && strpos($name, 'has') !== 0) {
      throw new \Exception("Prefix your method with 'get' or 'has'");
    }

    $prop = lcfirst(substr($name, 3));
    $propName = strtolower(preg_replace('/([A-Z])/', '_$1', $prop));

    if (strpos($name, 'has') === 0) {
      return isset($this->source[$propName]) || isset($this->source[$prop]);
    }

    if (isset($this->source[$prop])) {
      return $this->source[$prop];
    }
    if (isset($this->source[$propName])) {
      return $this->source[$propName];
    } else {
      throw new \Exception("Unknown property '$prop'");
    }
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

  /**
   * Apply key mapping to the given source array
   *
   * @param array $source Hash arry to map keys
   * @param array $map Hash arry with key mapping (from -> to)
   * @param boolean $merge Merge unmapped values into result
   * @return array resulting array with mapped key -> value pairs
   */
  protected static function mapSource($source, $map, $merge = true)
  {
    $result = [];

    foreach ($source as $key => $val) {
      if (isset($map[$key])) {
        $result[$map[$key]] = $val;
      } else if ($merge) {
        $result[$key] = $val;
      }
    }

    return $result;
  }
}
