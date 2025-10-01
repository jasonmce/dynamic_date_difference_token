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

  public function testDeltaComputes() {
    $instance = DynamicTokenInstance::create([
      'id' => 'countdown',
      'label' => 'Countdown',
      'plugin' => 'dynamic_date_difference_token',
      'speed' => 1,
      'plugin_config' => ['target_datetime' => '2030-01-01T00:00:00Z'],
      'status' => TRUE,
    ]);
    $instance->save();

    $manager = $this->container->get('plugin.manager.dynamic_token');
    $plugin = $manager->createWithInstance('dynamic_date_difference_token', $instance);
    $v = (int) $plugin->value();
    $this->assertIsInt($v);
  }

}
