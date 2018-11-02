<?php

namespace NextEvent\PHPSDK\Util;

use NextEvent\PHPSDK\Exception\QueryException;

/**
 * A query represents a set of query parameters for a GET request
 *
 * You should use it to pass query and filter parameters to the
 * Client's get* methods.
 *
 * For example:
 * ```
 * $myQuery = new Query();
 * $myQuery->set('foo', 'bar');
 * $myQuery->setPageSize(10);
 * $myQuery->addFilter(new Filter('field', 'value'));
 * ```
 *
 * @package NextEvent\PHPSDK\Util
 */
class Query
{

  /**
   * The parts of this query.
   *
   * @var array
   */
  protected $parts;

  protected $filters;

  /**
   * @param array|Query $parts An optional array which may contain query parameters or a query instance to clone from.
   */
  public function __construct($partsOrQuery = null)
  {
    $this->filters = array();
    if ($partsOrQuery instanceof Query) {
      $this->parts = $partsOrQuery->toArray();
    } else if (is_array($partsOrQuery)) {
      $this->parts = $partsOrQuery;
    } else {
      $this->parts = array();
    }
    $this->applyFiltersFrom($this->parts);
  }


  /**
   * Filters the given array with the given predicate function.
   *
   * @param array $array
   * @param function $predicate The passed argument is the array key.
   * @return array
   */
  protected function filterByKey($array, $predicate) {
    $arr = array();
    foreach ($array as $k => $v) {
      if ($predicate($k)) {
        $arr[$k] = $v;
      }
    }
    return $arr;
  }


  /**
   * Applies filter definitions from the given parts to this query.
   *
   * @param array $parts
   * @return Query
   */
  protected function applyFiltersFrom($parts)
  {
    $ops = $this->filterByKey($parts, function($key){
      return strpos($key, '_op') !== false;
    });

    foreach ($ops as $key => $value) {
      $re = $this->filterByKey($parts, function($k) use ($key) {
        return ($k . '_op') == $key;
      });
      if (count($re) > 0) {
        $name = substr($key, 0, -3);
        $this->remove($name);
        $this->remove($key);
        $this->addFilter(new Filter($name, $re[$name], $value));
      }
    }
    return $this;
  }


  /**
   * Sets a a parameter with it's value.
   *
   * @param string $name The name of the parameter.
   * @param int|string|array|bool $value The value of the parameter.
   * @return Query
   */
  public function set($name, $value)
  {
    $this->parts[$name] = $value;
    return $this;
  }

  public function get($name)
  {
    return isset($this->parts[$name]) ? $this->parts[$name] : null;
  }


  /**
   * Removes the given query parameter.
   *
   * @param int $name The name of the parameter to remove.
   * @return Query
   */
  public function remove($name)
  {
    unset($this->parts[$name]);
    return $this;
  }


  /**
   * Sets a query parameter which can be used as a filter.
   *
   * Example:
   *
   * ```
   * $query = new Query();
   * $query->addFilter(new Filter('created', '2018-10-23', '>=')));
   * ```
   *
   * @param Filter $filter
   * @throws QueryException If a filter with the same name already exists.
   * @return Query
   */
  public function addFilter($filter)
  {
    $found = $this->getFilter($filter->getName());

    if ($found) {
      throw new QueryException('Multiple filters for the same field are not supported yet. ' .
                                'A filter with name "' . $filter->getName() . '" already exists in this query!');
    }

    $this->filters[] = $filter;
    return $this;
  }


  /**
   * Searches for the filter with the given name and returns it.
   *
   * @param string $name The name of the filter.
   * @return Filter The found filter or `null` if not found.
   */
  public function getFilter($name)
  {
    $found = array_filter($this->filters, function($f) use ($name) {
      return $f->getName() === $name;
    });

    if (count($found) > 0) {
      return reset($found);
    } else {
      return null;
    }
  }


  /**
   * Removes the previous filter, i.e. removes the given parameter and it's operator.
   *
   * @param string $name The filter name.
   * @return Query
   */
  public function removeFilter($nameOrFilter)
  {
    $filter = $nameOrFilter instanceof Filter ? $nameOrFilter : $this->getFilter($nameOrFilter);
    if ($filter) {
      $key = array_search($filter, $this->filters);
      unset($this->filters[$key]);
    }
    return $this;
  }


  /**
   * Sets the page size for this query.
   *
   * Helper function which can be used to set a page size when fetching a collection of models.
   *
   * @param int $value The page size.
   * @throws QueryException If the page size is not an integer or not positive.
   * @return Query
   */
  public function setPageSize($value)
  {
    if (!is_int($value) || $value <= 0) {
      throw new QueryException('A page size has to be an integer and at least 1!');
    }
    $this->set('page_size', $value);
    return $this;
  }


  /**
   * @return int The page size.
   */
  public function getPageSize()
  {
    return $this->get('page_size') ? $this->get('page_size') : 25; // 25 is always the default
  }


  /**
   * Sets the page for this query.
   *
   * Helper function which can be used to set a specific page when fetching a collection of models.
   *
   * @param int $value The page.
   * @throws QueryException If the page is not an integer or not positive.
   * @return Query
   */
  public function setPage($value)
  {
    if (!is_int($value) || $value <= 0) {
      throw new QueryException('A page has to be an integer and at least 1!');
    }
    $this->set('page', $value);
    return $this;
  }


  /**
   * @return int The page.
   */
  public function getPage()
  {
    return $this->get('page') ? $this->get('page') : 1; // The first page is the default one.
  }


  /**
   * Clears all parts in this query.
   *
   * @return Query
   */
  public function clear()
  {
    $this->parts = array();
    $this->filters = array();
    return $this;
  }


  /**
   * @return array The parts of this query.
   */
  public function toArray()
  {
    $arr = $this->parts;
    foreach ($this->filters as $filter) {
      $arr = array_merge($arr, $filter->toArray());
    }
    return $arr;
  }


  /**
   * Converts the given array or query to a string, which can be used for a GET request.
   * For example
   * ```array('myId' => array(1,2), 'myHash' => 'abc')```
   * becomes
   * ```myId=1,2&myHash=abc```.
   *
   * @param array|Query $arrayOrQuery A query instance or a list of query parameters.
   *                                  Array values will be converted into a ',' separated string.
   * @return string An url encoded string.
   */
  public static function toString($arrayOrQuery = null)
  {
    if ($arrayOrQuery === null) {
      return '';
    }
    if ($arrayOrQuery instanceof Query) {
      $arrayOrQuery = $arrayOrQuery->toArray();
    }

    $parts = [];
    $mappingFn = function($val) {
      return urlencode($val);
    };

    foreach ($arrayOrQuery as $key => $val) {
      if (!isset($val)) {
        continue;
      }
      if (is_array($val)) {
        $encoded = array_map($mappingFn, $val) ;
        $parts[] = $key . '=' . implode(',', $encoded);
      } else {
        $v = urlencode($val);
        $parts[] = "$key=$v";
      }
    }

    return implode('&', $parts);
  }

}
