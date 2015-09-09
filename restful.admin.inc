<?php

/**
 * Menu callback; Admin settings form.
 */
function restful_admin_settings($form_state) {
  $form = array();

  $form['restful_default_output_formatter'] = array(
    '#type' => 'radios',
    '#title' => t('Default formatter'),
    '#description' => t('Determine the default formatter that would be used.'),
    '#options' => array(),
    '#default_value' => variable_get('restful_default_output_formatter', 'json'),
    '#required' => TRUE,
  );

  $element = &$form['restful_default_output_formatter'];
  foreach (restful_get_formatter_plugins() as $plugin_name => $plugin) {
    $element['#options'][$plugin_name] = check_plain($plugin['label']);

    // Add description for each formatter.
    if (!$plugin['description']) {
      continue;
    }

    $element[$plugin_name]['#description'] = check_plain($plugin['description']);
  }

  $params = array(
    '@api' => variable_get('restful_hook_menu_base_path', 'api'),
  );

  $form['file_upload'] = array(
    '#type' => 'fieldset',
    '#title' => t('File upload'),
  );

  $form['file_upload']['restful_file_upload'] = array(
    '#type' => 'checkbox',
    '#title' => t('File upload'),
    '#description' => t('When enabled a file upload resource will be available.'),
    '#default_value' => variable_get('restful_file_upload', FALSE),
  );

  $form['file_upload']['restful_file_upload_allow_anonymous_user'] = array(
    '#type' => 'checkbox',
    '#title' => t('Anonymous file upload'),
    '#description' => t('When enabled a file upload resource will be available also for anonymous users.'),
    '#default_value' => variable_get('restful_file_upload_allow_anonymous_user', FALSE),
    '#states' => array(
      'visible' => array(
        ':input[name=restful_file_upload]' => array('checked' => TRUE),
      ),
    ),
  );

  $form['restful_cache'] = array(
    '#type' => 'fieldset',
    '#title' => t('Cache'),
    '#description' => t('Cache options for the different layers of RESTful.'),
    '#collapsible' => FALSE,
  );

  $form['restful_cache']['restful_page_cache'] = array(
    '#type' => 'checkbox',
    '#title' => t('Page cache'),
    '#description' => t('RESTful can leverage page cache, this will boost your performace for anonymous traffic. !link to start caching responses. Status: <strong>@status</strong>. <strong>CAUTION:</strong> If your resources are using authentication providers other than cookie, you will want to turn this off. Otherwise you may get cached anonymous values for your authenticated GET requests.', array(
      '!link' => l(t('Enable page cache'), 'admin/config/development/performance'),
      '@status' => variable_get('cache', FALSE) ? t('Enabled') : t('Disabled'),
    )),
    '#disabled' => !variable_get('cache', FALSE),
    '#default_value' => variable_get('restful_page_cache', FALSE) && variable_get('cache', FALSE),
  );

  $form['restful_cache']['restful_render_cache'] = array(
    '#type' => 'checkbox',
    '#title' => t('Cache results'),
    '#description' => t('When enabled any resource that has not explicitly disabled the caching will be cached. Note that the first hit may result with slower response, although the next ones would be significantly faster. This is different from the page cache in the sense that it acts at the row level (a single entity, a single database row, ...), therefore allowing you to assemble non cached pages with the cached bits faster.'),
    '#default_value' => variable_get('restful_render_cache', FALSE),
  );

  $form['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  $form['advanced']['restful_hijack_api_pages'] = array(
    '#type' => 'checkbox',
    '#title' => t('Hijack API pages'),
    '#description' => t('When enabled all URLS under @api will be handled by RESTful module.', $params),
    '#default_value' => variable_get('restful_hijack_api_pages', TRUE),
  );

  $form['advanced']['restful_hook_menu_base_path'] = array(
    '#type' => 'textfield',
    '#title' => t('API Base path'),
    '#description' => t('Determines the base path of all resources.'),
    '#default_value' => variable_get('restful_hook_menu_base_path', 'api'),
  );

  $form['advanced']['restful_enable_user_login_resource'] = array(
    '#type' => 'checkbox',
    '#title' => t('Login resource'),
    '#description' => t('Determines if the default user login resource should be enabled.'),
    '#default_value' => variable_get('restful_enable_user_login_resource', TRUE),
  );

  $form['advanced']['restful_enable_users_resource'] = array(
    '#type' => 'checkbox',
    '#title' => t('User resource'),
    '#description' => t('Determines if the default user resource should be enabled.'),
    '#default_value' => variable_get('restful_enable_users_resource', TRUE),
  );

  $form['advanced']['restful_enable_discovery_resource'] = array(
    '#type' => 'checkbox',
    '#title' => t('Discovery resource'),
    '#description' => t('Enable discovery resource which shows all accessible resources under @api URL.', $params),
    '#default_value' => variable_get('restful_enable_discovery_resource', TRUE),
  );

  $form['advanced']['restful_global_rate_limit'] = array(
    '#type' => 'textfield',
    '#title' => t('Rate limit - hits'),
    '#description' => t('The number of allowed hits. This is global for all roles. 0 means no global rate limit should be applied.'),
    '#default_value' => variable_get('restful_global_rate_limit', 0),
    '#element_validate' => array('element_validate_integer'),
  );

  $form['advanced']['restful_global_rate_period'] = array(
    '#type' => 'textfield',
    '#title' => t('Rate limit - period'),
    '#description' => t('The period string compatible with <a href="@url">\DateInterval</a>. After this period the module will restart counting hits.', array('@url' => 'http://php.net/manual/en/dateinterval.createfromdatestring.php')),
    '#default_value' => variable_get('restful_global_rate_period', 'P1D'),
    '#element_validate' => array('restful_date_time_format_element_validate'),
  );

  return system_settings_form($form);
}
