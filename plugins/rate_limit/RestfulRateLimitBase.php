<?php

/**
 * @file
 * Contains \RestfulRateLimitBase
 */

abstract class RestfulRateLimitBase implements \RestfulRateLimitInterface {
  /**
   * @var array
   *
   * Array of maximum limit of requests across all endpoints per role.
   */
  protected $limits = array();

  /**
   * @var \DateInterval
   *
   * Period after which the rate limit is expired.
   */
  protected $period;

  /**
   * @var string
   *
   * The event name.
   */
  protected $name;

  /**
   * @var string
   *
   * The resource name this object is limiting access to.
   */
  protected $resource;

  /**
   * Constructor.
   *
   * @param array $plugin_info
   *   Plugin definition sub-array.
   *
   * @param \RestfulEntityBase $resource
   *   The resource this object is limiting access to.
   */
  public function __construct(array $plugin_info, $resource = NULL) {
    $this->period = $plugin_info['period'];
    $this->limits = $plugin_info['limits'];
    $this->name = $plugin_info['event'];
    $this->resource = $resource;
  }

  /**
   * Set the rate limit.
   *
   * @param array $limits
   *   The limits to set.
   */
  public function setLimit($limits) {
    $this->limits = $limits;
  }

  /**
   * Get the rate limit. Returns the highest rate limit for the current account.
   *
   * @param \stdClass $account
   *   The account object for the user making the request.
   *
   * @return int
   */
  public function getLimit(\stdClass $account = NULL) {
    // If the user is anonymous.
    if (empty($account->roles)) {
      return $this->limits['anonymous user'];
    }
    // If the user is logged then return the best limit for all the roles the
    // user has.
    $max_limit = 0;
    foreach ($account->roles as $rid => $role) {
      if (!isset($this->limits[$role])) {
        // No limit configured for this role.
        continue;
      }
      if ($this->limits[$role] < $max_limit &&
        $this->limits[$role] != \RestfulRateLimitManager::UNLIMITED_RATE_LIMIT) {
        // The limit is smaller than one previously found.
        continue;
      }
      // This is the highest limit for the current user given all their roles.
      $max_limit = $this->limits[$role];
    }
    return $max_limit;
  }

  /**
   * Set the rate limit period.
   *
   * @param \DateInterval $period
   */
  public function setPeriod(\DateInterval $period) {
    $this->period = $period;
  }

  /**
   * Get the rate limit period.
   *
   * @return \DateInterval
   */
  public function getPeriod() {
    return $this->period;
  }

  /**
   * Generates an identifier for the event and the request.
   *
   * @param \stdClass $account
   *   The account object for the user making the request.
   *
   * @return string
   */
  public function generateIdentifier(\stdClass $account = NULL) {
    $identifier = $this->resource . '::';
    if ($this->name == 'global') {
      // Don't split the id by resource if the event is global.
      $identifier = '';
    }
    $identifier .= $this->name . '::';
    $identifier .= empty($account->uid) ? ip_address() : $account->uid;
    return $identifier;
  }

  /**
   * Load rate limit entity.
   *
   * @param \stdClass $account
   *   The account object for the user making the request.
   *
   * @return \RestfulRateLimit
   *   The loaded entity or NULL if none found.
   */
  public function loadRateLimitEntity(\stdClass $account = NULL) {
    $query = new \EntityFieldQuery();
    $results = $query
      ->entityCondition('entity_type', 'rate_limit')
      ->entityCondition('bundle', $this->name)
      ->propertyCondition('identifier', $this->generateIdentifier($account))
      ->execute();
    if (empty($results['rate_limit'])) {
      return;
    }
    $rlid = key($results['rate_limit']);
    return entity_load_single('rate_limit', $rlid);
  }

}
