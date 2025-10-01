<?php

namespace Drupal\dynamic_date_difference_token\Plugin\DynamicToken;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dynamic_token_manager\Annotation\DynamicToken;
use Drupal\dynamic_token_manager\Plugin\DynamicTokenBase;
use DateTimeZone;


/**
 * @DynamicToken(
 *   id = "dynamic_date_difference_token",
 *   label = @Translation("Dynamic Date Difference Token")
 * )
 */
class DynamicDateDifferenceToken extends DynamicTokenBase {

  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $config): array {
    $default_value = NULL;
    if (!empty($config['target_datetime'])) {
      if (is_string($config['target_datetime'])) {
        try {
          $default_value = new DrupalDateTime($config['target_datetime'], new DateTimeZone('UTC'));
        } catch (\Exception $e) {
          // Fall back to current time if parsing fails
          $default_value = new DrupalDateTime('now', new DateTimeZone('UTC'));
        }
      } elseif ($config['target_datetime'] instanceof DrupalDateTime) {
        $default_value = $config['target_datetime'];
        $default_value->setTimezone(new DateTimeZone('UTC'));
      }
    }

    $form['target_datetime'] = [
      '#type' => 'datetime',
      '#title' => t('Target date/time (UTC)'),
      '#default_value' => $default_value,
      '#date_timezone' => 'UTC',
      '#required' => TRUE,
      '#description' => t('Stored as ISO8601 UTC (e.g., 2030-01-01T00:00:00Z).'),
      '#element_validate' => [[static::class, 'validateDatetime']],
    ];
    return $form;
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): array {
    // Log the raw form state values
    $form_state_values = $form_state->getValues();
    \Drupal::logger('dynamic_date_difference_token')->debug('Form state values: @values', ['@values' => print_r($form_state_values, TRUE)]);
    
    // Get the datetime value from form state
    $dt = $form_state->getValue(['plugin_config','target_datetime']);
    if (!$dt) { 
      $dt = $form_state->getValue('target_datetime'); 
    }
    
    // Log the raw datetime value
    $dt_type = is_object($dt) ? get_class($dt) : gettype($dt);
    \Drupal::logger('dynamic_date_difference_token')->debug('Raw datetime value (@type): @value', [
      '@type' => $dt_type,
      '@value' => print_r($dt, TRUE)
    ]);
    
    // Default fallback value
    $default_iso = '1970-01-01T00:00:00Z';
    $result = [];
    
    try {
      // Handle different input types
      if ($dt instanceof DrupalDateTime) {
        $dt->setTimezone(new DateTimeZone('UTC'));
        $result['target_datetime'] = $dt->format('Y-m-d\TH:i:s\Z');
      } 
      // Handle array input from datetime form element
      elseif (is_array($dt) && !empty($dt['date'])) {
        $time = $dt['time'] ?? '00:00:00';
        $date = DrupalDateTime::createFromFormat('Y-m-d H:i:s', $dt['date'] . ' ' . $time, new DateTimeZone('UTC'));
        if ($date) {
          $result['target_datetime'] = $date->format('Y-m-d\TH:i:s\Z');
        } else {
          $result['target_datetime'] = $default_iso;
        }
      }
      // Handle string input
      elseif (is_string($dt) && $dt !== '') {
        try {
          $date = new DrupalDateTime($dt, new DateTimeZone('UTC'));
          $result['target_datetime'] = $date->format('Y-m-d\TH:i:s\Z');
        } catch (\Exception $e) {
          $result['target_datetime'] = $default_iso;
        }
      }
      else {
        $result['target_datetime'] = $default_iso;
      }
    } catch (\Exception $e) {
      // If anything goes wrong, use the default value
      $result['target_datetime'] = $default_iso;
    }
    
    return $result;
  }

  public function spanExtraAttributes(): array {
    $cfg = $this->cfg();
    $iso = isset($cfg['target_datetime']) ? (string) $cfg['target_datetime'] : '1970-01-01T00:00:00Z';
    return ['data-target-datetime' => $iso];
  }

  public function value(): string {
    $cfg = $this->cfg();
    $iso = isset($cfg['target_datetime']) ? (string) $cfg['target_datetime'] : '1970-01-01T00:00:00Z';
    $target = strtotime($iso) ?: 0;
    $diff = $target - $this->requestTime();
    return (string) (int) $diff;
  }

  /**
   * {@inheritdoc}
   */
  public function attachments(): array {
    return [
      'library' => ['dynamic_date_difference_token/runtime'],
    ];
  }

  /**
   * Form element validation handler for datetime elements.
   */
  public static function validateDatetime(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $form_state->getValue($element['#parents']);
    
    // If the value is already a DrupalDateTime object, ensure it's in UTC
    if ($value instanceof DrupalDateTime) {
      $value->setTimezone(new DateTimeZone('UTC'));
      $form_state->setValueForElement($element, $value);
      return;
    }
    
    // If the value is an array (from the datetime widget), convert it to a DrupalDateTime
    if (is_array($value) && !empty($value['date']) && !empty($value['time'])) {
      try {
        $date = DrupalDateTime::createFromFormat('Y-m-d H:i:s', $value['date'] . ' ' . $value['time'], new DateTimeZone('UTC'));
        $form_state->setValueForElement($element, $date);
      } catch (\Exception $e) {
        $form_state->setError($element, t('The datetime is not valid.'));
      }
      return;
    }
    
    // If the value is a string, try to parse it
    if (is_string($value) && $value !== '') {
      try {
        $date = new DrupalDateTime($value, new DateTimeZone('UTC'));
        $form_state->setValueForElement($element, $date);
      } catch (\Exception $e) {
        $form_state->setError($element, t('The datetime format is not valid.'));
      }
      return;
    }
    
    // If we get here, the value is not valid
    $form_state->setError($element, t('A valid datetime is required.'));
  }
}
