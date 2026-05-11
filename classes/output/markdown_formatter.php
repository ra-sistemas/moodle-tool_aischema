<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace tool_aischema\output;

defined('MOODLE_INTERNAL') || die();

class markdown_formatter {

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
        $o .= "# Moodle Database Schema\n\n";
        $o .= "Generated: " . date('Y-m-d H:i:s T') . "\n\n";
        $o .= "## Statistics\n\n";
        $o .= "| Metric | Count |\n";
        $o .= "|--------|-------|\n";
        $o .= "| Components | {$this->stats['components']} |\n";
        $o .= "| Tables | {$this->stats['tables']} |\n";
        $o .= "| Fields | {$this->stats['fields']} |\n";
        $o .= "| Foreign Keys | {$this->stats['foreignkeys']} |\n";
        $o .= "| Indexes | {$this->stats['indexes']} |\n\n";

        $o .= "## Table Index\n\n";
        $bycomponent = [];
        foreach ($this->schema as $t) {
            $bycomponent[$t['component']][] = $t['name'];
        }
        ksort($bycomponent);
        foreach ($bycomponent as $comp => $tables) {
            $o .= "### {$comp}\n\n";
            sort($tables);
            foreach ($tables as $t) {
                $o .= "- [{$t}](#" . $this->anchor($t) . ")\n";
            }
            $o .= "\n";
        }

        $o .= "## Relationship Map\n\n";
        $o .= "| Source Table | Source Fields | Target Table | Target Fields | Key Name | Type |\n";
        $o .= "|-------------|--------------|-------------|--------------|----------|------|\n";
        foreach ($this->relationships as $rel) {
            $fromanchor = "[{$rel['from_table']}](#" . $this->anchor($rel['from_table']) . ")";
            $toanchor = "[{$rel['to_table']}](#" . $this->anchor($rel['to_table']) . ")";
            $fromfields = implode(', ', $rel['from_fields']);
            $tofields = implode(', ', $rel['to_fields']);
            $o .= "| {$fromanchor} | {$fromfields} | {$toanchor} | {$tofields} | {$rel['key_name']} | {$rel['type']} |\n";
        }
        $o .= "\n";

        $o .= "## Table Definitions\n\n";
        ksort($this->schema);
        foreach ($this->schema as $tablename => $table) {
            $o .= $this->format_table($table);
        }

        return $o;
    }

    protected function format_table(array $table): string {
        $o = '';
        $o .= "### `{$table['name']}`\n\n";
        if ($table['comment']) {
            $o .= "> {$table['comment']}\n\n";
        }
        $o .= "**Component:** `{$table['component']}`\n\n";

        $incoming = $this->get_incoming($table['name']);
        $outgoing = $this->get_outgoing($table['name']);
        if ($incoming || $outgoing) {
            $o .= "**Relationships:**\n";
            foreach ($outgoing as $rel) {
                $o .= "- **references** `{$rel['to_table']}` ({$rel['to_fields'][0]}) via `{$rel['from_fields'][0]}` [{$rel['type']}]\n";
            }
            foreach ($incoming as $rel) {
                $o .= "- **referenced by** `{$rel['from_table']}` ({$rel['from_fields'][0]}) [{$rel['type']}]\n";
            }
            $o .= "\n";
        }

        $o .= "#### Fields\n\n";
        $o .= "| Field | Type | Length | Not Null | Default | Sequence | Comment |\n";
        $o .= "|-------|------|--------|----------|---------|----------|--------|\n";
        foreach ($table['fields'] as $field) {
            $default = $field['default'] !== null ? (string)$field['default'] : 'NULL';
            $notnull = $field['notnull'] ? 'YES' : '';
            $sequence = $field['sequence'] ? 'AUTO' : '';
            $length = $field['length'] ?? '-';
            $comment = $field['comment'] ?: '-';
            $o .= "| `{$field['name']}` | {$field['type']} | {$length} | {$notnull} | `{$default}` | {$sequence} | {$comment} |\n";
        }
        $o .= "\n";

        if ($table['keys']) {
            $o .= "#### Keys\n\n";
            $o .= "| Key | Type | Fields | References |\n";
            $o .= "|-----|------|--------|------------|\n";
            foreach ($table['keys'] as $key) {
                $fields = implode(', ', $key['fields']);
                $ref = '';
                if ($key['reftable']) {
                    $ref = "`{$key['reftable']}` (" . implode(', ', $key['reffields']) . ')';
                }
                $o .= "| `{$key['name']}` | {$key['type']} | {$fields} | {$ref} |\n";
            }
            $o .= "\n";
        }

        if ($table['indexes']) {
            $o .= "#### Indexes\n\n";
            $o .= "| Index | Unique | Fields |\n";
            $o .= "|-------|--------|--------|\n";
            foreach ($table['indexes'] as $index) {
                $unique = $index['unique'] ? 'YES' : '';
                $fields = implode(', ', $index['fields']);
                $o .= "| `{$index['name']}` | {$unique} | {$fields} |\n";
            }
            $o .= "\n";
        }

        $o .= "---\n\n";
        return $o;
    }

    protected function get_incoming(string $table): array {
        $result = [];
        foreach ($this->relationships as $rel) {
            if ($rel['to_table'] === $table) {
                $result[] = $rel;
            }
        }
        return $result;
    }

    protected function get_outgoing(string $table): array {
        $result = [];
        foreach ($this->relationships as $rel) {
            if ($rel['from_table'] === $table) {
                $result[] = $rel;
            }
        }
        return $result;
    }

    protected function anchor(string $name): string {
        return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
    }
}
