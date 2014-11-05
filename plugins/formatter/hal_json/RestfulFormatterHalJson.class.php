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

    $curies_resource = variable_get('restful_hal_curies_name', 'hal') . ':' . $this->handler->getResourceName();
    $output = array();

    foreach ($data as &$row) {
      $row = $this->prepareRow($row, $output);
    }

    $output[$curies_resource] = $data;

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
    $curies_resource = variable_get('restful_hal_curies_name', 'hal') . ':' . $this->handler->getResourceName();

    $data['_links'] = array();

    // Get self link.
    $data['_links']['self'] = array(
      'title' => 'Self',
      'href' => $this->handler->getUrl($request),
    );

    $page = !empty($request['page']) ? $request['page'] : 1;

    if ($page > 1) {
      $request['page'] = $page - 1;
      $data['_links']['previous'] = array(
        'title' => 'Previous',
        'href' => $this->handler->getUrl($request),
      );
    }

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

    $href = variable_get('restful_hal_curies_href');

    $data['_links']['curies'] = array(
      'name' => variable_get('restful_hal_curies_name', 'hal'),
      'href' => $href ? $href : url('docs/rels', array('absolute' => TRUE)) . '/{rel}',
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
      elseif ($this->handler instanceof \RestfulEntityBase) {
        // @todo: How to deal with non entity resource, where we can't
        // entity_load()?
        $id = $row['id'];
        $entity_type = $this->handler->getEntityType();
        $entity = entity_load_single($entity_type, $id);
        list(,, $bundle) = entity_extract_ids($entity_type, $entity);

        foreach ($public_field['resource'] as $resource_bundle => $resource) {
          if ($resource_bundle == $bundle) {
            $resource_name = $resource['name'];
            continue 2;
          }
        }
      }

      $curies_resource = variable_get('restful_hal_curies_name', 'hal') . ':' . $resource_name;

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


}

