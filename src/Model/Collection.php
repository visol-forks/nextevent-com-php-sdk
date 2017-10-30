<?php

namespace NextEvent\PHPSDK\Model;

use NextEvent\PHPSDK\Exception\CollectionException;
use NextEvent\PHPSDK\Exception\InvalidArgumentException;
use Iterator;
use Countable;
use ArrayAccess;
use Exception;
use ReflectionClass;


/**
 * A collection holds instances of models by converting them from raw data.
 * It has meta information about the total amount of items, pages and references to other pages.
 * Further more it allows to iterate over all total items, by simply fetching the next page if the bounds
 * of the current page have been reached.
 *
 * If you want to fetch the next page manually, you can call {@link Collection::fetchNextPage()}.
 * This will fetch the next page, if it is available and apply the content to this collection.
 *
 * In order to fetch a specific page and apply it to the collection, you can fetch it with the rest client
 * and then apply it via {@link Collection::setData()}. Whether you want to reset the current data or not
 * can be controlled with the flag `reset`.
 *
 * @package NextEvent\PHPSDK\Model
 */
class Collection implements Iterator, Countable, ArrayAccess
{
  /**
   * The internal iterator position.
   *
   * @var int
   */
  protected $position;

  /**
   * The class name of the model to instantiate.
   *
   * @var string
   */
  protected $modelClass;

  /**
   * Rest client for fetching the next page.
   *
   * @var NextEvent\PHPSDK\Rest\Client
   */
  protected $restClient;

  /**
   * Items in total.
   *
   * @var int
   */
  protected $totalItems;

  /**
   * Pages in total.
   *
   * @var int
   */
  protected $totalPages;

  /**
   * The current page number.
   *
   * @var int
   */
  protected $page;

  /**
   * The current page size.
   *
   * @var int
   */
  protected $pageSize;

  /**
   * Internal array of models.
   *
   * @var array
   */
  protected $models;

  /**
   * The path for the next page.
   *
   * @var string
   */
  protected $nextPage;

  /**
   * The path for the previous page.
   *
   * @var string
   */
  protected $previousPage;

  /**
   * The path for the current page.
   *
   * @var string
   */
  protected $currentPage;

  /**
   * The path for the last page.
   *
   * @var string
   */
  protected $lastPage;

  /**
   * Additional arguments passed to the model constructor.
   *
   * @var array
   */
  protected $instanceArgs;

  /**
   * Reference to the original raw data.
   *
   * @var array
   */
  protected $originalData;

  /**
   * Creates a new collection.
   *
   * @param string $modelClass The class of the models in this collection.
   * @param array $instanceArgs Additional instance arguments to pass to the model constructor.
   * @param array $data Optional response data to initialize the collection with.
   * @param NextEvent\PHPSDK\Rest\Client $restClient Rest client for fetching next pages.
   */
  public function __construct($modelClass, $instanceArgs = null, $data = null, $restClient = null)
  {
    $this->totalItems = 0;
    $this->totalPages = 0;
    $this->page = 1;
    $this->pageSize = 0;
    $this->models = array();
    $this->modelClass = $modelClass;
    $this->restClient = $restClient;
    $this->position = 0;
    $this->instanceArgs = $instanceArgs;
    if (!$this->instanceArgs) {
      $this->instanceArgs = array();
    }
    if (isset($data)) {
      $this->setData($data);
    }
  }


  /**
   * Set additional arguments which should be passed to the model constructor.
   * You can pass a function as a value, which has to return the value type expected by the constructor of the model.
   * The raw model data will be passed to that function.
   *
   * @param array $arguments
   * @return void
   */
  public function setInstanceArguments(array $arguments)
  {
    $this->instanceArgs = $arguments;
  }


  /**
   * Sets the data data by applying the given response data.
   * This method assumes that the given data holds the structure of a HAL response.
   *
   * @param array $data
   * @param bool $reset Whether to reset the internal models array.
   * @return void
   * @throws CollectionException
   */
  public function setData($data, $reset = true)
  {
    if (!isset($data['_embedded'])) {
      throw new CollectionException('This seems to be not valid collection data. Please provide the _embedded data');
    }
    if (!isset($reset)) {
      $reset = true;
    }
    if ($reset) {
      $this->models = array();
    }
    reset($data['_embedded']);
    $arrayData = current($data['_embedded']);

    $r = new ReflectionClass($this->modelClass);
    if ($arrayData !== false) {
      foreach ($arrayData as $modelData) {
        $args = array($modelData);
        foreach ($this->instanceArgs as $arg) {
          if (is_callable($arg)) {
            $args[] = $arg($modelData);
          } else {
            $args[] = $arg;
          }
        }
        $this->models[] = $r->newInstanceArgs($args);
      }
    }

    $links = isset($data['_links']) ? $data['_links'] : array();
    $this->currentPage = isset($links['self']) ? $links['self'] : null;
    $this->nextPage = isset($links['next']) ? $links['next'] : null;
    $this->previousPage = isset($links['prev']) ? $links['prev'] : null;
    $this->lastPage = isset($links['last']) ? $links['last'] : null;

    $this->totalItems = isset($data['total_items']) ? intval($data['total_items']) : count($this->models);
    $this->totalPages = isset($data['page_count']) ? intval($data['page_count']) : 1;
    $this->page = isset($data['page']) ? intval($data['page']) : 1;
    $this->pageSize = isset($data['page_size']) ? intval($data['page_size']) : $this->totalItems;
    $this->originalData = $data;
  }


  /**
   * Returns the current page.
   *
   * @return string
   */
  public function getCurrentPage()
  {
    return $this->currentPage;
  }


  /**
   * Returns the next page.
   *
   * @return string
   */
  public function getNextPage()
  {
    return $this->nextPage;
  }


  /**
   * Returns the previous page.
   *
   * @return string
   */
  public function getPreviousPage()
  {
    return $this->previousPage;
  }


  /**
   * Returns the last page.
   *
   * @return string
   */
  public function getLastPage()
  {
    return $this->lastPage;
  }


  /**
   * Fetches the next page, if it is set and updates the data.
   *
   * @throws NextEvent\PHPSDK\Exception\CollectionException If no rest client has been set.
   * @return bool Whether the next page has been fetched or not. `false` means, we have no next page to fetch.
   */
  public function fetchNextPage()
  {
    if (!isset($this->nextPage)) {
      return false;
    }

    if (!$this->restClient) {
      throw new CollectionException('Can not fetch the next page without a rest client');
    }

    $data = $this->restClient->get($this->nextPage)->getContent();
    $this->setData($data, false);
    return true;
  }


  /**
   * Returns whether the given offset exists.
   *
   * @param mixed $offset The offset to check.
   * @return bool
   */
  public function offsetExists($offset)
  {
    return isset($this->models[$offset]);
  }


  /**
   * Returns the value at the given offset.
   *
   * @param mixed $offset The offset to get the value at.
   * @return mixed
   */
  public function offsetGet($offset)
  {
    if ($offset < $this->totalItems) {
      while ($offset >= count($this->models) && $this->fetchNextPage());
    }
    return isset($this->models[$offset]) ? $this->models[$offset] : null;
  }


  /**
   * Sets the given value at the given offset.
   * Only instances of the current model class can be set in this collection.
   *
   * @param mixed $offset The offset to set the value at.
   * @param mixed $value The value to set.
   * @throws NextEvent\PHPSDK\Exception\CollectionException If the value is not an instance of the current model class.
   * @return void
   */
  public function offsetSet($offset, $value)
  {
    if (get_class($value) === $this->modelClass) {
      if (is_null($offset)) {
        $this->models[] = $value;
      }
      else {
        $this->models[$offset] = $value;
      }
      if (count($this->models) > $this->totalItems) {
        $this->totalItems = count($this->models);
      }
    } else {
      throw new CollectionException('Only instances of ' . $this->modelClass . ' allowed');
    }
  }


  /**
   * Removes the value at the given $offset.
   *
   * @param mixed $offset The offset at which to remove the value.
   * @return void
   */
  public function offsetUnset($offset)
  {
    unset($this->models[$offset]);
  }


  /**
   * Returns the value at current iterator position.
   *
   * @return mixed
   */
  public function current()
  {
    return $this->models[$this->position];
  }


  /**
   * Returns the current iterator position.
   *
   * @return int
   */
  public function key()
  {
    return $this->position;
  }


  /**
   * Increases the iterator position.
   *
   * @return void
   */
  public function next()
  {
    $this->position++;
  }


  /**
   * Resets the iterator position.
   *
   * @return void
   */
  public function rewind()
  {
    $this->position = 0;
  }


  /**
   * Returns whether the current iterator position has a valid value.
   * If the iterator position is at the end of the page, but not at the end of the whole
   * collection, the next page will be fetched.
   *
   * @return bool
   */
  public function valid()
  {
    if ($this->position < $this->totalItems) {
      while ($this->position >= count($this->models) && $this->fetchNextPage());
    }
    return isset($this->models[$this->position]);
  }


  /**
   * Returns the total amount of items in this collection.
   *
   * @return int
   */
  public function count()
  {
    return $this->totalItems;
  }


  /**
   * The amount of pages in total.
   *
   * @return int
   */
  public function getPages()
  {
    return $this->totalPages;
  }


  /**
   * The current page number.
   *
   * @return int
   */
  public function getPage()
  {
    return $this->page;
  }


  /**
   * The page size of this collection.
   *
   * @return int
   */
  public function getPageSize()
  {
    return $this->pageSize;
  }


  /**
   * The model class of this collection.
   *
   * @return string
   */
  public function getModelClass()
  {
    return $this->modelClass;
  }


  /**
   * Filters this collection with the given callback.
   *
   * @param callable $callback
   * @return NextEvent\PHPSDK\Model\Collection A new collection instance with the filtered content.
   */
  public function filter($callback)
  {
    if (!is_callable($callback)) {
      throw new InvalidArgumentException('The callback argument has to be callable');
    }

    $filtered = array_filter($this->models, $callback);
    $embedded = array();

    if (isset($this->originalData) && isset($this->originalData['_embedded'])) {
      reset($this->originalData['_embedded']);
      $firstKey = key($this->originalData['_embedded']);
    } else {
      $firstKey = 0;
    }

    $embedded[$firstKey] = array_map(function($item) {
      return $item->toArray();
    }, $filtered);

    $initData = array(
      '_embedded' => $embedded,
      '_links' => array(
        'self' => $this->getCurrentPage(),
        'next' => null,
        'prev' => null,
        'last' => $this->getCurrentPage(),
      ),
      'total_items' => count($filtered),
      'page_count' => 1,
      'page' => 1,
      'page_size' => count($filtered)
    );

    $collection = new Collection($this->modelClass, $this->instanceArgs, $initData, $this->restClient);
    return $collection;
  }

  /**
   * Applies a callback to the elements of the collection
   *
   * This works similar to PHP's `array_map()` function and allows to
   * transform the collection with a given callback function.
   *
   * @param callable $callback Callback function to run for each element. Receives the current element as argument.
   * @return array containing all the elements of the collection1 after applying the callback function to each one
   * @throws InvalidArgumentException
   */
  public function map($callback)
  {
    if (!is_callable($callback)) {
      throw new InvalidArgumentException('The callback argument has to be callable');
    }

    $result = [];

    foreach ($this as $k => $item) {
      $result[$k] = $callback($item);
    }

    return $result;
  }
}
