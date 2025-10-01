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
    // Convert config string value to DrupalDateTime.
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
    // Get the datetime value from form state.
    $dt = $form_state->getValue(['plugin_config', 'target_datetime']);
    if ($dt === NULL || $dt === '') {
      $dt = $form_state->getValue('target_datetime');
    }

    $default_iso = '1970-01-01T00:00:00Z';
    $result = [];

    try {
      // DrupalDateTime provided directly.
      if ($dt instanceof DrupalDateTime) {
        $dt->setTimezone(new DateTimeZone('UTC'));
        $result['target_datetime'] = $dt->format('Y-m-d\TH:i:s\Z');
      }
      // Array from datetime widget.
      elseif (is_array($dt) && !empty($dt['date'])) {
        $time = $dt['time'] ?? '00:00:00';
        $date = DrupalDateTime::createFromFormat('Y-m-d H:i:s', $dt['date'] . ' ' . $time, new DateTimeZone('UTC'));
        $result['target_datetime'] = $date ? $date->format('Y-m-d\TH:i:s\Z') : $default_iso;
      }
      // String input.
      elseif (is_string($dt) && $dt !== '') {
        $date = new DrupalDateTime($dt, new DateTimeZone('UTC'));
        $result['target_datetime'] = $date->format('Y-m-d\TH:i:s\Z');
      }
      // Anything else -> default.
      else {
        $result['target_datetime'] = $default_iso;
      }
    }
    catch (\Exception $e) {
      $result['target_datetime'] = $default_iso;
    }

    // Store normalized value back into FormState in case anything else
    // reads from it later in the submit pipeline.
    if (isset($result['target_datetime'])) {
      $form_state->setValue(['plugin_config', 'target_datetime'], $result['target_datetime']);
      $form_state->setValue('target_datetime', $result['target_datetime']);
    }

    return $result;
  }

  public function spanExtraAttributes(): array {
    $cfg = $this->cfg();
    $iso = isset($cfg['target_datetime']) ? (string) $cfg['target_datetime'] : '1970-01-01T00:00:00Z';
    return ['data-target-datetime' => $iso];
  }

  // Return the difference as a decimal of years.
  public function value(): string {
    $cfg = $this->cfg();
    $iso = isset($cfg['target_datetime']) ? (string) $cfg['target_datetime'] : '1970-01-01T00:00:00Z';
    $target = strtotime($iso) ?: 0;
    $diff = $target - $this->requestTime();
    return sprintf('%0.8f', abs($diff / 31536000));
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
    $default_iso = '1970-01-01T00:00:00Z';

    // If the value is already a DrupalDateTime object, normalize to ISO string (UTC).
    if ($value instanceof DrupalDateTime) {
      try {
        $value->setTimezone(new DateTimeZone('UTC'));
        $form_state->setValueForElement($element, $value->format('Y-m-d\TH:i:s\Z'));
      }
      catch (\Exception $e) {
        $form_state->setValueForElement($element, $default_iso);
      }
      return;
    }

    // If the value is an array (from the datetime widget), convert it to ISO string.
    if (is_array($value) && !empty($value['date'])) {
      try {
        $time = $value['time'] ?? '00:00:00';
        $date = DrupalDateTime::createFromFormat('Y-m-d H:i:s', $value['date'] . ' ' . $time, new DateTimeZone('UTC'));
        if ($date) {
          $form_state->setValueForElement($element, $date->format('Y-m-d\TH:i:s\Z'));
        }
        else {
          $form_state->setError($element, t('The datetime is not valid.'));
        }
      }
      catch (\Exception $e) {
        $form_state->setError($element, t('The datetime is not valid.'));
      }
      return;
    }

    // If the value is a string, try to normalize it to ISO string.
    if (is_string($value) && $value !== '') {
      try {
        $date = new DrupalDateTime($value, new DateTimeZone('UTC'));
        $form_state->setValueForElement($element, $date->format('Y-m-d\TH:i:s\Z'));
      }
      catch (\Exception $e) {
        $form_state->setError($element, t('The datetime format is not valid.'));
      }
      return;
    }

    // If we get here, the value is not valid.
    $form_state->setError($element, t('A valid datetime is required.'));
  }
}
