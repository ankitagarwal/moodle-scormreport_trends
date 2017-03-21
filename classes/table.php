<?php
// This file is part of SCORM trends report for Moodle
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
 * Core table class for scoremreport_trends
 *
 * @package    scormreport_trends
 * @copyright  2017 onwards Ankit Kumar Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace scormreport_trends;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/tablelib.php');

/**
 * Table log class for displaying logs.
 *
 * @package    scormreport_trends
 * @copyright  2017 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class table extends \flexible_table {

    /**
     * Sets up the table parameters.
     *
     * @param string $uniqueid unique id of form.
     */
    public function __construct($uniqueid) {
        global $PAGE;

        parent::__construct($uniqueid);

        $columns = array('question', 'element', 'value', 'freq');
        $headers = array(
            get_string('questioncount', 'scormreport_trends'),
            get_string('element', 'scormreport_trends'),
            get_string('value', 'scormreport_trends'),
            get_string('freq', 'scormreport_trends'));

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->define_baseurl($PAGE->url);

        // Don't show repeated data.
        $this->column_suppress('question');
        $this->column_suppress('element');

        $this->collapsible(false);
        $this->sortable(false);
        $this->pageable(false);
        $this->is_downloadable(false);
        $this->setup();
    }
}
