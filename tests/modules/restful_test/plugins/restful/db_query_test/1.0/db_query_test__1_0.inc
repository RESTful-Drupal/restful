<?php

$plugin = array(
  'label' => t('DB Query test'),
  'description' => t('Export the test table.'),
  'resource' => 'db_query_test',
  'name' => 'db_query_test__1_0',
  'class' => 'RestfulDbQueryTestTable',
  'data_provider_options' => array(
    'table_name' => 'restful_test_db_query',
    'id_column' => 'id',
    'primary' => 'id',
  ),
  'render_cache' => array(
    'render' => TRUE,
  ),
);
