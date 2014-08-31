<?php

/**
 * @file
 * Contains RestfulRateLimitRequest.
 */

class RestfulRateLimitRequest extends \RestfulRateLimitBase {

  /**
   * {@inheritdoc}
   */
  public function isRequestedEvent(array $request = array()) {
    return TRUE;
  }

}
