<?php

namespace NextEvent\PHPSDK\Model;

use NextEvent\PHPSDK\Exception\InvalidModelDataException;

/**
 * BaseCategory model
 *
 * Provides the structure for a base category record.
 *
 * Base categories are categories which can not be booked and are never linked to an order item.
 * Base categories are internally used to create category records.
 * All values which are set for a base category will automatically be applied to
 * the related category records.
 *
 * @package NextEvent\PHPSDK\Model
 */
class BaseCategory extends MutableModel implements Spawnable
{

  /**
   * The internal reference to the basePrices collection.
   *
   * @var NextEvent\PHPSDK\Model\Collection
   */
  protected $basePrices;

  /**
   * A reference to the rest client.
   *
   * @var NextEvent\PHPSDK\Rest\Client
   */
  protected $restClient;

  /**
   * Internal flag for determining whether this base category is new, i.e. not persisted yet.
   *
   * @var bool
   */
  protected $_isNew;

  /**
   * @inheritdoc
   * @param \NextEvent\PHPSDK\Rest\Client|null $restClient Rest client reference for fetching base prices.
   */
  public function __construct($source, $restClient = null)
  {
    if (isset($source['base_category_id'])) {
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
  public function isNew()
  {
    return $this->_isNew;
  }


  /**
   * @inheritdoc
   */
  public function isValid()
  {
    return (isset($this->source['base_category_id']) || $this->isNew()) &&
            isset($this->source['title']) &&
            isset($this->source['event_id']);
  }


  /**
   * Set the rest client for this base category, base prices may be fetched for it.
   *
   * @param \NextEvent\PHPSDK\Rest\Client $restClient
   * @return BasePrice
   */
  public function setRestClient($restClient)
  {
    $this->restClient = $restClient;
    return $this;
  }


  /**
   * Get the unique identifier for this base category.
   *
   * @return int
   */
  public function getId()
  {
    return isset($this->source['base_category_id']) ? $this->source['base_category_id'] : -1;
  }


  /**
   * Get the creation data of this base category
   *
   * @return DateTime|null
   */
  public function getCreatedDate()
  {
    return isset($this->source['created']) ? DateTime::fromJson($this->source['created']) : null;
  }


  /**
   * Get the changed date of this base category
   *
   * @return DateTime|null
   */
  public function getChangedDate()
  {
    return isset($this->source['changed']) ? DateTime::fromJson($this->source['changed']) : null;
  }


  /**
   * Get the title/name of this base category.
   *
   * @return string
   */
  public function getTitle()
  {
    if (isset($this->source['facets']['title']['value'])) {
      return $this->source['facets']['title']['value'];
    } else if (isset($this->source['displayname'])) {
      return $this->source['displayname'];
    } else {
      return $this->source['title'];
    }
  }


  /**
   * Set the title/name of this base category.
   *
   * @param string
   * @return BaseCategory
   */
  public function setTitle($title)
  {
    if (isset($this->source['facets']['title'])) {
      $this->source['facets']['title']['value'] = $title;
    } else {
      $this->source['title'] = $title;
    }
    return $this;
  }


  /**
   * Get the display name of this category
   *
   * Use this in listing, checkout summary or invoices
   *
   * @return string|null
   */
  public function getDisplayname()
  {
    return isset($this->source['displayname']) ? $this->source['displayname'] : null;
  }

  /**
   * Get the event identifier this category belongs to
   *
   * @return string
   */
  public function getEventId()
  {
    return strval($this->source['event_id']);
  }


  /**
   * Returns a list of base prices for this base category.
   *
   * @return Collection
   */
  public function getBasePrices()
  {
    if (!isset($this->basePrices) && isset($this->restClient)) {
      $prices = $this->restClient->get('/base_price?base_category_id=' . $this->getId())->getContent();
      $collection = new Collection('NextEvent\PHPSDK\Model\BasePrice', array(), $prices, $this->restClient);
      $this->setBasePrices($collection);
    }

    return $this->basePrices;
  }


  /**
   * Sets the base prices for this base category.
   *
   * @param \NextEvent\PHPSDK\Model\Collection $basePrices
   * @return void
   */
  public function setBasePrices($basePrices)
  {
    foreach ($basePrices as $price) {
      $price->setBaseCategory($this);
    }
    $this->basePrices = $basePrices;
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
        return $k != 'base_category_id';
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
   * Creates a new base category instance with the given data.
   * The resulting instance will be marked as a new base category
   * which has not been persisted via API.
   *
   * The created category will also get an empty base price collection assigned.
   * This way, you to not have to do it yourself and can just push your prices into the collection.
   *
   * Use this method if you want to create a new base category and persist it via the client.
   *
   * @param array $data
   * @return BaseCategory
   */
  public static function spawn($data)
  {
    $baseCategory = new BaseCategory($data);
    $baseCategory->_isNew = true;
    $baseCategory->setBasePrices(new Collection('NextEvent\PHPSDK\Model\BasePrice'));
    if (!$baseCategory->isValid()) {
      throw new InvalidModelDataException('Given $data for ' . get_class($baseCategory) . ' creation is invalid');
    }
    return $baseCategory;
  }
}
