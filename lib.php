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

function tool_aischema_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        send_file_not_found();
    }

    require_login();
    require_capability('tool/aischema:export', $context);

    if ($filearea !== 'export') {
        send_file_not_found();
    }

    $itemid = (int)array_shift($args);
    $filename = array_shift($args);

    if (empty($filename)) {
        send_file_not_found();
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'tool_aischema', 'export', $itemid, '/', $filename);

    if (!$file) {
        send_file_not_found();
    }

    \core\session\manager::write_close();
    send_stored_file($file, 0, 0, true, $options);
}
