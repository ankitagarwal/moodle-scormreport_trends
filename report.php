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
 * Core Report class of basic reporting plugin
 * @package    scormreport
 * @subpackage graphs
 * @author     Ankit Kumar Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class scorm_trends_report extends scorm_default_report {
    /**
     * displays the full report
     * @param stdClass $scorm full SCORM object
     * @param stdClass $cm - full course_module object
     * @param stdClass $course - full course object
     * @param string $download - type of download being requested
     */
    function display($scorm, $cm, $course, $download) {
        global $CFG, $DB, $OUTPUT, $PAGE;
        $contextmodule= get_context_instance(CONTEXT_MODULE, $cm->id);
        if ($scoes = $DB->get_records('scorm_scoes', array("scorm"=>$scorm->id), 'id')) {
            foreach ($scoes as $sco) {
                if ($sco->launch!='') {
                    $imageurl = new moodle_url('/mod/scorm/report/graphs/graph.php',
                            array('scoid' => $sco->id, 'cmid' => $cm->id));
                    $graphname = $sco->title;
                    echo $OUTPUT->heading($graphname);
                    // Construct the SQL
                    $select = 'SELECT DISTINCT '.$DB->sql_concat('st.userid', '\'#\'', 'COALESCE(st.attempt, 0)').' AS uniqueid, ';
                    $select .= 'st.userid AS userid, st.scormid AS scormid, st.attempt AS attempt, st.scoid AS scoid ';
                    $from = 'FROM {scorm_scoes_track} st ';
                    $where = ' WHERE st.scoid = ?';
                    $attempts = $DB->get_records_sql($select.$from.$where, array($sco->id));
                    // Determine maximum number to loop through
                    $loop = 10;
                    
                    $columns = array(get_string('element', 'scormreport_trends'), get_string('data', 'scormreport_trends'));
                    
                    $table = new flexible_table('mod-scorm-trends-report-'.$sco->id);

                    $table->define_columns($columns);
                    //$table->define_headers($headers);
                    $table->define_baseurl($PAGE->url);
                    
                    for ($i = 0; $i<10; $i++) {
                        $rowdata = array('type' => array(array(), array()),
                                      'student_response' => array(array(), array()),
                                        'result' => array(array(), array())
                                        );
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
                                    }
                                    if (stristr($element, "cmi.interactions_$i.student_response") !== false) {
                                        $key = array_search($value, $rowdata['student_response'][0]);
                                        if ($key !== false) {
                                            $rowdata['student_response'][1][$key]++;
                                        } else {
                                            $rowdata['student_response'][0][] = $value;
                                            $rowdata['student_response'][1][] = 1;
                                        }
                                    }
                                    if (stristr($element, "cmi.interactions_$i.result") !== false) {
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
                    // Format data for tables.
                    foreach ($tabledata as $interaction => $rowinst) {
                        foreach ($rowinst as $element => $data) {
                   
                            $columns = array(get_string('value', 'scormreport_trends'), get_string('freq', 'scormreport_trends'));
                        
                            $datatable = new flexible_table('mod-scorm-trends-report-'.$sco->id.'-'.$element);
        
                            $datatable->define_columns($columns);
                            //$table->define_headers($headers);
                            $datatable->define_baseurl($PAGE->url);
                            foreach ($data[0] as $pairindex => $value) {
                                $datatable->add_data(array($value, $data[1][$pairindex]));
                            }
                            $datatable->finish_output();
                        }
                    }
                        $table->add_data($row);
                        $table->finish_output();
                        echo "<pre>";print_r($tabledata);
                    
                }
            }
        }
        return true;
    }
}
