<?php

/**
 * @file
 * Contains RestfulRateLimitInterface.
 */

interface RestfulRateLimitInterface {
  /**
   * Checks if the current request meets the event for the implementing class.
   *
   * @param array $request
   *   (optional) The request array.
   *
   * @return boolean
   *   TRUE if the event is met and the rate limit hits counter should be
   *   incremented.
   */
  public function isRequestedEvent(array $request = array());

}
