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
 * Core Report class of graphs reporting plugin
 *
 * @package    scormreport_trends
 * @copyright  2012 Ankit Kumar Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Main class for the trends report
 *
 * @package    scormreport_trends
 * @copyright  2012 Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class scorm_trends_report extends scorm_default_report {
    /**
     * Displays the trends report
     *
     * @param stdClass $scorm full SCORM object
     * @param stdClass $cm - full course_module object
     * @param stdClass $course - full course object
     * @param string $download - type of download being requested
     * @return bool true on success
     */
    function display($scorm, $cm, $course, $download) {
        global $DB, $OUTPUT, $PAGE;
        $contextmodule = context_module::instance($cm->id);
        $scoes = $DB->get_records('scorm_scoes', array("scorm"=>$scorm->id), 'id');

        // Groups are being used, Display a form to select current group
        if ($groupmode = groups_get_activity_groupmode($cm)) {
                groups_print_activity_menu($cm, new moodle_url($PAGE->url));
        }

        // find out current group
        $currentgroup = groups_get_activity_group($cm, true);

        // Group Check
        $nostudents = false;
        if (empty($currentgroup)) {
            // all users who can attempt scoes
            if (!$students = get_users_by_capability($contextmodule, 'mod/scorm:savetrack', '', '', '', '', '', '', false)) {
                echo $OUTPUT->notification(get_string('nostudentsyet'));
                $nostudents = true;
                $allowedlist = '';
            } else {
                $allowedlist = array_keys($students);
            }
        } else {
            // all users who can attempt scoes and who are in the currently selected group
            if (!$groupstudents = get_users_by_capability($contextmodule, 'mod/scorm:savetrack', '', '', '', '', $currentgroup, '', false)) {
                echo $OUTPUT->notification(get_string('nostudentsingroup'));
                $nostudents = true;
                $groupstudents = array();
            }
            $allowedlist = array_keys($groupstudents);
        }
        // Do this only if we have students to report
        if(!$nostudents) {

            $params = array();
            list($usql, $params) = $DB->get_in_or_equal($allowedlist);


            // Construct the SQL
            $select = 'SELECT DISTINCT '.$DB->sql_concat('st.userid', '\'#\'', 'COALESCE(st.attempt, 0)').' AS uniqueid, ';
            $select .= 'st.userid AS userid, st.scormid AS scormid, st.attempt AS attempt, st.scoid AS scoid ';
            $from = 'FROM {scorm_scoes_track} st ';
            $where = ' WHERE st.userid ' .$usql. ' and st.scoid = ?';

            foreach ($scoes as $sco) {
                if ($sco->launch!='') {
                    echo $OUTPUT->heading($sco->title);
                    $sqlargs = array_merge($params, array($sco->id));
                    $attempts = $DB->get_records_sql($select.$from.$where, $sqlargs);
                    // Determine maximum number to loop through
                    $loop = get_sco_question_count($sco->id, $attempts);

                    $columns = array('question', 'element', 'value', 'freq');
                    $headers = array(
                        get_string('questioncount', 'scormreport_trends'),
                        get_string('element', 'scormreport_trends'),
                        get_string('value', 'scormreport_trends'),
                        get_string('freq', 'scormreport_trends'));

                    $table = new flexible_table('mod-scorm-trends-report-'.$sco->id);

                    $table->define_columns($columns);
                    $table->define_headers($headers);
                    $table->define_baseurl($PAGE->url);
                    $table->setup();

                    for ($i = 0; $i < $loop; $i++) {
                        $rowdata = array(
                            'type' => array(array(), array()),
                            'student_response' => array(array(), array()),
                            'result' => array(array(), array()));
                        foreach ($attempts as $attempt) {
                            if ($trackdata = scorm_get_tracks($sco->id, $attempt->userid, $attempt->attempt)) {
                                foreach ($trackdata as $element => $value) {
                                    if (stristr($element, "cmi.interactions_$i.type") !== false) {
                                        $key = array_search($value, $rowdata['type'][0]);
                                        if ($key !== false) {
                                            $rowdata['type'][1][$key]++;
                                        } else {
                                            $rowdata['type'][0][] = $value;
                                            $rowdata['type'][1][] = 1;
                                        }
                                    } else if (stristr($element, "cmi.interactions_$i.student_response") !== false) {
                                        $key = array_search($value, $rowdata['student_response'][0]);
                                        if ($key !== false) {
                                            $rowdata['student_response'][1][$key]++;
                                        } else {
                                            $rowdata['student_response'][0][] = $value;
                                            $rowdata['student_response'][1][] = 1;
                                        }
                                    } else if (stristr($element, "cmi.interactions_$i.result") !== false) {
                                        $key = array_search($value, $rowdata['result'][0]);
                                        if ($key !== false) {
                                            $rowdata['result'][1][$key]++;
                                        } else {
                                            $rowdata['result'][0][] = $value;
                                            $rowdata['result'][1][] = 1;
                                        }
                                    }
                                }
                            }
                        }// End of foreach loop of attempts
                        $tabledata[] = $rowdata;
                    }// End of foreach loop of interactions loop
                    // Format data for tables and generate output.
                    $formated_data = array();
                    if (!empty($tabledata)) {
                        foreach ($tabledata as $interaction => $rowinst) {
                            $firstelement = 1;
                            foreach ($rowinst as $element => $data) {
                                if($firstelement) {
                                    $formated_data = array("Question $interaction ", " - <b>$element</b>", '', '');
                                    $firstelement = 0;
                                } else {
                                    $formated_data = array('', " - <b>$element</b>", '', '');
                                }
                                $table->add_data($formated_data);
                                foreach ($data[0] as $index => $value) {
                                    $formated_data = array('', '', $value, $data[1][$index]);
                                    $table->add_data($formated_data);
                                }
                            }
                        }
                        $table->finish_output();
                    }// End of generating output
                }
            }
        } else {
            echo $OUTPUT->notification(get_string('noactivity', 'scorm'));
        }
        return true;
    }
}
