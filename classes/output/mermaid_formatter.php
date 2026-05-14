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
class mermaid_formatter {

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
        $o = "%%{init: {'theme': 'base', 'themeVariables': {'fontSize': '12px'}}}%%\n";
        $o .= "erDiagram\n\n";

        ksort($this->schema);
        foreach ($this->schema as $tablename => $table) {
            $o .= $this->format_table($tablename, $table);
        }

        $o .= $this->format_relationships();

        return $o;
    }

    protected function format_table(string $tablename, array $table): string {
        $o = '    "' . $tablename . '" {' . "\n";

        $pkfields = $this->get_primary_key_fields($table);

        foreach ($table['fields'] as $field) {
            $pk = in_array($field['name'], $pkfields) ? 'PK' : '';
            $fk = $this->is_fk_field($tablename, $field['name']) ? 'FK' : '';
            $uk = $this->is_unique_field($table, $field['name']) ? 'UK' : '';
            $tags = array_filter([$pk, $fk, $uk]);
            $tagstr = $tags ? ' "' . implode(',', $tags) . '"' : '';

            $type = $this->map_type($field['type'], $field['length']);
            $comment = $field['comment'] ? ' "' . str_replace('"', "'", $field['comment']) . '"' : '';
            $o .= "        {$type} {$field['name']}{$tagstr}{$comment}\n";
        }

        $o .= "    }\n\n";
        return $o;
    }

    protected function format_relationships(): string {
        $o = "    %% Relationships\n\n";
        $seen = [];

        foreach ($this->relationships as $rel) {
            $from = $rel['from_table'];
            $to = $rel['to_table'];
            $key = "{$from}->{$to}";
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $fromfields = implode(', ', $rel['from_fields']);
            $tofields = implode(', ', $rel['to_fields']);
            $label = "{$fromfields} -> {$tofields}";

            $o .= "    \"{$from}\" }o--|| \"{$to}\" : \"{$label}\"\n";
        }

        return $o;
    }

    protected function get_primary_key_fields(array $table): array {
        foreach ($table['keys'] as $key) {
            if ($key['type'] === 'primary') {
                return $key['fields'];
            }
        }
        return [];
    }

    protected function is_fk_field(string $tablename, string $fieldname): bool {
        foreach ($this->relationships as $rel) {
            if ($rel['from_table'] === $tablename && in_array($fieldname, $rel['from_fields'])) {
                return true;
            }
        }
        return false;
    }

    protected function is_unique_field(array $table, string $fieldname): bool {
        foreach ($table['keys'] as $key) {
            if (($key['type'] === 'unique' || $key['type'] === 'foreign_unique') && in_array($fieldname, $key['fields'])) {
                return true;
            }
        }
        return false;
    }

    protected function map_type(string $type, $length): string {
        $map = [
            'int' => "INT{$this->paren($length)}",
            'number' => "NUM{$this->paren($length)}",
            'float' => "FLOAT",
            'char' => "VARCHAR{$this->paren($length)}",
            'text' => "TEXT",
            'binary' => "BLOB",
            'datetime' => "DATETIME",
            'timestamp' => "TIMESTAMP",
        ];
        return $map[$type] ?? strtoupper($type);
    }

    protected function paren($val): string {
        return $val ? "({$val})" : '';
    }

}

