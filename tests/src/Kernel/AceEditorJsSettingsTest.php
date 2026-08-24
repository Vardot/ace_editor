<?php

declare(strict_types=1);

namespace Drupal\Tests\ace_editor\Kernel;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the settings handed to the editor JavaScript.
 *
 * The configuration form nests its values under "fieldset", while
 * configuration created programmatically - by a recipe, a config import, or an
 * older release - keeps them at the top level. Both shapes must produce
 * usable settings: a NULL reaches the JavaScript as format.editorSettings and
 * the editor never attaches.
 *
 * @group ace_editor
 */
#[Group('ace_editor')]
#[RunTestsInSeparateProcesses]
class AceEditorJsSettingsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'filter',
    'editor',
    'ace_editor',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['ace_editor', 'filter']);

    FilterFormat::create([
      'format' => 'ace_test_format',
      'name' => 'Ace test format',
    ])->save();
  }

  /**
   * Returns the JS settings for an editor with the given stored settings.
   */
  protected function jsSettingsFor(array $settings): array {
    $editor = Editor::create([
      'format' => 'ace_test_format',
      'editor' => 'ace_editor',
      'settings' => $settings,
    ]);
    $editor->save();

    $plugin = $this->container->get('plugin.manager.editor')
      ->createInstance('ace_editor');
    return $plugin->getJsSettings($editor);
  }

  /**
   * Settings nested under "fieldset" (saved through the form) are returned.
   */
  public function testNestedFieldsetSettings(): void {
    $js = $this->jsSettingsFor([
      'fieldset' => [
        'theme' => 'monokai',
        'syntax' => 'php',
        'height' => '400px',
      ],
    ]);

    $this->assertSame('monokai', $js['theme']);
    $this->assertSame('php', $js['syntax']);
    $this->assertSame('400px', $js['height']);
  }

  /**
   * Flat settings (recipe, config import, older release) are returned.
   */
  public function testFlatSettings(): void {
    $js = $this->jsSettingsFor([
      'theme' => 'twilight',
      'syntax' => 'yaml',
    ]);

    $this->assertSame('twilight', $js['theme']);
    $this->assertSame('yaml', $js['syntax']);
  }

  /**
   * Empty stored settings still produce a usable set of defaults.
   *
   * This is the case that used to hand NULL to the JavaScript.
   */
  public function testEmptySettingsFallBackToDefaults(): void {
    $js = $this->jsSettingsFor([]);

    $this->assertIsArray($js);
    $this->assertNotEmpty($js);
    $this->assertArrayHasKey('theme', $js);
    $this->assertArrayHasKey('syntax', $js);
  }

  /**
   * Partial settings are completed from the module defaults.
   */
  public function testPartialSettingsAreCompleted(): void {
    $js = $this->jsSettingsFor(['fieldset' => ['theme' => 'github']]);

    $this->assertSame('github', $js['theme']);
    $this->assertArrayHasKey('syntax', $js, 'The missing syntax setting comes from the defaults.');
  }

  /**
   * Saved editor settings do not carry the form's option lists.
   *
   * Drupal merges the plugin defaults into the saved entity, so returning the
   * whole module configuration used to write the 34 theme and 140 syntax
   * options into every editor.editor.* configuration.
   */
  public function testOptionListsAreNotSaved(): void {
    $editor = Editor::create([
      'format' => 'ace_test_format',
      'editor' => 'ace_editor',
      'settings' => ['fieldset' => ['theme' => 'chrome']],
    ]);
    $editor->save();

    $saved = $this->config('editor.editor.ace_test_format')->get('settings');
    $this->assertArrayNotHasKey('theme_list', $saved);
    $this->assertArrayNotHasKey('syntax_list', $saved);
  }

}
