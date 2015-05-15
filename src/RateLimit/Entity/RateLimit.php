<?php

/**
 * @file
 * Contains Drupal\restful\RateLimit\Entity\RateLimit.
 */

namespace Drupal\restful\RateLimit\Entity;

class RateLimit extends \Entity {

  /**
   * Number of hits.
   *
   * @var int
   */
  public $hits = 0;

  /**
   * Expiration timestamp.
   *
   * @var int
   */
  public $expiration = 0;

  /**
   * Saves an extra hit.
   */
  public function hit() {
    $this->hits++;
    $this->save();
  }

  /**
   * Checks if the entity is expired.
   */
  public function isExpired() {
    return REQUEST_TIME > $this->expiration;
  }

}
