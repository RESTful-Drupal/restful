<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\formatter\FormatterJsonApi.
 */

namespace Drupal\restful\Plugin\formatter;

use Drupal\restful\Exception\InternalServerErrorException;
use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Http\Request;
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
    $output = array('data' => $this->renormalize($extracted, $included));
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
   * @throws \Drupal\restful\Exception\ServerConfigurationException
   */
  protected function extractFieldValues($data, array $parents = array(), array $parent_hashes = array()) {
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
      $interpreter = $data->getInterpreter();
      if (!$id_field = $data->getIdField()) {
        throw new ServerConfigurationException('Invalid required ID field for JSON API formatter.');
      }
      $output['#fields'][$public_field_name] = $this->embedField($resource_field, $id_field->render($interpreter), $interpreter, $parents, $parent_hashes);
    }

    if ($data instanceof ResourceFieldCollectionInterface) {
      $output['#resource_name'] = $data->getResourceName();
      $output['#resource_plugin'] = $data->getResourceId();
      $resource_id = $data->getIdField()->render($data->getInterpreter());
      if (!is_array($resource_id)) {
        // In some situations when making an OPTIONS call the $resource_id
        // returns the array of discovery information instead of a real value.
        $output['#resource_id'] = (string) $resource_id;
      }
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
   * @param bool|string[] $allowed_fields
   *   The sparse fieldset information. FALSE to select all fields.
   * @param array $includes_parents
   *   An array containing the included path until the current field being
   *   processed.
   *
   * @return array
   *   The processed data.
   */
  protected function renormalize(array $output, array &$included, $allowed_fields = NULL, $includes_parents = array()) {
    static $depth = -1;
    $depth++;
    if (!isset($allowed_fields)) {
      $request = ($resource = $this->getResource()) ? $resource->getRequest() : restful()->getRequest();
      $input = $request->getParsedInput();
      // Set the field limits to false if there are no limits.
      $allowed_fields = empty($input['fields']) ? FALSE : explode(',', $input['fields']);
    }
    if (!is_array($output)) {
      // $output is a simple value.
      $depth--;
      return $output;
    }
    $result = array();
    if (ResourceFieldBase::isArrayNumeric($output)) {
      foreach ($output as $item) {
        $result[] = $this->renormalize($item, $included, $allowed_fields, $includes_parents);
      }
      $depth--;
      return $result;
    }
    if (!empty($output['#resource_name'])) {
      $result['type'] = $output['#resource_name'];
    }
    if (!empty($output['#resource_id'])) {
      $result['id'] = $output['#resource_id'];
    }
    if (!isset($output['#fields'])) {
      $depth--;
      return $this->renormalize($output, $included, $allowed_fields, $includes_parents);
    }
    foreach ($output['#fields'] as $field_name => $field_contents) {
      if ($allowed_fields !== FALSE && !in_array($field_name, $allowed_fields)) {
        continue;
      }
      if (empty($field_contents['#embedded'])) {
        $result['attributes'][$field_name] = $field_contents;
      }
      else {
        // Handle single and multiple relationships.
        $rel = array();
        $single_item = $field_contents['#cardinality'] == 1;
        $relationship_links = empty($field_contents['#relationship_links']) ? NULL : $field_contents['#relationship_links'];
        unset($field_contents['#embedded']);
        unset($field_contents['#cardinality']);
        unset($field_contents['#relationship_links']);
        foreach ($field_contents as $field_item) {
          $includes_parents[] = $field_name;
          $include_links = empty($field_item['#include_links']) ? NULL : $field_item['#include_links'];
          unset($field_contents['#include_links']);
          $field_path = $this->buildIncludePath($includes_parents);
          $field_item = $this->populateCachePlaceholder($field_item, $field_path);
          unset($field_item['#cache_placeholder']);
          $element = $field_item['#relationship_info'];
          unset($field_item['#relationship_info']);
          $include_key = $field_item['#resource_plugin'] . '--' . $field_item['#resource_id'];
          $nested_allowed_fields = $this->unprefixInputOptions($allowed_fields, $field_name);
          // If the list of the child allowed fields is empty, but the parent is
          // part of the includes, it means that the consumer meant to include
          // all the fields in the children.
          if (is_array($allowed_fields) && empty($nested_allowed_fields) && in_array($field_name, $allowed_fields)) {
            $nested_allowed_fields = FALSE;
          }
          // If we get here is because the relationship is included in the
          // sparse fieldset. That means that in this context, empty field
          // limits mean all the fields.
          $included[$field_path][$include_key] = $this->renormalize($field_item, $included, $nested_allowed_fields, $includes_parents);
          $included[$field_path][$include_key] += $include_links ? array('links' => $include_links) : array();
          $rel[] = $element;
        }
        // Only place the relationship info.
        $result['relationships'][$field_name] = array(
          'data' => $single_item ? reset($rel) : $rel,
        );
        if (!empty($relationship_links)) {
          $result['relationships'][$field_name]['links'] = $relationship_links;
        }
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
    $included_keys = array();
    foreach ($requested_includes as $requested_include) {
      if (empty($included[$requested_include])) {
        continue;
      }
      foreach ($included[$requested_include] as $include_key => $included_item) {
        if (in_array($include_key, $included_keys)) {
          continue;
        }
        $output['included'][] = $included_item;
        $included_keys[] = $include_key;
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
   * @param string $parent_id
   *   ID in the parent resource where this is being embedded.
   * @param DataInterpreterInterface $interpreter
   *   The context for the $resource_field.
   * @param array $parents
   *   Tracks the parents of the field to construct the dot notation for the
   *   field name.
   * @param string[] $parent_hashes
   *   An array that holds the name of the parent cache hashes that lead to the
   *   current data structure.
   *
   * @return array
   *   The contents for the JSON API attribute or relationship.
   */
  protected function embedField(ResourceFieldInterface $resource_field, $parent_id, DataInterpreterInterface $interpreter, array &$parents, array &$parent_hashes) {
    // If the field points to a resource that can be included, include it
    // right away.
    if (!$resource_field instanceof ResourceFieldResourceInterface) {
      return $resource_field->render($interpreter);
    }
    // Check if the resource needs to be included. If not then set 'full_view'
    // to false.
    $cardinality = $resource_field->cardinality();
    $output = array();
    $public_field_name = $resource_field->getPublicName();
    if (!$ids = $resource_field->compoundDocumentId($interpreter)) {
      return NULL;
    }
    $ids = $cardinality == 1 ? $ids = array($ids) : $ids;
    $value = array();
    $empty_value = array(
      '#fields' => array(),
      '#embedded' => TRUE,
      '#cache_placeholder' => array(
        'parents' => array_merge($parents, array($public_field_name)),
        'parent_hashes' => $parent_hashes,
      ),
    );
    $value = array_pad($value, count($ids), $empty_value);
    if ($this->needsIncluding($resource_field, $parents)) {
      $value = $resource_field->render($interpreter);
      if (
        empty($value) ||
        !static::isIterable($value)
      ) {
        return $value;
      }
      $new_parents = $parents;
      $new_parents[] = $public_field_name;
      $value = $this->extractFieldValues($value, $new_parents, $parent_hashes);
      $value = $cardinality == 1 ? array($value) : $value;
      // If some IDs were filtered out in the value while rendering due to the
      // nested filtering with a target, we should remove those from the IDs
      // in the relationship.
      $filter_invalid_ids = function ($resource_id) use ($value) {
        foreach ($value as $info) {
          if (empty($info['#resource_id'])) {
            return FALSE;
          }
          if ($info['#resource_id'] == $resource_id) {
            return TRUE;
          }
        }
        return FALSE;
      };
      $ids = array_filter($ids, $filter_invalid_ids);
    }
    // At this point we are dealing with an embed.
    $value = array_filter($value);
    $combined = $ids ? array_combine($ids, array_pad($value, count($ids), $empty_value)) : array();
    // Set the resource for the reference to get HATEOAS from them.
    $resource_plugin = $resource_field->getResourcePlugin();
    foreach ($combined as $id => $value_item) {
      $basic_info = array(
        'type' => $resource_field->getResourceMachineName(),
        'id' => (string) $id,
      );

      // We want to be able to include only the images in articles.images,
      // but not articles.related.images. That's why we need the path
      // including the parents.
      $item = array(
        '#resource_name' => $basic_info['type'],
        '#resource_plugin' => $resource_plugin->getPluginId(),
        '#resource_id' => $basic_info['id'],
        '#include_links' => array(
          'self' => $resource_plugin->versionedUrl($basic_info['id']),
        ),
        '#relationship_info' => array(
          'type' => $basic_info['type'],
          'id' => $basic_info['id'],
        ),
      ) + $value_item;
      $output[] = $item;
    }
    // If there is a resource plugin for the parent, set the related
    // links.
    $links = array();
    if ($resource = $this->getResource()) {
      $links['related'] = $resource->versionedUrl('', array(
        'absolute' => TRUE,
        'query' => array(
          'filter' => array($public_field_name => reset($ids)),
        ),
      ));
      $links['self'] = $resource_plugin->versionedUrl($parent_id . '/relationships/' . $public_field_name);
    }

    return $output + array(
      '#embedded' => TRUE,
      '#cardinality' => $cardinality,
      '#relationship_links' => $links,
    );
  }

  /**
   * Checks if a resource field needs to be embedded in the response.
   *
   * @param ResourceFieldResourceInterface $resource_field
   *   The embedded resource field.
   * @param array $parents
   *   The parents of this embedded resource.
   *
   * @return bool
   *   TRUE if the field needs including. FALSE otherwise.
   */
  protected function needsIncluding(ResourceFieldResourceInterface $resource_field, $parents) {
    $input = $this->getRequest()->getParsedInput();
    $includes = empty($input['include']) ? array() : explode(',', $input['include']);
    return in_array($this->buildIncludePath($parents, $resource_field->getPublicName()), $includes);
  }

  /**
   * Build the dot notation path for an array of parents.
   *
   * Remove numeric parents since those only indicate that the field was
   * multivalue, not a parent: articles[related][1][tags][2][name] turns into
   * 'articles.related.tags.name'.
   *
   * @param array $parents
   *   The nested parents.
   * @param string $public_field_name
   *   The field name.
   *
   * @return string
   *   The path.
   */
  protected function buildIncludePath(array $parents, $public_field_name = NULL) {
    $array_path = $parents;
    if ($public_field_name) {
      array_push($array_path, $public_field_name);
    }
    $include_path = implode('.', array_filter($array_path, function ($item) {
      return !is_numeric($item);
    }));
    return $include_path;
  }

  /**
   * Given a field item that contains a cache placeholder render and cache it.
   *
   * @param array $field_item
   *   The output to render.
   *
   * @param string $includes_path
   *   The includes path encoded in dot notation.
   *
   * @return array
   *   The rendered embedded field item.
   *
   * @throws \Drupal\restful\Exception\BadRequestException
   * @throws \Drupal\restful\Exception\InternalServerErrorException
   */
  protected function populateCachePlaceholder(array $field_item, $includes_path) {
    if (
      empty($field_item['#cache_placeholder']) ||
      empty($field_item['#resource_id']) ||
      empty($field_item['#resource_plugin'])
    ) {
      return $field_item;
    }
    $embedded_resource = restful()
      ->getResourceManager()
      ->getPluginCopy($field_item['#resource_plugin']);
    $input = $this->getRequest()->getParsedInput();
    $new_input = $input + array('include' => '', 'fields' => '');
    // If the field is not supposed to be included, then bail.
    $old_includes = array_filter(explode(',', $new_input['include']));
    if (!in_array($includes_path, $old_includes)) {
      return $field_item;
    }
    $new_input['fields'] = implode(',', $this->unprefixInputOptions(explode(',', $new_input['fields']), $includes_path));
    $new_input['include'] = implode(',', $this->unprefixInputOptions($old_includes, $includes_path));
    // Create a new request from scratch copying most of the values but the
    // $query.
    $embedded_resource->setRequest(Request::create(
      $this->getRequest()->getPath(),
      array_filter($new_input),
      $this->getRequest()->getMethod(),
      $this->getRequest()->getHeaders(),
      $this->getRequest()->isViaRouter(),
      $this->getRequest()->getCsrfToken(),
      $this->getRequest()->getCookies(),
      $this->getRequest()->getFiles(),
      $this->getRequest()->getServer(),
      $this->getRequest()->getParsedBody()
    ));
    $data = $embedded_resource->getDataProvider()
      ->view($field_item['#resource_id']);
    return array_merge(
      $field_item,
      $this->extractFieldValues($data, $field_item['#cache_placeholder']['parents'], $field_item['#cache_placeholder']['parent_hashes'])
    );
  }

}
