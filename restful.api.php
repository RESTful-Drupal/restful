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
 * Perform cleanup tasks.
 *
 * This hook is run at the end of most regular page requests. It is often
 * used for page logging and specialized cleanup. This hook MUST NOT print
 * anything because by the time it runs the response is already sent to
 * the browser.
 *
 * Only use this hook if your code must run even for cached page views.
 * If you have code which must run once on all non-cached pages, use
 * hook_init() instead. That is the usual case. If you implement this hook
 * and see an error like 'Call to undefined function', it is likely that
 * you are depending on the presence of a module which has not been loaded yet.
 * It is not loaded because Drupal is still in bootstrap mode.
 *
 * @see hook_exit().
 */
function hook_restful_exit() {
  db_update('counter')
    ->expression('hits', 'hits + 1')
    ->condition('type', 1)
    ->execute();
}

/**
 * @} End of "addtogroup hooks".
 */
