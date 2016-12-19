<?php
/**
 * @file
 * Drush integration for Ace Editor.
 */

/**
 * The Ace plugin URI and the internal path inside the zip.
 */
define('ACE_DOWNLOAD_URI',
  'https://github.com/ajaxorg/ace-builds/archive/master.zip');
define('ACE_ZIP_INTERNAL_PATH', '/ace-builds-master/');

/**
 * Implements hook_drush_command().
 */
function ace_editor_drush_command() {
  $items = array();
  // The key in the $items array is the name of the command.
  $items['download-ace'] = array(
    'callback' => 'drush_download_ace',
    'description' => dt('Download and install the Ace editor plugin from ' .
      ACE_DOWNLOAD_URI . ' in the Libraries directory.'),
    'arguments' => array(
      'ace version' => dt('Ace library version to install:' . PHP_EOL .
        '  src: concatenated but not minified (default)' . PHP_EOL .
        '  src-min: concatenated and minified with uglify.js.'),
    ),
    'drupal dependencies' => array('libraries'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'aliases' => array('dl-ace'),
  );

  return $items;
}

/**
 * Command to download the Ace plugin.
 */
function drush_download_ace($ace_version = '') {
  // Reset --yes/no global options.
  drush_set_context('DRUSH_NEGATIVE', FALSE);
  drush_set_context('DRUSH_AFFIRMATIVE', FALSE);
  // Libraries module is a dependency.
  if (!drupal_load('module', 'libraries')) {
    drush_log(dt('Libraries module must be installed.'), 'error');
    return;
  }
  // Check argument if available.
  if (!empty($ace_version) && !in_array($ace_version, array('src', 'src-min'))) {
    drush_log(dt('Invalid argument provided. Must be "src" or "src-min".'), 'error');
    return;
  }
  $lib_path = libraries_get_path('ace');
  $path = (!empty($lib_path)) ?
    drush_get_context('DRUSH_DRUPAL_ROOT') . '/' . $lib_path :
    drush_get_context('DRUSH_DRUPAL_ROOT') . '/sites/all/libraries/ace';

  // Ask the Ace version to be installed.
  if (empty($ace_version)) {
    $versions = array(
      'src' => 'concatenated but not minified',
      'src-min' => 'concatenated and minified with uglify.js',
    );
    $choice = drush_choice($versions,
      dt('Select the Ace version to be downloaded and installed:'));

    if (empty($choice)) {
      return;
    }
    else {
      $ace_version = $choice;
    }
  }

  // Check if the Ace library dir exists.
  if (!empty($lib_path) && is_dir($path)) {
    if (!drush_confirm('The Ace library seems installed at ' . $path . '. Do you want to delete and override it with the selected version?')) {
      return;
    }
    else {
      if (!drush_get_option('simulate')) {
        drush_delete_dir($path, TRUE);
      }
    }
  }

  // Create the path.
  if (!drush_get_option('simulate')) {
    drupal_mkdir($path);
  }

  // Make sure that the target directory exists and is writable.
  if (!file_prepare_directory($path)) {
    drush_log(dt('Directory @path does not exist or is not writable.',
      array('@path' => $path)),
        'error');
    return;
  }

  // Download the chosen Ace version files to the libraries directory.
  $rc = drush_shell_cd_and_exec($path,
    'wget --no-check-certificate ' . ACE_DOWNLOAD_URI .
    ' -O ' . basename(ACE_DOWNLOAD_URI)
    );

  if ($rc && !drush_get_option('simulate')) {
    // Unzip. Move to the expected subdir. Clean temporary & unrequired fields.
    do {
      $rc = drush_tarball_extract($path . '/' . basename(ACE_DOWNLOAD_URI));
      if (!$rc) {
        break;
      }
      $rc = drush_move_dir($path . ACE_ZIP_INTERNAL_PATH . $ace_version,
        $path . '/src');
      if (!$rc) {
        break;
      }
      $rc = copy($path . ACE_ZIP_INTERNAL_PATH . 'ChangeLog.txt',
        $path . '/ChangeLog.txt');
      if (!$rc) {
        break;
      }
      $rc = copy($path . ACE_ZIP_INTERNAL_PATH . 'package.json',
        $path . '/package.json');
      if (!$rc) {
        break;
      }
      $rc = drush_delete_dir($path . ACE_ZIP_INTERNAL_PATH, TRUE);
      if (!$rc) {
        break;
      }
      $rc = unlink($path . '/' . basename(ACE_DOWNLOAD_URI));
    } while (FALSE);
  }

  if ($rc) {
    // Get the installed release from package.json.
    $release_installed = '';
    if ($pjson = drupal_json_decode(file_get_contents($path . '/package.json'))) {
      $release_installed = $pjson['version'];
    }

    drush_log(dt('Ace Editor plugin (release ' .
      $release_installed . ') has been installed in @path',
      array('@path' => $path)), 'success');
  }
  else {
    drush_log(dt('Drush was unable to install the Ace Editor plugin to @path. Run the drush command with the --debug option for more info.',
      array('@path' => $path)),
       'error');
  }
}

/**
 * Implements drush_MODULE_pre_COMMAND().
 */
function drush_ace_editor_pre_pm_enable($project = NULL) {
  if ($project == 'ace_editor' && drush_confirm('Do you want to automatically install the library now?')) {
    // If Libraries module was not enabled at this moment,
    // library download will fail. We will try again in the post_COMMAND().
    drush_download_ace();
  }
}

/**
 * Implements drush_MODULE_post_COMMAND().
 */
function drush_ace_editor_post_pm_enable($project = NULL) {
  if ($project == 'ace_editor') {
    if (!ace_editor_library_installed()) {
      drush_download_ace();
      ace_editor_get_assets();
    }
  }
}
