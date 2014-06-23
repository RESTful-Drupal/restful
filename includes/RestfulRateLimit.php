<?php

/**
 * @file
 * Contains RestfulRateLimit.
 */


class RestfulRateLimit extends Entity {

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
