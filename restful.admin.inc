<?php

/**
 * Menu callback; Admin settings form.
 */
function restful_admin_settings($form_state) {
  $form = array();

  $options = array();
  foreach (restful_get_formatter_plugins() as $plugin_name => $plugin) {
    $options[$plugin_name] = check_plain($plugin['label']);
  }

  $form['restful_default_output_formatter'] = array(
    '#type' => 'radios',
    '#title' => t('Default formatter'),
    '#description' => t('Determine the default formatter that would be used.'),
    '#options' => $options,
    '#default_value' => variable_get('restful_default_output_formatter', 'hal_json'),
    '#required' => TRUE,
  );

  // Add description for each formatter.
  foreach (restful_get_formatter_plugins() as $plugin_name => $plugin) {
    if (!$plugin['description']) {
      continue;
    }

    $form['restful_default_output_formatter'][$plugin_name]['#description'] = check_plain($plugin['description']);
  }

  $params = array(
    '@api' => variable_get('restful_hook_menu_base_path', 'api'),
  );

  $form['restful_enable_discovery_resource'] = array(
    '#type' => 'checkbox',
    '#title' => t('Discovery resource'),
    '#description' => t('Enable discovery resource which shows all accessible resources under @api URL.', $params),
    '#default_value' => variable_get('restful_enable_discovery_resource', TRUE),
  );

  $form['restful_file_upload'] = array(
    '#type' => 'checkbox',
    '#title' => t('File upload'),
    '#description' => t('When enabled a file upload resource will be available.'),
    '#default_value' => variable_get('restful_file_upload', FALSE),
  );

  $form['restful_file_upload_allow_anonymous_user'] = array(
    '#type' => 'checkbox',
    '#title' => t('Anonymous file upload'),
    '#description' => t('When enabled a file upload resource will be available also for anonymous users.'),
    '#default_value' => variable_get('restful_file_upload_allow_anonymous_user', FALSE),
  );

  $form['restful_hijack_api_pages'] = array(
    '#type' => 'checkbox',
    '#title' => t('Hijack API pages'),
    '#description' => t('When enabled all URLS under @api will be handled by RESTful module.', $params),
    '#default_value' => variable_get('restful_hijack_api_pages', TRUE),
  );

  $form['restful_hijack_api_pages'] = array(
    '#type' => 'textfield',
    '#title' => t('API Base path'),
    '#description' => t('Determines the base path of all resources.'),
    '#default_value' => variable_get('restful_hook_menu_base_path', 'api'),
  );

  $form['restful_user_login_menu_item'] = array(
    '#type' => 'checkbox',
    '#title' => t('Login resource'),
    '#description' => t('Determines if the default user login resource should be enabled allowing anonymous users to login via a REST call.'),
    '#default_value' => variable_get('restful_user_login_menu_item', TRUE),
  );

  $form['restful_global_rate_limit'] = array(
    '#type' => 'textfield',
    '#title' => t('Rate limit'),
    '#description' => t('The number of allowed hits. This is global for all roles. 0 means no globa rate limit should be applied.'),
    '#default_value' => variable_get('restful_global_rate_limit', 0),
    '#element_validate' => array('element_validate_integer_positive'),
  );

  $form['restful_global_rate_period'] = array(
    '#type' => 'textfield',
    '#title' => t('Rate limit'),
    '#description' => t('The period string compatible with <a href="@url">\DateInterval</a>.', array('@url' => 'http://php.net/manual/en/class.dateinterval.php')),
    '#default_value' => variable_get('restful_global_rate_period', 'P1D'),
  );

  $form['restful_enable_users_resource'] = array(
    '#type' => 'checkbox',
    '#title' => t('User resource'),
    '#description' => t('Determines if the default user resource should be enabled.'),
    '#default_value' => variable_get('restful_enable_users_resource', TRUE),
  );

  return system_settings_form($form);
}
