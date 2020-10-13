<?php

namespace NextEvent\PHPSDK\Model;

use NextEvent\PHPSDK\Exception\MissingDocumentException;

/**
 * Ticket model
 *
 * Model class representing a booked ticket
 *
 * @package NextEvent\PHPSDK\Model
 */
class Ticket extends Model
{
  /**
   * @inheritdoc
   */
  public function isValid()
  {
    return isset($this->source['ticket_id']) &&
      isset($this->source['status']);
  }


  /**
   * Get the unique identifier of this item
   *
   * @return int
   */
  public function getId()
  {
    return $this->source['ticket_id'];
  }


  /**
   * Check if ticket has been issued
   *
   * Once it's issued there should be a document attached
   *
   * @return bool
   */
  public function isIssued()
  {
    return $this->source['status'] === 'issued';
  }


  /**
   * Getter for ticket state
   *
   * @return string Either one of 'new', 'issued', 'revoked'
   */
  public function getState()
  {
    return $this->source['status'];
  }


  /**
   * Check for attached document
   *
   * @return bool
   */
  public function hasDocument()
  {
    return $this->isIssued() &&
      isset($this->source['document']) &&
      is_array($this->source['document']);
  }


  /**
   * Get the printable PDF document attached to this ticket
   *
   * @return TicketDocument
   * @throws MissingDocumentException
   */
  public function getDocument()
  {
    if ($this->hasDocument()) {
      return new TicketDocument($this->source['document'] + ['title' => $this->getEventTitle()]);
    } else {
      throw new MissingDocumentException('Missing document for ticket ' . $this->getId());
    }
  }


  /**
   * Get the title of the event this ticket refers to
   *
   * @return string
   */
  public function getEventTitle()
  {
    return isset($this->source['event']['title']) ? $this->source['event']['title'] : '';
  }
}
