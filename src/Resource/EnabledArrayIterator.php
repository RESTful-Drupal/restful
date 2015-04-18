<?php

/**
 * @file
 * Contains \Drupal\restful\Resource\EnabledArrayIterator.
 */

namespace Drupal\restful\Resource;

class EnabledArrayIterator extends \FilterIterator {

  /**
   * Check whether the current element of the iterator is acceptable.
   *
   * @return bool
   *   TRUE if the current element is acceptable, otherwise FALSE.
   *
   * @link http://php.net/manual/en/filteriterator.accept.php
   */
  public function accept() {
    if (!$resource = $this->current()) {
      return FALSE;
    }
    return $resource->isEnabled();
  }

}
