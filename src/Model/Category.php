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
    else if (isset($this->source['displayname']))
      return $this->source['displayname'];
    else
      return $this->source['title'];
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
   * @return string
   */
  public function getEventId()
  {
    return strval($this->source['event_id']);
  }


  /**
   * Get the name of the event this category belongs to
   *
   * @return string|null
   */
  public function getEventTitle()
  {
    return isset($this->source['event_title']) ? $this->source['event_title'] : null;
  }


  /**
   * Getter for the deleted flag
   *
   * Denotes that the category has been deleted and is no longer
   * available for booking.
   *
   * @return bool True if this category has been deleted
   */
  public function isDeleted()
  {
    return !empty($this->source['deleted']);
  }
}
