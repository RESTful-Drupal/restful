<?php

/**
 * @file
 * Contains RestfulRateLimitRequest.
 */

class RestfulRateLimitRequest implements RestfulRateLimitInterface {

  /**
   * {@inheritdoc}
   */
  public function isRequestedEvent($request = NULL) {
    return TRUE;
  }
}

