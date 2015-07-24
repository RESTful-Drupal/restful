<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\formatter\FormatterJson.
 */

namespace Drupal\restful\Plugin\formatter;
use Drupal\restful\Plugin\resource\Field\ResourceFieldBase;
use Drupal\restful\Plugin\resource\Field\ResourceFieldInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldResourceInterface;

/**
 * Class FormatterHalJson
 * @package Drupal\restful\Plugin\formatter
 *
 * @Formatter(
 *   id = "json",
 *   label = "Simple JSON",
 *   description = "Output in using the JSON format."
 * )
 */
class FormatterJson extends Formatter implements FormatterInterface {

  /**
   * Content Type
   *
   * @var string
   */
  protected $contentType = 'application/json; charset=utf-8';

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

    $output = array('data' => $this->prepareRows($data));

    if ($resource = $this->getResource()) {
      $request = $resource->getRequest();
      $data_provider = $resource->getDataProvider();
      if ($request->isListRequest($resource->getPath())) {
        // Get the total number of items for the current request without
        // pagination.
        $output['count'] = $data_provider->count();
      }
      if (method_exists($resource, 'additionalHateoas')) {
        $output = array_merge($output, $resource->additionalHateoas($output));
      }

      // Add HATEOAS to the output.
      $this->addHateoas($output);
    }

    return $output;
  }

  /**
   * Prepare an array of rows.
   *
   * @param array[] $rows
   *   The array of rows.
   *
   * @return array[]
   *   The array of prepared data.
   */
  protected function prepareRows(array $rows) {
    $output = array();
    foreach ($rows as $public_field_name => $resource_field) {
      if (!$resource_field instanceof ResourceFieldInterface) {
        // If $resource_field is not a ResourceFieldInterface it means that we
        // are dealing with a nested structure of some sort. If it is an array
        // we process it as a set of rows, if not then use the value directly.
        $output[$public_field_name] = is_array($resource_field) ? $this->prepareRows($resource_field) : $resource_field;
        continue;
      }
      $value = $resource_field->value();
      $value = $resource_field->executeProcessCallbacks($value);
      if ($resource_field instanceof ResourceFieldResourceInterface) {
        $value = $this->prepareRows($value);
      }
      $output[$public_field_name] = $value;
    }
    return $output;
  }

  /**
   * Add HATEOAS links to list of item.
   *
   * @param $data
   *   The data array after initial massaging.
   */
  protected function addHateoas(array &$data) {
    if (!$resource = $this->getResource()) {
      return;
    }
    $request = $resource->getRequest();

    // Get self link.
    $data['self'] = array(
      'title' => 'Self',
      'href' => $resource->versionedUrl($resource->getPath()),
    );

    $input = $request->getParsedInput();
    $page = !empty($input['page']) ? $input['page'] : 1;

    if ($page > 1) {
      $query = array(
        'page' => $page - 1,
      ) + $input;
      $data['previous'] = array(
        'title' => 'Previous',
        'href' => $resource->versionedUrl('', array('query' => $query), TRUE),
      );
    }

    // We know that there are more pages if the total count is bigger than the
    // number of items of the current request plus the number of items in
    // previous pages.
    $items_per_page = $resource->getDataProvider()->getRange();
    $previous_items = ($page - 1) * $items_per_page;
    if (isset($data['count']) && $data['count'] > count($data['data']) + $previous_items) {
      $query = array(
        'page' => $page + 1,
      ) + $input;
      $data['next'] = array(
        'title' => 'Next',
        'href' => $resource->versionedUrl('', array('query' => $query), TRUE),
      );
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
}

