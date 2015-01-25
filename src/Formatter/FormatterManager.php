<?php

/**
 * @file
 * Contains \Drupal\restful\Formatter\FormatterManager
 */

namespace Drupal\restful\Formatter;

use Drupal\restful\Plugin\formatter\FormatterInterface;
use Drupal\restful\Plugin\FormatterPluginManager;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

class FormatterManager implements FormatterManagerInterface {

  /**
   * The rate limit plugins.
   *
   * @var FormatterPluginCollection
   */
  protected $plugins;

  /**
   * Constructs FormatterManager.
   *
   * @param \RestfulBase $resource
   *   TODO: Remove this coupling.
   *   The resource.
   */
  public function __construct($resource = NULL) {
    $this->resource = $resource;
    $manager = FormatterPluginManager::create();
    $options = array();
    foreach ($manager->getDefinitions() as $plugin_id => $plugin_definition) {
      // Since there is only one instance per plugin_id use the plugin_id as
      //instance_id.
      // Add the restful resource to the plugin options.
      // TODO: Remove this coupling.
      $options[$plugin_id] = $plugin_definition + array(
        'resource' => $resource,
      );
    }
    $this->plugins = new FormatterPluginCollection($manager, $options);
  }


  /**
   * {@inheritdoc}
   */
  public function format(array $data, $formatter_name = NULL) {
    return $this->negotiateFormatter($GLOBALS['_SERVER']['HTTP_ACCEPT'], $formatter_name)->format($data);
  }

  /**
   * {@inheritdoc}
   */
  public function negotiateFormatter($accept, $formatter_name = NULL) {
    if ($formatter_name) {
      return $this->plugins->get($formatter_name);
    }
    // Sometimes we will get a default Accept: */* in that case we want to return
    // the default content type and not just any.
    if (!empty($accept) && $accept != '*/*') {
      try {
        foreach (explode(',', $accept) as $accepted_content_type) {
          // Loop through all the formatters and find the first one that matches the
          // Content-Type header.
          foreach ($this->plugins as $formatter_name => $formatter) {
            /** @var FormatterInterface $formatter */
            if (static::matchContentType($formatter->getContentTypeHeader(), $accepted_content_type)) {
              return $formatter;
            }
          }
        }
      }
      catch (PluginNotFoundException $e) {
        // Catch the exception and throw one of our own.
        throw new \RestfulServerConfigurationException($e->getMessage());
      }
    }
    // Return the default formatter.
    return $this->plugins->get(variable_get('restful_default_output_formatter', 'json'));
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
   * {@inheritdocs}
   */
  public function getPlugins() {
    return $this->plugins;
  }

  /**
   * {@inheritdocs}
   */
  public function getPlugin($instance_id) {
    return $this->plugins->get($instance_id);
  }

}
