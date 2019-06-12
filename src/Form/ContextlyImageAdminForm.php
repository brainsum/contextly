<?php

namespace Drupal\contextly\Form;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ContextlyImageAdminForm.
 */
class ContextlyImageAdminForm extends ConfigFormBase implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'contextly.contextlyimageadmin',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contextly_image_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form,
    FormStateInterface $form_state) {
    $available_fields = $this->container->get('contextly.base')
      ->settingsGetAvailableFields(['image']);
    if (empty($available_fields)) {
      $this->container->get('messenger')
        ->addWarning($this->t('Add some image fields to Contextly-enabled node types to use featured images.'));
      return $form;
    }
    $config = $this->config('contextly.contextlyimageadmin');

    $form['images'] = [
      '#type' => 'details',
      '#title' => t('Featured images'),
      '#open' => TRUE,
    ];

    $count = count($available_fields);
    $these_fields = $this->container->get('string_translation')
      ->formatPlural($count, 'This field lets', 'These fields let');
    $form['images']['info'] = [
      '#type' => 'item',
      '#markup' => t('@these_fields you control what images will be used to create the thumbnails for Contextly recommendations. The first image from the selected field below will be used to create the thumbnail.', [
        '@these_fields' => $these_fields]
      ),
      // TODO Move to help.
    ];

    $node_types = node_type_get_types();
    foreach ($available_fields as $type => $fields) {
      $options = $fields;
      $node_type = $node_types[$type];
      $form['images']['contextly_featured_image__' . $type] = array(
        '#type' => 'select',
        '#title' => $node_type->label(),
        '#options' => $options,
        '#empty_value' => '',
        '#default_value' => $config->get('contextly_featured_image__' . $type) ?: '',
      );
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

    $config = $this->config('contextly.contextlyimageadmin');
    $available_fields = $this->container->get('contextly.base')
      ->settingsGetAvailableFields(['image']);
    $node_types = node_type_get_types();
    foreach ($available_fields as $type => $fields) {
      $config->set('contextly_featured_image__' . $type,
        $form_state->getValue('contextly_featured_image__' . $type));
    }
    $config->save();
  }

}
