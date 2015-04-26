<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Decorators\RateLimitedResource
 */

namespace Drupal\restful\Plugin\resource\Decorators;

use Drupal\restful\Plugin\resource\Field\ResourceFieldCollection;
use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\RateLimit\RateLimitManager;

class RateLimitedResource extends ResourceDecoratorBase implements ResourceDecoratorInterface {

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
    $this->rateLimitManager = $rate_limit_manager ? $rate_limit_manager : new RateLimitManager($this, $plugin_definition['rateLimit']);
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

}
