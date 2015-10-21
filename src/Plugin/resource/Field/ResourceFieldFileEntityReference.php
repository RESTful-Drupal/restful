<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldFileEntityReference.
 */

namespace Drupal\restful\Plugin\resource\Field;

use Drupal\restful\Http\Request;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Plugin\ResourcePluginManager;

class ResourceFieldFileEntityReference extends ResourceFieldEntityReference implements ResourceFieldEntityReferenceInterface {

  // TODO: Add testing to this!
  /**
   * Helper function to get the identifier from a property wrapper.
   *
   * @param \EntityMetadataWrapper $property_wrapper
   *   The property wrapper to get the ID from.
   *
   * @return string
   *   An identifier.
   */
  protected function propertyIdentifier(\EntityMetadataWrapper $property_wrapper) {
    // The property wrapper is a reference to another entity get the entity
    // ID.
    $file_array = $property_wrapper->value();
    $identifier = $file_array['fid'];
    $resource = $this->getResource();
    // TODO: Make sure we still want to support fullView.
    if (!$resource || !$identifier || (isset($resource['fullView']) && $resource['fullView'] === FALSE)) {
      return $identifier;
    }
    // If there is a resource that we are pointing to, we need to use the id
    // field that that particular resource has in its configuration. Trying to
    // load by the entity id in that scenario will lead to a 404.
    // We'll load the plugin to get the idField configuration.
    $instance_id = sprintf('%s:%d.%d', $resource['name'], $resource['majorVersion'], $resource['minorVersion']);
    /* @var ResourceInterface $resource */
    $resource = restful()
      ->getResourceManager()
      ->getPluginCopy($instance_id, Request::create('', array(), RequestInterface::METHOD_GET));
    $plugin_definition = $resource->getPluginDefinition();
    if (empty($plugin_definition['dataProvider']['idField'])) {
      return $identifier;
    }
    try {
      $file_wrapper = entity_metadata_wrapper('file', $file_array['fid']);
      return $file_wrapper->{$plugin_definition['dataProvider']['idField']}->value();
    }
    catch (\EntityMetadataWrapperException $e) {
      return $identifier;
    }
  }

  /**
   * Builds a metadata item for a field value.
   *
   * It will add information about the referenced entity.
   *
   * @param \EntityMetadataWrapper $wrapper
   *   The wrapper for the referenced file array.
   *
   * @return array
   *   The metadata array item.
   */
  protected function buildResourceMetadataItem($wrapper) {
    $file_array = $wrapper->value();
    /* @var \EntityDrupalWrapper $wrapper */
    $wrapper = entity_metadata_wrapper('file', $file_array['fid']);
    return parent::buildResourceMetadataItem($wrapper);
  }

  /**
   * Helper function to get the referenced entity ID.
   *
   * @param \EntityStructureWrapper $property_wrapper
   *   The wrapper for the referenced file array.
   *
   * @return mixed
   *   The ID.
   */
  protected function referencedId($property_wrapper) {
    $file_array = $property_wrapper->value();
    if (!$this->referencedIdProperty) {
      return $file_array['fid'];
    }
    /* @var \EntityDrupalWrapper $wrapper */
    $wrapper = entity_metadata_wrapper('file', $file_array['fid']);
    return $wrapper->{$this->referencedIdProperty}->value();
  }

  /**
   * {@inheritdoc}
   */
  public function getRequest() {
    return $this->decorated->getRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function setRequest(RequestInterface $request) {
    $this->decorated->setRequest($request);
  }

}
