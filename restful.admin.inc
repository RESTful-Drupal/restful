<?php

/**
 * Menu callback; Admin settings form.
 */
function restful_admin_settings($form_state) {
  $form = array();

  $form['og_group_manager_full_access'] = array(
    '#type' => 'checkbox',
    '#title' => t('Group manager full permissions'),
    '#description' => t('When enabled the group manager will have all the permissions in the group.'),
    '#default_value' => variable_get('og_group_manager_full_access', TRUE),
  );

  return system_settings_form($form);
}
