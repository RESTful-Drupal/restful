<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\formatter\FormatterSingleJson.
 */

namespace Drupal\restful\Plugin\formatter;

use Drupal\restful\Plugin\resource\Field\ResourceFieldBase;

/**
 * Class FormatterSingleJson
 * @package Drupal\restful\Plugin\formatter
 *
 * @Formatter(
 *   id = "single_json",
 *   label = "Single JSON",
 *   description = "Output a single item using the JSON format."
 * )
 */
class FormatterSingleJson extends FormatterJson {

  /**
   * Content Type
   *
   * @var string
   */
  protected $contentType = 'application/drupal.single+json; charset=utf-8';

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
    $output = $this->limitFields($extracted);
    // Force returning a single item.
    $output = ResourceFieldBase::isArrayNumeric($output) ? reset($output) : $output;

    return $output ?: array();
  }

}

