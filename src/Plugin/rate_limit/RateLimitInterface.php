<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\rate_limit\RateLimitInterface
 */

namespace Drupal\restful\Plugin\rate_limit;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\restful\Http\RequestInterface;

interface RateLimitInterface extends PluginInspectionInterface {
  /**
   * Checks if the current request meets the event for the implementing class.
   *
   * @param RequestInterface $request
   *   (optional) The request array.
   *
   * @return bool
   *   TRUE if the event is met and the rate limit hits counter should be
   *   incremented.
   */
  public function isRequestedEvent(RequestInterface $request);


  /**
   * Set the rate limit.
   *
   * @param array $limits
   *   The limits to set.
   */
  public function setLimit($limits);

  /**
   * Get the rate limit. Returns the highest rate limit for the current account.
   *
   * @param object $account
   *   The account object for the user making the request.
   *
   * @return int
   *   The limit.
   */
  public function getLimit($account = NULL);

  /**
   * Set the rate limit period.
   *
   * @param \DateInterval $period
   */
  public function setPeriod(\DateInterval $period);

  /**
   * Get the rate limit period.
   *
   * @return \DateInterval
   *   The period.
   */
  public function getPeriod();

  /**
   * Generates an identifier for the event and the request.
   *
   * @param object $account
   *   The account object for the user making the request.
   *
   * @return string
   *   The ID.
   */
  public function generateIdentifier($account = NULL);

  /**
   * Load rate limit entity.
   *
   * @param object $account
   *   The account object for the user making the request.
   *
   * @return RateLimitInterface
   *   The loaded entity or NULL if none found.
   */
  public function loadRateLimitEntity($account = NULL);

}
