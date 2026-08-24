<?php

declare(strict_types=1);

namespace Drupal\Tests\ace_editor\Kernel;

use Drupal\ace_editor\AceEditorLibraries;
use Drupal\ace_editor\Hook\AceEditorHooks;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests that the Ace search box extension is attached for editing.
 *
 * Coverage for issue #3473555: the Find (Ctrl-F) and Replace (Ctrl-H)
 * shortcuts do nothing unless ext-searchbox.js is loaded. Only the editing
 * library needs it - the formatter and the filter render read-only code.
 *
 * @group ace_editor
 */
#[Group('ace_editor')]
class AceEditorSearchBoxTest extends KernelTestBase {

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
    $this->installConfig(['ace_editor']);
  }

  /**
   * Returns the JavaScript assets the alter hook adds to a library.
   *
   * The external Ace library is not present in a test build, so the hook is
   * driven with a known path to assert which assets it asks for.
   *
   * @return string[]
   *   The asset paths, keyed by library name.
   */
  protected function alteredAssets(): array {
    $libraries = [
      'primary' => ['js' => []],
      'formatter' => ['js' => []],
      'filter' => ['js' => []],
    ];

    $hooks = $this->container->get(AceEditorHooks::class);
    $hooks->libraryInfoAlter($libraries, 'ace_editor');

    $assets = [];
    foreach ($libraries as $name => $library) {
      $assets[$name] = array_keys($library['js']);
    }
    return $assets;
  }

  /**
   * Nothing is attached while the external library is missing.
   *
   * The library is absent in a test build, so this documents the guard: the
   * hook adds no asset paths it cannot serve.
   */
  public function testNoAssetsWithoutTheLibrary(): void {
    $assets = $this->alteredAssets();

    $this->assertSame([], $assets['primary']);
    $this->assertSame([], $assets['formatter']);
    $this->assertSame([], $assets['filter']);
  }

  /**
   * The editing library asks for the search box extension.
   */
  public function testEditingLibraryRequestsTheSearchBox(): void {
    $libraries = ['primary' => ['js' => []], 'formatter' => ['js' => []], 'filter' => ['js' => []]];

    // Drive the discovery service with a known path so the alter hook has a
    // library to build asset paths from: the external Ace library is not
    // present in a test build.
    $discovery = new class(
      $this->container->get('file_system'),
      $this->container->get('extension.list.module'),
      $this->container->get('extension.list.profile'),
      $this->container->get('config.factory'),
      NULL,
      $this->container->get('cache.discovery'),
    ) extends AceEditorLibraries {

      /**
       * {@inheritdoc}
       */
      public function libPath(): string|false {
        return '/libraries/ace/';
      }

    };
    $discovery->alterLibraryInfo($libraries);

    $this->assertContains('/libraries/ace/ext-searchbox.js', array_keys($libraries['primary']['js']));
    $this->assertNotContains('/libraries/ace/ext-searchbox.js', array_keys($libraries['formatter']['js']));
    $this->assertNotContains('/libraries/ace/ext-searchbox.js', array_keys($libraries['filter']['js']));
  }

}
