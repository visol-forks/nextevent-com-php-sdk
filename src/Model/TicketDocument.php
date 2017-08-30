<?php

namespace NextEvent\PHPSDK\Model;

/**
 * TicketDocument model
 *
 * Represents a printable PDF document attached to a ticket
 *
 * @package NextEvent\PHPSDK\Model
 */
class TicketDocument extends Model
{
  /**
   * @inheritdoc
   */
  public function isValid()
  {
    return isset($this->source['document_id']) &&
      isset($this->source['uri']);
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
}
