<?php

/**
 * @file
 * Contains \Drupal\restful\Authentication\UserSessionStateInterface.
 */

namespace Drupal\restful\Authentication;

/**
 * Class UserSessionStateInterface.
 *
 * @package Drupal\restful\Authentication
 */
interface UserSessionStateInterface {

  /**
   * Check if the user has already been switched.
   *
   * We need this information to perform additional actions the first time a
   * user is switched.
   *
   * @return bool
   *   TRUE if the user has been switched previously. FALSE otherwise.
   */
  public static function isSwitched();

  /**
   * Make the passed in user to be the account for the Drupal thread.
   *
   * @param object $account
   *   The account to switch to.
   */
  public function switchUser($account);

  /**
   * Switch the user to the authenticated user, and back.
   *
   * This should be called only for an API call. It should not be used for calls
   * via the menu system, as it might be a login request, so we avoid switching
   * back to the anonymous user.
   */
  public function switchUserBack();

}
