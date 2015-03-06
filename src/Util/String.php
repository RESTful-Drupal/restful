<?php

/**
 * @file
 * Contains \Drupal\restful\Util\String.
 */

namespace Drupal\restful\Util;

class String {

  /**
   * Turns a string into camel case. From search_api_index to SearchApiIndex.
   *
   * @param string $input
   *   The input string.
   *
   * @return string
   *   The camelized string.
   */
  public static function camelize($input) {
    $input = preg_replace('/[-_]/', ' ', $input);
    $input = ucwords($input);
    $parts = explode(' ', $input);
    return implode('', $parts);
  }

}
