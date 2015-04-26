<?php

/**
 * @file
 * Contains \Drupal\restful_test\Plugin\resource\entity_test\main\v1\Main__1_0.
 */

namespace Drupal\restful_test\Plugin\resource\entity_test\main\v1;

use Drupal\restful\Plugin\resource\ResourceEntity;
use Drupal\restful\Plugin\resource\ResourceInterface;

/**
 * Class Main__1_0
 * @package Drupal\restful_test\Plugin\resource
 *
 * @Resource(
 *   name = "main:1.0",
 *   resource = "main",
 *   label = "Main",
 *   description = "Export the entity test 'main' bundle.",
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "entity_test",
 *     "bundles": {
 *       "main"
 *     },
 *   },
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class Main__1_0 extends ResourceEntity implements ResourceInterface {}
