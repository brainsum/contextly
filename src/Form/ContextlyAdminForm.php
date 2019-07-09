<?php

namespace Drupal\contextly\Form;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Class ContextlyAdminForm.
 */
class ContextlyAdminForm extends ConfigFormBase implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'contextly.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contextly_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form,
    FormStateInterface $form_state) {
    $config = $this->config('contextly.settings');
    $key = $config->get('api_key');

    /** @var \Drupal\Core\Access\CsrfTokenGenerator $token_generator */
    $token_generator = $this->container->get('csrf_token');
    $settings_token = $this->container->get('contextly.base')
      ->settingsCpTokenValue('tour');

    $form['module'] = [
      '#type' => 'details',
      '#title' => $this->t('Module settings'),
      '#open' => empty($key),
    ];

    if (empty($key)) {
      // Add registration link if empty API key.
      $link = Link::createFromRoute(
          $this->t("Contextly's settings panel"),
          'contextly.admin_controller_cp_tour',
          ['token' => $token_generator->get($settings_token)],
          ['attributes' => ['target' => '_blank']])->toString();

      $form['module']['link'] = [
        '#type' => 'item',
        '#markup' => $this->t('In order to communicate securely, we use a shared secret key. You can find your secret API key on @home_link. Copy and paste it below.', [
          '@home_link' => $link,
        ]),
      ];
    }

    $form['module']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('Enter your contextly api key.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $key,
    ];

    $form['module']['server_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Server mode'),
      '#options' => [
        'live' => $this->t('Live'),
        'dev' => $this->t('Development'),
      ],
      '#default_value' => $config->get('server_mode'),
    ];

    $form['service'] = [
      '#type' => 'details',
      '#title' => $this->t('Service settings'),
      '#open' => !empty($key),
    ];

    $form['service']['notice'] = [
      '#type' => 'item',
      '#title' => $this->t('The majority of the settings for Contextly are handled outside of Drupal. Click the settings button to securely login to your settings panel. If this fails, please email us at <a href="@url">info@contextly.com</a>.', [
        '@url' => 'mailto:info@contextly.com',
      ]),
      '#default_value' => $config->get('notice'),
    ];

    $form['service']['go'] = [
      '#type' => 'submit',
      '#value' => $this->t('Settings'),
      '#name' => 'go',
    ];

    $form['#theme'] = 'system_config_form';

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
    switch ($form_state->getTriggeringElement()['#id']) {
      case 'edit-submit':
        parent::submitForm($form, $form_state);
        $this->config('contextly.settings')
          ->set('api_key', $form_state->getValue('api_key'))
          ->set('server_mode', $form_state->getValue('server_mode'))
          ->save();

        $this->container->get('contextly.base')->settingsResetSharedToken();
        break;

      case 'edit-go':
        // Redirects to Contextly service settings page.
        return $this->container->get('contextly.base')->settingsUrl('settings');
        break;
    }
  }

}
