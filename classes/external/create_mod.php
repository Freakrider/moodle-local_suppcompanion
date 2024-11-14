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
use core_courseformat\formatactions;
use core_external\external_format_value;
use moodle_exception;

/**
 * External function to create a course for a given user that will be enrolled as teacher.
 *
 * @package    local_suppcompanion
 * @copyright  2024 Paola Maneggia <paola.maneggia@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_mod extends external_api
{

    /**
     * Returns description of method parameters.
     * @return external_function_parameters
     */
    public static function execute_parameters()
    {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User Id'),
            'courseid' => new external_value(PARAM_INT, 'Course Id'),
            'moduleinfo' => new external_multiple_structure(
                new external_single_structure(
                    [
                        'mod' => new external_value(PARAM_RAW, 'allowed modtype'),
                        'name' => new external_value(PARAM_RAW, 'name of the mod'),
                        'section' => new external_value(PARAM_ALPHANUM, 'section number'),
                    ]
                ),
                'modinfo'
            ),
        ]);
    }

    /**
     * Create module.
     * 
     * Example curl request
     * curl -v -X POST -H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: 782d90b3bc94c038f0f523d8eb7c2820" -d '{"userid": "15", "courseid": "12", "moduleinfo": [{"mod": "quiz", "name": "test quiz", "section": "1"}]}' "http://localhost:8080/moodle-404/webservice/restful/server.php/local_suppcompanion_create_mod"
     *
     * @param int $userid
     * @param int $courseid
     * @param object $moduleinfo
     * @return //json {{modid: id}} or false
     */
    public static function execute($userid, $courseid, $moduleinfo)
    {
        global $DB, $CFG;
        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->libdir . '/completionlib.php');
        // Validate. Valid mods quiz, questions, text
        // $params = self::validate_parameters(self::execute_parameters(), ['userid' => $userid, 'mod' => $moduleinfo]);

        $transaction = $DB->start_delegated_transaction();

        $modid = 0;

        $modType = '';
        $modName = '';

        foreach ($moduleinfo as $module) {
            $modType = $module['mod'];
            $modName = $module['name'];
            $modSection = $module['section'];
        }

        // Ensure the current user is allowed to run this function
        $context = \context_course::instance($courseid);
        try {
            self::validate_context($context);
        } catch (\Exception $e) {
            throw new \moodle_exception('errorcoursecontextnotvalid', 'webservice', '');
        }
        // Create Section if doesnt exist
        $sectioninfo = get_fast_modinfo($courseid)->get_section_info($modSection);
        if (!$sectioninfo) {
            formatactions::section($courseid)->create_if_missing([$modSection]);
            $sectioninfo = get_fast_modinfo($courseid)->get_section_info($modSection);
        }

        $allowedModTypes = ['quiz', 'label', 'book'];
        if (!in_array($modType, $allowedModTypes)) {
            throw new \moodle_exception('errorinvalidmod', 'webservice', '', $modType);
        }
        
        require_capability("mod/{$modType}:addinstance", $context, $userid);
        
        $introText = "<p>This is the introductory text for the {$modType} module.</p>";
        $moduleInfo = (object) [
            'modulename' => $modType,
            'course' => $courseid,
            'section' => $modSection,
            'visible' => true,
            'introeditor' => [
                'text' => $introText,
                'format' => FORMAT_HTML
            ]
        ];
        
        if ($modType === 'quiz') {
            $moduleInfo->quizpassword = 'oer';
        }
        
        $module = \create_module($moduleInfo);
        $modid = $module->id;
        

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

        return ['modid' => $modid];
    }


    /**
     * Returns description of method result value.
     * @return external_single_structure
     */
    public static function execute_returns()
    {
        return new external_single_structure(['modid' => new external_value(PARAM_INT, 'module id')]);
    }
}
