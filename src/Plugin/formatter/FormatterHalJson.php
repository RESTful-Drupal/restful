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
    if (!$resource = $this->getResource()) {
      throw new ServerConfigurationException('Resource unavailable for HAL formatter.');
    }

    $curies_resource = $this->withCurie($resource->getResourceMachineName());
    $output = array();

    foreach ($data as &$row) {
      if (is_array($row)) {
        $row = $this->prepareRow($row, $output);
      }
    }

    $output[$curies_resource] = $data;

    if (!empty($resource)) {
      $data_provider = $resource->getDataProvider();
      if (
        $data_provider &&
        method_exists($data_provider, 'count') &&
        $resource->getRequest()->isListRequest($resource->getPath())
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

    $input = $request->getParsedInput();
    $data_provider = $resource->getDataProvider();
    $page = !empty($input['page']) ? $input['page'] : 1;

    if ($page > 1) {
      $input['page'] = $page - 1;
      $data['_links']['previous'] = array(
        'title' => 'Previous',
        'href' => $resource->getUrl(),
      );
    }

    $curies_resource = $this->withCurie($resource->getResourceMachineName());

    // We know that there are more pages if the total count is bigger than the
    // number of items of the current request plus the number of items in
    // previous pages.
    $items_per_page = $data_provider->getRange();
    $previous_items = ($page - 1) * $items_per_page;
    if (isset($data['count']) && $data['count'] > count($data[$curies_resource]) + $previous_items) {
      $input['page'] = $page + 1;
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

    $embedded = array();
    $nested_embed = $this
      ->getResource()
      ->getRequest()
      ->isListRequest($this->getResource()->getPath());

    $field_definitions = clone $this->getResource()->getFieldDefinitions();
    foreach ($field_definitions as $pubilc_field_name => $resource_field) {
      /** @var \Drupal\restful\Plugin\resource\Field\ResourceFieldInterface $resource_field */
      if (!$resource_field->getResource()) {
        // Not a resource.
        continue;
      }

      if (empty($row[$pubilc_field_name])) {
        // No value.
        continue;
      }

      if ($nested_embed) {
        $output += array('_embedded' => array());
      }

      $this->moveReferencesToEmbeds($output, $row, $resource_field, $embedded);
    }

    if (!empty($embedded) && $nested_embed) {
      $row['_embedded'] = $embedded;
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
   * Note that for multiple value entityreference
   * fields $row[$public_field_name] will be an array of values rather than a
   * single value.
   *
   * @param array $output
   *   Output array to be modified.
   * @param array $row
   *   The row being processed.
   * @param ResourceFieldInterface  $resource_field
   *   The public field configuration array.
   * @param array $embedded
   *   Embedded array to be modified.
   */
  protected function moveReferencesToEmbeds(array &$output, array &$row, ResourceFieldInterface $resource_field, array &$embedded) {
    $resource = $this->getResource();
    $public_field_name = $resource_field->getPublicName();
    $is_list_request = $resource
      ->getRequest()
      ->isListRequest($resource->getPath());
    $values_metadata = $resource_field->getMetadata($row['id']);

    // Wrap the row in an array if it isn't.
    if (!is_array($row[$public_field_name])) {
      $row[$public_field_name] = array();
    }
    $rows = ResourceFieldBase::isArrayNumeric($row[$public_field_name]) ? $row[$public_field_name] : array($row[$public_field_name]);

    foreach ($rows as $subindex => $subrow) {
      $metadata = $values_metadata[$subindex];

      // Loop through each value for the field.
      foreach ($subrow as $index => $resource_row) {
        if (empty($metadata[$index])) {
          // No metadata.
          continue;
        }

        $resource_info = $resource_field->getResource();
        $resource_name = $resource_info['name'];

        $curies_resource = $this->withCurie($resource_name);
        $prepared_row = $this->prepareRow($subrow, $output);
        if ($is_list_request) {
          $embedded[$curies_resource][] = $prepared_row;
        }
        else {
          $output['_embedded'][$curies_resource][] = $prepared_row;
        }
      }
    }

    // Remove the original reference.
    unset($row[$public_field_name]);
  }

}
