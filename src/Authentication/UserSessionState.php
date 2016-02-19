<?php

/**
 * @file
 * Contains \Drupal\restful\Authentication\UserSessionState.
 */

namespace Drupal\restful\Authentication;

/**
 * Class UserSessionState.
 *
 * @package Drupal\restful\Authentication
 */
class UserSessionState implements UserSessionStateInterface {

  /**
   * Boolean holding if this is the first switch.
   *
   * @var bool
   */
  protected static $isSwitched = FALSE;

  /**
   * Boolean holding if the session needs to be saved.
   *
   * @var bool
   */
  protected $needsSaving = FALSE;

  /**
   * Object holding the original user.
   *
   * This is saved for switch back purposes.
   *
   * @var object
   */
  protected $originalUser;

  /**
   * {@inheritdoc}
   */
  public static function isSwitched() {
    return static::$isSwitched;
  }

  /**
   * {@inheritdoc}
   */
  public function switchUser($account) {
    global $user;

    if (!static::isSwitched() && !$this->originalUser && !$this->needsSaving) {
      // This is the first time a user switched, and there isn't an original
      // user session.
      $this->needsSaving = drupal_save_session();
      $this->originalUser = $user;

      // Don't allow a session to be saved. Provider that require a session to
      // be saved, like the cookie provider, need to explicitly set
      // drupal_save_session(TRUE).
      // @see LoginCookie__1_0::loginUser().
      drupal_save_session(FALSE);
    }

    // Set the global user.
    $user = $account;
  }

  /**
   * Switch the user to the authenticated user, and back.
   *
   * This should be called only for an API call. It should not be used for calls
   * via the menu system, as it might be a login request, so we avoid switching
   * back to the anonymous user.
   */
  public function switchUserBack() {
    global $user;
    if (!$this->originalUser) {
      return;
    }

    $user = $this->originalUser;
    drupal_save_session($this->needsSaving);
    $this->reset();
  }

  /**
   * Reset the initial values.
   */
  protected function reset() {
    // Reset initial values.
    static::$isSwitched = FALSE;
    $this->originalUser = NULL;
    $this->needsSaving = FALSE;
  }

}
