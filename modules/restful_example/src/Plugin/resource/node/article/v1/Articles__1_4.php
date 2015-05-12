<?php

/**
 * @file
 * Contains \Drupal\restful_example\Plugin\resource\node\article\v1\Articles__1_4.
 */

namespace Drupal\restful_example\Plugin\resource\node\article\v1;

use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Plugin\resource\ResourceNode;
use Drupal\restful\RateLimit\RateLimitManager;

/**
 * Class Articles__1_4
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "articles:1.4",
 *   resource = "articles",
 *   label = "Articles",
 *   description = "Export the articles with all authentication providers.",
 *   authenticationTypes = TRUE,
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "node",
 *     "bundles": {
 *       "article"
 *     },
 *   },
 *   majorVersion = 1,
 *   minorVersion = 4
 * )
 */
class Articles__1_4 extends ResourceNode implements ResourceInterface {

  /**
   * Constructs an Articles__1_4 object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pluginDefinition['rateLimit'] = array(
      // The 'request' event is the basic event. You can declare your own
      // events.
      'request' => array(
        'event' => 'request',
        // Rate limit is cleared every day.
        'period' => 'P1D',
        'limits' => array(
          'authenticated user' => 3,
          'anonymous user' => 2,
          'administrator' => RateLimitManager::UNLIMITED_RATE_LIMIT,
        ),
      ),
    );
  }

}
