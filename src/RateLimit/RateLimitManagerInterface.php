<?php

/**
 * @file
 * Contains \Drupal\restful\RateLimit\RateLimitManagerInterface
 */

namespace Drupal\restful\RateLimit;

use \Drupal\restful\Exception\FloodException;
use Drupal\restful\Http\RequestInterface;

interface RateLimitManagerInterface {

  /**
   * Set the account.
   *
   * @param object $account
   */
  public function setAccount($account);

  /**
   * Get the account.
   *
   * @return object
   *   The account object,
   */
  public function getAccount();

  /**
   * Checks if the current request has reached the rate limit.
   *
   * If the user has reached the limit this method will throw an exception. If
   * not, the hits counter will be updated for subsequent calls. Since the
   * request can match multiple events, the access is only granted if all events
   * are cleared.
   *
   * @param RequestInterface $request
   *   The request array.
   *
   * @throws FloodException if the rate limit has been reached for the
   * current request.
   */
  public function checkRateLimit(RequestInterface $request);

  /**
   * Delete all expired rate limit entities.
   */
  public static function deleteExpired();

}
