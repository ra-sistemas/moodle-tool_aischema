<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Create model form.
 *
 * @package    tool_aischema
 * @copyright  2026 RA SISTEMAS - Davison Ramos <ramosdealmeidasistemas@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

$usage = "
Export Moodle database schema in AI-optimised formats.

Usage:
    php admin/tool/aischema/cli/export_schema.php [options]

Options:
    --format=FORMAT    Export format: markdown, json, mermaid, consolidated, all
                       Default: all
    --plugin=PLUGIN    Filter to a specific plugin (e.g. mod_forum, core).
                       Default: all plugins
    --output=PATH      Output directory path. Default: dataroot/aischema/
    --stdout           Write to stdout instead of a file
    -h, --help         Print this help

Examples:
    php admin/tool/aischema/cli/export_schema.php --format=json
    php admin/tool/aischema/cli/export_schema.php --format=all --plugin=mod_forum
    php admin/tool/aischema/cli/export_schema.php --format=markdown --stdout
    php admin/tool/aischema/cli/export_schema.php --format=consolidated --output=/tmp/schema
";

list($options, $unrecognized) = cli_get_params(
    ['format' => 'all', 'plugin' => 'all', 'output' => '', 'stdout' => false, 'help' => false],
    ['h' => 'help']
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("Unrecognized options:\n  {$unrecognized}\n\nPlease use --help.");
}

if ($options['help']) {
    echo $usage;
    exit(0);
}

$format = $options['format'];
$pluginfilter = $options['plugin'];
$usestdout = $options['stdout'];
$outputdir = $options['output'];

$validformats = ['markdown', 'json', 'mermaid', 'consolidated', 'all'];
if (!in_array($format, $validformats)) {
    cli_error("Invalid format '{$format}'. Valid: " . implode(', ', $validformats));
}

$validplugins = ['all', 'core', 'plugins'];
if (!in_array($pluginfilter, $validplugins)) {
    cli_error("Invalid plugin filter '{$pluginfilter}'. Valid: " . implode(', ', $validplugins));
}

cli_heading('AI Schema Export');
echo "Extracting schema";

if ($pluginfilter === 'all') {
    echo " (all plugins)...\n";
} elseif ($pluginfilter === 'core') {
    echo " (core only)...\n";
} elseif ($pluginfilter === 'plugins') {
    echo " (plugins only)...\n";
} else {
    echo " for plugin: {$pluginfilter}...\n";
}

$extractor = new \tool_aischema\schema_extractor();
if ($pluginfilter === 'all') {
    $extractor->extract(null);
} elseif ($pluginfilter === 'plugins') {
    $extractor->extract_plugins_only();
} elseif ($pluginfilter === 'core') {
    $extractor->extract_core_only();
} else {
    $extractor->extract($pluginfilter);
}

$schema = $extractor->get_schema();
$relationships = $extractor->get_relationships();
$componentmap = $extractor->get_component_map();
$stats = $extractor->get_stats();

echo "  Components: {$stats['components']}\n";
echo "  Tables:     {$stats['tables']}\n";
echo "  Fields:     {$stats['fields']}\n";
echo "  Foreign keys: {$stats['foreignkeys']}\n";
echo "  Indexes:    {$stats['indexes']}\n\n";

$formatters = [];

if ($format === 'all' || $format === 'markdown') {
    $formatters['moodle_schema.md'] = new \tool_aischema\output\markdown_formatter($schema, $relationships, $componentmap, $stats);
}
if ($format === 'all' || $format === 'json') {
    $formatters['moodle_schema.json'] = new \tool_aischema\output\json_formatter($schema, $relationships, $componentmap, $stats);
}
if ($format === 'all' || $format === 'mermaid') {
    $formatters['moodle_schema.mmd'] = new \tool_aischema\output\mermaid_formatter($schema, $relationships, $componentmap, $stats);
}
if ($format === 'all' || $format === 'consolidated') {
    $formatters['moodle_schema.txt'] = new \tool_aischema\output\consolidated_formatter($schema, $relationships, $componentmap, $stats);
}

if ($usestdout && count($formatters) === 1) {
    $formatter = reset($formatters);
    echo $formatter->export();
    exit(0);
}

if (empty($outputdir)) {
    $outputdir = $CFG->dataroot . '/aischema';
}

if (!is_dir($outputdir)) {
    mkdir($outputdir, 0755, true);
}

if ($format === 'all' && !$usestdout) {
    $files = [];
    foreach ($formatters as $filename => $formatter) {
        $files[$filename] = $formatter->export();
    }

    $zipfile = $outputdir . '/moodle_schema_export.zip';
    $zip = new ZipArchive();
    if ($zip->open($zipfile, ZipArchive::OVERWRITE | ZipArchive::CREATE) !== true) {
        cli_error("Cannot create ZIP file: {$zipfile}");
    }
    foreach ($files as $name => $content) {
        $zip->addFromString($name, $content);
        echo "  Added: {$name} (" . number_format(strlen($content)) . " bytes)\n";
    }
    $zip->close();
    echo "\n  ZIP created: {$zipfile}\n";
} else {
    foreach ($formatters as $filename => $formatter) {
        $filepath = rtrim($outputdir, '/') . '/' . $filename;
        $content = $formatter->export();
        file_put_contents($filepath, $content);
        echo "  Written: {$filepath} (" . number_format(strlen($content)) . " bytes)\n";
    }
}

echo "\nDone.\n";
exit(0);
