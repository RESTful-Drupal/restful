<?php

/**
 * @file
 * Page callbacks for restful_token_auth module.
 */

/**
 * Generates and shows an API key.
 *
 * @param \stdClass $account
 *   A user account object.
 *
 * @return array
 *   A render array.
 */
function restful_token_auth_get_credentials($account) {
  $query = new EntityFieldQuery();
  $result = $query
    ->entityCondition('entity_type', 'restful_token_auth')
    ->entityCondition('bundle', 'access_token')
    ->propertyCondition('uid', $account->uid)
    ->execute();

  if (empty($result['restful_token_auth'])) {
    return drupal_get_form('restful_token_auth_generate_token_form', $account->uid);
  }
  else {
    $id = key($result['restful_token_auth']);
    $auth_token = entity_load_single('restful_token_auth', $id);
    return array(
      '#theme' => 'restful_token_auth_user_key',
      '#token' => $auth_token->token,
    );
  }
}

/**
 * Form callback to generate an API token.
 *
 * @param array $form
 *   The form array.
 * @param array $form_state
 *   The form state array.
 * @param int $uid
 *   The user identifier.
 *
 * @return array
 *   The form as a render array.
 */
function restful_token_auth_generate_token_form($form, &$form_state, $uid) {
  $form['description'] = array(
    '#markup' => t('<p>You don\'t have an API key yet. Click below to generate one.</p>'),
  );
  $form['uid'] = array(
    '#type' => 'value',
    '#value' => $uid,
  );

  $form['submit_button'] = array(
    '#type' => 'submit',
    '#value' => t('Generate token'),
  );

  return $form;
}

/**
 * Form validator for generating a token.
 *
 * @param array $form
 *   The form array.
 * @param array $form_state
 *   The form state array.
 */
function restful_token_auth_generate_token_form_validate($form, &$form_state) {
}

/**
 * Form submit handler for generating a token.
 *
 * @param array $form
 *   The form array.
 * @param array $form_state
 *   The form state array.
 */
function restful_token_auth_generate_token_form_submit($form, &$form_state) {
  /** @var \Drupal\restful_token_auth\Entity\RestfulTokenAuthController $controller */
  $controller = entity_get_controller('restful_token_auth');
  $controller->generateAccessToken($form_state['values']['uid']);
  drupal_set_message(t('Token generated successfully.'), 'status');
}
