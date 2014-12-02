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
    'some_header' => !empty($_SERVER['X_HTTP_SOME_HEADER']) ? $_SERVER['X_HTTP_SOME_HEADER'] : NULL,
  );
}

/**
 * @} End of "addtogroup hooks".
 */
