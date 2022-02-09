<?php

namespace Drupal\wingsuit_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConfigForm.
 */
class ConfigForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wingsuit_companion_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('wingsuit_companion.config');
    $form['dist_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dist path'),
      '#description' => $this->t(
        'A local file system path to your dist/app-drupal directory.'
      ),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('dist_path'),
      '#weight' => 10,
    ];
    $form['only_own_layout'] = [
      '#weight' => 20,
      '#type' => 'checkbox',
      '#title' => $this->t('Use only Wingsuit patterns.'),
      '#description' => $this->t(
        'Check this to hide all other layouts in layout builder.'
      ),
      '#default_value' => $config->get('only_own_layout'),
    ];
    $form['submit'] = [
      '#weight' => 30,
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('wingsuit_companion.config');
    foreach ($form_state->getValues() as $key => $value) {
      if (!in_array(
        $key,
        ['submit', 'form_build_id', 'form_token', 'form_id', 'op']
      )) {
        $config->set($key, $value);
      }

    }
    $config->save();
    $this->messenger()->addMessage('Wingsuit settings stored successfully.');
  }

}
