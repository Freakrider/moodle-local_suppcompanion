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
class create_course extends external_api
{

    /**
     * Returns description of method parameters.
     * @return external_function_parameters
     */
    public static function execute_parameters()
    {
        $courseconfig = get_config('moodlecourse'); //needed for many default values
        return new external_function_parameters(
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
            ]
        );
    }

    /**
     * Create module.
     * 
     * Example curl request
     * curl -X POST \
     * -H "Content-Type: application/json" \
     * -H "Accept: application/json" \
     * -H 'Authorization: 35be0fef0cc21dba05570ba53a0d6a1a' \
     * -d'{"userid":"2", "courseid":"12", "moduleinfo":{"name": "test quiz"}}' \
     * "http://localhost:8000/webservice/restful/server.php/local_suppcompanion_create_mod"
     *
     * @param int $userid
     * @param object $course
     * @return //json {{courseid: id}} or false
     */
    public static function execute($userid, $courseid, $moduleinfo)
    {
        global $DB, $CFG;
        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->libdir . '/completionlib.php');
        // Validate.
        $params = self::validate_parameters(self::execute_parameters(), ['userid' => $userid, 'mod' => $moduleinfo]);

        $transaction = $DB->start_delegated_transaction();

        // Ensure the current user is allowed to run this function
        $context = \context_coursecat::instance($course['categoryid'], IGNORE_MISSING);
        try {
            self::validate_context($context);
        } catch (\Exception $e) {
            $exceptionparam = new \stdClass();
            $exceptionparam->message = $e->getMessage();
            $exceptionparam->catid = $course['categoryid'];
            throw new \moodle_exception('errorcatcontextnotvalid', 'webservice', '', $exceptionparam);
        }
        // require_capability('moodle/course:create', $context, $userid); // capability mod:create???
        require_capability('moodle/course:create', $context, $userid); // capability mod:create???


        // Create Quiz module.

        $quiz = \create_module('quiz', array(
            'course' => $courseid));

        // Create questions.

        // $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        // $context = \context_course::instance($course->id);
        // $cat = $questiongenerator->create_question_category(array('contextid' => $context->id));
        // $question = $questiongenerator->create_question('multichoice', null, array('category' => $cat->id));

        // // Add to the quiz.
        // quiz_add_quiz_question($question->id, $quiz);
        // \mod_quiz\external\submit_question_version::execute(
        //         $DB->get_field('quiz_slots', 'id', ['quizid' => $quiz->id, 'slot' => 1]), 1);

        // $questiondata = \question_bank::load_question_data($question->id);

        // $firstanswer = array_shift($questiondata->options->answers);
        // $DB->set_field('question_answers', 'answer', $CFG->wwwroot . '/course/view.php?id=' . $course->id,
        //     ['id' => $firstanswer->id]);

        // $secondanswer = array_shift($questiondata->options->answers);
        // $DB->set_field('question_answers', 'answer', $CFG->wwwroot . '/mod/quiz/view.php?id=' . $quiz->cmid,
        //     ['id' => $secondanswer->id]);

        // $thirdanswer = array_shift($questiondata->options->answers);
        // $DB->set_field('question_answers', 'answer', $CFG->wwwroot . '/grade/report/index.php?id=' . $quiz->cmid,
        //     ['id' => $thirdanswer->id]);

        // $fourthanswer = array_shift($questiondata->options->answers);
        // $DB->set_field('question_answers', 'answer', $CFG->wwwroot . '/mod/quiz/index.php?id=' . $quiz->cmid,
        //     ['id' => $fourthanswer->id]);

        // $transaction->allow_commit();

        return ['modid' => $quiz->id];
    }


    /**
     * Returns description of method result value.
     * @return external_single_structure
     */
    public static function execute_returns()
    {
        return new external_single_structure(['courseid' => new external_value(PARAM_INT, 'course id')]);
    }
}
