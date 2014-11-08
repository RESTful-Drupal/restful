<?php

/**
 * Contains RestfulRateLimitManager
 */

class RestfulRateLimitManager extends \RestfulPluginBase {
  const UNLIMITED_RATE_LIMIT = -1;

  /**
   * @var \stdClass
   *
   * The identified user account for the request.
   */
  protected $account;

  /**
   * @var string
   *
   * Resource name being checked.
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
   * Get the account.
   *
   * @return \stdClass
   *   The account object,
   */
  public function getAccount() {
    return $this->account;
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
   * Constructor for RestfulRateLimitManager.
   *
   * @param string $resource
   *   Resource name being checked.
   * @param array $plugin
   *   The plugin info array for the rate limit.
   * @param \stdClass $account
   *   The identified user account for the request.
   */
  public function __construct($resource, array $plugin, $account = NULL) {
    parent::__construct($plugin);
    $this->resource = $resource;
    $this->setPluginInfo($plugin);
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
    foreach ($this->getPlugin() as $event_name => $info) {
      // Check if there is a rate_limit plugin for the event.
      // There are no error checks on purpose, let the exceptions bubble up.
      $rate_limit_plugin = restful_get_rate_limit_plugin($info['event']);
      $rate_limit_class = ctools_plugin_get_class($rate_limit_plugin, 'class');

      $handler = new $rate_limit_class($info, $this->resource);
      // If the limit is unlimited then skip everything.
      $limit = $handler->getLimit($this->account);
      $period = $handler->getPeriod();
      if ($limit == static::UNLIMITED_RATE_LIMIT) {
        // User has unlimited access to the resources.
        continue;
      }
      // If the current request matches the configured event then check if the
      // limit has been reached.
      if (!$handler->isRequestedEvent($request)) {
        continue;
      }
      if (!$rate_limit_entity = $handler->loadRateLimitEntity($this->account)) {
        // If there is no entity, then create one.
        // We don't need to save it since it will be saved upon hit.
        $rate_limit_entity = entity_create('rate_limit', array(
          'timestamp' => REQUEST_TIME,
          'expiration' => $now->add($period)->format('U'),
          'hits' => 0,
          'event' => $event_name,
          'identifier' => $handler->generateIdentifier($this->account),
        ));
      }
      // When the new rate limit period starts.
      $new_period = new \DateTime();
      $new_period->setTimestamp($rate_limit_entity->expiration);
      if ($rate_limit_entity->isExpired()) {
        // If the rate limit has expired renew the timestamps and assume 0
        // hits.
        $rate_limit_entity->timestamp = REQUEST_TIME;
        $rate_limit_entity->expiration = $now->add($period)->format('U');
        $rate_limit_entity->hits = 0;
        if ($limit == 0) {
          $exception = new \RestfulFloodException('Rate limit reached');
          $exception->setHeader('Retry-After', $new_period->format(\DateTime::RFC822));
          throw $exception;
        }
      }
      else {
        if ($rate_limit_entity->hits >= $limit) {
          $exception = new \RestfulFloodException('Rate limit reached');
          $exception->setHeader('Retry-After', $new_period->format(\DateTime::RFC822));
          throw $exception;
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
   * Delete all expired rate limit entities.
   */
  public static function deleteExpired() {
    // Clear the expired restful_rate_limit entries.
    $query = new \EntityFieldQuery();
    $results = $query
      ->entityCondition('entity_type', 'rate_limit')
      ->propertyCondition('expiration', REQUEST_TIME, '>')
      ->execute();
    if (!empty($results['rate_limit'])) {
      $rlids = array_keys($results['rate_limit']);
      entity_delete_multiple('rate_limit', $rlids);
    }
  }
}
