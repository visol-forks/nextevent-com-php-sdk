<?php

namespace NextEvent\PHPSDK\Model;

/**
 * Category model
 *
 * Struct for a category record assigned to an order/basket item.
 *
 * @package NextEvent\PHPSDK\Model
 */
class Category extends Model
{
  /**
   * @inheritdoc
   */
  public function isValid()
  {
    return isset($this->source['category_id']) &&
      isset($this->source['facets']) &&
      isset($this->source['displayname']) &&
      isset($this->source['event_id']);
  }


  /**
   * Get the unique identifier for this category
   *
   * @return int
   */
  public function getId()
  {
    return $this->source['category_id'];
  }


  /**
   * Get the title/name of this category
   *
   * @return string
   */
  public function getTitle()
  {
    if (isset($this->source['facets']['title']['value']))
      return $this->source['facets']['title']['value'];
    else
      return $this->source['displayname'];
  }


  /**
   * Get the display name of this category
   *
   * Use this in listing, checkout summary or invoices
   *
   * @return string
   */
  public function getDisplayname()
  {
    return $this->source['displayname'];
  }

  /**
   * Get the event identifier this category belongs to
   *
   * @return int
   */
  public function getEventId()
  {
    return $this->source['event_id'];
  }


  /**
   * Get the name of the event this category belongs to
   *
   * @return string
   */
  public function getEventTitle()
  {
    return $this->source['event_title'];
  }
}
