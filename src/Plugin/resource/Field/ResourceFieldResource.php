<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldResource.
 */

namespace Drupal\restful\Plugin\resource\Field;

use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;
use Drupal\restful\Plugin\resource\Field\PublicFieldInfo\PublicFieldInfoInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Util\ExplorableDecoratorInterface;

class ResourceFieldResource implements ResourceFieldResourceInterface, ExplorableDecoratorInterface {

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
   * Resource plugin.
   *
   * Resource this field points to.
   *
   * @var ResourceInterface
   */
  protected $resourcePlugin;

  /**
   * Target Column.
   *
   * @var string
   */
  protected $targetColumn;

  /**
   * Constructor.
   *
   * @param array $field
   *   Contains the field values.
   *
   * @param RequestInterface $request
   *   The request.
   */
  public function __construct(array $field, RequestInterface $request) {
    if ($this->decorated) {
      $this->setRequest($request);
    }
    $this->resourceMachineName = $field['resource']['name'];
    // Compute the target column if empty.
    if (!empty($field['targetColumn'])) {
      $this->targetColumn = $field['targetColumn'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceId(DataInterpreterInterface $interpreter) {
    if (isset($this->resourceId)) {
      return $this->resourceId;
    }
    $this->resourceId = $this->compoundDocumentId($interpreter);
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
  public function getResourcePlugin() {
    if (isset($this->resourcePlugin)) {
      return $this->resourcePlugin;
    }
    $resource_info = $this->getResource();
    $this->resourcePlugin = restful()
      ->getResourceManager()
      ->getPlugin(sprintf('%s:%d.%d', $resource_info['name'], $resource_info['majorVersion'], $resource_info['minorVersion']));
    return $this->resourcePlugin;
  }

  /**
   * Gets the cardinality of the field.
   *
   * @return int
   *   The number of potentially returned fields. Reuses field cardinality
   *   constants.
   */
  public function getCardinality() {
    if ($this->decorated instanceof ResourceFieldEntityInterface) {
      return $this->decorated->getCardinality();
    }
    // Default to single cardinality.
    return 1;
  }

  /**
   * {@inheritdoc}
   */
  public function setCardinality($cardinality) {
    $this->decorated->setCardinality($cardinality);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $field, RequestInterface $request = NULL) {
    $request = $request ?: restful()->getRequest();
    $resource_field = ResourceField::create($field, $request);
    $output = new static($field, $request);
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
  public function compoundDocumentId(DataInterpreterInterface $interpreter) {
    return $this->decorated->compoundDocumentId($interpreter);
  }

  /**
   * {@inheritdoc}
   */
  public function render(DataInterpreterInterface $interpreter) {
    return $this->executeProcessCallbacks($this->value($interpreter));
  }

  /**
   * If any method not declared, then defer it to the decorated field.
   */
  public function __call($name, $arguments) {
    return call_user_func_array(array($this->decorated, $name), $arguments);
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

  /**
   * {@inheritdoc}
   */
  public function getDefinition() {
    return $this->decorated->getDefinition();
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicFieldInfo() {
    return $this->decorated->getPublicFieldInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function setPublicFieldInfo(PublicFieldInfoInterface $public_field_info) {
    $this->decorated->setPublicFieldInfo($public_field_info);
  }

  /**
   * {@inheritdoc}
   */
  public function autoDiscovery() {
    if (method_exists($this->decorated, 'autoDiscovery')) {
      return $this->decorated->autoDiscovery();
    }
    return ResourceFieldBase::emptyDiscoveryInfo($this->getPublicName());
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetColumn() {
    if (empty($this->targetColumn)) {
      // Check the definition of the decorated field.
      $definition = $this->decorated->getDefinition();
      if (!empty($definition['targetColumn'])) {
        $this->targetColumn = $definition['targetColumn'];
      }
      elseif ($this->isInstanceOf(ResourceFieldEntityReferenceInterface::class)) {
        $entity_info = entity_get_info($this->getResourcePlugin()->getEntityType());
        // Assume that the relationship is through the entity key id.
        $this->targetColumn = $entity_info['entity keys']['id'];
      }
      else {
        throw new ServerConfigurationException(sprintf('Target column could not be found for field "%s".', $this->getPublicName()));
      }
    }
    return $this->targetColumn;
  }

  /**
   * Checks if the decorated object is an instance of something.
   *
   * @param string $class
   *   Class or interface to check the instance.
   *
   * @return bool
   *   TRUE if the decorated object is an instace of the $class. FALSE
   *   otherwise.
   */
  public function isInstanceOf($class) {
    if ($this instanceof $class || $this->decorated instanceof $class) {
      return TRUE;
    }
    // Check if the decorated resource is also a decorator.
    if ($this->decorated instanceof ExplorableDecoratorInterface) {
      return $this->decorated->isInstanceOf($class);
    }
    return FALSE;
  }

}
