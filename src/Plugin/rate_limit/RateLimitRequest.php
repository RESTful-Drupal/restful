<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\rate_limit\RateLimitRequest
 */

namespace Drupal\restful\Plugin\rate_limit;

/**
 * Class RateLimitGlobal
 * @package Drupal\restful\Plugin\rate_limit
 *
 * @RateLimit(
 *   name = "request",
 *   label = "Any request",
 *   description = "The basic rate limit plugin. Every call to a resource is counted.",
 * )
 */
class RateLimitRequest extends RateLimit {

  /**
   * {@inheritdoc}
   */
  public function isRequestedEvent(array $request = array()) {
    return TRUE;
  }

}
