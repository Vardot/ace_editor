<?php

declare(strict_types=1);

namespace Drupal\ace_editor;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ProfileExtensionList;
use Drupal\Core\File\FileSystemInterface;

/**
 * Locates the external Ace library and builds its Drupal libraries.
 *
 * Holds the logic that used to live in the procedural helpers in
 * ace_editor.module. Those functions are kept as thin backwards compatible
 * wrappers that delegate here.
 */
class AceEditorLibraries {

  /**
   * Statically cached library path for the current request.
   *
   * FALSE means "not found"; NULL means "not resolved yet".
   */
  protected string|false|null $libPath = NULL;

  public function __construct(
    protected FileSystemInterface $fileSystem,
    protected ModuleExtensionList $moduleExtensionList,
    protected ProfileExtensionList $profileExtensionList,
    protected ConfigFactoryInterface $configFactory,
    protected ?string $installProfile,
  ) {
  }

  /**
   * Finds the Ace library path relative to the Drupal root.
   *
   * The library is searched in the directory DRUPAL_ROOT/libraries.
   * DRUPAL_ROOT/libraries is recommended for storing external libraries.
   * For preserving backwards compatibility, ace_editor/libraries is also
   * checked.
   *
   * @return string|false
   *   The path of the directory holding ace.js, with a leading and trailing
   *   slash, or FALSE when the library is not installed.
   */
  public function libPath(): string|false {
    if ($this->libPath !== NULL) {
      return $this->libPath;
    }

    $paths_to_check = [
      '/libraries/ace',
      '/libraries/ace-builds',
      '/' . $this->moduleExtensionList->getPath('ace_editor') . '/libraries',
    ];

    // Add profile path only if a profile is configured.
    if ($this->installProfile) {
      $paths_to_check[] = '/' . $this->profileExtensionList->getPath($this->installProfile) . '/libraries/ace';
    }

    $this->libPath = FALSE;
    foreach ($paths_to_check as $path) {
      if (!is_dir(DRUPAL_ROOT . $path)) {
        continue;
      }

      $found = $this->fileSystem->scanDirectory(DRUPAL_ROOT . $path, '/^ace\.js/', ['recurse' => TRUE]);
      if ($found) {
        $this->libPath = substr(preg_replace('/ace\.js/', '', reset($found)->uri), strlen(DRUPAL_ROOT));
        break;
      }
    }
    return $this->libPath;
  }

  /**
   * Builds the dynamic theme and mode libraries from the Ace library.
   *
   * @return array[]
   *   Library definitions keyed by "<theme|mode>.<name>".
   */
  public function buildLibraryInfo(): array {
    $path = $this->libPath();
    if (!$path) {
      return [];
    }

    // Collects all theme and mode files available.
    $files = $this->fileSystem->scanDirectory(DRUPAL_ROOT . $path, '/(theme|mode)-(.+)\.js$/', ['recurse' => FALSE]);

    $libraries = [];
    foreach ($files as $file_info) {
      $asset = explode('-', $file_info->name);
      $library_name = $asset[0] . '.' . $asset[1];
      $libraries[$library_name] = $path . $file_info->filename;
    }

    $libs = [];
    foreach ($libraries as $key => $value) {
      $libs[$key] = [
        'js' => [
          $value => [],
        ],
      ];
    }
    return $libs;
  }

  /**
   * Injects the located Ace assets into the module's static libraries.
   *
   * @param array $libraries
   *   The module's library definitions, altered in place.
   */
  public function alterLibraryInfo(array &$libraries): void {
    $library_path = $this->libPath();
    if (!$library_path) {
      return;
    }
    $libraries['primary']['js'][$library_path . 'ace.js'] = ['weight' => -2];
    $config = $this->configFactory->get('ace_editor.settings')->get();
    if (isset($config['auto_complete'])) {
      $libraries['primary']['js'][$library_path . 'ext-language_tools.js'] = ['weight' => -2];
    }
    $libraries['formatter']['js'][$library_path . 'ace.js'] = ['weight' => -2];
    $libraries['filter']['js'][$library_path . 'ace.js'] = ['weight' => -2];
  }

}
