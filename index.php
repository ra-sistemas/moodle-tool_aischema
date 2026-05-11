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

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
$context = context_system::instance();
require_capability('tool/aischema:export', $context);

admin_externalpage_setup('tool_aischema_export');

$PAGE->set_url(new moodle_url('/admin/tool/aischema/index.php'));
$PAGE->set_title(get_string('export_title', 'tool_aischema'));
$PAGE->set_pagelayout('admin');

$action = optional_param('action', '', PARAM_ALPHA);
$pluginfilter = optional_param('plugin', 'all', PARAM_ALPHANUMEXT);
$deleteid = optional_param('deleteid', 0, PARAM_INT);

$fs = get_file_storage();

if ($action === 'delete' && $deleteid && confirm_sesskey()) {
    $file = $fs->get_file_by_id($deleteid);
    if ($file && $file->get_component() === 'tool_aischema' && $file->get_filearea() === 'export') {
        $file->delete();
        \core\notification::success(get_string('export_deleted', 'tool_aischema'));
    }
    redirect(new moodle_url('/admin/tool/aischema/index.php'));
}

if ($action === 'export' && confirm_sesskey()) {
    $extractor = new \tool_aischema\schema_extractor();
    $extractor->extract($pluginfilter === 'all' ? null : $pluginfilter);

    $schema = $extractor->get_schema();
    $relationships = $extractor->get_relationships();
    $componentmap = $extractor->get_component_map();
    $stats = $extractor->get_stats();

    if (empty($schema)) {
        \core\notification::warning(get_string('export_no_schema', 'tool_aischema'));
        redirect(new moodle_url('/admin/tool/aischema/index.php'));
    }

    $mdfmt = new \tool_aischema\output\markdown_formatter($schema, $relationships, $componentmap, $stats);
    $jsonfmt = new \tool_aischema\output\json_formatter($schema, $relationships, $componentmap, $stats);
    $mermaidfmt = new \tool_aischema\output\mermaid_formatter($schema, $relationships, $componentmap, $stats);
    $consfmt = new \tool_aischema\output\consolidated_formatter($schema, $relationships, $componentmap, $stats);

    $zipcontent = create_zip_from_strings([
        'moodle_schema.md' => $mdfmt->export(),
        'moodle_schema.json' => $jsonfmt->export(),
        'moodle_schema.mmd' => $mermaidfmt->export(),
        'moodle_schema.txt' => $consfmt->export(),
    ]);

    $now = time();
    $datestr = userdate($now, '%Y%m%d_%H%M');
    $scopetag = ($pluginfilter === 'all') ? 'full' : $pluginfilter;
    $filename = "moodle_schema_{$scopetag}_{$datestr}.zip";

    $filerecord = [
        'contextid' => $context->id,
        'component' => 'tool_aischema',
        'filearea' => 'export',
        'itemid' => $now,
        'filepath' => '/',
        'filename' => $filename,
    ];

    $storedfile = $fs->create_file_from_string($filerecord, $zipcontent);

    \core\notification::success(get_string('export_success', 'tool_aischema') .
        ' (' . display_size($storedfile->get_filesize()) .
        ', ' . $stats['tables'] . ' tables, ' .
        $stats['foreignkeys'] . ' FK relationships)');

    redirect(new moodle_url('/admin/tool/aischema/index.php'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('export_title', 'tool_aischema'));

echo html_writer::tag('p', get_string('export_description', 'tool_aischema'));

echo $OUTPUT->heading(get_string('export_heading_new', 'tool_aischema'), 3);

echo html_writer::start_tag('div', ['class' => 'container-fluid']);
echo html_writer::start_tag('div', ['class' => 'row mb-3 align-items-center']);
echo html_writer::start_tag('div', ['class' => 'col-auto']);
echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => new moodle_url('/admin/tool/aischema/index.php'),
    'class' => 'form-inline',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'export']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

echo html_writer::tag('label', get_string('export_plugin', 'tool_aischema'),
    ['for' => 'id_plugin_filter', 'class' => 'mr-2']);
echo html_writer::select(
    [
        'all' => get_string('export_plugin_all', 'tool_aischema'),
        'core' => get_string('export_plugin_core', 'tool_aischema'),
        'plugins' => get_string('export_plugin_plugins', 'tool_aischema'),
    ],
    'plugin',
    'all',
    null,
    ['id' => 'id_plugin_filter', 'class' => 'mr-3']
);

echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'class' => 'btn btn-primary',
    'value' => get_string('export_button', 'tool_aischema'),
]);
echo html_writer::end_tag('form');
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['class' => 'row mb-2']);
echo html_writer::tag('div', html_writer::tag('strong', get_string('export_zip_contents', 'tool_aischema')),
    ['class' => 'col-12']);
echo html_writer::end_tag('div');

$formatdescs = [
    'moodle_schema.md' => get_string('format_markdown_desc', 'tool_aischema'),
    'moodle_schema.json' => get_string('format_json_desc', 'tool_aischema'),
    'moodle_schema.mmd' => get_string('format_mermaid_desc', 'tool_aischema'),
    'moodle_schema.txt' => get_string('format_consolidated_desc', 'tool_aischema'),
];
foreach ($formatdescs as $fname => $desc) {
    echo html_writer::start_tag('div', ['class' => 'row mb-1']);
    echo html_writer::tag('div', html_writer::tag('code', $fname), ['class' => 'col-md-2']);
    echo html_writer::tag('div', $desc, ['class' => 'col-md-10 text-muted small']);
    echo html_writer::end_tag('div');
}

echo html_writer::end_tag('div');

$files = $fs->get_area_files($context->id, 'tool_aischema', 'export', false, 'itemid DESC', false);

echo $OUTPUT->heading(get_string('export_heading_history', 'tool_aischema'), 3);

if (empty($files)) {
    echo html_writer::tag('p', get_string('export_none', 'tool_aischema'), ['class' => 'text-muted']);
} else {
    $table = new html_table();
    $table->head = [
        get_string('export_col_filename', 'tool_aischema'),
        get_string('export_col_plugin', 'tool_aischema'),
        get_string('export_col_date', 'tool_aischema'),
        get_string('export_col_size', 'tool_aischema'),
        get_string('export_col_expires', 'tool_aischema'),
        get_string('export_col_actions', 'tool_aischema'),
    ];
    $table->attributes['class'] = 'table table-striped';
    $table->data = [];

    $retentiondays = (int)get_config('tool_aischema', 'retention');
    if ($retentiondays <= 0) {
        $retentiondays = 30;
    }

    foreach ($files as $file) {
        $filename = $file->get_filename();
        $itemid = $file->get_itemid();
        $timecreated = $file->get_timecreated();

        $downloadurl = moodle_url::make_pluginfile_url(
            $context->id,
            'tool_aischema',
            'export',
            $itemid,
            '/',
            $filename
        );

        $downloadlink = html_writer::link($downloadurl, $filename);
        $filesize = display_size($file->get_filesize());
        $date = userdate($timecreated, get_string('strftimedatetime', 'langconfig'));

        $scopetag = setermine_scope_tag($filename);
        if ($scopetag === 'full') {
        $scopestr = get_string('export_filesscope', 'tool_aischema');
    } elseif ($scopetag === 'core') {
        $scopestr = get_string('export_filesscope_core', 'tool_aischema');
    } else {
        $scopestr = get_string('export_filesscope_plugins', 'tool_aischema');
    }

        $expires = userdate($timecreated + ($retentiondays * DAYSECS), get_string('strftimedatetime', 'langconfig'));

        $deleteurl = new moodle_url('/admin/tool/aischema/index.php', [
            'action' => 'delete',
            'deleteid' => $file->get_id(),
            'sesskey' => sesskey(),
        ]);
        $deletelink = html_writer::link($deleteurl, get_string('export_delete', 'tool_aischema'), [
            'class' => 'text-danger',
            'onclick' => 'return confirm("' . s(get_string('export_delete_confirm', 'tool_aischema')) . '");',
        ]);

        $table->data[] = [$downloadlink, $scopestr, $date, $filesize, $expires, $deletelink];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();

function create_zip_from_strings(array $files): string {
    $tempfile = tempnam(sys_get_temp_dir(), 'aischema_');
    $zip = new ZipArchive();
    $zip->open($tempfile, ZipArchive::OVERWRITE | ZipArchive::CREATE);
    foreach ($files as $name => $content) {
        $zip->addFromString($name, $content);
    }
    $zip->close();
    $data = file_get_contents($tempfile);
    unlink($tempfile);
    return $data;
}

function setermine_scope_tag(string $filename): string {
    if (strpos($filename, '_full_') !== false) {
        return 'full';
    }
    if (strpos($filename, '_core_') !== false) {
        return 'core';
    }
    $parts = explode('_', $filename);
    if (count($parts) >= 4) {
        return $parts[3];
    }
    return 'full';
}
