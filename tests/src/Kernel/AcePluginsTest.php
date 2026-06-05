<?php

declare(strict_types=1);

namespace Drupal\Tests\ace_editor\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that the Ace Editor plugins and default configuration are available.
 *
 * The module exposes the Ace editor through three plugins - a text editor, a
 * text filter and a field formatter - all driven by the ace_editor.settings
 * default configuration. This test asserts each plugin is discoverable and the
 * shipped defaults install with the expected values.
 *
 * @group ace_editor
 */
class AcePluginsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'filter',
    'editor',
    'field',
    'text',
    'ace_editor',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['ace_editor']);
  }

  /**
   * The Ace Editor text-editor plugin is discoverable.
   */
  public function testEditorPluginExists(): void {
    $manager = \Drupal::service('plugin.manager.editor');
    $this->assertTrue($manager->hasDefinition('ace_editor'));
  }

  /**
   * The Ace Filter text-filter plugin is discoverable.
   */
  public function testFilterPluginExists(): void {
    $manager = \Drupal::service('plugin.manager.filter');
    $this->assertTrue($manager->hasDefinition('ace_filter'));
  }

  /**
   * The Ace Format field formatter plugin is discoverable.
   */
  public function testFormatterPluginExists(): void {
    $manager = \Drupal::service('plugin.manager.field.formatter');
    $this->assertTrue($manager->hasDefinition('ace_formatter'));
  }

  /**
   * The shipped default configuration installs with the documented values.
   */
  public function testDefaultSettings(): void {
    $config = $this->config('ace_editor.settings');
    $this->assertSame('cobalt', $config->get('theme'));
    $this->assertSame('html', $config->get('syntax'));
    $this->assertTrue($config->get('print_margins'));
    $this->assertTrue($config->get('line_numbers'));
    // The theme and syntax option lists are non-empty maps.
    $this->assertNotEmpty($config->get('theme_list'));
    $this->assertNotEmpty($config->get('syntax_list'));
  }

}
