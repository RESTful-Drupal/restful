<?php

/**
 * @file
 * Contains \RestfulManager.
 */

class RestfulManager {

  /**
   * Add defaults values to the restful related plugins.
   *
   * Properties for the "restful" plugin type:
   * - description: The description of the resource. Defaults to empty string.
   * - discoverable: Determines if the resource should be discoverable by the
   *   "discovery" resource. Defaults to TRUE.
   * - data_provider_options: An array of options specific to the data provider.
   *   For example the DB query data provider requires the table name in order to
   *   know which table to act upon. Defaults to an empty array.
   * - major_version: The major version of the resource. This will change the URL
   *   of the resource endpoint. For example setting major version to 2 for the
   *   "articles" resource will result with "api/v2/articles" as the URL. Defaults
   *   to 1.
   * - minor_version: The minor version of the resource. Setting the minor version
   *   via CURL is done by setting HTTP_X_RESTFUL_MINOR_VERSION in the HTTP headers.
   *   Defaults to 0.
   * - options: Array of options needed for the plugin. See
   *   "per_role_content__1_0.inc" in RESTful example module. Defaults to empty
   *   array.
   * - entity type: The entity type of the resource. Defaults to FALSE, which
   *   indicates the resource isn't connected to any entity type.
   * - bundle: The name of a single bundle the resource is connected to. Defaults
   *   to FALSE.
   * - authentication_types: TRUE or Array with name of authentication providers
   *   that should "protect" the resource, and ensure only authenticated users can
   *   use it. If set to TRUE, then all the existing authentication providers
   *   would be used until the user is authenticated. If user was not
   *   authenticated with any of the authentication providers, an
   *   \RestfulUnauthorizedException exception would be thrown.
   *   Defaults to empty array, which means no authentication is done by default.
   * - authentication_optional: If "authentication_types" and TRUE this determines
   *   if the resource may be accessed by an anonymous user when no provider was
   *   able to authenticate the user. Otherwise a \RestfulUnauthorizedException
   *   exception would be thrown.
   * - hook_menu: Determines if RESTful module should declare the resource in its
   *   pwn hook_menu(). If FALSE, it is up to the implementing module to declare
   *   it. Defaults to TRUE.
   * - render_cache: Stores the cache settings. An associative array with:
   *   - render: Set it to FALSE to disable the render cache completely
   *     Defaults to FALSE.
   *   - class: The cache class for this resource. Defaults to NULL, which
   *     will probably end up resolving to 'DrupalDatabaseCache'.
   *   - bin: The name of the bin. It is the developer's responsibility to
   *     create this bin in the cache backend if it does not exist. Defaults to
   *     'cache_restful'.
   *   - expire: TTL for the cache records. See DrupalCacheInterface::set()
   *     for the allowed values. Defaults to CACHE_PERMANENT.
   *   - simple_invalidate: Set it to false to prevent the RESTful module to
   *     invalidate any cache it may have been generated. The developer will be
   *     responsible to invalidate caches in this scenario. Defaults to TRUE.
   *   - granularity: DRUPAL_CACHE_PER_USER or DRUPAL_CACHE_PER_ROLE.
   * - rate_limit: The configuration array for the rate limits. There is a special
   *   limit category called 'global' that will not be limited to resource but
   *   will aggregate all request hits across all resources. To enable the global
   *   limit set the variable 'restful_global_rate_limit' to the desired limit and
   *   'restful_global_rate_period' to the wanted period.
   *   - period: A \DateInterval object representing the period on which the rate
   *     limitations apply.
   *   - event: The name of the event to limit as declared in the rate_limit
   *     plugin.
   *   - limits: An associative array with the number of allowed requests in the
   *     selected period for every role.
   *     array(
   *       'request' => array(
   *         'event' => 'request',
   *         'period' => new \DateInterval('P1D'),
   *         'limits' => array(
   *           'authenticated user' => 100,
   *           'anonymous user' => 10,
   *           'administrator' => \RestfulRateLimitManager::UNLIMITED_RATE_LIMIT,
   *         ),
   *       ),
   *     ),
   * - autocomplete: Stores the autocomplete settings. An associative array with:
   *   - enable: Determines if the autocomplete functionality should be used.
   *     Defaults to TRUE.
   *   - range: Determines how many matches should return on every query. Defaults
   *     to 10.
   *   - operator: Determines the operator used to match the given string. Values
   *     can be 'STARTS_WITH' or 'CONTAINS'. Defaults to 'CONTAINS'.
   * - formatter: The name of the formatter plugin. It defaults to the contents of
   *   the variable 'restful_default_output_formatter'. If the variable is empty
   *   it defaults to 'hal_json'.
   * Properties for the "authentication" plugin type:
   * - description: The description of the authentication provider. Defaults to
   *   empty string.
   * - settings: Array with the settings needed for the plugin. Defaults to empty
   *   array.
   * - allow_origin: A string containing the allowed origin as in the
   *   Access-Control-Allow-Origin header. If a request has a referer header and
   *   it does not match the allow_origin value the access will be denied.
   *   Typically used to avoid CORS problems. This will also populate the
   *   Access-Control-Allow-Origin header in the response.
   * - url_params: Associative array to configure if the "sort", "filter" and
   *   "range" url parameters should be allowed. Defaults to TRUE in all of
   *   them.
   * - view_mode: Associative array that contains two keys:
   *   - name: The name of the view mode to read from to add the public fields.
   *   - field_map: An associative array that pairs the name of the Drupal field
   *     with the name of the exposed (public) field.
   */
  public static function pluginProcessRestful($plugin, $info) {
    $plugin += array(
      'major_version' => 1,
      'minor_version' => 0,
      'options' => array(),
      'entity_type' => FALSE,
      'bundle' => FALSE,
      'authentication_types' => array(),
      'authentication_optional' => FALSE,
      'hook_menu' => TRUE,
      'render_cache' => array(),
      'autocomplete' => array(),
      'allow_origin' => NULL,
      'discoverable' => TRUE,
      'data_provider_options' => array(),
      'menu_item' => FALSE,
      'url_params' => array(),
    );

    $plugin['render_cache'] += array(
      'render' => variable_get('restful_render_cache', FALSE),
      'class' => NULL,
      'bin' => 'cache_restful',
      'expire' => CACHE_PERMANENT,
      'simple_invalidate' => TRUE,
      'granularity' => DRUPAL_CACHE_PER_USER,
    );

    $plugin['autocomplete'] += array(
      'enable' => TRUE,
      'operator' => 'CONTAINS',
      'range' => 10,
    );

    $plugin['url_params'] += array(
      'sort' => TRUE,
      'range' => TRUE,
      'filter' => TRUE,
    );

    if (!empty($plugin['rate_limit'])) {
      foreach ($plugin['rate_limit'] as $event_name => $rate_limit_info) {
        $plugin['rate_limit'][$event_name]['limits'] += array('anonymous user' => 0);
      }
    }

    // Set the global limit. This limit is always attached, but it can be
    // disabled by unsetting the variable 'restful_global_rate_limit'. The
    // global limit can be overridden in the restful plugin definition.
    if (empty($plugin['rate_limit']['global'])) {
      $plugin['rate_limit'] = empty($plugin['rate_limit']) ? array() : $plugin['rate_limit'];
      // Setup the global limits to the variable value.
      $plugin['rate_limit']['global'] = array(
        'event' => 'global',
        'period' => new \DateInterval(variable_get('restful_global_rate_period', 'P1D')),
        'limits' => array(),
      );
    }

    return $plugin;
  }

  /**
   * Add defaults values to the restful related plugins.
   *
   * Properties for the "authentication" plugin type:
   * - description: The description of the event. Defaults to an empty string.
   * - name: The name of the event.
   * - class: Name of the class implementing RestfulRateLimitInterface.
   */
  public static function pluginProcessAuthentication($plugin, $info) {
    $plugin += array(
      'settings' => array(),
    );

    return $plugin;
  }

  /**
   * Add defaults values to the restful related plugins.
   *
   * Properties for the "rate_limit" plugin type:
   * - description: The description of the event. Defaults to an empty string.
   * - name: The name of the event.
   * - class: Name of the class implementing RestfulRateLimitInterface.
   */
  public static function pluginProcessRateLimit($plugin, $info) {
    // Nothing to be done.
    return $plugin;
  }

  /**
   * Add defaults values to the restful related plugins.
   *
   * Properties for the "formatter" plugin type:
   * - description: The description of the formatter. Defaults to an empty string.
   * - name: The name of the formatter.
   * - class: Name of the class implementing RestfulFormatterInterface.
   */
  public static function pluginProcessFormatter($plugin, $info) {
    // Nothing to be done.
    return $plugin;
  }

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
        // Bypass case where Content-Type HTTP_ACCEPT looks like '*/*,image/webp'.
        if ($accepted_content_type == '*/*') {
          continue;
        }
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
    $formatter_name = variable_get('restful_default_output_formatter', 'json');
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
      if (method_exists($handler, 'cacheInvalidate')) {
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
        $version_cid = 'v' . $version['major'] . '.' . $version['minor'] . '::' . $handler->getResourceName() . '::uu' . $uid;
        $handler->cacheInvalidate($version_cid . '::' . $cid);
      }
    }
  }

  /**
   * Get the value from an HTTP header.
   *
   * As Apache may be strict with variables with underscore, we check also
   * the headers directly from Apache, if they are not present in the $_SEVER
   *
   * @param string $key
   *   The key to use.
   * @param string $default_value
   *   The default value to return if no value exists. Defaults to NULL.
   *
   * @return string
   *   The value in the HTTP header if exists, other the value of the given
   *   "default value".
   */
  public static function getRequestHttpHeader($key, $default_value = NULL) {
    $capital_name = 'HTTP_' . strtoupper(str_replace('-', '_', $key));

    $value = !empty($_SERVER[$capital_name]) ? $_SERVER[$capital_name] : $default_value;

    if (!$value && function_exists('apache_request_headers')) {
      $headers = apache_request_headers();
      $value = !empty($headers[$key]) ? $headers[$key] : $default_value;
    }

    return $value;
  }

  /**
   * Helper function to echo static strings.
   *
   * @param mixed $value
   *   The resource value.
   * @param string $message
   *   The string to relay.
   *
   * @return string
   *   Returns $message
   */
  public static function echoMessage($value, $message) {
    return $message;
  }

  /**
   * Performs end-of-request tasks.
   *
   * This function sets the page cache if appropriate, and allows modules to
   * react to the closing of the page by calling hook_exit().
   *
   * This is just a wrapper around drupal_page_footer() so extending classes can
   * override this method if necessary.
   *
   * @see drupal_page_footer().
   */
  public static function pageFooter() {
    drupal_page_footer();
  }

}
