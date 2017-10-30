<?php

namespace NextEvent\PHPSDK\Model;

use NextEvent\PHPSDK\Exception\InvalidModelDataException;
use NextEvent\PHPSDK\Util\Log\LogContextInterface;

/**
 * Base class for model classes
 *
 * Data structures retrieved from the NextEvent API are wrapped
 * into dependants of this class in order to provide a well-defined
 * struct for accessing these informations.
 *
 * @package NextEvent\PHPSDK\Model
 */
abstract class Model implements LogContextInterface
{
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
    if (!is_array($source) || !$this->isValid()) {
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
    return $this->source[$var];
  }


  /**
   * Supports get-Methods for properties which are yet unknown.
   * If the source of your model contains, e.g. a property named 'my_property',
   * you can getMyProperty() to retrieve it's value.
   * @param string $name
   * @param array $args
   * @return mixed
   */
  public function __call($name, $args)
  {
    if (strpos($name, 'get') === false) throw new \Exception('Prefix your method with \'get\'');
    $prop = lcfirst(substr($name, 3));
    if (isset($this->source[$prop])) {
      return $this->source[$prop];
    }
    $propName = substr(strtolower(preg_replace('/([A-Z])/', '_$1', $name)), 4);
    if (isset($this->source[$propName])) {
      return $this->source[$propName];
    } else {
      throw new \Exception("Unknown property '$prop'");
    }
  }
}
