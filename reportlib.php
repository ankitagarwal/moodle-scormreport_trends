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
 * Core Report class of graphs reporting plugin
 *
 * @package    scormreport_trends
 * @copyright  2013 Ankit Kumar Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns The maximum numbers of Questions associated with a Sco object
 *
 * @param int Sco ID
 * @return int an integer representing the question count
 */
function get_sco_question_count($scoid) {
    global $DB;
    $count = 0;
    $params = array();
    $select = "scoid = ? AND ";
    $select .= $DB->sql_like("element", "?", false);
    $params[] = $scoid;
    $params[] = "cmi.interactions_%.id";
    $rs = $DB->get_recordset_select("scorm_scoes_track", $select, $params, 'element');
    $keywords = array("cmi.interactions_", ".id");
    if ($rs->valid()) {
        foreach ($rs as $record) {
            $num = trim(str_ireplace($keywords, '', $record->element));
            if (is_numeric($num) && $num > $count) {
                $count = $num;
            }
        }
        // Done as interactions start at 0 (do only if we have something to report).
        $count++;
    }
    $rs->close(); // closing recordset
    return $count;
}

