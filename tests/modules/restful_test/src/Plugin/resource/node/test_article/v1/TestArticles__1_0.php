<?php

/**
 * @file
 * Contains \Drupal\restful_test\Plugin\resource\node\test_article\v1\TestArticles__1_0.
 */

namespace Drupal\restful_test\Plugin\resource\node\test_article\v1;

use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Plugin\resource\ResourceNode;

/**
 * Class TestArticles__1_0
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "test_articles:1.0",
 *   resource = "test_articles",
 *   label = "Test Articles",
 *   description = "Export the article content type.",
 *   authenticationTypes = {
 *     "basic_auth",
 *     "cookie"
 *   },
 *   dataProvider = {
 *     "entityType": "node",
 *     "bundles": {
 *       "article"
 *     },
 *   },
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class TestArticles__1_0 extends ResourceNode implements ResourceInterface {

  /**
   * {@inheritdoc}
   */
  protected function publicFields() {
    $public_fields = parent::publicFields();

    if (!module_exists('entity_validator')) {
      return $public_fields;
    }
    $public_fields['title'] = $public_fields['label'];
    $public_fields['body'] = array(
      'property' => 'body',
      'sub_property' => 'value',
    );

    return $public_fields;
  }

  /**
   * {@inheritdoc}
   */
  protected function processPublicFields(array $field_definitions) {
    $field_definitions = parent::processPublicFields($field_definitions);
    if (!$altered_public_name = variable_get('restful_test_revoke_public_field_access')) {
      return $field_definitions;
    }
    foreach ($field_definitions as $public_name => &$field_definition) {
      if ($public_name != $altered_public_name) {
        continue;
      }
      $field_definition['access_callbacks'] = array(array($this, 'publicFieldAccessFalse'));
    }
    return $field_definitions;
  }

  /**
   * An access callback that returns TRUE if title is "access". Otherwise FALSE.
   *
   * @param string $op
   *   The operation that access should be checked for. Can be "view" or "edit".
   *   Defaults to "edit".
   * @param ResourceFieldInterface $resource_field
   *   The resource field to check access upon.
   * @param DataInterpreterInterface $interpreter
   *   The data interpreter.
   *
   * @return string
   *   "Allow" or "Deny" if user has access to the property.
   */
  public static function publicFieldAccessFalse($op, ResourceFieldInterface $resource_field, DataInterpreterInterface $interpreter) {
    return $interpreter->getWrapper()->label() == 'access' ? \Drupal\restful\Plugin\resource\Field\ResourceFieldBase::ACCESS_ALLOW : \Drupal\restful\Plugin\resource\Field\ResourceFieldBase::ACCESS_DENY;
  }

}
