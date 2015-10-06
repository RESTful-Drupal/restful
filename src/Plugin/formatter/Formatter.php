<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\formatter\Formatter
 */

namespace Drupal\restful\Plugin\formatter;

use Drupal\Component\Plugin\PluginBase;
use Drupal\restful\Plugin\ConfigurablePluginTrait;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\RenderCache\RenderCache;
use Drupal\restful\RenderCache\RenderCacheInterface;

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
   * @param mixed $output
   *   The rendered data to output.
   */
  protected function setCachedData($data, $output) {
    if (!$render_cache = $this->createCacheController($data)) {
      return;
    }
    $render_cache->set($output);
  }

  /**
   * Gets a cache controller based on the data to be rendered.
   *
   * @param mixed $data
   *   The data to be rendered.
   *
   * @return RenderCacheInterface
   *   The cache controller.
   */
  protected function createCacheController($data) {
    $context = $data->getContext();
    if (!$cache_fragments = $context['cache_fragments']) {
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
    return RenderCache::create($cache_fragments, $cached_resource->getCacheController());
  }

  /**
   * Remove the unnecessary fields from the response.
   *
   * @param string[] $limit_fields
   *   The list of fields.
   * @param array $output
   *   The prepared output.
   *
   * @return array
   *   The filtered contents.
   */
  public function limitFields($limit_fields, array $output) {
    if (!$limit_fields) {
      return $output;
    }
    foreach (array('attributes', 'relationships') as $section) {
      if (empty($output[$section])) {
        continue;
      }
      foreach (array_keys($output[$section]) as $field_name) {
        if (!in_array($field_name, $limit_fields)) {
          unset($output[$section][$field_name]);
        }
      }
    }
    return $output;
  }

}
