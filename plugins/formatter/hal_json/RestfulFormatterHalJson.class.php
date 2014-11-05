<?php

/**
 * @file
 * Contains RestfulFormatterHalJson.
 */

class RestfulFormatterHalJson extends \RestfulFormatterBase implements \RestfulFormatterInterface {
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

    foreach ($data as &$row) {
      $row = $this->prepareRow($row);
    }

    $curies_resource = variable_get('restful_hal_curies_name', 'hal') . ':' . $this->handler->getResourceName();

    $output = array($curies_resource => $data);

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

    // Get self link.
    $data['_links']['self']['href'] = $this->handler->getUrl($request);

    $page = !empty($request['page']) ? $request['page'] : 1;

    if ($page > 1) {
      $request['page'] = $page - 1;
      $data['_links']['previous']['href'] = $this->handler->getUrl($request);
    }

    // We know that there are more pages if the total count is bigger than the
    // number of items of the current request plus the number of items in
    // previous pages.
    $items_per_page = $this->handler->getRange();
    $previous_items = ($page - 1) * $items_per_page;
    if ($data['count'] > count($data['data']) + $previous_items) {
      $request['page'] = $page + 1;
      $data['_links']['next']['href'] = $this->handler->getUrl($request);
    }

    $href = variable_get('restful_hal_curies_href');

    $data['curies'] = array(
      'name' => variable_get('restful_hal_curies_name', 'hal'),
      'href' => $href ? $href : url('docs/rels', array('absolute' => TRUE)) . '/{rel}',
      'templated' => TRUE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(array $row) {
    $this->addHateoasRow($row);
    return $row;
  }

  protected function addHateoasRow(array &$row) {
    $row += array('_links' => array());

    if ($row['self']) {
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


}

