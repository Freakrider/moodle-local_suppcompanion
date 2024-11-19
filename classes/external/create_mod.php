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
                        'title' => new external_value(PARAM_RAW, 'title of the mod'),
                        'url' => new external_value(PARAM_URL, 'URL of the file to download', VALUE_OPTIONAL),
                        'text' => new external_value(PARAM_RAW, 'intro text of the mod'),
                        'section' => new external_value(PARAM_ALPHANUM, 'section number'),
                    ]
                ),
                'modinfo',
                VALUE_OPTIONAL
            ),
            'questioninfos' =>  new external_multiple_structure(
                new external_single_structure(
                    [
                        'quizid' => new external_value(PARAM_INT, 'ID of the quiz'),
                        'type' => new external_value(PARAM_ALPHANUMEXT, 'Type of the question, e.g., multichoice'),
                        'name' => new external_value(PARAM_RAW, 'Name of the question'),
                        'questiontext' => new external_value(PARAM_RAW, 'Question text'),
                        'single' => new external_value(PARAM_BOOL, 'Whether it is a single-answer question'),
                        'shuffleanswers' => new external_value(PARAM_BOOL, 'Whether the answers should be shuffled'),
                        'answernumbering' => new external_value(PARAM_ALPHANUM, 'Answer numbering style, e.g., abc or 123'),
                        'answers' => new external_multiple_structure(
                            new external_single_structure(
                                [
                                    'text' => new external_value(PARAM_RAW, 'Answer text'),
                                    'fraction' => new external_value(PARAM_FLOAT, 'Fraction of the grade for this answer (1.0 for correct, 0.0 for incorrect)'),
                                    'feedback' => new external_value(PARAM_RAW, 'Feedback for this answer'),
                                ]
                            ),
                            'List of possible answers'
                        ),
                    ],
                ),
                'questioninfo',
                VALUE_OPTIONAL
            )
        ]);
    }

    /**
     * Create module.
     *
     * Example curl request
     * curl -v -X POST -H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: 69505a04ee43cd571c454e573703773e" -d '{"userid": "13", "courseid": "12", "moduleinfo": [{"mod": "quiz", "title": "test quiz", "url": "", "text": "This is the introductory text for the quiz", "section": "1"}], "questioninfos": []}' "http://localhost:8080/moodle-404/webservice/restful/server.php/local_suppcompanion_create_mod"
     * curl -v -X POST -H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: 69505a04ee43cd571c454e573703773e" -d '{"userid": "13", "courseid": "12", "moduleinfo": [{"mod": "label", "title": "test label", "url": "", "text": "This is the introductory text for the label", "section": "2"}], "questioninfos": []}' "http://localhost:8080/moodle-404/webservice/restful/server.php/local_suppcompanion_create_mod"
     * curl -v -X POST -H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: 69505a04ee43cd571c454e573703773e" -d '{"userid": "13", "courseid": "12", "moduleinfo": [{"mod": "book", "title": "test book", "url": "", "text": "This is the introductory text for the book", "section": "6"}], "questioninfos": []}' "http://localhost:8080/moodle-404/webservice/restful/server.php/local_suppcompanion_create_mod"
     * curl -v -X POST -H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: 69505a04ee43cd571c454e573703773e" -d '{"userid": 13, "courseid": 12, "moduleinfo": [], "questioninfos": [{"quizid": 14, "type": "multiplechoice", "name": "Sample Multiple Choice Question", "questiontext": "What is the capital of France?", "single": true, "shuffleanswers": true, "answernumbering": "abc", "answers": [{"text": "Paris", "fraction": 1.0, "feedback": "Correct! Paris is the capital of France."}, {"text": "London", "fraction": 0.0, "feedback": "Incorrect! The capital of France is Paris."}, {"text": "Berlin", "fraction": 0.0, "feedback": "Incorrect! The capital of France is Paris."}, {"text": "Madrid", "fraction": 0.0, "feedback": "Incorrect! The capital of France is Paris."}]}]}' "http://localhost:8080/moodle-404/webservice/restful/server.php/local_suppcompanion_create_mod"
     * curl -v -X POST -H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: 69505a04ee43cd571c454e573703773e" -d '{"userid": "13", "courseid": "12", "moduleinfo": [{"mod": "resource", "title": "test file", "url": "https://surfsharekit.nl/objectstore/87d862b5-c43f-4a8e-a2af-d3a20b06d26c", "text": "This is the introductory text for the file", "section": "0"}], "questioninfos": []}' "http://localhost:8080/moodle-404/webservice/restful/server.php/local_suppcompanion_create_mod"

     *
     * @param int $userid
     * @param int $courseid
     * @param object $moduleinfo
     * @param object $questioninfos
     * @return //['status' => 'success', 'addedMods' => $addedMods, 'addedQuestions' => $addedQuestions]
     */
    public static function execute($userid, $courseid, $moduleinfo, $questioninfos)
    {
        //TODO
        //rename section

        global $DB, $CFG;
        require_once($CFG->dirroot . '/config.php');
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->dirroot . '/question/editlib.php');
        require_once($CFG->dirroot . '/mod/resource/lib.php');
        require_once($CFG->dirroot . '/question/type/multichoice/questiontype.php');
        require_once($CFG->dirroot . '/course/modlib.php');

        // Validate. Valid mods quiz, questions, text
        // $params = self::validate_parameters(self::execute_parameters(), ['userid' => $userid, 'mod' => $moduleinfo]);

        $transaction = $DB->start_delegated_transaction();

        // Ensure the current user is allowed to run this function
        $context = \context_course::instance($courseid);
        try {
            self::validate_context($context);
        } catch (\Exception $e) {
            //User not enrolled, make sure to use prev created course
            return ['status' => 'error', 'message' => 'errorcoursecontextnotvalid'];
        }

        $addedMods = [];
        $addedQuestions = [];

        $allowedModTypes = ['quiz', 'label', 'book'];
        $allowedModTypes2 = ['resource'];
        $allowedOtherTypes = ['multiplechoice'];

        foreach ($moduleinfo as $module) {
            $modType = $module['mod'];
            $modTitle = $module['title'];
            $modText = $module['text'];
            $modSection = $module['section'];
            if (in_array($modType, $allowedModTypes)) {
                require_capability("mod/{$modType}:addinstance", $context, $userid);

                // Create Section if doesnt exist
                $sectioninfo = get_fast_modinfo($courseid)->get_section_info($modSection);
                if (!$sectioninfo) {
                    formatactions::section($courseid)->create_if_missing([$modSection]);
                }


                $introText = $modText;
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

                if ($module->id !== null) {
                    $addedMods[] =  [
                        'moduleid' => $module->id,
                        'modulename' => $module->name
                    ];
                } else {
                    $addedMods[] =  [
                        'moduleid' => (int) 0,
                        'modulename' => $module->name // Exclude moduleid if it's null
                    ];
                }
                $transaction->allow_commit();
            } else if (in_array($modType, $allowedModTypes2)) {
                // require_capability('moodle/course:managefiles', $context);

                // Create the "resource" module in the course.
                $draftitemid = file_get_unused_draft_itemid();

                $filerecord = [
                    'contextid' =>  \context_user::instance($userid)->id, //TODO NEEDS OTHER CONTEXT not $context->id \context_module::instance($createdModule->id)
                    'component' => 'user',
                    'filearea' => 'draft',
                    'itemid' => $draftitemid,
                    'filepath' => '/',
                    'filename' => basename(time() . '.pdf'), //$module['title']) .
                ];

                $fs = get_file_storage();
                $file = $fs->create_file_from_url($filerecord, $module['url']);

                $moduleinfo = (object) [
                    'modulename' => 'resource',
                    'course' => $courseid,
                    'section' => $module['section'],
                    'visible' => true,
                    'introeditor' => [
                        'text' => $module['text'],
                        'format' => FORMAT_HTML
                    ]
                ];

                $createdModule = \create_module($moduleinfo);
                $transaction->allow_commit();

                $uploadinfoArray = (object) [
                    'course' => (object) [
                        'id' => '' . $courseid,
                    ],
                    'displayname' => $modTitle,
                    'coursemodule' => $createdModule->coursemodule,
                    'draftitemid' => $draftitemid,
                ];

                // Convert the array to an object
                $uploadinfo = (object) $uploadinfoArray;

                resource_dndupload_handle($uploadinfo);
            } else {
                return ['status' => 'error', 'message' => 'content not allowed'];
            }
        }

        foreach ($questioninfos as $questioninfo) {
            //TODO add check for questiontypes other than multiplechoice
            $qType = $questioninfo['type'];
            if (in_array($qType, $allowedOtherTypes)) {
                require_capability("moodle/course:manageactivities", $context, $userid);
                // Prepare the question data.
                $questionArray = [
                    'category' => $questioninfo->category ?? 1, // Default to category ID 1 if not provided.
                    'name' => $questioninfo->name,
                    'questiontext' => $questioninfo->questiontext,
                    'questiontextformat' => FORMAT_HTML,
                    'generalfeedback' => $questioninfo->generalfeedback ?? '', // Optional feedback.
                    'generalfeedbackformat' => FORMAT_HTML,
                    'defaultmark' => $questioninfo->defaultmark ?? 1, // Default score.
                    'penalty' => $questioninfo->penalty ?? 0.3333333, // Default penalty.
                    'qtype' => $questioninfo->type ?? 'multichoice',
                    'length' => 1,
                    'hidden' => 0,
                ];

                if ($questionArray['qtype'] === 'multichoice') {
                    $questionArray['single'] = $questioninfo->single ? 1 : 0;
                    $questionArray['shuffleanswers'] = $questioninfo->shuffleanswers ? 1 : 0;
                    $questionArray['answernumbering'] = $questioninfo->answernumbering ?? 'abc';

                    // Process answers.
                    $answers = $questioninfo->answers;
                    $questionArray['answer'] = [];
                    $questionArray['fraction'] = [];
                    $questionArray['feedback'] = [];
                    $questionArray['answerformat'] = [];
                    $questionArray['feedbackformat'] = [];

                    foreach ($answers as $answer) {
                        $questionArray['answer'][] = $answer->text;
                        $questionArray['fraction'][] = $answer->fraction;
                        $questionArray['feedback'][] = $answer->feedback;
                        $questionArray['answerformat'][] = FORMAT_HTML;
                        $questionArray['feedbackformat'][] = FORMAT_HTML;
                    }
                }

                // Convert the array to an object.
                $question = (object) $questionArray;

                //TODO Add question
                // Add the question to the database.
                // $questionId = question_bank::add_question($question);

                // // Add the question to the quiz.
                // quiz_add_quiz_question($questionId, $questioninfo->quizid); // Add to quiz

                $addedQuestions[] = [
                    'questionid' => $questionId,
                    'questionname' => $question->name,
                ];
                $transaction->allow_commit();
            } else {
                return ['status' => 'error', 'message' => 'content not allowed'];
            }
        }

        return ['status' => 'success', 'addedMods' => $addedMods, 'addedQuestions' => $addedQuestions];
    }

    /**
     * Returns description of method result value.
     * @return external_single_structure
     */
    public static function execute_returns()
    {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Response status, e.g., success or error'),
            'message' => new external_value(PARAM_RAW, 'Error message, present only on error', VALUE_OPTIONAL),
            'addedMods' => new external_multiple_structure(
                new external_single_structure([
                    'moduleid' => new external_value(PARAM_INT, 'ID of the added module', VALUE_OPTIONAL),
                    'modulename' => new external_value(PARAM_RAW, 'Name of the added module'),
                ]),
                'List of added modules',
                VALUE_OPTIONAL
            ),
            'addedQuestions' => new external_multiple_structure(
                new external_single_structure([
                    'questionid' => new external_value(PARAM_INT, 'ID of the added question'),
                    'questionname' => new external_value(PARAM_RAW, 'Name of the added question'),
                ]),
                'List of added questions',
                VALUE_OPTIONAL
            )
        ]);
    }
}
