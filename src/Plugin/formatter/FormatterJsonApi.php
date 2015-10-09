<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\formatter\FormatterJsonApi.
 */

namespace Drupal\restful\Plugin\formatter;

use Drupal\restful\Exception\InternalServerErrorException;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldBase;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldResourceInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;

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

    $extracted = $this->extractFieldValues($data);
    $included = array();
    $output = array('data' => $this->decompress($extracted, $included));
    $output = $this->populateIncludes($output, $included);

    if ($resource = $this->getResource()) {
      $request = $resource->getRequest();
      $data_provider = $resource->getDataProvider();
      $is_list_request = $request->isListRequest($resource->getPath());
      if ($is_list_request) {
        // Get the total number of items for the current request without
        // pagination.
        $output['meta']['count'] = $data_provider->count();
      }
      else {
        // For non-list requests do not return an array of one item.
        $output['data'] = reset($output['data']);
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
   * @param array $included
   *   An array to hold the external references to have them in the top-level.
   *
   * @return array[]
   *   The array of prepared data.
   *
   * @throws InternalServerErrorException
   */
  protected function extractFieldValues($data, array $parents = array()) {
    $output = array();
    if ($this->isCacheEnabled($data) && ($cache = $this->getCachedData($data))) {
      /* @var ResourceFieldCollectionInterface $data */
      return $this->limitFields($data->getLimitFields(), $cache->data);
    }
    foreach ($data as $public_field_name => $resource_field) {
      if (!$resource_field instanceof ResourceFieldInterface) {
        // If $resource_field is not a ResourceFieldInterface it means that we
        // are dealing with a nested structure of some sort. If it is an array
        // we process it as a set of rows, if not then use the value directly.
        $parents[] = $public_field_name;
        $output[$public_field_name] = static::isIterable($resource_field) ? $this->extractFieldValues($resource_field, $parents) : $resource_field;
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
      if ($resource = $this->getResource()) {
        $output['__resource__name'] = $resource->getResourceMachineName();
        $resource_id = $data->getIdField()->value($data->getInterpreter());
        if (!is_array($resource_id)) {
          // In some situations when making an OPTIONS call the $resource_id
          // returns the array of discovery information instead of a real value.
          $output['__resource__id'] = (string) $resource_id;
        }
        $this->addHateoas($output, $resource, $resource_id);
      }
      $interpreter = $data->getInterpreter();
      $output['__fields'][$public_field_name] = $this->embedField($resource_field, $interpreter, $parents);
    }
    if ($this->isCacheEnabled($data)) {
      $this->setCachedData($data, $output);
      $output = $this->limitFields($data->getLimitFields(), $output);
    }
    return $output;
  }

  /**
   * Add HATEOAS links to list of item.
   *
   * @param array $data
   *   The data array after initial massaging.
   * @param ResourceInterface $resource
   *   The resource to use.
   * @param string $path
   *   The resource path.
   */
  protected function addHateoas(array &$data, ResourceInterface $resource = NULL, $path = NULL) {
    $top_level = empty($resource);
    $resource = $resource ?: $this->getResource();
    $path = isset($path) ? $path : $resource->getPath();
    if (!$resource) {
      return;
    }
    $request = $resource->getRequest();

    if (!isset($data['links'])) {
      $data['links'] = array();
    }

    $input = $original_input = $request->getParsedInput();

    // Get self link.
    $options = $top_level ? array('query' => $input) : array();
    $data['links']['self'] = $resource->versionedUrl($path, $options);

    $data_provider = $resource->getDataProvider();
    $page = !empty($input['page']) ? $input['page'] : 1;

    // We know that there are more pages if the total count is bigger than the
    // number of items of the current request plus the number of items in
    // previous pages.
    $items_per_page = empty($original_input['range']) ? $data_provider->getRange() : $original_input['range'];
    if (isset($data['meta']['count']) && $data['meta']['count'] > $items_per_page) {
      $num_pages = ceil($data['meta']['count'] / $items_per_page);
      unset($input['page']);
      $data['links']['first'] = $resource->getUrl(array('query' => $input), FALSE);

      if ($page > 1) {
        $input = $original_input;
        $input['page'] = $page - 1;
        $data['links']['previous'] = $resource->getUrl(array('query' => $input), FALSE);
      }
      if ($num_pages > 1) {
        $input = $original_input;
        $input['page'] = $num_pages;
        $data['links']['last'] = $resource->getUrl(array('query' => $input), FALSE);
        if ($page != $num_pages) {
          $input = $original_input;
          $input['page'] = $page + 1;
          $data['links']['next'] = $resource->getUrl(array('query' => $input), FALSE);
        }
      }
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
   * Gets the request object for this formatter.
   *
   * @return RequestInterface
   *   The request object.
   */
  protected function getRequest() {
    if ($resource = $this->getResource()) {
      return $resource->getRequest();
    }
    return restful()->getRequest();
  }

  /**
   * Move the embedded resources to the included key.
   *
   * Change the data structure from an auto-contained hierarchical tree to the
   * final JSON API structure. The auto-contained tree has redundant information
   * because every branch contains all the information that is embedded in there
   * and can be used as stand alone.
   *
   * @param array $output
   *   The output array to modify to include the compounded documents.
   * @param array $included
   *   Pool of documents to compound.
   *
   * @return array
   *   The processed data.
   *
   * @throws \Drupal\restful\Exception\InternalServerErrorException
   */
  protected function decompress(array $output, array &$included) {
    static $depth = -1;
    $depth++;
    if (!is_array($output)) {
      // $output is a simple value.
      $depth--;
      return $output;
    }
    $result = array();
    if (ResourceFieldBase::isArrayNumeric($output)) {
      foreach ($output as $item) {
        $result[] = $this->decompress($item, $included);
      }
      $depth--;
      return $result;
    }
    if (!empty($output['__resource__name'])) {
      $result['type'] = $output['__resource__name'];
    }
    if (!empty($output['__resource__id'])) {
      $result['id'] = $output['__resource__id'];
    }
    if (empty($output['__fields'])) {
      $depth--;
      return $this->decompress($output, $included);
    }
    foreach ($output['__fields'] as $field_name => $field_contents) {
      if (empty($field_contents['__relationship__info'])) {
        $result['attributes'][$field_name] = $field_contents;
      }
      else {
        $rel = $field_contents['__relationship__info'];
        unset($field_contents['__relationship__info']);
        $field_path = $field_contents['__relationship__field_path'];
        unset($field_contents['__relationship__field_path']);
        $include_key = $field_contents['__resource__name'] . '--' . $field_contents['__resource__id'];
        $included[$field_path][$include_key] = $this->decompress($field_contents, $included) + array('links' => $rel['links']);
        // Only place the relationship info.
        $result['relationships'][$field_name] = $rel;
      }
    }

    // Decrease the depth level.
    $depth--;
    return $result;
  }

  /**
   * Gather all of the includes.
   */
  protected function populateIncludes($output, $included) {
    // Loop through the included resource entities and add them to the output if
    // they are included from the request.
    $input = $this->getRequest()->getParsedInput();
    $requested_includes = empty($input['include']) ? array() : explode(',', $input['include']);
    // Keep track of everything that has been included.
    $include_keys = array();
    foreach ($requested_includes as $requested_include) {
      foreach ($included[$requested_include] as $include_key => $included_item) {
        if (in_array($include_key, $include_keys)) {
          continue;
        }
        $output['included'][] = $included_item;
        $include_keys[] = $include_key;
      }
    }
    return $output;
  }

  /**
   * Remove the unnecessary fields from the response.
   *
   * @param string[] $limit_fields
   *   The list of fields.
   * @param array $output
   *   The prepared output.
   *
   * @return array
   *   The filtered contents.
   */
  public function limitFields($limit_fields, array $output) {
    if (!$limit_fields) {
      return $output;
    }
    if (empty($output['__fields'])) {
      return $output;
    }
    foreach (array_keys($output['__fields']) as $field_name) {
      if (!in_array($field_name, $limit_fields)) {
        unset($output['__fields'][$field_name]);
      }
    }
    return $output;
  }

  /**
   * Embeds the final contents of a field.
   *
   * If the field is a relationship to another resource, it embeds the resource.
   *
   * @param ResourceFieldInterface $resource_field
   *   The resource field being processed. If it is a related resource, this is
   *   used to extract the contents of the resource. If not, it's used to
   *   extract the simple value.
   * @param DataInterpreterInterface $interpreter
   *   The context for the $resource_field.
   * @param array $parents
   *   Tracks the parents of the field to construct the dot notation for the
   *   field name.
   *
   * @return array
   *   The contents for the JSON API attribute or relationship.
   */
  protected function embedField(ResourceFieldInterface $resource_field, DataInterpreterInterface $interpreter, array &$parents) {
    // If the field points to a resource that can be included, include it
    // right away.
    $value = $resource_field->render($interpreter);
    $public_field_name = $resource_field->getPublicName();
    if (
      empty($value) ||
      !static::isIterable($value) ||
      !$resource_field instanceof ResourceFieldResourceInterface
    ) {
      return $value;
    }
    // At this point we are dealing with an embed.
    $output = array();
    $new_parents = $parents;
    $new_parents[] = $public_field_name;
    $value = $this->extractFieldValues($value, $new_parents);
    $ids = $resource_field->compoundDocumentId($interpreter);
    $cardinality = $resource_field->cardinality();
    if ($cardinality == 1) {
      $value = array($value);
      $ids = array($ids);
    }
    // If some IDs were filtered out in the value while rendering due to the
    // nested filtering with a target, we should remove those from the IDs
    // in the relationship.
    $filter_invalid_ids = function ($id) use ($value) {
      foreach ($value as $info) {
        if (empty($info['__resource__id'])) {
          return FALSE;
        }
        if ($info['__resource__id'] == $id) {
          return TRUE;
        }
      }
      return FALSE;
    };
    $ids = array_filter($ids, $filter_invalid_ids);
    $value = array_filter($value);
    $combined = $ids ? array_combine($ids, array_pad($value, count($ids), NULL)) : array();
    // Set the resource for the reference to get HATEOAS from them.
    $resource_plugin = $resource_field->getResourcePlugin();
    foreach ($combined as $id => $value_item) {
      $basic_info = array(
        'type' => $resource_field->getResourceMachineName(),
        'id' => (string) $id,
      );
      // If there is a resource plugin for the parent, set the related
      // links.
      if ($resource = $this->getResource()) {
        $basic_info['links']['self'] = $resource_plugin->versionedUrl($id);
        $basic_info['links']['related'] = $resource->versionedUrl('', array(
          'absolute' => TRUE,
          'query' => array(
            'filter' => array($resource_field->getPublicName() => $id),
          ),
        ));
      }

      // We want to be able to include only the images in articles.images,
      // but not articles.related.images. That's why we need the path
      // including the parents.

      // Remove numeric parents since those only indicate that the field was
      // multivalue, not a parent: articles[related][1][tags][2][name] turns
      // into 'articles.related.tags.name'.
      $array_path = $parents;
      array_push($array_path, $public_field_name);
      $include_path = implode('.', array_filter($array_path, function ($item) {
        return !is_numeric($item);
      }));
      $item = array(
        '__resource__name' => $basic_info['type'],
        '__resource__id' => $basic_info['id'],
        '__relationship__field_path' => $include_path,
        '__relationship__info' => array(
          'data' => array(
            'type' => $basic_info['type'],
            'id' => $basic_info['id'],
          ),
          'links' => $basic_info['links'],
        ),
      ) + $value_item;
      $output[] = $item;
    }
    if ($cardinality == 1) {
      // Make them single items.
      $output = reset($output);
    }
    return $output;
  }

}
