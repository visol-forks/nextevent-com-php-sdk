<?php

namespace NextEvent\PHPSDK\Model;

use GuzzleHttp\Message\ResponseInterface;

/**
 * Class HALResponse
 *
 * A wrapper class for Responses returned by GuzzleHttp.
 * The Responses are parsed as HAL+JSON and made accessible by this class.
 *
 * @package NextEvent\PHPSDK\REST
 */
class HALResponse extends APIResponse
{
  /**
   * HALResponse constructor.
   *
   * Wrap Response as HALResponse
   * @param ResponseInterface $response
   */
  public function __construct($response)
  {
    parent::__construct($response);
    $this->content = null;
  }


  /**
   * Get the entities embedded in this response
   *
   * @return array
   */
  public function getEmbedded()
  {
    $content = $this->getContent();

    if ($content) {
      return isset($content['_embedded']) ? $content['_embedded'] : $content;
    } else {
      return null;
    }
  }


  /**
   * Get the page size
   *
   * @return int Number of items per page
   */
  public function getPageSize()
  {
    $content = $this->getContent();

    if ($content) {
      return $content['page_size'];
    } else {
      return null;
    }
  }


  /**
   * Get the total number of items
   *
   * @return int Number of items in this result set
   */
  public function getTotalItems()
  {
    $content = $this->getContent();

    if ($content) {
      return $content['total_items'];
    } else {
      return null;
    }
  }


  /**
   * Get the number of pages
   *
   * @return int
   */
  public function getPageCount()
  {
    $content = $this->getContent();

    if ($content) {
      return $content['page_count'];
    } else {
      return null;
    }
  }


  /**
   * Get the current page
   *
   * @return int Current page of the result set, starting at 1
   */
  public function getPage()
  {
    $content = $this->getContent();

    if ($content) {
      return $content['page'];
    } else {
      return null;
    }
  }
}
