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

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'AI Schema Export';
$string['privacy:metadata'] = 'The AI Schema Export plugin does not store any personal data.';

$string['export_title'] = 'Export Database Schema for AI';
$string['export_description'] = 'Export Moodle\'s complete database schema in formats optimised for AI agents. Exports are stored as ZIP archives containing Markdown, JSON, Mermaid ERD, and consolidated text representations of every table, field, foreign key relationship, and index across all installed plugins.';
$string['export_plugin'] = 'Plugin scope';
$string['export_plugin_all'] = 'All plugins (full schema)';
$string['export_plugin_core'] = 'Core only';
$string['export_plugin_plugins'] = 'Plugins only (exclude core)';
$string['export_button'] = 'Generate export';
$string['export_success'] = 'Schema export generated successfully.';
$string['export_no_schema'] = 'No database schema files found.';
$string['export_stats'] = 'Export statistics';
$string['export_stat_tables'] = 'Total tables';
$string['export_stat_fields'] = 'Total fields';
$string['export_stat_foreignkeys'] = 'Foreign key relationships';
$string['export_stat_indexes'] = 'Total indexes';
$string['export_stat_components'] = 'Components scanned';
$string['export_heading_new'] = 'Generate new export';
$string['export_heading_history'] = 'Previous exports';
$string['export_col_filename'] = 'File';
$string['export_col_date'] = 'Created';
$string['export_col_size'] = 'Size';
$string['export_col_expires'] = 'Expires';
$string['export_col_plugin'] = 'Scope';
$string['export_col_actions'] = 'Actions';
$string['export_download'] = 'Download';
$string['export_delete'] = 'Delete';
$string['export_delete_confirm'] = 'Are you sure you want to delete this export?';
$string['export_deleted'] = 'Export deleted.';
$string['export_none'] = 'No exports yet. Click "Generate export" above to create one.';
$string['export_filesscope'] = 'all plugins';
$string['export_filesscope_core'] = 'core only';
$string['export_filesscope_plugins'] = 'plugins only';
$string['export_content_md'] = 'Markdown documentation';
$string['export_content_json'] = 'JSON structured data';
$string['export_content_mmd'] = 'Mermaid ERD graph';
$string['export_content_txt'] = 'Consolidated text';
$string['export_zip_contents'] = 'This ZIP contains 4 files:';

$string['setting:retention'] = 'File retention (days)';
$string['setting:retention_desc'] = 'Number of days to keep exported schema files. After this period, files are automatically deleted by the scheduled cleanup task. Set to 0 to keep files indefinitely.';
$string['task:cleanup'] = 'Clean up expired schema exports';

$string['format_markdown_desc'] = 'Human-readable Markdown documentation with full table definitions, field details, key constraints and indexes. Best for AI full-text search and RAG pipelines.';
$string['format_json_desc'] = 'Structured JSON with nested table/field/key/index objects and a global relationship map. Best for programmatic AI consumption.';
$string['format_mermaid_desc'] = 'Mermaid Entity-Relationship Diagram syntax. Renders as a visual node graph showing tables as entities and foreign keys as edges. Best for AI spatial reasoning about schema topology.';
$string['format_consolidated_desc'] = 'Single flattened text file optimised for embedding. Every table is described in a compact, keyword-rich paragraph. Best for vector similarity search and small-context AI models.';
