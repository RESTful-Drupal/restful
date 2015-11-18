<?php

/**
 * @file
 * Contains \Drupal\restful_example\Plugin\resource\comment\Comments__1_0.
 */

namespace Drupal\restful_example\Plugin\resource\comment;

use Drupal\restful\Plugin\resource\ResourceEntity;
use Drupal\restful\Plugin\resource\ResourceInterface;

/**
 * Class Comments__1_0
 * @package Drupal\restful_example\Plugin\resource\comment
 *
 * @Resource(
 *   name = "comments:1.0",
 *   resource = "comments",
 *   label = "Comments",
 *   description = "Export the comments with all authentication providers.",
 *   authenticationTypes = TRUE,
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "comment",
 *     "bundles": FALSE,
 *   },
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class Comments__1_0 extends ResourceEntity implements ResourceInterface {}
