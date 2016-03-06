<?php

/**
 * @file
 * Contains \Drupal\restful\Form\ResourceConfigForm.
 */

namespace Drupal\restful\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ResourceConfigForm.
 *
 * @package Drupal\restful\Form
 */
class ResourceConfigForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $resource_config = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $resource_config->label(),
      '#description' => $this->t("Label for the Resource Config."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $resource_config->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\restful\Entity\ResourceConfig::load',
      ),
      '#disabled' => !$resource_config->isNew(),
    );

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $resource_config = $this->entity;
    $status = $resource_config->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Resource Config.', [
          '%label' => $resource_config->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Resource Config.', [
          '%label' => $resource_config->label(),
        ]));
    }
    $form_state->setRedirectUrl($resource_config->urlInfo('collection'));
  }

}
