<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Decorators\ResourceDecoratorBase.
 */

namespace Drupal\restful\Plugin\resource\Decorators;


use Drupal\Component\Plugin\PluginBase;
use Drupal\restful\Exception\BadRequestException;
use Drupal\restful\Http\Request;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\DataProvider\DataProviderInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Util\ExplorableDecoratorInterface;

/**
 * Class ResourceDecoratorBase.
 *
 * @package Drupal\restful\Plugin\resource\Decorators
 */
abstract class ResourceDecoratorBase extends PluginBase implements ResourceDecoratorInterface, ExplorableDecoratorInterface {

  /**
   * The decorated resource.
   *
   * @var ResourceInterface
   */
  protected $subject;

  /**
   * {@inheritdoc}
   */
  public function getDecoratedResource() {
    return $this->subject;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrimaryResource() {
    $resource = $this->getDecoratedResource();
    while ($resource instanceof ResourceDecoratorInterface) {
      $resource = $resource->getDecoratedResource();
    }
    return $resource;
  }

  /**
   * {@inheritdoc}
   */
  public function dataProviderFactory() {
    return $this->subject->dataProviderFactory();
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount($cache = TRUE) {
    return $this->subject->getAccount($cache);
  }

  /**
   * {@inheritdoc}
   */
  public function setAccount($account) {
    $this->subject->setAccount($account);
    $this->getDataProvider()->setAccount($account);
  }

  /**
   * {@inheritdoc}
   */
  public function switchUserBack() {
    $this->subject->switchUserBack();
  }

  /**
   * {@inheritdoc}
   */
  public function discover($path = NULL) {
    return $this->subject->discover($path);
  }

  /**
   * {@inheritdoc}
   */
  public function getRequest() {
    return $this->subject->getRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function setRequest(RequestInterface $request) {
    $this->subject->setRequest($request);
    // Make sure that the request is updated in the data provider.
    $this->getDataProvider()->setRequest($request);
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->subject->getPath();
  }

  /**
   * {@inheritdoc}
   */
  public function setPath($path) {
    $this->subject->setPath($path);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinitions() {
    return $this->subject->getFieldDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function getDataProvider() {
    return $this->subject->getDataProvider();
  }

  /**
   * {@inheritdoc}
   */
  public function setDataProvider(DataProviderInterface $data_provider = NULL) {
    $this->subject->setDataProvider($data_provider);
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceName() {
    return $this->subject->getResourceName();
  }

  /**
   * {@inheritdoc}
   */
  public function process() {
    return $this->subject->process();
  }

  /**
   * {@inheritdoc}
   */
  public function controllersInfo() {
    return $this->subject->controllersInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function getControllers() {
    return $this->subject->getControllers();
  }

  /**
   * {@inheritdoc}
   */
  public function index($path) {
    return $this->subject->index($path);
  }

  /**
   * {@inheritdoc}
   */
  public function view($path) {
    return $this->subject->view($path);
  }

  /**
   * {@inheritdoc}
   */
  public function create($path) {
    return $this->subject->create($path);
  }

  /**
   * {@inheritdoc}
   */
  public function update($path) {
    return $this->subject->update($path);
  }

  /**
   * {@inheritdoc}
   */
  public function replace($path) {
    return $this->subject->replace($path);
  }

  /**
   * {@inheritdoc}
   */
  public function remove($path) {
    $this->subject->remove($path);
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return $this->subject->getVersion();
  }

  /**
   * {@inheritdoc}
   */
  public function versionedUrl($path = '', $options = array(), $version_string = TRUE) {
    return $this->subject->versionedUrl($path, $options, $version_string);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->subject->getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->subject->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return $this->subject->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return $this->subject->calculateDependencies();
  }

  /**
   * {@inheritdoc}
   */
  public function access() {
    return $this->subject->access();
  }

  /**
   * {@inheritdoc}
   */
  public function getControllerFromPath($path = NULL, ResourceInterface $resource = NULL) {
    return $this->subject->getControllerFromPath($path, $resource ?: $this);
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceMachineName() {
    return $this->subject->getResourceMachineName();
  }

  /**
   * {@inheritdoc}
   *
   * This is a decorated resource, get proxy the request until you reach the
   * annotated resource.
   */
  public function getPluginDefinition() {
    return $this->subject->getPluginDefinition();
  }

  /**
   * {@inheritdoc}
   *
   * This is a decorated resource, set proxy the request until you reach the
   * annotated resource.
   */
  public function setPluginDefinition(array $plugin_definition) {
    $this->subject->setPluginDefinition($plugin_definition);
    if (!empty($plugin_definition['dataProvider'])) {
      $this->getDataProvider()->addOptions($plugin_definition['dataProvider']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function enable() {
    $this->subject->enable();
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    $this->subject->disable();
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->subject->isEnabled();
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldDefinitions(ResourceFieldCollectionInterface $field_definitions) {
    return $this->subject->setFieldDefinitions($field_definitions);
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl(array $options = array(), $keep_query = TRUE, RequestInterface $request = NULL) {
    return $this->subject->getUrl($options, $keep_query, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function doGet($path = '', array $query = array()) {
    $this->setPath($path);
    $this->setRequest(Request::create($this->versionedUrl($path, array('absolute' => FALSE)), $query, RequestInterface::METHOD_GET));
    return $this->process();
  }

  /**
   * {@inheritdoc}
   */
  public function doPost(array $parsed_body) {
    return $this->doWrite(RequestInterface::METHOD_POST, '', $parsed_body);
  }

  /**
   * {@inheritdoc}
   */
  public function doPatch($path, array $parsed_body) {
    if (!$path) {
      throw new BadRequestException('PATCH requires a path. None given.');
    }
    return $this->doWrite(RequestInterface::METHOD_PATCH, $path, $parsed_body);
  }

  /**
   * {@inheritdoc}
   */
  public function doPut($path, array $parsed_body) {
    if (!$path) {
      throw new BadRequestException('PUT requires a path. None given.');
    }
    return $this->doWrite(RequestInterface::METHOD_PUT, $path, $parsed_body);
  }

  /**
   * {@inheritdoc}
   */
  private function doWrite($method, $path, array $parsed_body) {
    $this->setPath($path);
    $this->setRequest(Request::create($this->versionedUrl($path, array('absolute' => FALSE)), array(), $method, NULL, FALSE, NULL, array(), array(), array(), $parsed_body));
    return $this->process();
  }

  /**
   * {@inheritdoc}
   */
  public function doDelete($path) {
    if (!$path) {
      throw new BadRequestException('DELETE requires a path. None given.');
    }
    $this->setPath($path);
    $this->setRequest(Request::create($this->versionedUrl($path, array('absolute' => FALSE)), array(), RequestInterface::METHOD_DELETE));
    return $this->process();
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->subject->getPluginId();
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
    if ($this instanceof $class || $this->subject instanceof $class) {
      return TRUE;
    }
    // Check if the decorated resource is also a decorator.
    if ($this->subject instanceof ExplorableDecoratorInterface) {
      return $this->subject->isInstanceOf($class);
    }
    return FALSE;
  }

  /**
   * If any method not declared, then defer it to the decorated field.
   *
   * This decorator class is proxying all the calls declared in the
   * ResourceInterface to the underlying decorated resource. But it is not
   * doing it for any of the methods of the parents of ResourceInterface.
   *
   * With this code, any method that is not declared in the class will try to
   * make that method call it in the decorated resource.
   *
   * @param string $name
   *   The name of the method that could not be found.
   * @param array $arguments
   *   The arguments passed to the method, collected in an array.
   *
   * @return mixed
   *   The result of the call.
   */
  public function __call($name, $arguments) {
    return call_user_func_array(array($this->subject, $name), $arguments);
  }

}
