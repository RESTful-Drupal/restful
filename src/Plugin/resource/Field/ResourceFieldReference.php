<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldReference.
 */

namespace Drupal\restful\Plugin\resource\Field;

use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;

/**
 * Class ResourceFieldReference.
 *
 * This field type is useful when you have an arbitrary field, that is not an
 * entity reference field, that returns an ID to another resource. This resource
 * field type will allow you to have a field definition with a callback return
 * an ID and use that as a relationship.
 *
 * This is specially useful when adding a relationship to an entity based
 * resource from a DB query, or vice versa. See an example of this in action in
 * the example resource main:1.8.
 *
 * If you need to add a reference to entity things like $node->uid, use
 * \Drupal\restful\Plugin\resource\Field\ResourceFieldEntityReference instead.
 *
 * @package Drupal\restful\Plugin\resource\Field
 */
class ResourceFieldReference extends ResourceField {

  /**
   * Overrides ResourceField::compoundDocumentId().
   */
  public function compoundDocumentId(DataInterpreterInterface $interpreter) {
    $collection = parent::compoundDocumentId($interpreter);
    if (!$collection instanceof ResourceFieldCollectionInterface) {
      return NULL;
    }
    $id_field = $collection->getIdField();
    if (!$id_field instanceof ResourceFieldInterface) {
      return NULL;
    }
    return $id_field->render($collection->getInterpreter());
  }

}
