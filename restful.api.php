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
 * @param $request
 *   The request array.
 */
function hook_restful_parse_request_alter(&$request) {
  $request['__application'] += array(
    'some_header' => \RestfulManager::getRequestHttpHeader('X-Some-Header'),
  );
}

/**
 * Allow other module alter the public fields info.
 *
 * The alter hook will be invoked only when the plugin defined it.
 *
 * @param array $public_fields
 *   The restful plugin base.
 * @param array $plugin_info
 *   The plugin info.
 */
function hook_restful_public_fields_alter(&$public_fields, $plugin_info) {

}

/**
 * @} End of "addtogroup hooks".
 */
