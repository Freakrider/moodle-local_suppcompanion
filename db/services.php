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
 * Local support companion functions and service definitions.
 *
 * @package    local_suppcompanion
 * @category   external
 * @copyright  2024 Paola Maneggia <paola.maneggia@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = [

    'local_suppcompanion_create_course' => [
        'classname'     => 'local_suppcompanion\external\create_course',
        'description'   => 'Create a new course for a user in a course category',
        'type'          => 'write',
        'services'      => []
    ],

];

$services = [
    'Support Companion' => [
        'functions' => [
            'local_suppcompanion_create_course',
            'core_user_get_users',
        ],
        'restrictedusers'   => 1, // If 1, the administrator must manually select which user can use this service.
        // (Administration > Plugins > Web services > Manage Services, Authorised users).
        'enabled'           => 1, // If 0, then the token linked to this service won't work.
    ],
];
