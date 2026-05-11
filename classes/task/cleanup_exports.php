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

namespace tool_aischema\task;

defined('MOODLE_INTERNAL') || die();

class cleanup_exports extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('task:cleanup', 'tool_aischema');
    }

    public function execute(): void {
        $retentiondays = (int)get_config('tool_aischema', 'retention');

        if ($retentiondays <= 0) {
            mtrace('[tool_aischema] File retention is set to indefinite (0 days). Skipping cleanup.');
            return;
        }

        $cutoff = time() - ($retentiondays * DAYSECS);
        $context = \context_system::instance();
        $fs = get_file_storage();

        $files = $fs->get_area_files($context->id, 'tool_aischema', 'export', false, 'itemid ASC', false);

        $deleted = 0;
        foreach ($files as $file) {
            if ($file->get_timecreated() < $cutoff) {
                mtrace("[tool_aischema] Deleting expired export: {$file->get_filename()} " .
                    "(created " . userdate($file->get_timecreated()) . ")");
                $file->delete();
                $deleted++;
            }
        }

        mtrace("[tool_aischema] Cleanup complete. Deleted {$deleted} expired export(s).");
    }
}
