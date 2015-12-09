<?php

/**
 * @file
 * Contains \Drupal\restful_example\Plugin\resource\variables\DataProviderVariable.
 */

namespace Drupal\restful_example\Plugin\resource\variables;

use Drupal\restful\Exception\BadRequestException;
use Drupal\restful\Exception\InaccessibleRecordException;
use Drupal\restful\Exception\UnprocessableEntityException;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\DataInterpreter\ArrayWrapper;
use Drupal\restful\Plugin\resource\DataProvider\DataProvider;
use Drupal\restful\Plugin\resource\DataProvider\DataProviderInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;

/**
 * Class DataProviderVariable.
 *
 * @package Drupal\restful_example\Plugin\resource\variables
 */
class DataProviderVariable extends DataProvider implements DataProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(RequestInterface $request, ResourceFieldCollectionInterface $field_definitions, $account, $plugin_id, $resource_path = NULL, array $options = array(), $langcode = NULL) {
    parent::__construct($request, $field_definitions, $account, $plugin_id, $resource_path, $options, $langcode);
    if (empty($this->options['urlParams'])) {
      $this->options['urlParams'] = array(
        'filter' => TRUE,
        'sort' => TRUE,
        'fields' => TRUE,
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->getIndexIds());
  }

  /**
   * {@inheritdoc}
   */
  public function create($object) {
    // Overly simplified update method. Search for the name and value fields,
    // and set the variable.
    $name_key = $this->searchPublicFieldByProperty('name');
    $value_key = $this->searchPublicFieldByProperty('value');
    if (empty($object[$name_key]) || empty($object[$value_key])) {
      throw new BadRequestException('You need to provide the variable name and value.');
    }
    $identifier = $object[$name_key];
    if (!empty($GLOBALS['conf'][$identifier])) {
      throw new UnprocessableEntityException('The selected variable already exists.');
    }
    variable_set($identifier, $object[$value_key]);
    return array($this->view($identifier));
  }

  /**
   * {@inheritdoc}
   */
  public function view($identifier) {
    $resource_field_collection = $this->initResourceFieldCollection($identifier);

    $input = $this->getRequest()->getParsedInput();
    $limit_fields = !empty($input['fields']) ? explode(',', $input['fields']) : array();

    foreach ($this->fieldDefinitions as $resource_field_name => $resource_field) {
      /* @var \Drupal\restful\Plugin\resource\Field\ResourceFieldInterface $resource_field */

      if ($limit_fields && !in_array($resource_field_name, $limit_fields)) {
        // Limit fields doesn't include this property.
        continue;
      }

      if (!$this->methodAccess($resource_field) || !$resource_field->access('view', $resource_field_collection->getInterpreter())) {
        // The field does not apply to the current method or has denied
        // access.
        continue;
      }

      $resource_field_collection->set($resource_field->id(), $resource_field);
    }
    return $resource_field_collection;
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $identifiers) {
    $return = array();
    foreach ($identifiers as $identifier) {
      try {
        $row = $this->view($identifier);
      }
      catch (InaccessibleRecordException $e) {
        $row = NULL;
      }
      $return[] = $row;
    }

    return array_values(array_filter($return));
  }

  /**
   * {@inheritdoc}
   */
  public function update($identifier, $object, $replace = FALSE) {
    // Overly simplified update method. Search for the name and value fields,
    // and set the variable.
    $name_key = $this->searchPublicFieldByProperty('name');
    $value_key = $this->searchPublicFieldByProperty('value');
    if (empty($object[$value_key])) {
      if (!$replace) {
        return array($this->view($identifier));
      }
      $object[$value_key] = NULL;
    }
    if (!empty($object[$name_key]) && $object[$name_key] != $identifier) {
      // If the variable name is changed, then remove the old one.
      $this->remove($identifier);
      $identifier = $object[$name_key];
    }
    variable_set($identifier, $object[$value_key]);
    return array($this->view($identifier));
  }

  /**
   * {@inheritdoc}
   */
  public function remove($identifier) {
    variable_del($identifier);
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexIds() {
    $output = array();
    foreach ($GLOBALS['conf'] as $key => $value) {
      $output[] = array('name' => $key, 'value' => $value);
    }
    // Apply filters.
    $output = $this->applyFilters($output);
    $output = $this->applySort($output);
    return array_map(function ($item) { return $item['name']; }, $output);
  }

  /**
   * Removes plugins from the list based on the request options.
   *
   * @param \Drupal\restful\Plugin\resource\ResourceInterface[] $variables
   *   The array of resource plugins keyed by instance ID.
   *
   * @return \Drupal\restful\Plugin\resource\ResourceInterface[]
   *   The same array minus the filtered plugins.
   *
   * @throws \Drupal\restful\Exception\BadRequestException
   * @throws \Drupal\restful\Exception\ServiceUnavailableException
   */
  protected function applyFilters(array $variables) {
    $filters = $this->parseRequestForListFilter();

    // Apply the filter to the list of plugins.
    foreach ($variables as $delta => $variable) {
      $variable_name = $variable['name'];
      // A filter on a result needs the ResourceFieldCollection representing the
      // result to return.
      $interpreter = $this->initDataInterpreter($variable_name);
      $this->fieldDefinitions->setInterpreter($interpreter);
      foreach ($filters as $filter) {
        if (!$this->fieldDefinitions->evalFilter($filter)) {
          unset($variables[$delta]);
        }
      }
    }
    $this->fieldDefinitions->setInterpreter(NULL);
    return $variables;
  }

  /**
   * Sorts plugins on the list based on the request options.
   *
   * @param \Drupal\restful\Plugin\resource\ResourceInterface[] $variables
   *   The array of resource plugins keyed by instance ID.
   *
   * @return \Drupal\restful\Plugin\resource\ResourceInterface[]
   *   The sorted array.
   *
   * @throws \Drupal\restful\Exception\BadRequestException
   * @throws \Drupal\restful\Exception\ServiceUnavailableException
   */
  protected function applySort(array $variables) {
    if ($sorts = $this->parseRequestForListSort()) {
      uasort($variables, function ($variable1, $variable2) use ($sorts) {
        $interpreter1 = $this->initDataInterpreter($variable1['name']);
        $interpreter2 = $this->initDataInterpreter($variable2['name']);
        foreach ($sorts as $key => $order) {
          $property = $this->fieldDefinitions->get($key)->getProperty();
          $value1 = $interpreter1->getWrapper()->get($property);
          $value2 = $interpreter2->getWrapper()->get($property);
          if ($value1 == $value2) {
            continue;
          }

          return ($order == 'DESC' ? -1 : 1) * strcmp($value1, $value2);
        }

        return 0;
      });
    }
    return $variables;
  }

  /**
   * {@inheritdoc}
   */
  protected function initDataInterpreter($identifier) {
    return new DataInterpreterVariable($this->getAccount(), new ArrayWrapper(array(
      'name' => $identifier,
      'value' => variable_get($identifier),
    )));
  }

  /**
   * Finds the public field name that has the provided property.
   *
   * @param string $property
   *   The property to find.
   *
   * @return string
   *   The name of the public name.
   */
  protected function searchPublicFieldByProperty($property) {
    foreach ($this->fieldDefinitions as $public_name => $resource_field) {
      if ($resource_field->getProperty() == $property) {
        return $public_name;
      }
    }
    return NULL;
  }

}
