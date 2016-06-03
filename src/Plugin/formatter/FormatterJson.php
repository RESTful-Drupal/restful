<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\formatter\FormatterJson.
 */

namespace Drupal\restful\Plugin\formatter;

use Drupal\restful\Exception\BadRequestException;
use Drupal\restful\Exception\InternalServerErrorException;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldResourceInterface;

/**
 * Class FormatterHalJson.
 *
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
   * Content Type.
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

    $extracted = $this->extractFieldValues($data);
    $output = array('data' => $this->limitFields($extracted));

    if ($resource = $this->getResource()) {
      $request = $resource->getRequest();
      $data_provider = $resource->getDataProvider();
      if ($request->isListRequest($resource->getPath())) {
        // Get the total number of items for the current request without
        // pagination.
        $output['count'] = $data_provider->count();
        // If there are items that were taken out during access checks,
        // report them as denied in the metadata.
        if (variable_get('restful_show_access_denied', FALSE) && ($inaccessible_records = $data_provider->getMetadata()->get('inaccessible_records'))) {
          $output['denied'] = empty($output['meta']['denied']) ? $inaccessible_records : $output['meta']['denied'] + $inaccessible_records;
        }
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
   * @param string[] $parents
   *   An array that holds the name of the parent fields that lead to the
   *   current data structure.
   * @param string[] $parent_hashes
   *   An array that holds the name of the parent cache hashes that lead to the
   *   current data structure.
   *
   * @return array[]
   *   The array of prepared data.
   *
   * @throws InternalServerErrorException
   */
  protected function extractFieldValues($data, array $parents = array(), array &$parent_hashes = array()) {
    $output = array();
    if ($this->isCacheEnabled($data)) {
      $parent_hashes[] = $this->getCacheHash($data);
      if ($cache = $this->getCachedData($data)) {
        return $cache->data;
      }
    }
    foreach ($data as $public_field_name => $resource_field) {
      if (!$resource_field instanceof ResourceFieldInterface) {
        // If $resource_field is not a ResourceFieldInterface it means that we
        // are dealing with a nested structure of some sort. If it is an array
        // we process it as a set of rows, if not then use the value directly.
        $parents[] = $public_field_name;
        $output[$public_field_name] = static::isIterable($resource_field) ? $this->extractFieldValues($resource_field, $parents, $parent_hashes) : $resource_field;
        continue;
      }
      if (!$data instanceof ResourceFieldCollectionInterface) {
        throw new InternalServerErrorException('Inconsistent output.');
      }

      // This feels a bit awkward, but if the result is going to be cached, it
      // pays off the extra effort of generating the whole resource entity. That
      // way we can get a different field set with the previously cached entity.
      // If the entity is not going to be cached, then avoid generating the
      // field data altogether.
      $limit_fields = $data->getLimitFields();
      if (
        !$this->isCacheEnabled($data) &&
        $limit_fields &&
        !in_array($resource_field->getPublicName(), $limit_fields)
      ) {
        // We are not going to cache this and this field is not in the output.
        continue;
      }
      $value = $resource_field->render($data->getInterpreter());
      // If the field points to a resource that can be included, include it
      // right away.
      if (
        static::isIterable($value) &&
        $resource_field instanceof ResourceFieldResourceInterface
      ) {
        $value = $this->extractFieldValues($value, $parents, $parent_hashes);
      }
      $output[$public_field_name] = $value;
    }
    if ($this->isCacheEnabled($data)) {
      $this->setCachedData($data, $output, $parent_hashes);
    }
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

    // Get self link.
    $data['self'] = array(
      'title' => 'Self',
      'href' => $resource->versionedUrl($resource->getPath()),
    );

    $input = $request->getParsedInput();
    unset($input['page']);
    unset($input['range']);
    $input['page'] = $request->getPagerInput();
    $page = $input['page']['number'];

    if ($page > 1) {
      $query = $input;
      $query['page']['number'] = $page - 1;
      $data['previous'] = array(
        'title' => 'Previous',
        'href' => $resource->versionedUrl('', array('query' => $query), TRUE),
      );
    }

    // We know that there are more pages if the total count is bigger than the
    // number of items of the current request plus the number of items in
    // previous pages.
    $items_per_page = $this->calculateItemsPerPage($resource);
    $previous_items = ($page - 1) * $items_per_page;
    if (isset($data['count']) && $data['count'] > count($data['data']) + $previous_items) {
      $query = $input;
      $query['page']['number'] = $page + 1;
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

  /**
   * {@inheritdoc}
   */
  public function parseBody($body) {
    if (!$decoded_json = drupal_json_decode($body)) {
      throw new BadRequestException(sprintf('Invalid JSON provided: %s.', $body));
    }
    return $decoded_json;
  }

}

