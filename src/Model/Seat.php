<?php

namespace NextEvent\PHPSDK\Model;

/**
 * Seat information model
 *
 * Struct for seat information assigend an order/basket item.
 *
 * @package NextEvent\PHPSDK\Model
 */
class Seat extends Model
{
  /**
   * @inheritdoc
   */
  public function isValid()
  {
    return isset($this->source['seat_id']) &&
      isset($this->source['description']) &&
      isset($this->source['row']);
  }


  /**
   * Get the unique identifier for this seat
   *
   * @return int
   */
  public function getId()
  {
    return $this->source['seat_id'];
  }


  /**
   * Get the display name of this seat
   *
   * Can be used to display seat information in basket/order listings
   *
   * @return string
   */
  public function getDisplayname()
  {
    if (isset($this->source['displayname'])) {
      return $this->source['displayname'];
    }

    $segments = [$this->getRowLabel('R%s'), $this->getPlaceLabel('S%s')];
    return join(' / ', array_filter($segments));
  }


  /**
   * Compose a label for the seat's row information
   *
   * @param string $template (Localized) template with a %s placeholder for the row number
   * @return string
   */
  public function getRowLabel($template = '')
  {
    if ($this->source['row'] !== '' && strpos($template, '%') !== false) {
      return sprintf($template, $this->source['row']);
    }

    return $this->source['row'];
  }


  /**
   * Compose a label for the seat's place information
   *
   * @param string $template (Localized) template with a %s placeholder for the place number
   * @return string
   */
  public function getPlaceLabel($template = '')
  {
    if ($this->source['description'] !== '' && strpos($template, '%') !== false) {
      return sprintf($template, $this->source['description']);
    }

    return $this->source['description'];
  }


  /**
   * Get the name/title of the seat map
   *
   * @return string|null
   */
  public function getMapTitle()
  {
    return isset($this->source['seat_map_title']) ? $this->source['seat_map_title'] : null;
  }

}
