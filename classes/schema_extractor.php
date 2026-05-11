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

namespace tool_aischema;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/ddllib.php');
require_once($CFG->libdir . '/adminlib.php');

class schema_extractor {

    protected array $schema = [];
    protected array $relationships = [];
    protected array $componentmap = [];
    protected array $stats = [
        'components' => 0,
        'tables' => 0,
        'fields' => 0,
        'foreignkeys' => 0,
        'indexes' => 0,
    ];

    public function extract(?string $pluginfilter = null): self {
        $this->schema = [];
        $this->relationships = [];
        $this->componentmap = [];
        $this->stats = ['components' => 0, 'tables' => 0, 'fields' => 0, 'foreignkeys' => 0, 'indexes' => 0];

        $dbdirs = $this->get_db_directories_filtered($pluginfilter);

        foreach ($dbdirs as $component => $dbdir) {
            $installfile = $dbdir . '/install.xml';
            if (!file_exists($installfile)) {
                continue;
            }

            $xmldbfile = new \xmldb_file($installfile);
            if (!$xmldbfile->loadXMLStructure()) {
                continue;
            }

            $structure = $xmldbfile->getStructure();
            if (!$structure) {
                continue;
            }

            $this->stats['components']++;
            $this->process_structure($structure, $component);
        }

        $this->build_relationship_index();
        return $this;
    }

    public function extract_plugins_only(): self {
        $this->schema = [];
        $this->relationships = [];
        $this->componentmap = [];
        $this->stats = ['components' => 0, 'tables' => 0, 'fields' => 0, 'foreignkeys' => 0, 'indexes' => 0];

        $dbdirs = $this->get_db_directories_filtered(null);

        foreach ($dbdirs as $component => $dbdir) {
            $installfile = $dbdir . '/install.xml';
            if (!file_exists($installfile)) {
                continue;
            }

            $xmldbfile = new \xmldb_file($installfile);
            if (!$xmldbfile->loadXMLStructure()) {
                continue;
            }

            $structure = $xmldbfile->getStructure();
            if (!$structure) {
                continue;
            }

            if ($component === 'core') {
                continue;
            }

            $this->stats['components']++;
            $this->process_structure($structure, $component);
        }

        $this->build_relationship_index();
        return $this;
    }

    public function extract_core_only(): self {
        $this->schema = [];
        $this->relationships = [];
        $this->componentmap = [];
        $this->stats = ['components' => 0, 'tables' => 0, 'fields' => 0, 'foreignkeys' => 0, 'indexes' => 0];

        $dbdirs = $this->get_db_directories_filtered(null);

        foreach ($dbdirs as $component => $dbdir) {
            $installfile = $dbdir . '/install.xml';
            if (!file_exists($installfile)) {
                continue;
            }

            $xmldbfile = new \xmldb_file($installfile);
            if (!$xmldbfile->loadXMLStructure()) {
                continue;
            }

            $structure = $xmldbfile->getStructure();
            if (!$structure) {
                continue;
            }

            if ($component !== 'core') {
                continue;
            }

            $this->stats['components']++;
            $this->process_structure($structure, $component);
        }

        $this->build_relationship_index();
        return $this;
    }

    protected function get_db_directories_filtered(?string $pluginfilter = null): array {
        global $CFG;

        $result = [];
        $dbdirs = get_db_directories();

        foreach ($dbdirs as $dbdir) {
            $component = $this->resolve_component($dbdir, $CFG->dirroot);

            if ($pluginfilter !== null && $pluginfilter !== '') {
                if ($pluginfilter === 'core' && $component !== 'core') {
                    continue;
                }
                if ($pluginfilter !== 'core' && $component !== $pluginfilter && strpos($component, $pluginfilter) !== 0) {
                    continue;
                }
            }

            $result[$component] = $dbdir;
        }

        return $result;
    }

    protected function resolve_component(string $dbdir, string $dirroot): string {
        static $typemap = null;

        if ($typemap === null) {
            $typemap = [];
            foreach (\core_component::get_plugin_types() as $type => $fulldir) {
                $typemap[$fulldir] = $type;
            }
            uksort($typemap, function($a, $b) {
                return strlen($b) - strlen($a);
            });
        }

        $normalized = str_replace('\\', '/', $dbdir);
        $normalizedroot = str_replace('\\', '/', $dirroot);

        if (strpos($normalized, $normalizedroot . '/lib/db') === 0) {
            return 'core';
        }

        foreach ($typemap as $fulldir => $type) {
            $normalizedtype = str_replace('\\', '/', $fulldir);
            $prefix = $normalizedtype . '/';
            if (strpos($normalized, $prefix) === 0) {
                $remainder = substr($normalized, strlen($prefix));
                $remainder = preg_replace('#/db$#', '', $remainder);
                return $type . '_' . $remainder;
            }
        }

        return 'unknown_' . md5($dbdir);
    }

    protected function process_structure(\xmldb_structure $structure, string $component): void {
        $tables = $structure->getTables();

        foreach ($tables as $table) {
            $tablename = $table->getName();
            $this->componentmap[$tablename] = $component;
            $this->stats['tables']++;

            $tabledata = [
                'name' => $tablename,
                'component' => $component,
                'comment' => $table->getComment() ?? '',
                'fields' => [],
                'keys' => [],
                'indexes' => [],
            ];

            foreach ($table->getFields() as $field) {
                $this->stats['fields']++;
                $fielddata = [
                    'name' => $field->getName(),
                    'type' => $this->resolve_type_name($field->getType()),
                    'length' => $field->getLength(),
                    'decimals' => $field->getDecimals(),
                    'notnull' => $field->getNotNull(),
                    'sequence' => $field->getSequence(),
                    'default' => $field->getDefault(),
                    'comment' => $field->getComment() ?? '',
                ];
                $tabledata['fields'][$field->getName()] = $fielddata;
            }

            foreach ($table->getKeys() as $key) {
                $keydata = [
                    'name' => $key->getName(),
                    'type' => $this->resolve_key_type_name($key->getType()),
                    'fields' => $key->getFields(),
                    'reftable' => $key->getRefTable(),
                    'reffields' => $key->getRefFields(),
                ];
                $tabledata['keys'][$key->getName()] = $keydata;

                if ($key->getType() === XMLDB_KEY_FOREIGN || $key->getType() === XMLDB_KEY_FOREIGN_UNIQUE) {
                    $this->stats['foreignkeys']++;
                }
            }

            foreach ($table->getIndexes() as $index) {
                $this->stats['indexes']++;
                $indexdata = [
                    'name' => $index->getName(),
                    'unique' => $index->getUnique(),
                    'fields' => $index->getFields(),
                ];
                $tabledata['indexes'][$index->getName()] = $indexdata;
            }

            $this->schema[$tablename] = $tabledata;
        }
    }

    protected function build_relationship_index(): void {
        $this->relationships = [];

        foreach ($this->schema as $tablename => $tabledata) {
            foreach ($tabledata['keys'] as $key) {
                if ($key['type'] === 'foreign' || $key['type'] === 'foreign_unique') {
                    $reftable = $key['reftable'];
                    if (isset($this->schema[$reftable])) {
                        $this->relationships[] = [
                            'from_table' => $tablename,
                            'from_fields' => $key['fields'],
                            'to_table' => $reftable,
                            'to_fields' => $key['reffields'],
                            'key_name' => $key['name'],
                            'type' => $key['type'],
                            'from_component' => $tabledata['component'],
                            'to_component' => $this->schema[$reftable]['component'] ?? 'unknown',
                        ];
                    }
                }
            }
        }
    }

    protected function resolve_type_name(int $type): string {
        $map = [
            XMLDB_TYPE_INTEGER => 'int',
            XMLDB_TYPE_NUMBER => 'number',
            XMLDB_TYPE_FLOAT => 'float',
            XMLDB_TYPE_CHAR => 'char',
            XMLDB_TYPE_TEXT => 'text',
            XMLDB_TYPE_BINARY => 'binary',
            XMLDB_TYPE_DATETIME => 'datetime',
            XMLDB_TYPE_TIMESTAMP => 'timestamp',
        ];
        return $map[$type] ?? 'unknown';
    }

    protected function resolve_key_type_name(int $type): string {
        $map = [
            XMLDB_KEY_PRIMARY => 'primary',
            XMLDB_KEY_UNIQUE => 'unique',
            XMLDB_KEY_FOREIGN => 'foreign',
            XMLDB_KEY_CHECK => 'check',
            XMLDB_KEY_FOREIGN_UNIQUE => 'foreign_unique',
        ];
        return $map[$type] ?? 'unknown';
    }

    public function get_schema(): array {
        return $this->schema;
    }

    public function get_relationships(): array {
        return $this->relationships;
    }

    public function get_component_map(): array {
        return $this->componentmap;
    }

    public function get_stats(): array {
        return $this->stats;
    }

    public function get_tables_by_component(): array {
        $result = [];
        foreach ($this->schema as $tablename => $tabledata) {
            $component = $tabledata['component'];
            if (!isset($result[$component])) {
                $result[$component] = [];
            }
            $result[$component][] = $tablename;
        }
        ksort($result);
        return $result;
    }

    public function get_incoming_relationships(string $tablename): array {
        $incoming = [];
        foreach ($this->relationships as $rel) {
            if ($rel['to_table'] === $tablename) {
                $incoming[] = $rel;
            }
        }
        return $incoming;
    }

    public function get_outgoing_relationships(string $tablename): array {
        $outgoing = [];
        foreach ($this->relationships as $rel) {
            if ($rel['from_table'] === $tablename) {
                $outgoing[] = $rel;
            }
        }
        return $outgoing;
    }
}
