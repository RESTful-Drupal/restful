<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\formatter\FormatterJson.
 */

namespace Drupal\restful\Plugin\formatter;

use Drupal\restful\Exception\InternalServerErrorException;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldResourceInterface;

/**
 * Class FormatterJson
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

    if ($this->isError($data['status'])) {
      // Return early, as the response is an error.
      return $data;
    }

    $output = array('data' => $this->extractFieldValues($data));

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
   * Extracts the actual values from the resource fields.
   *
   * @param array[]|ResourceFieldCollectionInterface $data
   *   The array of rows or a ResourceFieldCollection.
   *
   * @return array[]
   *   The array of prepared data.
   *
   * @throws InternalServerErrorException
   */
  protected function extractFieldValues($data) {
    $output = array();
    foreach ($data as $public_field_name => $resource_field) {
      if (!$resource_field instanceof ResourceFieldInterface) {
        // If $resource_field is not a ResourceFieldInterface it means that we
        // are dealing with a nested structure of some sort. If it is an array
        // we process it as a set of rows, if not then use the value directly.
        $output[$public_field_name] = static::isIterable($resource_field) ? $this->extractFieldValues($resource_field) : $resource_field;
        continue;
      }
      if (!$data instanceof ResourceFieldCollectionInterface) {
        throw new InternalServerErrorException('Inconsistent output.');
      }
      $value = $resource_field->render($data->getInterpreter());
      // If the field points to a resource that can be included, include it
      // right away.
      if (
        static::isIterable($value) &&
        $resource_field instanceof ResourceFieldResourceInterface
      ) {
        $value = $this->extractFieldValues($value);
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

