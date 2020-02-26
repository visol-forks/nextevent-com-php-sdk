<?php

namespace NextEvent\PHPSDK\Model;

use NextEvent\PHPSDK\Exception\InvalidModelDataException;

/**
 * Event model
 *
 * Model class for an event record registered in NextEvent.
 * Provides simplified access to event title, details, start/end dates, etc.
 *
 * @see http://schema.org/Event
 * @package NextEvent\PHPSDK\Model
 */
class Event extends MutableModel implements Spawnable
{
  /**
   * Mapping of JSON data keys to match the expected source structure
   *
   * @var array
   */
  protected $mapJson = [
    'event_id'    => 'identifier',
    'title'       => 'name',
    'title_image' => 'image',
    'created'     => 'createdDate',
    'changed'     => 'changedDate',
    'description' => 'description',
    'location'    => 'location',
    'organizer'   => 'organizer',
  ];

  /**
   * Internal flag for determining whether this event is new, i.e. not persisted yet.
   *
   * @var bool
   */
  protected $_isNew;

  /**
   * @inheritdoc
   */
  public function __construct($source)
  {
    if (array_key_exists('event_id', $source)) {
      $source = self::mapSource($source, $this->mapJson, false) + ['state' => 'active'];
    }
    if (isset($source['identifier'])) {
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
    // some fields may be empty
    if ($this->isNew()) {
      return isset($this->source['title']);
    } else {
      return isset($this->source['identifier']) &&
        isset($this->source['state']) &&
        array_key_exists('name', $this->source) &&
        array_key_exists('description', $this->source);
    }
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
   * Possible values are: `draft`, `active`, `closed`.
   * Note, that booking in `draft` and `closed` is only possible for admin of the event.
   * In addition the order process can only be completed if the basket has __only__ tickets for `active` events.
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
    return $this->isNew() ? $this->source['title'] : $this->source['name'];
  }


  /**
   * Mutates the title of this event.
   *
   * @param string $title
   * @return Event
   */
  public function setTitle($title)
  {
    $this->source['title'] = $title;
    return $this;
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
   * Mutates the description of this event.
   *
   * @param string $description
   * @return Event
   */
  public function setDescription($description)
  {
    $this->source['description'] = $description;
    return $this;
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
   * Get the organizer info for this event
   *
   * @return Organization|null
   */
  public function getOrganizer()
  {
    return isset($this->source['organizer']) ? new Organization($this->source['organizer']) : null;
  }


  /**
   * Get the creation data of this event
   *
   * @return DateTime|null
   */
  public function getCreatedDate()
  {
    return isset($this->source['createdDate']) ? DateTime::fromJson($this->source['createdDate']) : null;
  }


  /**
   * Get the changed date of this event
   *
   * @return DateTime|null
   */
  public function getChangedDate()
  {
    return isset($this->source['changedDate']) ? DateTime::fromJson($this->source['changedDate']) : null;
  }

  /**
   * Get the start date of this event
   *
   * @return DateTime|null
   */
  public function getStartDate()
  {
    return isset($this->source['startDate']) ? DateTime::fromJson($this->source['startDate']) : null;
  }


  /**
   * Get the end date of this event
   *
   * @return DateTime|null
   */
  public function getEndDate()
  {
    return isset($this->source['endDate']) ? DateTime::fromJson($this->source['endDate']) : null;
  }


  /**
   * Get the title image of the event
   *
   * @return string|null The image url as string (if set)
   */
  public function getImage()
  {
    return isset($this->source['image']) ? $this->source['image'] : null;
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
   * Creates a new event instance with the given data.
   * The resulting instance will be marked as a new event
   * which has not been persisted via API.
   *
   * @param array $data
   * @return Event
   */
  public static function spawn($data)
  {
    $event = new Event($data);
    $event->_isNew = true;
    if (!$event->isValid()) {
      throw new InvalidModelDataException('Given $data for ' . get_class($event) . ' creation is invalid');
    }
    return $event;
  }
}
