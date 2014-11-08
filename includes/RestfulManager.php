<?php

/**
 * @file
 * Contains \RestfulManager.
 */

class RestfulManager {

  /**
   * Helper function to get the default output format from the current request.
   *
   * @param \RestfulBase $restful_handler
   *   The restful handler for the formatter.
   *
   * @return \RestfulFormatterBase
   *   The formatter plugin to use.
   */
  public static function outputFormat(\RestfulBase $restful_handler = NULL) {
    $restful_handler = $restful_handler ? $restful_handler : restful_get_restful_handler_for_path();
    if ($restful_handler && $formatter_name = $restful_handler->getPluginKey('formatter')) {
      return restful_get_formatter_handler($formatter_name, $restful_handler);
    }
    // Sometimes we will get a default Accept: */* in that case we want to return
    // the default content type and not just any.
    if (!empty($GLOBALS['_SERVER']['HTTP_ACCEPT']) && $GLOBALS['_SERVER']['HTTP_ACCEPT'] != '*/*') {
      foreach (explode(',', $GLOBALS['_SERVER']['HTTP_ACCEPT']) as $accepted_content_type) {
        // Loop through all the formatters and find the first one that matches the
        // Content-Type header.
        foreach (restful_get_formatter_plugins() as $formatter_info) {
          $formatter = restful_get_formatter_handler($formatter_info['name'], $restful_handler);
          if (static::matchContentType($formatter->getContentTypeHeader(), $accepted_content_type)) {
            return $formatter;
          }
        }
      }
    }
    $formatter_name = variable_get('restful_default_output_formatter', 'hal_json');
    return restful_get_formatter_handler($formatter_name, $restful_handler);
  }

  /**
   * Matches a string with path style wildcards.
   *
   * @param string $content_type
   *   The string to check.
   * @param string $pattern
   *   The pattern to check against.
   *
   * @return bool
   *   TRUE if the input matches the pattern.
   *
   * @see drupal_match_path().
   */
  protected static function matchContentType($content_type, $pattern) {
    $regexps = &drupal_static(__FUNCTION__);

    if (!isset($regexps[$pattern])) {
      // Convert path settings to a regular expression.
      $to_replace = array(
        '/\\\\\*/', // asterisks
      );
      $replacements = array(
        '.*',
      );
      $patterns_quoted = preg_quote($pattern, '/');

      // This will turn 'application/*' into '/^(application\/.*)(;.*)$/' allowing
      // us to match 'application/json; charset: utf8'
      $regexps[$pattern] = '/^(' . preg_replace($to_replace, $replacements, $patterns_quoted) . ')(;.*)?$/i';
    }
    return (bool) preg_match($regexps[$pattern], $content_type);
  }

  /**
   * Delete cached entities from all the cache bins associated to restful
   * resources.
   *
   * @param string $cid
   *   The wildcard cache id to invalidate.
   */
  public static function invalidateEntityCache($cid) {
    $plugins = restful_get_restful_plugins();
    foreach ($plugins as $plugin) {
      $handler = restful_get_restful_handler($plugin['resource'], $plugin['major_version'], $plugin['minor_version']);
      $reflector = new \ReflectionClass($handler);
      if ($reflector->hasMethod('cacheInvalidate')) {
        $version = $handler->getVersion();
        // Get the uid for the invalidation.
        try {
          $uid = $handler->getAccount(FALSE)->uid;
        }
        catch (\RestfulUnauthorizedException $e) {
          // If no user could be found using the handler default to the logged in
          // user.
          $uid = $GLOBALS['user']->uid;
        }
        $version_cid = 'v' . $version['major'] . '.' . $version['minor'] . '::uu' . $uid;
        $handler->cacheInvalidate($version_cid . '::' . $cid);
      }
    }
  }

}
