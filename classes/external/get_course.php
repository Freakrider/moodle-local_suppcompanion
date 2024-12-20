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
 * External function to create a course for a given user that will be enrolled as teacher.
 *
 * @package    local_suppcompanion
 * @copyright  2024 Paola Maneggia <paola.maneggia@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_suppcompanion\external;

defined('MOODLE_INTERNAL') || die;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;
use core_external\external_format_value;
use moodle_exception;

/**
 * External function to create a course for a given user that will be enrolled as teacher.
 *
 * @package    local_suppcompanion
 * @copyright  2024 Paola Maneggia <paola.maneggia@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_course extends external_api
{

    /**
     * Returns description of method parameters.
     * @return external_function_parameters
     */
    public static function execute_parameters()
    {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User Id'),
            'courseid' => new external_value(PARAM_INT, 'course id', VALUE_OPTIONAL),
            'shortname' => new external_value(PARAM_TEXT, 'course short name', VALUE_OPTIONAL)
        ]);
    }

    /**
     * Create course.
     * 
     * Example curl request
     * curl -X POST -H "Content-Type: application/json" -H "Accept: application/json" -H 'Authorization: 7a4508ba09cca94db558ce6d0237792a' -d'{"userid":13, "courseid":14, "shortname":"grundlagen_der_programmierung"}' "http://localhost:8080/moodle-404//webservice/restful/server.php/local_suppcompanion_get_course"
     *
     * @param int $courseid
     * @param string $shortname
     * @return object $course
     */
    public static function execute($userid, $courseid, $shortname)
    {
        global $DB, $CFG;
        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->libdir . '/completionlib.php');
        // Validate.
        $params = self::validate_parameters(self::execute_parameters(), ['userid' => $userid]);

        if (empty($courseid) && empty($shortname)) {
            throw new \moodle_exception('missingfield', 'error', '', 'At least one of "courseid" or "shortname" must be present.');
        }

        // Ensure the current user is allowed to run this function
        $context = \context_course::instance($courseid);
        try {
            self::validate_context($context);
        } catch (\Exception $e) {
            $exceptionparam = new \stdClass();
            $exceptionparam->message = $e->getMessage();
            $exceptionparam->catid = $course['categoryid'];
            throw new \moodle_exception('errorcatcontextnotvalid', 'webservice', '', $exceptionparam);
        }

        require_capability('moodle/course:manageactivities', $context, $userid);
        //TODO this is too much
        $courseinfo = get_fast_modinfo($courseid);
        return ['course' => $courseinfo];
    }


    /**
     * Returns description of method result value.
     * @return course_modinfo|null
     */
    public static function execute_returns()
    {
        // TODO select values to return
        $courseconfig = get_config('moodlecourse'); //needed for many default values
        return new external_single_structure(
            [
                'userid' => new external_value(PARAM_INT, 'id of user', VALUE_REQUIRED),
                'course' => new external_single_structure(
                    [
                        'fullname' => new external_value(PARAM_TEXT, 'full name'),
                        'shortname' => new external_value(PARAM_TEXT, 'course short name'),
                        'categoryid' => new external_value(PARAM_INT, 'category id'),
                        'idnumber' => new external_value(PARAM_RAW, 'id number', VALUE_OPTIONAL),
                        'summary' => new external_value(PARAM_RAW, 'summary', VALUE_OPTIONAL),
                        'summaryformat' => new external_format_value('summary', VALUE_DEFAULT),
                        'format' => new external_value(
                            PARAM_PLUGIN,
                            'course format: weeks, topics, social, site,..',
                            VALUE_DEFAULT,
                            $courseconfig->format
                        ),
                        'showgrades' => new external_value(
                            PARAM_INT,
                            '1 if grades are shown, otherwise 0',
                            VALUE_DEFAULT,
                            $courseconfig->showgrades
                        ),
                        'newsitems' => new external_value(
                            PARAM_INT,
                            'number of recent items appearing on the course page',
                            VALUE_DEFAULT,
                            $courseconfig->newsitems
                        ),
                        'startdate' => new external_value(
                            PARAM_INT,
                            'timestamp when the course start',
                            VALUE_OPTIONAL
                        ),
                        'enddate' => new external_value(
                            PARAM_INT,
                            'timestamp when the course end',
                            VALUE_OPTIONAL
                        ),
                        'numsections' => new external_value(
                            PARAM_INT,
                            '(deprecated, use courseformatoptions) number of weeks/topics',
                            VALUE_OPTIONAL
                        ),
                        'maxbytes' => new external_value(
                            PARAM_INT,
                            'largest size of file that can be uploaded into the course',
                            VALUE_DEFAULT,
                            $courseconfig->maxbytes
                        ),
                        'showreports' => new external_value(
                            PARAM_INT,
                            'are activity report shown (yes = 1, no =0)',
                            VALUE_DEFAULT,
                            $courseconfig->showreports
                        ),
                        'visible' => new external_value(
                            PARAM_INT,
                            '1: available to student, 0:not available',
                            VALUE_OPTIONAL
                        ),
                        'hiddensections' => new external_value(
                            PARAM_INT,
                            '(deprecated, use courseformatoptions) How the hidden sections in the course are displayed to students',
                            VALUE_OPTIONAL
                        ),
                        'groupmode' => new external_value(
                            PARAM_INT,
                            'no group, separate, visible',
                            VALUE_DEFAULT,
                            $courseconfig->groupmode
                        ),
                        'groupmodeforce' => new external_value(
                            PARAM_INT,
                            '1: yes, 0: no',
                            VALUE_DEFAULT,
                            $courseconfig->groupmodeforce
                        ),
                        'defaultgroupingid' => new external_value(
                            PARAM_INT,
                            'default grouping id',
                            VALUE_DEFAULT,
                            0
                        ),
                        'enablecompletion' => new external_value(
                            PARAM_INT,
                            'Enabled, control via completion and activity settings. Disabled,
                                        not shown in activity settings.',
                            VALUE_OPTIONAL
                        ),
                        'completionnotify' => new external_value(
                            PARAM_INT,
                            '1: yes 0: no',
                            VALUE_OPTIONAL
                        ),
                        'lang' => new external_value(
                            PARAM_SAFEDIR,
                            'forced course language',
                            VALUE_OPTIONAL
                        ),
                        'forcetheme' => new external_value(
                            PARAM_PLUGIN,
                            'name of the force theme',
                            VALUE_OPTIONAL
                        ),
                        'courseformatoptions' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'name' => new external_value(PARAM_ALPHANUMEXT, 'course format option name'),
                                    'value' => new external_value(PARAM_RAW, 'course format option value')
                                )
                            ),
                            'additional options for particular course format',
                            VALUE_OPTIONAL
                        ),
                        'customfields' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'shortname'  => new external_value(PARAM_ALPHANUMEXT, 'The shortname of the custom field'),
                                    'value' => new external_value(PARAM_RAW, 'The value of the custom field'),
                                )
                            ),
                            'custom fields for the course',
                            VALUE_OPTIONAL
                        )
                    ]
                ),
            ],
            'Course Information',
            VALUE_OPTIONAL
        );
    }
}
