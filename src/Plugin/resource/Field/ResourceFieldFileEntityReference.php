<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldFileEntityReference.
 */

namespace Drupal\restful\Plugin\resource\Field;

use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;

class ResourceFieldFileEntityReference extends ResourceFieldEntityReference implements ResourceFieldEntityReferenceInterface {

  // TODO: Add testing to this!
  /**
   * Get the wrapper for the property associated to the current field.
   *
   * @param DataInterpreterInterface $interpreter
   *   The data source.
   *
   * @return \EntityMetadataWrapper
   *   Either a \EntityStructureWrapper or a \EntityListWrapper.
   *
   * @throws ServerConfigurationException
   */
  protected function propertyWrapper(DataInterpreterInterface $interpreter) {
    $property_wrapper = parent::propertyWrapper($interpreter);
    // If the file_entity module is not installed, throw an exception.
    if (!module_exists('file_entity')) {
      throw new ServerConfigurationException(sprintf('You cannot use %s if file_entity is not installed.', __CLASS__));
    }
    // If the wrapper is around the file array, then get the entity wrapper.
    if ($property_wrapper instanceof \EntityDrupalWrapper) {
      return $property_wrapper;
    }
    $file_array = $property_wrapper->value();
    return new \EntityDrupalWrapper('file', $file_array['fid']);
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
