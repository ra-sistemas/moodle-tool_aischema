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
class consolidated_formatter {

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
        $o = '';
        $o .= "MOODLE DATABASE SCHEMA\n";
        $o .= "Generated: " . date('Y-m-d H:i:s T') . "\n";
        $o .= "Components: {$this->stats['components']} | Tables: {$this->stats['tables']} | ";
        $o .= "Fields: {$this->stats['fields']} | Foreign Keys: {$this->stats['foreignkeys']} | ";
        $o .= "Indexes: {$this->stats['indexes']}\n";
        $o .= "=" . str_repeat("=", 200) . "\n\n";

        $o .= "RELATIONSHIP INDEX\n";
        $o .= str_repeat("-", 200) . "\n";
        foreach ($this->relationships as $rel) {
            $fromf = implode(',', $rel['from_fields']);
            $tof = implode(',', $rel['to_fields']);
            $o .= "FK: {$rel['from_table']}.{$fromf} => {$rel['to_table']}.{$tof} [{$rel['type']}] ({$rel['from_component']} -> {$rel['to_component']})\n";
        }
        $o .= "\n";

        $o .= "TABLE DEFINITIONS\n";
        $o .= str_repeat("=", 200) . "\n\n";

        ksort($this->schema);
        foreach ($this->schema as $tablename => $table) {
            $o .= $this->format_table_entry($table);
        }

        return $o;
    }

    protected function format_table_entry(array $table): string {
        $o = '';
        $o .= "TABLE: {$table['name']}\n";
        $o .= "COMPONENT: {$table['component']}\n";
        if ($table['comment']) {
            $o .= "COMMENT: {$table['comment']}\n";
        }

        $incoming = [];
        $outgoing = [];
        foreach ($this->relationships as $rel) {
            if ($rel['to_table'] === $table['name']) {
                $incoming[] = $rel;
            }
            if ($rel['from_table'] === $table['name']) {
                $outgoing[] = $rel;
            }
        }

        if ($outgoing) {
            $parts = [];
            foreach ($outgoing as $r) {
                $parts[] = "{$r['to_table']}.{$r['to_fields'][0]} via {$r['from_fields'][0]}";
            }
            $o .= "REFERENCES: " . implode('; ', $parts) . "\n";
        }
        if ($incoming) {
            $parts = [];
            foreach ($incoming as $r) {
                $parts[] = "{$r['from_table']}.{$r['from_fields'][0]}";
            }
            $o .= "REFERENCED BY: " . implode('; ', $parts) . "\n";
        }

        $o .= "FIELDS:\n";
        foreach ($table['fields'] as $field) {
            $attrs = [];
            $attrs[] = $field['type'];
            if ($field['length']) {
                $attrs[] = "len={$field['length']}";
            }
            if ($field['notnull']) {
                $attrs[] = "NOT NULL";
            }
            if ($field['sequence']) {
                $attrs[] = "AUTO_INCREMENT";
            }
            if ($field['default'] !== null) {
                $attrs[] = "default={$field['default']}";
            }

            $pk = $this->is_pk($table, $field['name']);
            $fk = $this->is_fk($table['name'], $field['name']);
            if ($pk) $attrs[] = "PRIMARY_KEY";
            if ($fk) $attrs[] = "FOREIGN_KEY";

            $line = "  {$field['name']}: " . implode(' ', $attrs);
            if ($field['comment']) {
                $line .= " -- {$field['comment']}";
            }
            $o .= "{$line}\n";
        }

        if ($table['indexes']) {
            $o .= "INDEXES:\n";
            foreach ($table['indexes'] as $idx) {
                $unique = $idx['unique'] ? 'UNIQUE' : 'NON-UNIQUE';
                $o .= "  {$idx['name']}: {$unique} (" . implode(', ', $idx['fields']) . ")\n";
            }
        }

        $o .= str_repeat("-", 200) . "\n";
        return $o;
    }

    protected function is_pk(array $table, string $fieldname): bool {
        foreach ($table['keys'] as $key) {
            if ($key['type'] === 'primary' && in_array($fieldname, $key['fields'])) {
                return true;
            }
        }
        return false;
    }

    protected function is_fk(string $tablename, string $fieldname): bool {
        foreach ($this->relationships as $rel) {
            if ($rel['from_table'] === $tablename && in_array($fieldname, $rel['from_fields'])) {
                return true;
            }
        }
        return false;
    }
}
