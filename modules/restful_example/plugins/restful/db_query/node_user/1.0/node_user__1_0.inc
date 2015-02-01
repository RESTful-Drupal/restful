<?php

$plugin = array(
  'label' => t('Content and author'),
  'resource' => 'node_user',
  'name' => 'node_user__1_0',
  'data_provider_options' => array(
    'table_name' => 'node',
    'id_column' => 'nid',
  ),
  'description' => t('Expose the content along with the author name.'),
  'class' => 'RestfulExampleNodeUserResource',
);
