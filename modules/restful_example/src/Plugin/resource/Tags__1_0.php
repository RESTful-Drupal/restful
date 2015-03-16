<?php

/**
 * @file
 * Contains \Drupal\restful_example\Plugin\resource\Tags__1_0.
 */

namespace Drupal\restful_example\Plugin\resource;

use Drupal\restful\Http\Request;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldInterface;
use Drupal\restful\Plugin\resource\ResourceEntity;
use Drupal\restful\Plugin\resource\ResourceInterface;

/**
 * Class Tags
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "tags:1.0",
 *   resource = "tags",
 *   label = "Tags",
 *   description = "Export the tags taxonomy term.",
 *   authenticationTypes = TRUE,
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "taxonomy_term",
 *     "bundles": {
 *       "tags"
 *     },
 *   },
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class Tags__1_0 extends ResourceEntity implements ResourceInterface {

  /**
   * {@inheritdoc}
   */
  protected function publicFields() {
    return array(
      'id' => array(
        'wrapper_method' => 'getIdentifier',
        'wrapper_method_on_entity' => TRUE,
      ),
      'label' => array(
        'wrapper_method' => 'label',
        'wrapper_method_on_entity' => TRUE,
      ),
      'self' => array(
        'callback' => array($this, 'getEntitySelf'),
        'access_callbacks' => array(
          array($this, 'evenAccess'),
        ),
      ),
    );
  }

  /**
   * Access callback example.
   *
   * @param string $op
   *   Operation being performed.
   * @param ResourceFieldInterface $resource_field
   *   The resource field definition object.
   * @param DataInterpreterInterface $interpreter
   *   The data interpreter.
   *
   * @return bool
   *   TRUE for access granted.
   */
  public function evenAccess($op, ResourceFieldInterface $resource_field, DataInterpreterInterface $interpreter) {
    $account = $interpreter->getAccount();
    $value = $interpreter->getWrapper()->getIdentifier() + $account->uid;
    return $value % 2;
  }
}
