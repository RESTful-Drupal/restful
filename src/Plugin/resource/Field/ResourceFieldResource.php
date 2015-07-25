<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldResource.
 */

namespace Drupal\restful\Plugin\resource\Field;

use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;

class ResourceFieldResource implements ResourceFieldResourceInterface {

  /**
   * Decorated resource field.
   *
   * @var ResourceFieldInterface
   */
  protected $decorated;

  /**
   * The ID on the referenced resource.
   *
   * @var mixed
   */
  protected $resourceId;

  /**
   * The machine name, without version, of the referenced resource.
   *
   * @var string
   */
  protected $resourceMachineName;

  /**
   * Constructor.
   *
   * @param array $field
   *   Contains the field values.
   */
  public function __construct(array $field) {
    $this->resourceMachineName = $field['resource']['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceId() {
    if (isset($this->resourceId)) {
      return $this->resourceId;
    }
    $this->resourceId = $this->compoundDocumentId();
    return $this->resourceId;
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceMachineName() {
    return $this->resourceMachineName;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $field) {
    $resource_field = ResourceField::create($field);
    $output = new static($field);
    $output->decorate($resource_field);
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public static function isArrayNumeric(array $input) {
    return ResourceFieldBase::isArrayNumeric($input);
  }

  /**
   * {@inheritdoc}
   */
  public function value(DataInterpreterInterface $interpreter) {
    return $this->decorated->value($interpreter);
  }

  /**
   * {@inheritdoc}
   */
  public function access($op, DataInterpreterInterface $interpreter) {
    return $this->decorated->access($op, $interpreter);
  }

  /**
   * {@inheritdoc}
   */
  public function addDefaults() {
    $this->decorated->addDefaults();
  }

  /**
   * {@inheritdoc}
   */
  public function set($value, DataInterpreterInterface $interpreter) {
    $this->decorated->set($value, $interpreter);
  }


  /**
   * {@inheritdoc}
   */
  public function decorate(ResourceFieldInterface $decorated) {
    $this->decorated = $decorated;
  }

  /**
   * {@inheritdoc}
   */
  public function addMetadata($key, $value) {
    $this->decorated->addMetadata($key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata($key) {
    return $this->decorated->getMetadata($key);
  }

  /**
   * {@inheritdoc}
   */
  public function executeProcessCallbacks($value) {
    return $this->decorated->executeProcessCallbacks($value);
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicName() {
    return $this->decorated->getPublicName();
  }

  /**
   * {@inheritdoc}
   */
  public function setPublicName($public_name) {
    $this->decorated->setPublicName($public_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessCallbacks() {
    return $this->decorated->getAccessCallbacks();
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessCallbacks($access_callbacks) {
    $this->decorated->setAccessCallbacks($access_callbacks);
  }

  /**
   * {@inheritdoc}
   */
  public function getProperty() {
    return $this->decorated->getProperty();
  }

  /**
   * {@inheritdoc}
   */
  public function setProperty($property) {
    $this->decorated->setProperty($property);
  }

  /**
   * {@inheritdoc}
   */
  public function getCallback() {
    return $this->decorated->getCallback();
  }

  /**
   * {@inheritdoc}
   */
  public function setCallback($callback) {
    $this->decorated->setCallback($callback);
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessCallbacks() {
    return $this->decorated->getProcessCallbacks();
  }

  /**
   * {@inheritdoc}
   */
  public function setProcessCallbacks($process_callbacks) {
    $this->decorated->setProcessCallbacks($process_callbacks);
  }

  /**
   * {@inheritdoc}
   */
  public function getResource() {
    return $this->decorated->getResource();
  }

  /**
   * {@inheritdoc}
   */
  public function setResource($resource) {
    $this->decorated->setResource($resource);
  }

  /**
   * {@inheritdoc}
   */
  public function getMethods() {
    return $this->decorated->getMethods();
  }

  /**
   * {@inheritdoc}
   */
  public function setMethods($methods) {
    $this->decorated->setMethods($methods);
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->decorated->id();
  }

  /**
   * {@inheritdoc}
   */
  public function isComputed() {
    return $this->decorated->isComputed();
  }

  /**
   * {@inheritdoc}
   */
  public function compoundDocumentId() {
    return $this->decorated->compoundDocumentId();
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return $this->executeProcessCallbacks($this->value());
  }

}
