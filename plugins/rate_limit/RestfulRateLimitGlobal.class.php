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
   *
   * All limits are the same for the global limit. Return the first one.
   */
  public function getLimit(\stdClass $account = NULL) {
    return reset($this->limits);
  }

  /**
   * {@inheritdoc}
   *
   * Only track the global limit for the current user if the variable is on.
   */
  public function isRequestedEvent(array $request = array()) {
    return $this->getLimit() > 0;
  }

}
