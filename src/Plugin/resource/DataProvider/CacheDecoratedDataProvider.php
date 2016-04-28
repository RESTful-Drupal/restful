<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\CacheDecoratedDataProvider.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Drupal\restful\Exception\InaccessibleRecordException;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldInterface;
use Drupal\restful\RenderCache\RenderCache;
use Drupal\restful\Util\ExplorableDecoratorInterface;

/**
 * Class CacheDecoratedDataProvider.
 *
 * @package Drupal\restful\Plugin\resource\DataProvider
 */
class CacheDecoratedDataProvider implements CacheDecoratedDataProviderInterface, ExplorableDecoratorInterface {

  /**
   * The decorated object.
   *
   * @var DataProviderInterface
   */
  protected $subject;

  /**
   * The cache controller to interact with the cache backed.
   *
   * @var \DrupalCacheInterface
   */
  protected $cacheController;

  /**
   * Constructs a CacheDecoratedDataProvider object.
   *
   * @param DataProviderInterface $subject
   *   The data provider to add caching to.
   * @param \DrupalCacheInterface $cache_controller
   *   The cache controller to add the cache.
   */
  public function __construct(DataProviderInterface $subject, \DrupalCacheInterface $cache_controller) {
    $this->subject = $subject;
    $this->cacheController = $cache_controller;
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
  public function discover($path = NULL) {
    return $this->subject->discover($path);
  }

  /**
   * {@inheritdoc}
   */
  public function getRange() {
    return $this->subject->getRange();
  }

  /**
   * {@inheritdoc}
   */
  public function setRange($range) {
    return $this->subject->setRange($range);
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount() {
    return $this->subject->getAccount();
  }

  /**
   * {@inheritdoc}
   */
  public function setAccount($account) {
    $this->subject->setAccount($account);
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
  }

  /**
   * {@inheritdoc}
   */
  public function getLangCode() {
    return $this->subject->getLangCode();
  }

  /**
   * {@inheritdoc}
   */
  public function setLangCode($langcode) {
    $this->subject->setLangCode($langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->subject->getOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function addOptions(array $options) {
    $this->subject->addOptions($options);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheFragments($identifier) {
    return $this->subject->getCacheFragments($identifier);
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    // TODO: This is duplicating the code from DataProvider::index
    $ids = $this->getIndexIds();

    return $this->viewMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexIds() {
    return $this->subject->getIndexIds();
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return $this->subject->count();
  }

  /**
   * {@inheritdoc}
   */
  public function create($object) {
    return $this->subject->create($object);
  }

  /**
   * {@inheritdoc}
   */
  public function view($identifier) {
    $resource_field_collection = $this->subject->view($identifier);
    if (!$resource_field_collection instanceof ResourceFieldCollectionInterface) {
      return NULL;
    }

    $resource_field_collection->setContext('cache_fragments', $this->getCacheFragments($identifier));
    return $resource_field_collection;
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $identifiers) {
    $return = array();
    // If no IDs were requested, we should not throw an exception in case an
    // entity is un-accessible by the user.
    foreach ($identifiers as $identifier) {
      try {
        $row = $this->view($identifier);
      }
      catch (InaccessibleRecordException $e) {
        $row = NULL;
      }
      $return[] = $row;
    }

    return array_values(array_filter($return));
  }

  /**
   * {@inheritdoc}
   */
  public function update($identifier, $object, $replace = TRUE) {
    $this->clearRenderedCache($this->getCacheFragments($identifier));
    return $this->subject->update($identifier, $object, $replace);
  }

  /**
   * {@inheritdoc}
   */
  public function remove($identifier) {
    $this->clearRenderedCache($this->getCacheFragments($identifier));
    $this->subject->remove($identifier);
  }

  /**
   * {@inheritdoc}
   */
  public function methodAccess(ResourceFieldInterface $resource_field) {
    $this->subject->methodAccess($resource_field);
  }

  /**
   * {@inheritdoc}
   */
  public function canonicalPath($path) {
    return $this->subject->canonicalPath($path);
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
    $this->subject->setOptions($options);
  }

  /**
   * {@inheritdoc}
   */
  public function setResourcePath($resource_path) {
    $this->subject->setResourcePath($resource_path);
  }

  /**
   * {@inheritdoc}
   */
  public function getResourcePath() {
    return $this->subject->getResourcePath();
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata() {
    return $this->subject->getMetadata();
  }

  /**
   * Clears the cache entries related to the given cache fragments.
   *
   * @param \Doctrine\Common\Collections\ArrayCollection $cache_fragments
   *   The cache fragments to clear.
   */
  protected function clearRenderedCache(ArrayCollection $cache_fragments) {
    $cache_object = new RenderCache($cache_fragments, NULL, $this->cacheController);
    $cache_object->clear();
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

}
