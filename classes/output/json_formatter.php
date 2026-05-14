<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace tool_aischema\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Create model form.
 *
 * @package    tool_aischema
 * @copyright  2026 RA SISTEMAS - Davison Ramos <ramosdealmeidasistemas@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class json_formatter {

    protected array $schema;
    protected array $relationships;
    protected array $componentmap;
    protected array $stats;

    public function __construct(array $schema, array $relationships, array $componentmap, array $stats) {
        $this->schema = $schema;
        $this->relationships = $relationships;
        $this->componentmap = $componentmap;
        $this->stats = $stats;
    }

    public function export(): string {
        $data = [
            'metadata' => [
                'generated' => date('c'),
                'generator' => 'tool_aischema',
                'moodle_version' => $this->get_moodle_version(),
            ],
            'statistics' => $this->stats,
            'components' => $this->build_component_index(),
            'tables' => $this->schema,
            'relationships' => $this->build_relationship_map(),
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    protected function build_component_index(): array {
        $components = [];
        foreach ($this->schema as $table) {
            $comp = $table['component'];
            if (!isset($components[$comp])) {
                $components[$comp] = [];
            }
            $components[$comp][] = $table['name'];
        }
        ksort($components);
        foreach ($components as &$tables) {
            sort($tables);
        }
        return $components;
    }

    protected function build_relationship_map(): array {
        $bytable = [];
        foreach ($this->relationships as $rel) {
            $from = $rel['from_table'];
            $to = $rel['to_table'];
            if (!isset($bytable[$from])) {
                $bytable[$from] = ['outgoing' => [], 'incoming' => []];
            }
            if (!isset($bytable[$to])) {
                $bytable[$to] = ['outgoing' => [], 'incoming' => []];
            }
            $bytable[$from]['outgoing'][] = [
                'to_table' => $to,
                'from_fields' => $rel['from_fields'],
                'to_fields' => $rel['to_fields'],
                'key_name' => $rel['key_name'],
                'type' => $rel['type'],
            ];
            $bytable[$to]['incoming'][] = [
                'from_table' => $from,
                'from_fields' => $rel['from_fields'],
                'to_fields' => $rel['to_fields'],
                'key_name' => $rel['key_name'],
                'type' => $rel['type'],
            ];
        }
        ksort($bytable);
        return $bytable;
    }

    protected function get_moodle_version(): string {
        global $CFG;
        return $CFG->version ?? 'unknown';
    }
}
