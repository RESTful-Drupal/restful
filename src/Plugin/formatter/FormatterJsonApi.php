<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\formatter\FormatterJsonApi.
 */

namespace Drupal\restful\Plugin\formatter;
use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Plugin\resource\Field\ResourceFieldBase;
use Drupal\restful\Plugin\resource\Field\ResourceFieldInterface;

/**
 * Class FormatterJsonApi
 * @package Drupal\restful\Plugin\formatter
 *
 * @Formatter(
 *   id = "json_api",
 *   label = "JSON API",
 *   description = "Output in using the JSON API format."
 * )
 */
class FormatterJsonApi extends Formatter implements FormatterInterface {

  /**
   * Content Type
   *
   * @var string
   */
  protected $contentType = 'application/vnd.api+json; charset=utf-8';

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
      throw new ServerConfigurationException('Resource unavailable for JSON API formatter.');
    }
    return $data;
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

    if (!isset($data['links'])) {
      $data['links'] = array();
    }

    // Get self link.
    $data['links']['self'] = $resource->versionedUrl($resource->getPath());

    $input = $request->getParsedInput();
    $data_provider = $resource->getDataProvider();
    $page = !empty($input['page']) ? $input['page'] : 1;

    if ($page > 1) {
      $input['page'] = $page - 1;
      $data['links']['previous'] = $resource->getUrl();
    }

    // We know that there are more pages if the total count is bigger than the
    // number of items of the current request plus the number of items in
    // previous pages.
    $items_per_page = $data_provider->getRange();
    $previous_items = ($page - 1) * $items_per_page;
    if (isset($data['count']) && $data['count'] > count($data[$resource->getResourceMachineName()]) + $previous_items) {
      $input['page'] = $page + 1;
      $data['links']['next'] = $resource->getUrl();
    }

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

    $embedded = array();
    $nested_embed = $this
      ->getResource()
      ->getRequest()
      ->isListRequest($this->getResource()->getPath());

    $field_definitions = clone $this->getResource()->getFieldDefinitions();
    foreach ($field_definitions as $pubilc_field_name => $resource_field) {
      /* @var \Drupal\restful\Plugin\resource\Field\ResourceFieldInterface $resource_field */
      if (!$resource_field->getResource()) {
        // Not a resource.
        continue;
      }

      if (empty($row[$pubilc_field_name])) {
        // No value.
        continue;
      }

      if ($nested_embed) {
        $output += array('includes' => array());
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
      $row += array('links' => array());
      $row['links']['self'] = $row['self'];
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

        $prepared_row = $this->prepareRow($subrow, $output);
        if ($is_list_request) {
          $embedded[$resource_name][] = $prepared_row;
        }
        else {
          $output['included'][$resource_name][] = $prepared_row;
        }
      }
    }

    // Remove the original reference.
    unset($row[$public_field_name]);
    $row['relationships'][$public_field_name] = $row[$public_field_name];
  }

}
