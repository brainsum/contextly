<?php

namespace Drupal\contextly\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ContextlyNodeTypesAdminForm.
 */
class ContextlyNodeTypesAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'contextly.contextlynodetypesadmin',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contextly_node_types_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form,
    FormStateInterface $form_state) {
    $config = $this->config('contextly.contextlynodetypesadmin');

    $types = node_type_get_names();

    $form['types'] = [
      '#type' => 'details',
      '#title' => $this->t('Enabled node types'),
      '#open' => TRUE,
    ];

    $form['types']['info'] = [
      '#type' => 'item',
      '#markup' => t("Integration with Contextly will be enabled for selected node types only."), // TODO Move to help
    ];

    $form['types']['contextly_all_node_types'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('All node types.'),
      '#default_value' => $config->get('contextly_all_node_types') == 0 ? 0 : 1,
    ];

    $form['types']['contextly_node_types'] = [
      '#title' => t('Enabled types'),
      '#type' => 'checkboxes',
      '#options' => $types,
      '#default_value' => $config->get('contextly_node_types'),
      '#element_validate' => array('multiValueElementCleanup'),
      '#states' => [
        'visible' => [
          'input[name="contextly_all_node_types"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];

    // Custom handler to clear fields cache on submit.
    // $form['#submit'][] = 'contextly_settings_node_types_form_submit_clear_cache';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form,
    FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form,
    FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('contextly.contextlynodetypesadmin')
      ->set('contextly_all_node_types', $form_state->getValue('contextly_all_node_types'))
      ->set('contextly_node_types', $form_state->getValue('contextly_node_types'))
      ->save();
  }

}
