<?php

/**
 * @file
 * Contains \Drupal\restful\Util\String.
 */

namespace Drupal\restful\Util;

/**
 * Class StringHelper.
 *
 * @package Drupal\restful\Util
 */
class StringHelper {

  /**
   * Turns a string into camel case.
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

  /**
   * Remove the string prefix from the token value.
   *
   * @param string $prefix
   *   The prefix to remove.
   * @param string $haystack
   *   The string to remove the prefix from.
   *
   * @return string
   *   The prefixless string. NULL if the prefix is not found.
   */
  public static function removePrefix($prefix, $haystack) {
    $position = strpos($haystack, $prefix);
    if ($position === FALSE) {
      return NULL;
    }
    return substr($haystack, $position + strlen($prefix));
  }

}
