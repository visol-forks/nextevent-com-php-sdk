<?php

namespace NextEvent\PHPSDK\Model;

/**
 * OrderDocument model
 *
 * Represents a printable PDF document attached to an order
 *
 * @package NextEvent\PHPSDK\Model
 */
class OrderDocument extends Model
{
  /**
   * @inheritdoc
   */
  public function isValid()
  {
    return isset($this->source['uri']);
  }

  /**
   * Get the URL from where the document can be downloaded
   *
   * @return string
   */
  public function getDownloadUrl()
  {
    return $this->source['uri'];
  }

  /**
   * Getter for the document title/label
   *
   * @return string
   */
  public function getTitle()
  {
    return $this->get('title');
  }
}
