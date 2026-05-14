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

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('tool_aischema', get_string('pluginname', 'tool_aischema'));
    $ADMIN->add('development', $settings);

    $settings->add(new admin_setting_configtext(
        'tool_aischema/retention',
        get_string('setting:retention', 'tool_aischema'),
        get_string('setting:retention_desc', 'tool_aischema'),
        30,
        PARAM_INT
    ));

    $ADMIN->add('development', new admin_externalpage(
        'tool_aischema_export',
        get_string('export_title', 'tool_aischema'),
        new moodle_url('/admin/tool/aischema/index.php'),
        'tool/aischema:export'
    ));
}
