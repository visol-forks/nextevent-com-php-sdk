<?php

namespace NextEvent\PHPSDK\Model;

use NextEvent\PHPSDK\Exception\InvalidBaseCategoryException;
use NextEvent\PHPSDK\Exception\InvalidArgumentException;
use NextEvent\PHPSDK\Exception\InvalidModelDataException;

/**
 * BasePrice model
 *
 * Provides the structure for a base price record.
 *
 * A base price is a price which applies it's data to actual price records.
 * A base price is never linked to a category record.
 * It rather links to a {@link BaseCategory}.
 * Just like a base category, a base price is never linked to an order item.
 *
 * @package NextEvent\PHPSDK\Model
 */
class BasePrice extends MutableModel implements Spawnable
{

  /**
   * The internal reference to the base category model instance.
   *
   * @var NextEvent\PHPSDK\Model\BaseCategory
   */
  protected $baseCategory;

  /**
   * Internal flag for determining whether this base price is new, i.e. not persisted yet.
   *
   * @var bool
   */
  protected $_isNew;

  /**
   * @inheritdoc
   */
  public function __construct($source)
  {
    if (isset($source['base_price_id'])) {
      parent::__construct($source);
    } else {
      if (!is_array($source)) {
        throw new InvalidModelDataException('Given $source for ' . get_class($this) . ' creation is invalid');
      }
      $this->source = $source;
    }
  }


  /**
   * @inheritdoc
   */
  public function isNew()
  {
    return $this->_isNew;
  }


  /**
   * @inheritdoc
   */
  public function isValid()
  {
    return (isset($this->source['base_price_id']) || $this->isNew()) &&
      (isset($this->source['base_category_id'])) &&
      isset($this->source['event_id']) &&
      array_key_exists('title', $this->source) &&
      array_key_exists('price', $this->source) &&
      is_numeric($this->source['price']) &&
      isset($this->source['currency']);
  }


  /**
   * Get the unique identifier of this base price record.
   *
   * @return int
   */
  public function getId()
  {
    return $this->source['base_price_id'];
  }


  /**
   * Get the base category id for this base price.
   *
   * @return int
   */
  public function getBaseCategoryId()
  {
    return isset($this->source['base_category_id']) ? $this->source['base_category_id'] : -1;
  }


  /**
   * Get the base category model instance for this price, if any set
   *
   * @return NextEvent\PHPSDK\Model\BaseCategory|null
   */
  public function getBaseCategory()
  {
    return $this->baseCategory;
  }


  /**
   * Set the base category model instance for this price.
   *
   * The base_category_id and the event_id will be applied to the data of this base price if it is a new one.
   *
   * @param NextEvent\PHPSDK\Model\BaseCategory $baseCategory
   * @throws InvalidArgumentException If the given argument is not a base category.
   * @throws InvalidBaseCategoryException If the id of the given base category does not match the one of this instance.
   * @return void
   */
  public function setBaseCategory($baseCategory)
  {
    if (!($baseCategory instanceof BaseCategory)) {
      throw new InvalidArgumentException('The given base category instance does not match ' .
                                              'the base category id of this base price');
    }
    if ($baseCategory->getId() !== $this->getBaseCategoryId() && !$this->isNew()) {
      throw new InvalidBaseCategoryException('The given base category instance does not match ' .
                                              'the base category id of this base price');
    } else if ($this->isNew()) {
      $this->source['base_category_id'] = $baseCategory->getId();
      $this->source['event_id'] = $baseCategory->getEventId();
    }
    return $this->baseCategory = $baseCategory;
  }


  /**
   * Get the title for this price
   *
   * @return string
   */
  public function getTitle()
  {
    return $this->source['title'];
  }


  /**
   * Mutates the title of this base price.
   *
   * @param string $title The title to set.
   * @return BasePrice
   */
  public function setTitle($title)
  {
    $this->source['title'] = $title;
    return $this;
  }


  /**
   * Get the price of this base price.
   *
   * @return float
   */
  public function getPrice()
  {
    return $this->source['price'];
  }


  /**
   * Mutates the price of this base price.
   *
   * @param float $price The price to set.
   * @return BasePrice
   */
  public function setPrice($price)
  {
    $this->source['price'] = $price;
    return $this;
  }


  /**
   * Get the currency of this bae price.
   *
   * @return string Alphanumeric currency code according to ISO 4217.
   */
  public function getCurrency()
  {
    return $this->source['currency'];
  }


  /**
   * Mutate the currency of this bae price.
   *
   * @param string $currency The currency to set.
   * @return BasePrice
   */
  public function setCurrency($currency)
  {
    $this->source['currency'] = $currency;
    return $this;
  }


  /**
   * @inheritdoc
   *
   * @param boolean $filter Whether to filter the source array for persisting this model.
   */
  public function toArray($filter = false)
  {
    if ($filter) {
      $matchedKeys = array_filter(array_keys($this->source), function($k) {
        return $k != 'base_price_id';
      });
      return array_intersect_key($this->source, array_flip($matchedKeys));
    } else {
      return parent::toArray();
    }
  }


  /**
   * @access private
   * @inheritdoc
   */
  public function setSource($source)
  {
    parent::setSource($source);
    $this->_isNew = false;
  }


  /**
   * Creates a new base price instance with the given data.
   * The resulting instance will be marked as a new base price
   * which has not been persisted via API.
   *
   * Use this method if you want to create a new base price and persist it via the client.
   *
   * @param array $data
   * @param NextEvent\PHPSDK\Model\BaseCategory $baseCategory The base category reference.
   * @return NextEvent\PHPSDK\Model\BasePrice
   */
  public static function spawn($data, $baseCategory)
  {
    $basePrice = new BasePrice($data);
    $basePrice->_isNew = true;
    $basePrice->setBaseCategory($baseCategory);
    if (!$basePrice->isValid()) {
      throw new InvalidModelDataException('Given $data for ' . get_class($basePrice) . ' creation is invalid');
    }
    return $basePrice;
  }
}
