<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\formatter\Formatter
 */

namespace Drupal\restful\Plugin\formatter;

use Drupal\Component\Plugin\PluginBase;
use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Plugin\ConfigurablePluginTrait;
use Drupal\restful\Plugin\resource\Decorators\CacheDecoratedResourceInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldBase;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\RenderCache\Entity\CacheFragment;
use Drupal\restful\RenderCache\RenderCache;

/**
 * Class Formatter.
 *
 * @package Drupal\restful\Plugin\formatter
 */
abstract class Formatter extends PluginBase implements FormatterInterface {

  use ConfigurablePluginTrait;

  /**
   * The resource handler containing more info about the request.
   *
   * @var ResourceInterface
   */
  protected $resource;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function format(array $data) {
    return $this->render($this->prepare($data));
  }

  /**
   * {@inheritdoc}
   */
  public function getContentTypeHeader() {
    // Default to the most generic content type.
    return 'application/hal+json; charset=utf-8';
  }

  /**
   * {@inheritdoc}
   */
  public function getResource() {
    if (isset($this->resource)) {
      return $this->resource;
    }

    // Get the resource from the instance configuration.
    $instance_configuration = $this->getConfiguration();
    if (empty($instance_configuration['resource'])) {
      return NULL;
    }
    $this->resource = $instance_configuration['resource'] instanceof ResourceInterface ? $instance_configuration['resource'] : NULL;
    return $this->resource;
  }

  /**
   * {@inheritdoc}
   */
  public function setResource(ResourceInterface $resource) {
    $this->resource = $resource;
    $this->setConfiguration(array(
      'resource' => $resource,
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function parseBody($body) {
    throw new ServerConfigurationException(sprintf('Invalid body parser for: %s.', $body));
  }

  /**
   * Helper function to know if a variable is iterable or not.
   *
   * @param mixed $input
   *   The variable to test.
   *
   * @return bool
   *   TRUE if the variable is iterable.
   */
  protected static function isIterable($input) {
    return is_array($input) || $input instanceof \Traversable || $input instanceof \stdClass;
  }

  /**
   * Checks if the passed in data to be rendered can be cached.
   *
   * @param mixed $data
   *   The data to be prepared and rendered.
   *
   * @return bool
   *   TRUE if the data can be cached.
   */
  protected function isCacheEnabled($data) {
    // We are only caching field collections, but you could cache at different
    // layers too.
    if (!$data instanceof ResourceFieldCollectionInterface) {
      return FALSE;
    }
    if (!$context = $data->getContext()) {
      return FALSE;
    }
    return !empty($context['cache_fragments']);
  }

  /**
   * Gets the cached computed value for the fields to be rendered.
   *
   * @param mixed $data
   *   The data to be rendered.
   *
   * @return mixed
   *   The cached data.
   */
  protected function getCachedData($data) {
    if (!$render_cache = $this->createCacheController($data)) {
      return NULL;
    }
    return $render_cache->get();
  }

  /**
   * Gets the cached computed value for the fields to be rendered.
   *
   * @param mixed $data
   *   The data to be rendered.
   *
   * @return string
   *   The cache hash.
   */
  protected function getCacheHash($data) {
    if (!$render_cache = $this->createCacheController($data)) {
      return NULL;
    }
    return $render_cache->getCid();
  }

  /**
   * Gets the cached computed value for the fields to be rendered.
   *
   * @param mixed $data
   *   The data to be rendered.
   * @param mixed $output
   *   The rendered data to output.
   * @param string[] $parent_hashes
   *   An array that holds the name of the parent cache hashes that lead to the
   *   current data structure.
   */
  protected function setCachedData($data, $output, array $parent_hashes = array()) {
    if (!$render_cache = $this->createCacheController($data)) {
      return;
    }
    $render_cache->set($output);
    // After setting the cache for the current object, mark all parent hashes
    // with the current cache fragments. That will have the effect of allowing
    // to clear the parent caches based on the children fragments.
    $fragments = $this->cacheFragments($data);
    foreach ($parent_hashes as $parent_hash) {
      foreach ($fragments as $tag_type => $tag_value) {
        // Check if the fragment already exists.
        $query = new \EntityFieldQuery();
        $duplicate = (bool) $query
          ->entityCondition('entity_type', 'cache_fragment')
          ->propertyCondition('value', $tag_value)
          ->propertyCondition('type', $tag_type)
          ->propertyCondition('hash', $parent_hash)
          ->count()
          ->execute();
        if ($duplicate) {
          continue;
        }
        $cache_fragment = new CacheFragment(array(
          'value' => $tag_value,
          'type' => $tag_type,
          'hash' => $parent_hash,
        ), 'cache_fragment');
        try {
          $cache_fragment->save();
        }
        catch (\Exception $e) {
          watchdog_exception('restful', $e);
        }
      }
    }
  }

  /**
   * Gets a cache controller based on the data to be rendered.
   *
   * @param mixed $data
   *   The data to be rendered.
   *
   * @return \Drupal\restful\RenderCache\RenderCacheInterface;

   *   The cache controller.
   */
  protected function createCacheController($data) {
    if (!$cache_fragments = $this->cacheFragments($data)) {
      return NULL;
    }
    // Add the formatter fragment because every formatter may prepare the data
    // differently.
    /* @var \Doctrine\Common\Collections\ArrayCollection $cache_fragments */
    $cache_fragments->set('formatter', $this->getPluginId());
    /* @var \Drupal\restful\Plugin\resource\Decorators\CacheDecoratedResource $cached_resource */
    if (!$cached_resource = $this->getResource()) {
      return NULL;
    }
    if (!$cached_resource instanceof CacheDecoratedResourceInterface) {
      return NULL;
    }
    return RenderCache::create($cache_fragments, $cached_resource->getCacheController());
  }

  /**
   * Gets a cache fragments based on the data to be rendered.
   *
   * @param mixed $data
   *   The data to be rendered.
   *
   * @return \Doctrine\Common\Collections\ArrayCollection;

   *   The cache controller.
   */
  protected static function cacheFragments($data) {
    $context = $data->getContext();
    if (!$cache_fragments = $context['cache_fragments']) {
      return NULL;
    }
    return $cache_fragments;
  }

  /**
   * Returns only the allowed fields by filtering out the other ones.
   *
   * @param mixed $output
   *   The data structure to filter.
   * @param bool|string[] $allowed_fields
   *   FALSE to allow all fields. An array of allowed values otherwise.
   *
   * @return mixed
   *   The filtered output.
   */
  protected function limitFields($output, $allowed_fields = NULL) {
    if (!isset($allowed_fields)) {
      $request = ($resource = $this->getResource()) ? $resource->getRequest() : restful()->getRequest();
      $input = $request->getParsedInput();
      // Set the field limits to false if there are no limits.
      $allowed_fields = empty($input['fields']) ? FALSE : explode(',', $input['fields']);
    }
    if (!is_array($output)) {
      // $output is a simple value.
      return $output;
    }
    $result = array();
    if (ResourceFieldBase::isArrayNumeric($output)) {
      foreach ($output as $item) {
        $result[] = $this->limitFields($item, $allowed_fields);
      }
      return $result;
    }
    foreach ($output as $field_name => $field_contents) {
      if ($allowed_fields !== FALSE && !in_array($field_name, $allowed_fields)) {
        continue;
      }
      $result[$field_name] = $this->limitFields($field_contents, $this->unprefixInputOptions($allowed_fields, $field_name));
    }
    return $result;
  }

  /**
   * Given a prefix, return the allowed fields that apply removing the prefix.
   *
   * @param bool|string[] $allowed_fields
   *   The list of allowed fields in dot notation.
   * @param string $prefix
   *   The prefix used to select the fields and to remove from the front.
   *
   * @return bool|string[]
   *   The new allowed fields for the nested sub-request.
   */
  protected static function unprefixInputOptions($allowed_fields, $prefix) {
    if ($allowed_fields === FALSE) {
      return FALSE;
    }
    $closure_unprefix = function ($field_limit) use ($prefix) {
      if ($field_limit == $prefix) {
        return NULL;
      }
      $pos = strpos($field_limit, $prefix . '.');
      // Remove the prefix from the $field_limit.
      return $pos === 0 ? substr($field_limit, strlen($prefix . '.')) : NULL;
    };
    return array_filter(array_map($closure_unprefix, $allowed_fields));
  }


  /**
   * Helper function that calculates the number of items per page.
   *
   * @param ResourceInterface $resource
   *   The associated resource.
   *
   * @return int
   *   The items per page.
   */
  protected function calculateItemsPerPage(ResourceInterface $resource) {
    $data_provider = $resource->getDataProvider();
    $max_range = $data_provider->getRange();
    $original_input = $resource->getRequest()->getPagerInput();
    $items_per_page = empty($original_input['size']) ? $max_range : $original_input['size'];
    return $items_per_page > $max_range ? $max_range : $items_per_page;
  }

}
