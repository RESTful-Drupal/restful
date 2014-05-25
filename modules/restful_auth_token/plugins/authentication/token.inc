<?php

$plugin = array(
  'label' => t('Token based authentication'),
  'description' => t('Authenticate requests based on the token sent in the request.'),
  'name' => 'token',
  'class' => 'RestfulAuthenticationToken',
);
