<?php

/**
 * Menu callback; Admin settings form.
 */
function restful_admin_settings($form_state) {
  $form = array();

  $options = array();
  foreach (restful_get_formatter_plugins() as $plugin_name => $plugin) {
    $options[$plugin_name] = $plugin['label'];
  }

  $form['restful_default_output_formatter'] = array(
    '#type' => 'select',
    '#title' => t('Default formatter'),
    '#description' => t('Determine the default formatter that would be used.'),
    '#options' => $options,
    '#default_value' => variable_get('restful_default_output_formatter', 'hal_json'),
    '#required' => TRUE,
  );

  return system_settings_form($form);
}
