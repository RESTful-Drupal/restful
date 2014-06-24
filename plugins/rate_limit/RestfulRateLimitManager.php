<?php

/**
 * Contains RestfulRateLimitManager
 */

class RestfulRateLimitManager {
  const UNLIMITED_RATE_LIMIT = -1;

  /**
   * The identified user account for the request.
   * @var \stdClass
   */
  protected $account;

  /**
   * The plugin info array for the rate limit.
   * @var array
   */
  protected $pluginInfo;

  /**
   * Resource name being checked.
   * @var string
   */
  protected $resource;

  /**
   * Set the account.
   *
   * @param \stdClass $account
   */
  public function setAccount($account) {
    $this->account = $account;
  }

  /**
   * Set the plugin info.
   *
   * @param array $pluginInfo
   */
  public function setPluginInfo($pluginInfo) {
    $this->pluginInfo = $pluginInfo;
  }

  /**
   * Get the plugin info array.
   *
   * @return array
   */
  public function getPluginInfo() {
    return $this->pluginInfo;
  }

  /**
   * Constructor for RestfulRateLimitManager.
   *
   * @param string $resource
   *   Resource name being checked.
   * @param array $plugin_info
   *   The plugin info array for the rate limit.
   * @param \stdClass $account
   *   The identified user account for the request.
   */
  public function __construct($resource, $plugin_info, $account = NULL) {
    $this->resource = $resource;
    $this->setPluginInfo($plugin_info);
    $this->account = $account ? $account : drupal_anonymous_user();
  }

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
  public function checkRateLimit($request) {
    $now = new \DateTime();
    $now->setTimestamp(REQUEST_TIME);
    // Check all rate limits configured for this handler.
    foreach ($this->getPluginInfo() as $event_name => $info) {
      // If the limit is unlimited then skip everything.
      $limit = $this->rateLimit($info);
      if ($limit == static::UNLIMITED_RATE_LIMIT) {
        // User has unlimited access to the resources.
        continue;
      }
      // Check if there is a rate_limit plugin for the event.
      // There are no error checks on purpose, let the exceptions bubble up.
      $rate_limit_plugin = restful_get_rate_limit_plugin($info['event']);
      $rate_limit_class = ctools_plugin_get_class($rate_limit_plugin, 'class');

      $handler = new $rate_limit_class();
      // If the current request matches the configured event then check if the
      // limit has been reached.
      if (!$handler->isRequestedEvent($request)) {
        return;
      }
      if (!$rate_limit_entity = $this->loadRateLimitEntity($event_name)) {
        // If there is no entity, then create one.
        // We don't need to save it since it will be saved upon hit.
        $rate_limit_entity = entity_create('rate_limit', array(
          'timestamp' => REQUEST_TIME,
          'expiration' => $now->add($info['period'])->format('U'),
          'hits' => 0,
          'event' => $event_name,
          'identifier' => $this->generateIdentifier(),
        ));
      }
      if ($rate_limit_entity->isExpired()) {
        // If the rate limit has expired renew the timestamps and assume 0
        // hits.
        $rate_limit_entity->timestamp = REQUEST_TIME;
        $rate_limit_entity->expiration = $now->add($info['period'])->format('U');
        $rate_limit_entity->hits = 0;
        if ($limit == 0) {
          throw new \RestfulFloodException('Rate limit reached');
        }
      }
      else {
        if ($rate_limit_entity->hits >= $limit) {
          throw new \RestfulFloodException('Rate limit reached');
        }
      }
      // Save a new hit after generating the exception to mitigate DoS attacks.
      $rate_limit_entity->hit();

      // Add the limit headers to the response.
      $remaining = $limit == static::UNLIMITED_RATE_LIMIT ? 'unlimited' : $limit - ($rate_limit_entity->hits + 1);
      drupal_add_http_header('X-Rate-Limit-Limit', $limit, TRUE);
      drupal_add_http_header('X-Rate-Limit-Remaining', $remaining, TRUE);
      $time_remaining = $rate_limit_entity->expiration - REQUEST_TIME;
      drupal_add_http_header('X-Rate-Limit-Reset', $time_remaining, TRUE);

    }
  }

  /**
   * Generates an identifier for the event and the request.
   *
   * @return string
   */
  protected function generateIdentifier() {
    $identifier = $this->resource . '::';
    $identifier .= empty($this->account->uid) ? ip_address() : $this->account->uid;
    return $identifier;
  }

  /**
   * Load rate limit entity.
   *
   * @param string $event_name
   *   The name of the event.
   *
   * @return \RestfulRateLimit
   *   The loaded entity or NULL if none found.
   */
  protected function loadRateLimitEntity($event_name) {
    $query = new \EntityFieldQuery();
    $results = $query
      ->entityCondition('entity_type', 'rate_limit')
      ->entityCondition('bundle', $event_name)
      ->propertyCondition('identifier', $this->generateIdentifier())
      ->execute();
    if (empty($results['rate_limit'])) {
      return;
    }
    $rlid = key($results['rate_limit']);
    $rate_limit_entity = entity_load_single('rate_limit', $rlid);
    return $rate_limit_entity ? $rate_limit_entity : NULL;
  }

  /**
   * Gets the limit for the identified user.
   *
   * @param array $event_plugin_info
   *   The array containing the plugin info for the current event.
   *
   * @return int
   *   The limit.
   */
  protected function rateLimit($event_plugin_info) {
    // If the user is anonymous.
    if (empty($this->account->roles)) {
      return $event_plugin_info['limits']['anonymous user'];
    }
    // If the user is logged then return the best limit for all the roles the
    // user has.
    $max_limit = 0;
    foreach ($this->account->roles as $rid => $role) {
      if (!isset($event_plugin_info['limits'][$role])) {
        // No limit configured for this role.
        continue;
      }
      if ($event_plugin_info['limits'][$role] < $max_limit &&
        $event_plugin_info['limits'][$role] != static::UNLIMITED_RATE_LIMIT) {
        // The limit is smaller than one previously found.
        continue;
      }
      // This is the highest limit for the current user given all their roles.
      $max_limit = $event_plugin_info['limits'][$role];
    }
    return $max_limit;
  }

}