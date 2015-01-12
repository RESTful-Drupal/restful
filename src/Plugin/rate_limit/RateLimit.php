<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\rate_limit\RateLimit
 */

namespace Drupal\restful\Plugin\rate_limit;

use Drupal\Component\Plugin\PluginBase;
use Drupal\restful\RateLimit\RateLimitManager;

abstract class RateLimit extends PluginBase implements RateLimitInterface {

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
   * @var \RestfulBase
   *
   * The resource this object is limiting access to.
   */
  protected $resource;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->period = $configuration['period'];
    $this->limits = $configuration['limits'];
    $this->resource = $configuration['resource'];
  }

  /**
   * {@inheritdoc}
   */
  public function setLimit($limits) {
    $this->limits = $limits;
  }

  /**
   * {@inheritdoc}
   */
  public function getLimit($account = NULL) {
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
        $this->limits[$role] != RateLimitManager::UNLIMITED_RATE_LIMIT) {
        // The limit is smaller than one previously found.
        continue;
      }
      // This is the highest limit for the current user given all their roles.
      $max_limit = $this->limits[$role];
    }
    return $max_limit;
  }

  /**
   * {@inheritdoc}
   */
  public function setPeriod(\DateInterval $period) {
    $this->period = $period;
  }

  /**
   * {@inheritdoc}
   */
  public function getPeriod() {
    return $this->period;
  }

  /**
   * {@inheritdoc}
   */
  public function generateIdentifier($account = NULL) {
    $identifier = $this->resource->getResourceName() . '::';
    if ($this->getPluginId() == 'global') {
      // Don't split the id by resource if the event is global.
      $identifier = '';
    }
    $identifier .= $this->getPluginId() . '::';
    $identifier .= empty($account->uid) ? ip_address() : $account->uid;
    return $identifier;
  }

  /**
   * {@inheritdoc}
   */
  public function loadRateLimitEntity($account = NULL) {
    $query = new \EntityFieldQuery();
    $results = $query
      ->entityCondition('entity_type', 'rate_limit')
      ->entityCondition('bundle', $this->getPluginId())
      ->propertyCondition('identifier', $this->generateIdentifier($account))
      ->execute();
    if (empty($results['rate_limit'])) {
      return;
    }
    $rlid = key($results['rate_limit']);
    return entity_load_single('rate_limit', $rlid);
  }

}
