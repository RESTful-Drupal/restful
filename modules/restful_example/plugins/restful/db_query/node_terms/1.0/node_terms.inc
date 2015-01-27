<?php

$plugin = array(
  'label' => t('Node and term entries'),
  'resource' => 'node_terms',
  'name' => 'node_terms',
  'data_provider_options' => array(
    'table_name' => 'node',
    'id_column' => 'nid',
  ),
  'description' => t('Expose the ID, label and terms of a node.'),
  'class' => 'RestfulExampleNodeTermsResource',
);
