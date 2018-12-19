<?php

namespace Drupal\ace_editor\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'sample_wkt' formatter.
 *
 * @FieldFormatter (
 *   id = "ace_formatter",
 *   label = @Translation("Ace Format"),
 *   field_types = {
 *     "text_with_summary",
 *     "text_long",
 *   }
 * )
 */
class AceFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    // Get default ace_editor configuration.
    $config = \Drupal::config('ace_editor.settings')->get();
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = t('Displays your code in an editor format');
    return $summary;

  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $formState) {

    $settings = $this->getSettings();

    // $this->getSettings() will return values form defaultSettings() on first use.
    // afterwards it will return the forms saved configuration.
    $config = \Drupal::config('ace_editor.settings');

    return [
      'theme' => [
        '#type' => 'select',
        '#title' => t('Theme'),
        '#options' => $config->get('theme_list'),
        '#attributes' => [
          'style' => 'width: 150px;',
        ],
        '#default_value' => $settings['theme'],
      ],
      'syntax' => [
        '#type' => 'select',
        '#title' => t('Syntax'),
        '#description' => t('The syntax that will be highlighted.'),
        '#options' => $config->get('syntax_list'),
        '#attributes' => [
          'style' => 'width: 150px;',
        ],
        '#default_value' => $settings['syntax'],
      ],
      'height' => [
        '#type' => 'textfield',
        '#title' => t('Height'),
        '#description' => t('The height of the editor in either pixels or percents. You can use "auto" to let the editor calculate the adequate height.'),
        '#attributes' => [
          'style' => 'width: 100px;',
        ],
        '#default_value' => $settings['height'],
      ],
      'width' => [
        '#type' => 'textfield',
        '#title' => t('Width'),
        '#description' => t('The width of the editor in either pixels or percents.'),
        '#attributes' => [
          'style' => 'width: 100px;',
        ],
        '#default_value' => $settings['width'],
      ],
      'font_size' => [
        '#type' => 'textfield',
        '#title' => t('Font size'),
        '#description' => t('The the font size of the editor.'),
        '#attributes' => [
          'style' => 'width: 100px;',
        ],
        '#default_value' => $settings['font_size'],
      ],
      'line_numbers' => [
        '#type' => 'checkbox',
        '#title' => t('Show line numbers'),
        '#default_value' => $settings['line_numbers'],
      ],
      'print_margins' => [
        '#type' => 'checkbox',
        '#title' => t('Print Margins'),
        '#default_value' => $settings['print_margins'],
      ],
      'show_invisibles' => [
        '#type' => 'checkbox',
        '#title' => t('Show partially visible ... for better code matching'),
        '#default_value' => $settings['show_invisibles'],
      ],
      'use_wrap_mode' => [
        '#type' => 'checkbox',
        '#title' => t('Toggle word wrapping'),
        '#default_value' => $settings['use_wrap_mode'],
      ],
     ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Renders front-end of our formatter.
    $elements = [];
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'textarea',
        '#value' => $item->value,
        // Attach libraries as per the setting.
        '#attached' => [
          'library' => [
            'ace_editor/formatter',
            'ace_editor/theme.' . $settings['theme'],
            'ace_editor/mode.' . $settings['syntax'],
          ],
          'drupalSettings' => [
             // Pass settings variable ace_formatter to javascript.
            'ace_formatter' => $settings,
          ],
        ],
        '#attributes' => [
          'class' => [ 'content' ],
          'readonly' => 'readonly',
        ],
        '#prefix' => '<div class="ace_formatter">',
        '#suffix' => '<div>',
      ];
    }
    return $elements;
  }
}
