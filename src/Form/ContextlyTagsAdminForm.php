<?php

namespace Drupal\contextly\Form;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ContextlyTagsAdminForm.
 */
class ContextlyTagsAdminForm extends ConfigFormBase implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'contextly.contextlytagsadmin',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contextly_tags_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form,
    FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->config('contextly.contextlytagsadmin');
    $form['tags'] = [
      '#type' => 'details',
      '#title' => t('Taxonomy terms'),
      '#open' => TRUE,
    ];

    $form['tags']['info'] = [
      '#type' => 'item',
      '#markup' => t('Terms from selected fields will be sent to the Contextly.'), // TODO Move to help.
    ];

    $form['tags']['contextly_all_tags'] = [
      '#type' => 'checkbox',
      '#title' => t('All term reference fields'),
      '#default_value' => $config->get('contextly_all_tags') == 0 ? 0 : 1,
    ];

    $available_fields = $this->container->get('contextly.base')
      ->settingsGetAvailableFields(['default:taxonomy_term']);
    $node_types = node_type_get_types();
    $a = $config->get();
    foreach ($available_fields as $type => $fields) {
      $options = $fields;
      $node_type = $node_types[$type];
      $form['tags']['contextly_tags__' . $type] = [
        '#type' => 'checkboxes',
        '#title' => $node_type->label(),
        '#options' => $options,
        '#default_value' => $config->get('contextly_tags__' . $type) ?: [],
        '#element_validate' => ['_contextly_multi_value_element_cleanup'],
        '#states' => [
          'visible' => [
            'input[name="contextly_all_tags"]' => [
              'checked' => FALSE,
            ],
          ],
        ],
      ];
    }

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

    $config = $this->config('contextly.contextlytagsadmin');
    $all_tags = $form_state->getValue('contextly_all_tags');
    $available_fields = $this->container->get('contextly.base')
      ->settingsGetAvailableFields(['default:taxonomy_term']);
    $node_types = node_type_get_types();
    foreach ($available_fields as $type => $fields) {
      $value = empty($all_tags) ? $form_state->getValue('contextly_tags__' . $type) : 0;
      $config->set('contextly_tags__' . $type, $value);
    }
    $config->set('contextly_all_tags', $all_tags)->save();
  }

}
