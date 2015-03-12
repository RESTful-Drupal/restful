<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\rate_limit\RateLimitGlobal
 */

namespace Drupal\restful\Plugin\rate_limit;
use Drupal\Component\Plugin\PluginBase;
use Drupal\restful\Http\RequestInterface;

/**
 * Class RateLimitGlobal
 * @package Drupal\restful\Plugin\rate_limit
 *
 * @RateLimit(
 *   id = "global",
 *   label = "Global limitation",
 *   description = "This keeps a count across all the handlers.",
 * )
 */
class RateLimitGlobal extends RateLimit {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $limit = variable_get('restful_global_rate_limit', 0);
    foreach (user_roles() as $rid => $role_info) {
      $this->limits[$rid] = $limit;
    }
    $this->period = new \DateInterval(variable_get('restful_global_rate_period', 'P1D'));
  }

  /**
   * {@inheritdoc}
   */
  public function generateIdentifier($account = NULL) {
    $identifier = '';
    $identifier .= $this->getPluginId() . PluginBase::DERIVATIVE_SEPARATOR;
    $identifier .= empty($account->uid) ? ip_address() : $account->uid;
    return $identifier;
  }

  /**
   * {@inheritdoc}
   *
   * All limits are the same for the global limit. Return the first one.
   */
  public function getLimit($account = NULL) {
    return reset($this->limits);
  }

  /**
   * {@inheritdoc}
   *
   * Only track the global limit for the current user if the variable is on.
   */
  public function isRequestedEvent(RequestInterface $request) {
    return $this->getLimit() > 0;
  }

}
