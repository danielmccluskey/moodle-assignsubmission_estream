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
 * DB/upgrade stub for the Planet eStream Assignment Submission Plugin
 *
 * @package        assignsubmission_estream
 * @copyright        Planet Enterprises Ltd
 * @license        http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
function xmldb_assignsubmission_estream_upgrade($oldversion)
{
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2021061406) {

        // Changing type of field cdid on table assignsubmission_estream to char.
        $table = new xmldb_table('assignsubmission_estream');
        $field = new xmldb_field('cdid', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null, '0', 'submission');

        // Launch change of type for field cdid.
        $dbman->change_field_type($table, $field);

        // Changing precision of field embedcode on table assignsubmission_estream to (200).
        $table = new xmldb_table('assignsubmission_estream');
        $field = new xmldb_field('embedcode', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null, '0', 'cdid');

        // Launch change of precision for field embedcode.
        $dbman->change_field_precision($table, $field);

        // Estream savepoint reached.
        upgrade_plugin_savepoint(true, 2021061406, 'assignsubmission', 'estream');
    }

    return true;
}
