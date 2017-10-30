<?php

namespace NextEvent\PHPSDK\Model;

/**
 * A class which implements the Spawnable interface can be used to define whether a Model can be created, without
 * having specific properties set, such as a unique identifier.
 *
 * @package NextEvent\PHPSDK\Model
 */
interface Spawnable {

  /**
   * Determines whether this spawnable is new or not.
   *
   * @return bool
   */
  public function isNew();

}
