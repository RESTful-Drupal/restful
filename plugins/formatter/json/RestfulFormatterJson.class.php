<?php

/**
 * @file
 * Contains RestfulFormatterJson.
 */

class RestfulFormatterJson extends \RestfulFormatterBase implements \RestfulFormatterInterface {

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
    // Here we get the data after calling the backend storage for the resources.
    $output = array('data' => $data);

    if (!empty($this->handler)) {
      if (method_exists($this->handler, 'isListRequest') && !$this->handler->isListRequest()) {
        return $output;
      }
      if (method_exists($this->handler, 'getTotalCount')) {
        // Get the total number of items for the current request without pagination.
        $output['count'] = $this->handler->getTotalCount();
      }

      // Add HATEOAS to the output.
      $this->addHateoas($output);
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
   * Add HATEOAS links to list of item.
   *
   * @param $data
   *   The data array after initial massaging.
   */
  protected function addHateoas(array &$data) {
    if (!$this->handler) {
      return;
    }
    $request = $this->handler->getRequest();

    $data['_links'] = array();
    $page = !empty($request['page']) ? $request['page'] : 1;

    if ($page > 1) {
      $request['page'] = $page - 1;
      $data['_links']['previous'] = $this->handler->getUrl($request);
    }

    // We know that there are more pages if the total count is bigger than the
    // number of items of the current request plus the number of items in
    // previous pages.
    $items_per_page = $this->handler->getRange();
    $previous_items = ($page - 1) * $items_per_page;
    if ($data['count'] > count($data['data']) + $previous_items) {
      $request['page'] = $page + 1;
      $data['_links']['next'] = $this->handler->getUrl($request);
    }
  }
}

