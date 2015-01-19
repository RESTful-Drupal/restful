<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\formatter\FormatterJson.
 */

namespace Drupal\restful\Plugin\formatter;

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

    $output = array('data' => $data);

    if (!empty($this->resource)) {
      if (
        method_exists($this->resource, 'getTotalCount') &&
        method_exists($this->resource, 'isListRequest') &&
        $this->resource->isListRequest()
      ) {
        // Get the total number of items for the current request without pagination.
        $output['count'] = $this->resource->getTotalCount();
      }
      if (method_exists($this->resource, 'additionalHateoas')) {
        $output = array_merge($output, $this->resource->additionalHateoas());
      }

      // Add HATEOAS to the output.
      $this->addHateoas($output);
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
    if (!$this->resource) {
      return;
    }
    $request = $this->resource->getRequest();

    // Get self link.
    $data['self'] = array(
      'title' => 'Self',
      'href' => $this->resource->versionedUrl($this->resource->getPath()),
    );

    $page = !empty($request['page']) ? $request['page'] : 1;

    if ($page > 1) {
      $request['page'] = $page - 1;
      $data['previous'] = array(
        'title' => 'Previous',
        'href' => $this->resource->getUrl($request),
      );
    }

    // We know that there are more pages if the total count is bigger than the
    // number of items of the current request plus the number of items in
    // previous pages.
    $items_per_page = $this->resource->getRange();
    $previous_items = ($page - 1) * $items_per_page;
    if (isset($data['count']) && $data['count'] > count($data['data']) + $previous_items) {
      $request['page'] = $page + 1;
      $data['next'] = array(
        'title' => 'Next',
        'href' => $this->resource->getUrl($request),
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

