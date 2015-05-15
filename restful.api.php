<?php

/**
 * @file
 * Hooks provided by the RESTful module.
 */

/**
 * @addtogroup hooks
 * @{
 */


/**
 * Allow altering the request before it is processed.
 *
 * @param \Drupal\restful\Http\RequestInterface $request
 *   The request object.
 */
function hook_restful_parse_request_alter(\Drupal\restful\Http\RequestInterface &$request) {
  // Allow implementor modules to alter the request object.
  $request->setApplicationData('csrf_token', 'token');
}

/**
 * Allow altering the request before it is processed.
 *
 * @param \Drupal\restful\Plugin\resource\ResourceInterface &$resource
 *   The resource object to alter.
 */
function hook_restful_resource_alter(\Drupal\restful\Plugin\resource\ResourceInterface &$resource) {
  // Chain a decorator with the passed in resource based on the resource
  // annotation definition.
  $plugin_definition = $resource->getPluginDefinition();
  if (!empty($plugin_definition['renderCache']) && !empty($plugin_definition['renderCache']['render'])) {
    $resource = new \Drupal\restful\Plugin\resource\CachedResource($resource);
  }
}

/**
 * @} End of "addtogroup hooks".
 */
