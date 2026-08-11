<?php

/**
 * @file
 * Provision the fixtures the Ace Editor varbase-e2e scenarios rely on.
 *
 * Run once, after the module and its dependencies are enabled, with
 * "drush scr tests/fixtures/provision-fixtures.php".
 *
 * It is idempotent - safe to run repeatedly against the same database. It sets
 * up the three surfaces the suite exercises and the demo content they assert
 * against:
 *
 *   1. The "Full HTML" text format uses the Ace Editor text editor.
 *   2. The "Full HTML" format runs the Ace Filter first (weight -50) so <ace>
 *      snippets are extracted before filter_autop / filter_html touch them.
 *   3. The Article "body" field is displayed with the "Ace Format" formatter.
 *   4. A page node carrying an <ace> snippet and an article node for the
 *      formatter/editor scenarios.
 *
 * @see js/editor.js, js/formatter.js, js/filter.js
 */

use Drupal\editor\Entity\Editor;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

// Core 11.4 standard no longer ships the article/page content types; create
// what the fixtures below assume.
$ensure_type = function (string $type, string $name): void {
  if (!NodeType::load($type)) {
    NodeType::create(["type" => $type, "name" => $name, "display_submitted" => FALSE])->save();
    print "node type $type: created\n";
  }
  if (!FieldStorageConfig::loadByName("node", "body")) {
    FieldStorageConfig::create(["field_name" => "body", "entity_type" => "node", "type" => "text_with_summary"])->save();
  }
  if (!FieldConfig::loadByName("node", $type, "body")) {
    FieldConfig::create(["field_name" => "body", "entity_type" => "node", "bundle" => $type, "label" => "Body"])->save();
    $r = \Drupal::service("entity_display.repository");
    $r->getFormDisplay("node", $type)->setComponent("body", ["type" => "text_textarea_with_summary"])->save();
    $r->getViewDisplay("node", $type)->setComponent("body", ["type" => "text_default", "label" => "hidden"])->save();
  }
};
$ensure_type("article", "Article");
$ensure_type("page", "Basic page");

$shared_settings = [
  'theme' => 'cobalt',
  'syntax' => 'html',
  'height' => '300px',
  'width' => '100%',
  'font_size' => '14pt',
  'line_numbers' => TRUE,
  'print_margins' => TRUE,
  'show_invisibles' => FALSE,
  'use_wrap_mode' => TRUE,
  'auto_complete' => TRUE,
];

// 1. Assign the Ace Editor as the text editor for the Full HTML format.
if ($existing = Editor::load('full_html')) {
  $existing->delete();
}
Editor::create([
  'format' => 'full_html',
  'editor' => 'ace_editor',
  'settings' => ['fieldset' => $shared_settings],
  'image_upload' => [],
])->save();

// 2. Enable the Ace Filter on the Full HTML format, running first.
$full_html = FilterFormat::load('full_html');
$full_html->setFilterConfig('ace_filter', [
  'status' => TRUE,
  'weight' => -50,
  'settings' => ['height' => '200px'] + $shared_settings,
]);
$full_html->save();

// 3. Display the Article body with the Ace Format field formatter.
$display = \Drupal::service('entity_display.repository')
  ->getViewDisplay('node', 'article', 'default');
$display->setComponent('body', [
  'type' => 'ace_formatter',
  'label' => 'hidden',
  'settings' => ['height' => '250px'] + $shared_settings,
])->save();

// 4. Demo content. Keyed by title so reruns update rather than duplicate.
$nodes = [
  'Ace Filter Demo' => [
    'type' => 'page',
    'body' => [
      'value' => "<p>Below is a highlighted snippet:</p>\n<ace theme=\"twilight\" syntax=\"javascript\" print-margin=\"1\">function hello(name) {\n  return \"Hello, \" + name;\n}</ace>",
      'format' => 'full_html',
    ],
  ],
  'Ace Formatter Demo' => [
    'type' => 'article',
    'body' => [
      'value' => "<h1>Title</h1>\n<p>Some HTML code</p>",
      'format' => 'full_html',
    ],
  ],
];
foreach ($nodes as $title => $values) {
  $found = \Drupal::entityTypeManager()->getStorage('node')
    ->loadByProperties(['title' => $title]);
  $node = $found ? reset($found) : Node::create(['type' => $values['type'], 'title' => $title]);
  $node->set('body', $values['body']);
  $node->setPublished();
  $node->save();
}

// 5. Make the editor / formatter / filter markup observable: disable CSS/JS
// aggregation so the scenarios run against un-aggregated assets.
\Drupal::configFactory()->getEditable('system.performance')
  ->set('css.preprocess', FALSE)
  ->set('js.preprocess', FALSE)
  ->save();

drupal_flush_all_caches();

print "Ace Editor fixtures provisioned.\n";
