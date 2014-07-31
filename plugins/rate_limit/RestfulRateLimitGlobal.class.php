<?php

/**
 * @file
 * Contains RestfulRateLimitGlobal
 */

class RestfulRateLimitGlobal extends \RestfulRateLimitBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $plugin_info, $resource = NULL) {
    parent::__construct($plugin_info, $resource);
    $limit = variable_get('restful_global_rate_limit', 0);
    foreach (user_roles() as $rid => $role_info) {
      $this->limits[$rid] = $limit;
    }
    $this->period = new \DateInterval(variable_get('restful_global_rate_period', 'P1D'));
  }

  /**
   * {@inheritdoc}
   */
  public function generateIdentifier(\stdClass $account = NULL) {
    $identifier = '';
    $identifier .= $this->name . '::';
    $identifier .= empty($account->uid) ? ip_address() : $account->uid;
    return $identifier;
  }

  /**
   * {@inheritdoc}
   */
  public function getLimit(\stdClass $account = NULL) {
    // All limits are the same for the global limit. Return the first one.
    return reset($this->limits);
  }

  /**
   * {@inheritdoc}
   */
  public function isRequestedEvent(array $request = array()) {
    // Only track the global limit for the current user if the variable is on.
    return $this->getLimit() > 0;
  }

}
