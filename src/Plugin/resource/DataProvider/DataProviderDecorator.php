<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProviderDecorator.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldInterface;

abstract class DataProviderDecorator implements DataProviderInterface {

  /**
   * Decorated provider.
   *
   * @var DataProviderInterface
   */
  protected $decorated;

  /**
   * Contstructs a DataProviderDecorator class.
   *
   * @param DataProviderInterface $decorated
   *   The decorated data provider.
   */
  public function __construct(DataProviderInterface $decorated) {
    $this->decorated = $decorated;
  }

  /**
   * {@inheritdoc}
   */
  public function getRange() {
    return $this->decorated->getRange();
  }

  /**
   * {@inheritdoc}
   */
  public function setRange($range) {
    $this->decorated->setRange($range);
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount() {
    return $this->decorated->getAccount();
  }

  /**
   * {@inheritdoc}
   */
  public function setAccount($account) {
    $this->decorated->setAccount($account);
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
  public function getLangCode() {
    return $this->decorated->getLangCode();
  }

  /**
   * {@inheritdoc}
   */
  public function setLangCode($langcode) {
    $this->decorated->setLangCode($langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->decorated->getOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function addOptions(array $options) {
    $this->decorated->addOptions($options);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheFragments($identifier) {
    $this->decorated->getCacheFragments($identifier);
  }

  /**
   * {@inheritdoc}
   */
  public function canonicalPath($path) {
    return $this->decorated->canonicalPath($path);
  }

  /**
   * {@inheritdoc}
   */
  public function methodAccess(ResourceFieldInterface $resource_field) {
    return $this->decorated->methodAccess($resource_field);
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
    $this->decorated->setOptions($options);
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexIds() {
    return $this->decorated->getIndexIds();
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    return $this->decorated->index();
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return $this->decorated->count();
  }

  /**
   * {@inheritdoc}
   */
  public function create($object) {
    return $this->decorated->create($object);
  }

  /**
   * {@inheritdoc}
   */
  public function view($identifier) {
    return $this->decorated->view($identifier);
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $identifiers) {
    return $this->decorated->viewMultiple($identifiers);
  }

  /**
   * {@inheritdoc}
   */
  public function update($identifier, $object, $replace = FALSE) {
    return $this->decorated->update($identifier, $object, $replace);
  }

  /**
   * {@inheritdoc}
   */
  public function remove($identifier) {
    $this->decorated->remove($identifier);
  }

  /**
   * {@inheritdoc}
   */
  public function discover($path = NULL) {
    return $this->decorated->discover($path);
  }

  /**
   * {@inheritdoc}
   */
  public static function isNestedField($field_name) {
    return DataProvider::isNestedField($field_name);
  }

  /**
   * {@inheritdoc}
   */
  public static function processFilterInput($filter, $public_field) {
    return DataProvider::processFilterInput($filter, $public_field);
  }

  /**
   * {@inheritdoc}
   */
  public function setResourcePath($resource_path) {
    $this->decorated->setResourcePath($resource_path);
  }

  /**
   * {@inheritdoc}
   */
  public function getResourcePath() {
    return $this->decorated->getResourcePath();
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata() {
    return $this->decorated->getMetadata();
  }

}
