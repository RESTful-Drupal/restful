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

    $curies_resource = $this->withCurie($this->handler->getResourceName());
    $output = array();

    foreach ($data as &$row) {
      $row = $this->prepareRow($row, $output);
    }

    $output[$curies_resource] = $data;

    if (!empty($this->handler)) {
      if (
        method_exists($this->handler, 'getTotalCount') &&
        method_exists($this->handler, 'isListRequest') &&
        $this->handler->isListRequest()
      ) {
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
    $data['_links']['self'] = array(
      'title' => 'Self',
      'href' => $this->handler->versionedUrl($this->handler->getPath()),
    );

    $page = !empty($request['page']) ? $request['page'] : 1;

    if ($page > 1) {
      $request['page'] = $page - 1;
      $data['_links']['previous'] = array(
        'title' => 'Previous',
        'href' => $this->handler->getUrl($request),
      );
    }

    $curies_resource = $this->withCurie($this->handler->getResourceName());

    // We know that there are more pages if the total count is bigger than the
    // number of items of the current request plus the number of items in
    // previous pages.
    $items_per_page = $this->handler->getRange();
    $previous_items = ($page - 1) * $items_per_page;
    if ($data['count'] > count($data[$curies_resource]) + $previous_items) {
      $request['page'] = $page + 1;
      $data['_links']['next'] = array(
        'title' => 'Next',
        'href' => $this->handler->getUrl($request),
      );
    }

    if (!$curie = $this->getCurie()) {
      return;
    }

    $data['_links']['curies'] = array(
      'name' => $curie['name'],
      'href' => $curie['href'] ? $curie['href'] : url('docs/rels', array('absolute' => TRUE)) . '/{rel}',
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

    foreach ($this->handler->getPublicFields() as $name => $public_field) {
      if (empty($public_field['resource'])) {
        // Not a resource.
        continue;
      }

      if (empty($row[$name])) {
        // No value.
        continue;
      }

      if (count($public_field['resource']) == 1) {
        $resource = reset($public_field['resource']);
        $resource_name = $resource['name'];
      }
      else {
        foreach ($public_field['resource'] as $resource_bundle => $resource) {
          $resource_handler = restful_get_restful_handler($resource['name'], $resource['major_version'], $resource['minor_version']);
          if ($this->handler instanceof \RestfulEntityBase) {
            // Only entity resources can have multiple resources assigned to a
            // field. This is to avoid the creation of a resource with multiple
            // bundles for every entityreference field.
            continue;
          }
          $entity_type = $resource_handler->getEntityType();
          // @todo: To avoid an extra entity load we should be able to pass
          // this info in when generating the output array.
          $entity = entity_load_single($entity_type, $row['id']);
          list(,, $bundle) = entity_extract_ids($entity_type, $entity);
          if ($resource_handler->getBundle() == $bundle) {
            $resource_name = $resource['name'];
            continue 2;
          }
        }
      }

      $curies_resource = $this->withCurie($resource_name);

      $output += array('_embedded' => array());

      foreach ($row[$name] as $resource_row) {
        $resource_row = $this->prepareRow($resource_row, $output);
        $output['_embedded'][$curies_resource][] = $resource_row;
      }

      // Remove the original reference.
      unset($row[$name]);
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
      return $property_name ? $curie['name'] . ':' . $property_name : $curie['name'];
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
    return $this->getPluginKey('curie');
  }

}

