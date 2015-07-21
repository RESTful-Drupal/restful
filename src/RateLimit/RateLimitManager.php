<?php

/**
 * @file
 * Contains \Drupal\restful\RateLimit\RateLimitManager
 */

namespace Drupal\restful\RateLimit;

use Drupal\Component\Plugin\PluginBase;
use Drupal\restful\Exception\FloodException;
use Drupal\restful\Http\HttpHeader;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\RateLimitPluginManager;
use Drupal\restful\Plugin\rate_limit\RateLimit;
use Drupal\restful\Plugin\resource\ResourceInterface;

class RateLimitManager implements RateLimitManagerInterface {

  const UNLIMITED_RATE_LIMIT = -1;

  /**
   * The identified user account for the request.
   *
   * @var object
   */
  protected $account;

  /**
   * Resource name being checked.
   *
   * @var string
   */
  protected $resource;

  /**
   * The rate limit plugins.
   *
   * @var RateLimitPluginCollection
   */
  protected $plugins;

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
   * Constructor for RateLimitManager.
   *
   * @param ResourceInterface $resource
   *   Resource being checked.
   * @param array $plugin_options
   *   Array of options keyed by plugin id.
   * @param object $account
   *   The identified user account for the request.
   * @param RateLimitPluginManager $manager
   *   The plugin manager.
   */
  public function __construct(ResourceInterface $resource, array $plugin_options, $account = NULL, RateLimitPluginManager $manager = NULL) {
    $this->resource = $resource;
    $account = $account ? $account : $resource->getAccount();
    $this->account = $account ? $account : drupal_anonymous_user();
    $manager = $manager ?: RateLimitPluginManager::create();
    $options = array();
    foreach ($plugin_options as $plugin_id => $rate_options) {
      // Set the instance id to articles::request and specify the plugin id.
      $instance_id = $resource->getResourceName() . PluginBase::DERIVATIVE_SEPARATOR . $plugin_id;
      $options[$instance_id] = array(
        'id' => $plugin_id,
        'resource' => $resource,
      );
      $options[$instance_id] += $rate_options;
    }
    $this->plugins = new RateLimitPluginCollection($manager, $options);
  }

  /**
   * Checks if the current request has reached the rate limit.
   *
   * If the user has reached the limit this method will throw an exception. If
   * not, the hits counter will be updated for subsequent calls. Since the
   * request can match multiple events, the access is only granted if all events
   * are cleared.
   *
   * @param RequestInterface $request
   *   The request array.
   *
   * @throws FloodException if the rate limit has been reached for the
   * current request.
   */
  public function checkRateLimit(RequestInterface $request) {
    $now = new \DateTime();
    $now->setTimestamp(REQUEST_TIME);
    // Check all rate limits configured for this handler.
    foreach ($this->plugins as $instance_id => $plugin) {
      // If the limit is unlimited then skip everything.
      /* @var RateLimit $plugin */
      $limit = $plugin->getLimit($this->account);
      $period = $plugin->getPeriod();
      if ($limit == static::UNLIMITED_RATE_LIMIT) {
        // User has unlimited access to the resources.
        continue;
      }
      // If the current request matches the configured event then check if the
      // limit has been reached.
      if (!$plugin->isRequestedEvent($request)) {
        continue;
      }
      if (!$rate_limit_entity = $plugin->loadRateLimitEntity($this->account)) {
        // If there is no entity, then create one.
        // We don't need to save it since it will be saved upon hit.
        $rate_limit_entity = entity_create('rate_limit', array(
          'timestamp' => REQUEST_TIME,
          'expiration' => $now->add($period)->format('U'),
          'hits' => 0,
          'event' => $plugin->getPluginId(),
          'identifier' => $plugin->generateIdentifier($this->account),
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
          $exception = new FloodException('Rate limit reached');
          $exception->setHeader('Retry-After', $new_period->format(\DateTime::RFC822));
          throw $exception;
        }
      }
      else {
        if ($rate_limit_entity->hits >= $limit) {
          $exception = new FloodException('Rate limit reached');
          $exception->setHeader('Retry-After', $new_period->format(\DateTime::RFC822));
          throw $exception;
        }
      }
      // Save a new hit after generating the exception to mitigate DoS attacks.
      $rate_limit_entity->hit();

      // Add the limit headers to the response.
      $remaining = $limit == static::UNLIMITED_RATE_LIMIT ? 'unlimited' : $limit - ($rate_limit_entity->hits + 1);
      $response = restful()->getResponse();
      $headers = $response->getHeaders();
      $headers->append(HttpHeader::create('X-Rate-Limit-Limit', $limit));
      $headers->append(HttpHeader::create('X-Rate-Limit-Remaining', $remaining));
      $time_remaining = $rate_limit_entity->expiration - REQUEST_TIME;
      $headers->append(HttpHeader::create('X-Rate-Limit-Reset', $time_remaining));
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
