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

  return system_settings_form($form);
}
