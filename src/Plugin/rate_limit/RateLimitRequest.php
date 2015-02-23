<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\rate_limit\RateLimitRequest
 */

namespace Drupal\restful\Plugin\rate_limit;
use Drupal\restful\Http\RequestInterface;

/**
 * Class RateLimitGlobal
 * @package Drupal\restful\Plugin\rate_limit
 *
 * @RateLimit(
 *   id = "request",
 *   label = "Any request",
 *   description = "The basic rate limit plugin. Every call to a resource is counted.",
 * )
 */
class RateLimitRequest extends RateLimit {

  /**
   * {@inheritdoc}
   */
  public function isRequestedEvent(RequestInterface $request) {
    return TRUE;
  }

}
