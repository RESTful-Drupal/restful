<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\formatter\FormatterHalJson.
 */

namespace Drupal\restful\Plugin\formatter;
use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Plugin\resource\Field\ResourceFieldBase;
use Drupal\restful\Plugin\resource\Field\ResourceFieldInterface;

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
    if (!$this->getResource()) {
      throw new ServerConfigurationException('Resource unavailable for HAL formatter.');
    }

    $plugin_definition = $this->getResource()->getPluginDefinition();
    $curies_resource = $this->withCurie($plugin_definition['resource']);
    $output = array();

    foreach ($data as &$row) {
      $row = $this->prepareRow($row, $output);
    }

    $output[$curies_resource] = $data;

    if (!empty($this->resource)) {
      if (
        method_exists($this->resource, 'getTotalCount') &&
        method_exists($this->resource, 'isListRequest') &&
        $this->resource->isListRequest($this->resource->getPath())
      ) {
        // Get the total number of items for the current request without
        // pagination.
        $output['count'] = $this->resource->getTotalCount();
      }
      if (method_exists($this->resource, 'additionalHateoas')) {
        $output = array_merge($output, $this->resource->additionalHateoas());
      }

      // Add HATEOAS to the output.
      $this->addHateoas($output);
    }

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
   * @param $data
   *   The data array after initial massaging.
   */
  protected function addHateoas(array &$data) {
    if (!$this->resource) {
      return;
    }
    $request = $this->resource->getRequest();

    $data['_links'] = array();

    // Get self link.
    $data['_links']['self'] = array(
      'title' => 'Self',
      'href' => $this->resource->versionedUrl($this->resource->getPath()),
    );

    $page = !empty($request['page']) ? $request['page'] : 1;

    if ($page > 1) {
      $request['page'] = $page - 1;
      $data['_links']['previous'] = array(
        'title' => 'Previous',
        'href' => $this->resource->getUrl($request),
      );
    }

    $curies_resource = $this->withCurie($this->resource->getResourceName());

    // We know that there are more pages if the total count is bigger than the
    // number of items of the current request plus the number of items in
    // previous pages.
    $items_per_page = $this->resource->getRange();
    $previous_items = ($page - 1) * $items_per_page;
    if (isset($data['count']) && $data['count'] > count($data[$curies_resource]) + $previous_items) {
      $request['page'] = $page + 1;
      $data['_links']['next'] = array(
        'title' => 'Next',
        'href' => $this->resource->getUrl($request),
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
   * Massage the data of a single row.
   *
   * @param array $row
   *   A single row array.
   * @param array $output
   *   The output array, passed by reference.
   *
   * @return array
   *   The massaged data of a single row.
   */
  public function prepareRow(array $row, array &$output) {
    $this->addHateoasRow($row);

    if (!$curie = $this->getCurie()) {
      // Skip if there is no curie defined.
      return $row;
    }

    foreach ($this->getResource()->getFieldDefinitions() as $pubilc_field_name => $resource_field) {
      /** @var \Drupal\restful\Plugin\resource\Field\ResourceFieldInterface $resource_field */
      if (!$resource_field->getResource()) {
        // Not a resource.
        continue;
      }

      if (empty($row[$pubilc_field_name])) {
        // No value.
        continue;
      }

      $this->moveReferencesToEmbeds($output, $row, $resource_field);
    }

    return $row;
  }

  /**
   * Add Hateoas to a single row.
   *
   * @param array $row
   *   A single row array, passed by reference.
   */
  protected function addHateoasRow(array &$row) {
    if (!empty($row['self'])) {
      $row += array('_links' => array());
      $row['_links']['self']['href'] = $row['self'];
      unset($row['self']);
    }

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

  /**
   * Move the fields referencing other resources to the _embed key.
   *
   * @param array $output
   *   Output array to be modified.
   * @param array $row
   *   The row being processed.
   * @param ResourceFieldInterface  $resource_field
   *   The public field configuration array.
   */
  protected function moveReferencesToEmbeds(array &$output, array &$row, ResourceFieldInterface $resource_field) {
    $public_field_name = $resource_field->getPublicName();
    $value_metadata = $resource_field->getMetadata($row['id']);
    if (ResourceFieldBase::isArrayNumeric($row[$public_field_name])) {
      foreach ($row[$public_field_name] as $index => $resource_row) {
        if (empty($value_metadata[$index])) {
          // No metadata.
          continue;
        }
        $metadata = $value_metadata[$index];
        $this->moveMetadataResource($row, $resource_field, $metadata, $resource_row);
      }
    }
    else {
      $this->moveMetadataResource($row, $resource_field, $value_metadata, $row[$public_field_name]);
    }

    // Remove the original reference.
    unset($row[$public_field_name]);
  }

  /**
   * Move a single "embedded resource" to be under the "_embedded" property.
   *
   * @param array $output
   *   Output array to be modified. Passed by reference.
   * @param ResourceFieldInterface $resource_field
   *   The public field configuration array.
   * @param array $metadata
   *   The metadata to add.
   * @param mixed $resource_row
   *   The resource row.
   */
  protected function moveMetadataResource(array &$output, ResourceFieldInterface $resource_field, array $metadata, $resource_row) {
    // If there is no resource name in the metadata for this particular value,
    // assume that we are referring to the first resource in the field
    // definition.
    $resource = $resource_field->getResource();
    $resource_name = $resource['name'];

    $curies_resource = $this->withCurie($resource_name);
    $resource_row = $this->prepareRow($resource_row, $output);
    $output['_embedded'][$curies_resource][] = $resource_row;
  }

}
