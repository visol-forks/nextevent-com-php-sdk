<?php

namespace NextEvent\PHPSDK\Model;

use NextEvent\PHPSDK\Exception\InvalidModelDataException;
use NextEvent\PHPSDK\Util\Log\LogContextInterface;

/**
 * Base class for model classes
 *
 * Data structures retrieved from the NextEvent API are wrapped
 * into decendants of this class in order to provide a well-defined
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
   * @param array $source The source data as recevied from the API
   * @throws \Exception
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
}
