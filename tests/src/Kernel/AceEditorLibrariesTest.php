<?php

declare(strict_types=1);

namespace Drupal\Tests\ace_editor\Kernel;

use Drupal\ace_editor\AceEditorLibraries;
use Drupal\ace_editor\Hook\AceEditorHooks;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the library discovery service and the hook implementations.
 *
 * Coverage for issue #3594544: the procedural helpers moved into the
 * AceEditorLibraries service and the AceEditorHooks class, and the legacy
 * function wrappers must keep working. The Ace library itself is an external
 * asset that is not present in a test build, so these tests assert the
 * "library missing" behaviour: the discovery returns FALSE and the hooks
 * degrade without errors instead of building broken asset paths.
 *
 * @group ace_editor
 */
#[Group('ace_editor')]
#[RunTestsInSeparateProcesses]
class AceEditorLibrariesTest extends KernelTestBase {

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
   * The services are wired and injectable.
   */
  public function testServicesAreRegistered(): void {
    $this->assertInstanceOf(AceEditorLibraries::class, $this->container->get(AceEditorLibraries::class));
    $this->assertInstanceOf(AceEditorHooks::class, $this->container->get(AceEditorHooks::class));
  }

  /**
   * Library discovery reports FALSE when the external asset is absent.
   */
  public function testLibPathWithoutTheLibrary(): void {
    $libraries = $this->container->get(AceEditorLibraries::class);
    $this->assertFalse($libraries->libPath());
    // The legacy procedural wrapper returns the same value.
    $this->container->get('module_handler')->loadInclude('ace_editor', 'module');
    $this->assertFalse(ace_editor_lib_path());
  }

  /**
   * The build hook returns no dynamic libraries when the asset is absent.
   */
  public function testLibraryInfoBuildWithoutTheLibrary(): void {
    $this->assertSame([], $this->container->get(AceEditorHooks::class)->libraryInfoBuild());
  }

  /**
   * The alter hook leaves other modules' libraries untouched.
   */
  public function testLibraryInfoAlterIgnoresOtherExtensions(): void {
    $libraries = ['primary' => ['js' => []]];
    $original = $libraries;
    $this->container->get(AceEditorHooks::class)->libraryInfoAlter($libraries, 'system');
    $this->assertSame($original, $libraries, 'Libraries of other extensions are not altered.');
  }

  /**
   * The help hook returns the module help text for its own route.
   */
  public function testHelpText(): void {
    $hooks = $this->container->get(AceEditorHooks::class);
    $route_match = $this->container->get('current_route_match');
    $help = (string) $hooks->help('help.page.ace_editor', $route_match);
    $this->assertStringContainsString('code editor written in JavaScript', $help);
    $this->assertNull($hooks->help('help.page.system', $route_match));
  }

}
