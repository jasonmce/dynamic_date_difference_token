<?php

namespace Drupal\Tests\dynamic_date_difference_token\Kernel;

use Drupal\dynamic_token_manager\Entity\DynamicTokenInstance;
use Drupal\KernelTests\KernelTestBase;

/**
 * @group dynamic_tokens
 */
class DynamicDateDifferenceTokenKernelTest extends KernelTestBase {

  protected static $modules = [
    'system', 'user',
    'dynamic_token_manager',
    'dynamic_date_difference_token',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installConfig(['dynamic_token_manager']);
  }

  protected function validateTokenValue(DynamicTokenInstance $dateDifferenceTokenInstance, string $dateTest) {
    $dynamicTokenManager = $this->container->get('plugin.manager.dynamic_token');
    $dynamicDateDifferenceTokenPlugin = $dynamicTokenManager->createWithInstance('dynamic_date_difference_token', $dateDifferenceTokenInstance);
    $returnedValue = $dynamicDateDifferenceTokenPlugin->value();

    $computedValue = abs(strtotime($dateTest) - time()) / 31536000;
    
    $difference = abs($computedValue - $returnedValue);
    $this->assertTrue(($difference < 0.001) , "got " .   $returnedValue . " expected " . $computedValue . " so difference was " . $difference);
  }

  public function testPluginFutureValue() {
    $dateTest = '2030-01-01T00:00:00Z';
    $dateDifferenceTokenInstance = DynamicTokenInstance::create([
      'id' => 'countdown',
      'label' => 'Countdown',
      'plugin' => 'dynamic_date_difference_token',
      'speed' => 1, 
      'plugin_config' => ['target_datetime' => $dateTest],
      'status' => TRUE,
    ]);
    $dateDifferenceTokenInstance->save();
    $this->validateTokenValue($dateDifferenceTokenInstance, $dateTest);
  }

  public function testPluginPastValue() {
    $dateTest = '2020-01-01T00:00:00Z';
    $dateDifferenceTokenInstance = DynamicTokenInstance::create([
      'id' => 'countdown',
      'label' => 'Countdown',
      'plugin' => 'dynamic_date_difference_token',
      'speed' => 1,
      'plugin_config' => ['target_datetime' => $dateTest],
      'status' => TRUE,
    ]);
    $dateDifferenceTokenInstance->save();
    $this->validateTokenValue($dateDifferenceTokenInstance, $dateTest);
  }


}
