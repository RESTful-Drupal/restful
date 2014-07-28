<?php

/**
 * @file
 * Contains RestfulRateLimitGlobal
 */

class RestfulRateLimitGlobal implements RestfulRateLimitInterface {

  /**
   * {@inheritdoc}
   */
  public function isRequestedEvent($request = NULL) {
    // Only track the global limit for the current user if the variable is on.
    return variable_get('restful_global_rate_limit', 0) > 0;
  }

}
