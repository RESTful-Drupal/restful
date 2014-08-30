<?php

/**
 * @file
 * Contains RestfulFormatterHalJson.
 */

class RestfulFormatterHalJson extends \RestfulFormatterBase implements \RestfulFormatterInterface {
  /**
   * {@inheritdoc}
   */
  public function massage(array $data) {
    // Here we get the data after calling the backend storage for the resources.
    if (count($data) == 1 && !$this->handler->isListRequest()) {
      $output = array('data' => reset($data));
    }
    else {
      $output = array('data' => $data);
    }
    $this->addHateoas($output);
    // TODO: Provide a count of all the possible entities (not only the current
    // page)
    // $output['count'] = count($output['data']);
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
    return 'application/hal+json; charset=utf-8';
  }

  /**
   * Add HATEOAS links to list of item.
   *
   * @param $data
   *   The data array after initial massaging.
   */
  protected function addHateoas(array &$data) {
    $request = $this->handler->getRequest();

    $data['_links'] = array();
    $page = !empty($request['page']) ? $request['page'] : 1;

    if ($page > 1) {
      $request['page'] = $page - 1;
      $data['_links']['previous'] = $this->handler->getUrl();
    }

    if (count($data['data']) > $this->handler->getRange()) {
      $request['page'] = $page + 1;
      $data['_links']['next'] = $this->handler->getUrl();

      // Remove the last item, as it was just used to determine if there is a
      // "next" page.
      array_pop($data['data']);
    }
  }
}

