<?php

/**
 * @file
 * Contains \Drupal\restful\Formatter\FormatterManagerInterface
 */

namespace Drupal\restful\Formatter;

interface FormatterManagerInterface {

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
   * @param array $request
   *   The request array.
   *
   * @throws \RestfulFloodException if the rate limit has been reached for the
   * current request.
   */
  public function checkFormatter($request);

  /**
   * Delete all expired rate limit entities.
   */
  public static function deleteExpired();

}
