<?php

/**
 * @file
 * Contains \Drupal\restful\Formatter\FormatterManager
 */

namespace Drupal\restful\Formatter;

use Drupal\restful\Exception\ServiceUnavailableException;
use Drupal\restful\Http\HttpHeader;
use Drupal\restful\Plugin\formatter\FormatterInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Plugin\FormatterPluginManager;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\restful\Resource\ResourceManager;

/**
 * Class FormatterManager.
 *
 * @package Drupal\restful\Formatter
 */
class FormatterManager implements FormatterManagerInterface {

  /**
   * The rate limit plugins.
   *
   * @var FormatterPluginCollection
   */
  protected $plugins;

  /**
   * The resource.
   *
   * @todo: Remove this coupling.
   *
   * @var ResourceInterface
   */
  protected $resource;

  /**
   * Constructs FormatterManager.
   *
   * @param ResourceInterface $resource
   *   TODO: Remove this coupling.
   *   The resource.
   */
  public function __construct($resource = NULL) {
    $this->resource = $resource;
    $manager = FormatterPluginManager::create();
    $options = array();
    foreach ($manager->getDefinitions() as $plugin_id => $plugin_definition) {
      // Since there is only one instance per plugin_id use the plugin_id as
      // instance_id.
      $options[$plugin_id] = $plugin_definition;
    }
    $this->plugins = new FormatterPluginCollection($manager, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function setResource($resource) {
    $this->resource = $resource;
  }

  /**
   * {@inheritdoc}
   */
  public function format(array $data, $formatter_name = NULL) {
    return $this->processData('format', $data, $formatter_name);
  }

  /**
   * {@inheritdoc}
   */
  public function render(array $data, $formatter_name = NULL) {
    return $this->processData('render', $data, $formatter_name);
  }

  /**
   * {@inheritdoc}
   */
  public function negotiateFormatter($accept, $formatter_name = NULL) {
    $message = 'Formatter plugin class was not found.';
    $default_formatter_name = variable_get('restful_default_output_formatter', 'json');
    try {
      if ($formatter_name) {
        return $this->getPluginByName($formatter_name);
      }
      // Sometimes we will get a default Accept: */* in that case we want to
      // return the default content type and not just any.
      if (empty($accept) || $accept == '*/*') {
        // Return the default formatter.
        return $this->getPluginByName($default_formatter_name);
      }
      foreach (explode(',', $accept) as $accepted_content_type) {
        // Loop through all the formatters and find the first one that matches
        // the Content-Type header.
        $accepted_content_type = trim($accepted_content_type);
        if (strpos($accepted_content_type, '*/*') === 0) {
          return $this->getPluginByName($default_formatter_name);
        }
        foreach ($this->plugins as $formatter_name => $formatter) {
          /* @var FormatterInterface $formatter */
          if (static::matchContentType($formatter->getContentTypeHeader(), $accepted_content_type)) {
            $formatter->setConfiguration(array(
              'resource' => $this->resource,
            ));
            return $formatter;
          }
        }
      }
    }
    catch (PluginNotFoundException $e) {
      // Catch the exception and throw one of our own.
      $message = $e->getMessage();
    }
    throw new ServiceUnavailableException($message);
  }

  /**
   * Helper function to get a formatter and apply a method.
   *
   * @param string $method
   *   A valid method to call on the FormatterInterface object.
   * @param array $data
   *   The array of data to process.
   * @param string $formatter_name
   *   The name of the formatter for the current resource. Leave it NULL to use
   *   the Accept headers.
   *
   * @return string
   *   The processed output.
   */
  protected function processData($method, array $data, $formatter_name = NULL) {
    if ($resource = $this->resource) {
      $request = $resource->getRequest();
    }
    else {
      $request = restful()->getRequest();
    }
    $accept = $request
      ->getHeaders()
      ->get('accept')
      ->getValueString();
    $formatter = $this->negotiateFormatter($accept, $formatter_name);
    $output = ResourceManager::executeCallback(array($formatter, $method), array($data, $formatter_name));

    // The content type header is modified after the massaging if there is
    // an error code. Therefore we need to set the content type header after
    // formatting the output.
    $content_type = $formatter->getContentTypeHeader();
    $response_headers = restful()
      ->getResponse()
      ->getHeaders();
    $response_headers->add(HttpHeader::create('Content-Type', $content_type));

    return $output;
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
    $regexps = &drupal_static(__METHOD__);

    if (!isset($regexps[$pattern])) {
      // Convert path settings to a regular expression.
      $to_replace = array(
        '/\\\\\*/', // asterisks
      );
      $replacements = array(
        '.*',
      );
      $patterns_quoted = preg_quote($pattern, '/');

      // This will turn 'application/*' into '/^(application\/.*)(;.*)$/'
      // allowing us to match 'application/json; charset: utf8'
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

  /**
   * Gets a plugin by name initializing the resource.
   *
   * @param string $name
   *   The formatter name.
   *
   * @return FormatterInterface
   *   The plugin.
   */
  protected function getPluginByName($name) {
    /* @var FormatterInterface $formatter */
    $formatter = $this->plugins->get($name);
    if ($this->resource) {
      $formatter->setResource($this->resource);
    }
    return $formatter;
  }

}
