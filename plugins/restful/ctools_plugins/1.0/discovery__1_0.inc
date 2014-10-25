<?php

if (variable_get('restful_enable_discovery_resource', TRUE)) {
  $plugin = array(
    'label' => t('Discovery'),
    'description' => t('Discovery plugin'),
    'resource' => 'discovery',
    'class' => 'RestfulCToolsPluginsDiscovery',
    'data_provider_options' => array(
      'module' => 'restful',
      'type' => 'restful',
    ),
    'authentication_types' => TRUE,
    'authentication_optional' => TRUE,
    // We don't want the discovery resource itself to be discoverable.
    'discoverable' => FALSE,
    'menu_item' => variable_get('restful_hook_menu_base_path', 'api'),
  );
}
