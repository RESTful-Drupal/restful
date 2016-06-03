<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\formatter\FormatterHalJson.
 */

namespace Drupal\restful\Plugin\formatter;
use Drupal\restful\Exception\InternalServerErrorException;
use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldResourceInterface;

/**
 * Class FormatterHalJson
 * @package Drupal\restful\Plugin\formatter
 *
 * @Formatter(
 *   id = "hal_json",
 *   label = "HAL+JSON",
 *   description = "Output in using the HAL conventions and JSON format.",
 *   curie = {
 *     "name": "hal",
 *     "path": "doc/rels",
 *     "template": "/{rel}",
 *   },
 * )
 */
class FormatterHalJson extends Formatter implements FormatterInterface {

  const CURIE_SEPARATOR = ':';

  /**
   * Content Type
   *
   * @var string
   */
  protected $contentType = 'application/hal+json; charset=utf-8';

  /**
   * {@inheritdoc}
   */
  public function prepare(array $data) {
    // If we're returning an error then set the content type to
    // 'application/problem+json; charset=utf-8'.
    if (!empty($data['status']) && floor($data['status'] / 100) != 2) {
      $this->contentType = 'application/problem+json; charset=utf-8';
      return $data;
    }
    // Here we get the data after calling the backend storage for the resources.
    if (!$resource = $this->getResource()) {
      throw new ServerConfigurationException('Resource unavailable for HAL formatter.');
    }
    $is_list_request = $resource->getRequest()->isListRequest($resource->getPath());

    $values = $this->extractFieldValues($data);
    $values = $this->limitFields($values);
    if ($is_list_request) {
      // If this is a listing, move everything into the _embedded.
      $curies_resource = $this->withCurie($resource->getResourceMachineName());
      $output = array(
        '_embedded' => array($curies_resource => $values),
      );
    }
    else {
      $output = reset($values) ?: array();
    }

    $data_provider = $resource->getDataProvider();
    if (
      $data_provider &&
      method_exists($data_provider, 'count') &&
      $is_list_request
    ) {
      // Get the total number of items for the current request without
      // pagination.
      $output['count'] = $data_provider->count();
    }
    if (method_exists($resource, 'additionalHateoas')) {
      $output = array_merge($output, $resource->additionalHateoas());
    }

    // Add HATEOAS to the output.
    $this->addHateoas($output);

    // Cosmetic sorting to send the hateoas properties to the end of the output.
    uksort($output, function ($a, $b) {
      if (
        ($a[0] == '_' && $b[0] == '_') ||
        ($a[0] != '_' && $b[0] != '_')
      ) {
        return strcmp($a, $b);
      }
      return $a[0] == '_' ? 1 : -1;
    });

    return $output;
  }

  /**
   * Add HATEOAS links to list of item.
   *
   * @param array $data
   *   The data array after initial massaging.
   */
  protected function addHateoas(array &$data) {
    if (!$resource = $this->getResource()) {
      return;
    }
    $request = $resource->getRequest();

    if (!isset($data['_links'])) {
      $data['_links'] = array();
    }

    // Get self link.
    $data['_links']['self'] = array(
      'title' => 'Self',
      'href' => $resource->versionedUrl($resource->getPath()),
    );

    $input = $request->getPagerInput();
    $page = $input['number'];

    if ($page > 1) {
      $input['number'] = $page - 1;
      $data['_links']['previous'] = array(
        'title' => 'Previous',
        'href' => $resource->getUrl(),
      );
    }

    $curies_resource = $this->withCurie($resource->getResourceMachineName());
    $listed_items = empty($data['_embedded'][$curies_resource]) ? 1 : count($data['_embedded'][$curies_resource]);

    // We know that there are more pages if the total count is bigger than the
    // number of items of the current request plus the number of items in
    // previous pages.
    $items_per_page = $this->calculateItemsPerPage($resource);
    $previous_items = ($page - 1) * $items_per_page;
    if (isset($data['count']) && $data['count'] > $listed_items + $previous_items) {
      $input['number'] = $page + 1;
      $data['_links']['next'] = array(
        'title' => 'Next',
        'href' => $resource->getUrl(),
      );
    }

    if (!$curie = $this->getCurie()) {
      return;
    }

    $curie += array(
      'path' => 'doc/rels',
      'template' => '/{rel}',
    );
    $data['_links']['curies'] = array(
      'name' => $curie['name'],
      'href' => url($curie['path'], array('absolute' => TRUE)) . $curie['template'],
      'templated' => TRUE,
    );
  }

  /**
   * Extracts the actual values from the resource fields.
   *
   * @param array|\Traversable|\stdClass $data
   *   The array of rows.
   *
   * @return array[]
   *   The array of prepared data.
   *
   * @throws \Drupal\restful\Exception\InternalServerErrorException
   */
  protected function extractFieldValues($data, array $parents = array(), array &$parent_hashes = array()) {
    $output = array();
    if ($this->isCacheEnabled($data)) {
      $parent_hashes[] = $this->getCacheHash($data);
      if ($cache = $this->getCachedData($data)) {
        return $cache->data;
      }
    }
    foreach ($data as $public_field_name => $resource_field) {
      if (!$resource_field instanceof ResourceFieldInterface) {
        // If $resource_field is not a ResourceFieldInterface it means that we
        // are dealing with a nested structure of some sort. If it is an array
        // we process it as a set of rows, if not then use the value directly.
        $parents[] = $public_field_name;
        $output[$public_field_name] = static::isIterable($resource_field) ? $this->extractFieldValues($resource_field, $parents, $parent_hashes) : $resource_field;
        continue;
      }
      if (!$data instanceof ResourceFieldCollectionInterface) {
        throw new InternalServerErrorException('Inconsistent output.');
      }

      // This feels a bit awkward, but if the result is going to be cached, it
      // pays off the extra effort of generating the whole resource entity. That
      // way we can get a different field set with the previously cached entity.
      // If the entity is not going to be cached, then avoid generating the
      // field data altogether.
      $limit_fields = $data->getLimitFields();
      if (
        $this->isCacheEnabled($data) &&
        $limit_fields &&
        !in_array($resource_field->getPublicName(), $limit_fields)
      ) {
        // We are not going to cache this and this field is not in the output.
        continue;
      }
      $value = $resource_field->render($data->getInterpreter());
      // If the field points to a resource that can be included, include it
      // right away.
      if (
        static::isIterable($value)
        && $resource_field instanceof ResourceFieldResourceInterface
      ) {
        $output += array('_embedded' => array());
        $output['_embedded'][$this->withCurie($public_field_name)] = $this->extractFieldValues($value, $parents, $parent_hashes);
        continue;
      }
      $output[$public_field_name] = $value;
    }
    if ($this->isCacheEnabled($data)) {
      $this->setCachedData($data, $output, $parent_hashes);
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function render(array $structured_data) {
    return drupal_json_encode($structured_data);
  }

  /**
   * {@inheritdoc}
   */
  public function getContentTypeHeader() {
    return $this->contentType;
  }

  /**
   * Prefix a property name with the curie, if present.
   *
   * @param string $property_name
   *   The input string.
   *
   * @return string
   *   The property name prefixed with the curie.
   */
  protected function withCurie($property_name) {
    if ($curie = $this->getCurie()) {
      return $property_name ? $curie['name'] . static::CURIE_SEPARATOR . $property_name : $curie['name'];
    }
    return $property_name;
  }

  /**
   * Checks if the current plugin has a defined curie.
   *
   * @return array
   *   Associative array with the curie information.
   */
  protected function getCurie() {
    return $this->configuration['curie'];
  }

}
