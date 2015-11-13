<?php

/**
 * @file
 * Contains \Drupal\restful_example\Plugin\resource\node\article\v1\Articles__1_3.
 */

namespace Drupal\restful_example\Plugin\resource\node\article\v1;

use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Plugin\resource\ResourceNode;

/**
 * Class Articles__1_3
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "articles:1.3",
 *   resource = "articles",
 *   label = "Articles",
 *   description = "Export the articles with all authentication providers.",
 *   authenticationTypes = {
 *     "token"
 *   },
 *   dataProvider = {
 *     "entityType": "node",
 *     "bundles": {
 *       "article"
 *     },
 *   },
 *   majorVersion = 1,
 *   minorVersion = 3
 * )
 */
class Articles__1_3 extends ResourceNode implements ResourceInterface {

  /**
   * Constructs an Articles__1_3 object.
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
    if (!module_exists('restful_token_auth')) {
      $this->disable();
    }
  }

}
