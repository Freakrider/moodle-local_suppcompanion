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
 * @copyright  2024 Alexander Mikasch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

function xmldb_local_suppcompanion_upgrade($oldversion) {
    global $DB;

    $newversion = 2024110701; // Neue Versionsnummer für das Upgrade

    if ($oldversion < $newversion) {
        // Update the shortname for the existing service.
        $service = $DB->get_record('external_services', ['name' => 'Support Companion']);
        if ($service) {
            // Prüfen, ob die alte Funktion existiert.
            $oldFunction = $DB->get_record('external_services_functions', [
                'externalserviceid' => $service->id,
                'functionname' => 'local_suppcompanion_add_quiz_to_course'
            ]);

            // Wenn die alte Funktion existiert, ändern wir den Funktionsnamen.
            if ($oldFunction) {
                $oldFunction->functionname = 'local_suppcompanion_create_mod';
                $DB->update_record('external_services_functions', $oldFunction);
            } else {
                // Falls die Funktion noch nicht existiert, fügen wir sie hinzu.
                $DB->insert_record('external_services_functions', [
                    'externalserviceid' => $service->id,
                    'functionname' => 'local_suppcompanion_create_mod'
                ]);
            }

            // Aktualisieren des Shortnames, falls noch nicht geschehen.
            $service->shortname = 'support_companion';
            $DB->update_record('external_services', $service);
        }

        // Savepoint für das Upgrade setzen.
        upgrade_plugin_savepoint(true, $newversion, 'local', 'suppcompanion');
    }

    return true;
}
