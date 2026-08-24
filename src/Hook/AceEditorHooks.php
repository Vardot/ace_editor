<?php

namespace Drupal\ace_editor\Hook;

use Drupal\ace_editor\AceEditorLibraries;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for ace_editor.
 */
class AceEditorHooks {
  use StringTranslationTrait;

  public function __construct(
    protected AceEditorLibraries $libraries,
  ) {
  }

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      // Main module help for the ace_editor module.
      case 'help.page.ace_editor':
        $output = '';
        $output .= '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('Ace is a code editor written in JavaScript, allowing you to edit HTML, PHP and JavaScript (and more). It provides syntax highlighting, proper indentation, keyboard shortcuts, find and replace (including regular expressions).') . '</p>';
        $output .= '<p>' . $this->t("This module integrates the Ace editor into Drupal's node/block edit forms, for editing raw HTML, PHP, JS, etc... in a familiar way..") . '</p>';
        $output .= '<p>' . $this->t('It supports:') . '</p>';
        $output .= '<ul>' . $this->t('<li>node edit forms, including summary</li>') . '</ul>';
        $output .= '<ul>' . $this->t('<li>blocks edit forms</li>') . '</ul>';
        $output .= '<p>' . $this->t('It also provides a display formatter, along with a text filter and an API to embed and show code snippets in your content.') . '</p>';
        return $output;

      default:
    }
  }

  /**
   * Implements hook_library_info_build().
   *
   * Selects all theme and mode files from ace editor external library and add
   * it to drupal library.
   */
  #[Hook('library_info_build')]
  public function libraryInfoBuild() {
    return $this->libraries->buildLibraryInfo();
  }

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(&$libraries, $extension) {
    if ($extension == 'ace_editor') {
      $this->libraries->alterLibraryInfo($libraries);
    }
  }

}
