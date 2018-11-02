<?php

namespace NextEvent\PHPSDK\Util;

/**
 * A filter holds information for filtering data via the API.
 *
 * Example:
 *
 * ```
 * $filter = new Filter('created', '2018-10-23', '>=');
 * ```
 *
 * If you want to filter for `NULL` values, you can pass the string `'NULL'` as value.
 *
 * @package NextEvent\PHPSDK\Util
 */
class Filter
{
  /**
   * @var string
   */
  protected $name;

  /**
   * @var array|string|int|bool
   */
  protected $value;

  /**
   * @var string
   */
  protected $operator;

  /**
   * @param string $name The name of the parameter.
   * @param int|string|array|bool $value The value to filter for.
   * @param string $operator The operator. The supported values depend on the requested endpoint.
   */
  public function __construct($name, $value, $operator = '=')
  {
    $this->name = $name;
    $this->value = $value;
    $this->operator = $operator;
  }


  /**
   * Sets the name of this filter.
   *
   * @param string $name
   * @return Filter
   */
  public function setName($name)
  {
    $this->name = $name;
    return $this;
  }


  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }


  /**
   * Sets the value to filter for.
   *
   * @param array|string|int|bool $value
   * @return Filter
   */
  public function setValue($value)
  {
    $this->value = $value;
    return $this;
  }


  /**
   * @return array|string|int|bool
   */
  public function getValue()
  {
    return $this->value;
  }


  /**
   * Sets the filter operator.
   *
   * @param string $operator
   * @return Filter
   */
  public function setOperator($operator)
  {
    $this->operator = $operator;
    return $this;
  }


  /**
   * @return string
   */
  public function getOperator()
  {
    return $this->operator;
  }


  /**
   * Coverts this filter to an array so it can be used with NextEvent\PHPSDK\Util\Query.
   *
   * @return array
   */
  public function toArray()
  {
    $arr = array();
    $arr[$this->name] = $this->value;
    $arr[$this->name . '_op'] = $this->operator;
    return $arr;
  }

}
