<?php

namespace NextEvent\PHPSDK\Model;

/**
 * Event model
 *
 * Model class for an event record registered in NextEvent.
 * Privides simplified access to event title, details, start/end dates, etc.
 *
 * @see http://schema.org/Event
 * @package NextEvent\PHPSDK\Model
 */
class Event extends Model
{
  /**
   * @inheritdoc
   */
  public function isValid()
  {
    // some fields may be empty
    return isset($this->source['identifier']) &&
      isset($this->source['state']) &&
      array_key_exists('name',$this->source) &&
      array_key_exists('description', $this->source);
  }


  /**
   * Getter for the unique event identifier
   *
   * @return string
   */
  public function getId()
  {
    return strval($this->source['identifier']);
  }


  /**
   * Get the state of this event
   *
   * Tells whether the event is active for sale
   * or in another pre or post sale state.
   * 
   * Possible values are: draft, active, ended, processed,
   * cancelled, closed, pendingdebit, pendingcredit, credited, archived
   *
   * @return string
   */
  public function getState()
  {
    return $this->source['state'];
  }


  /**
   * Get the event title
   *
   * @return string
   */
  public function getTitle()
  {
    return $this->source['name'];
  }


  /**
   * Get a description text for the event
   *
   * @return string
   */
  public function getDescription()
  {
    return $this->source['description'];
  }


  /**
   * Get the location this event takes place
   *
   * @return Location|null
   */
  public function getLocation()
  {
    return isset($this->source['location']) ? new Location($this->source['location']) : null;
  }


  /**
   * Get the start date of this event
   *
   * @return \DateTime|null
   */
  public function getStartDate()
  {
    return isset($this->source['startDate']) ? new \DateTime($this->source['startDate']) : null;
  }


  /**
   * Get the end date of this event
   *
   * @return \DateTime|null
   */
  public function getEndDate()
  {
    return isset($this->source['endDate']) ? new \DateTime($this->source['endDate']) : null;
  }
}
