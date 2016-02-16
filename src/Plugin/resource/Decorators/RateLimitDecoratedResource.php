<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Decorators\RateLimitDecoratedResource
 */

namespace Drupal\restful\Plugin\resource\Decorators;

use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\RateLimit\RateLimitManager;

/**
 * Class RateLimitDecoratedResource.
 *
 * @package Drupal\restful\Plugin\resource\Decorators
 */
class RateLimitDecoratedResource extends ResourceDecoratorBase implements ResourceDecoratorInterface {

  /**
   * Authentication manager.
   *
   * @var RateLimitManager
   */
  protected $rateLimitManager;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param ResourceInterface $subject
   *   The decorated object.
   * @param RateLimitManager $rate_limit_manager
   *   Injected rate limit manager.
   */
  public function __construct(ResourceInterface $subject, RateLimitManager $rate_limit_manager = NULL) {
    $this->subject = $subject;
    $plugin_definition = $subject->getPluginDefinition();
    $rate_limit_info = empty($plugin_definition['rateLimit']) ? array() : $plugin_definition['rateLimit'];
    if ($limit = variable_get('restful_global_rate_limit', 0)) {
      $rate_limit_info['global'] = array(
        'period' => variable_get('restful_global_rate_period', 'P1D'),
        'limits' => array(),
      );
      foreach (user_roles() as $role_name) {
        $rate_limit_info['global']['limits'][$role_name] = $limit;
      }
    }
    $this->rateLimitManager = $rate_limit_manager ? $rate_limit_manager : new RateLimitManager($this, $rate_limit_info);
  }

  /**
   * Setter for $rateLimitManager.
   *
   * @param RateLimitManager $rate_limit_manager
   *   The rate limit manager.
   */
  protected function setRateLimitManager(RateLimitManager $rate_limit_manager) {
    $this->rateLimitManager = $rate_limit_manager;
  }

  /**
   * Getter for $rate_limit_manager.
   *
   * @return RateLimitManager
   *   The rate limit manager.
   */
  protected function getRateLimitManager() {
    return $this->rateLimitManager;
  }

  /**
   * {@inheritdoc}
   */
  public function process() {
    // This will throw the appropriate exception if needed.
    $this->getRateLimitManager()->checkRateLimit($this->getRequest());
    return $this->subject->process();
  }

  /**
   * {@inheritdoc}
   */
  public function setAccount($account) {
    $this->subject->setAccount($account);
    $this->rateLimitManager->setAccount($account);
  }

}
