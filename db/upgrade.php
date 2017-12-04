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
* This plugin provides access to Moodle data in form of analytics and reports in real time.
*
*
* @package    local_intelliboard
* @copyright  2017 IntelliBoard, Inc
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
* @website    http://intelliboard.net/
*/

function xmldb_local_intelliboard_upgrade($oldversion) {
	global $DB;

	$dbman = $DB->get_manager();

	if ($oldversion < 2015020900) {
		// Define table local_intelliboard_tracking to be created.
		$table = new xmldb_table('local_intelliboard_tracking');
		// Adding fields to table local_intelliboard_tracking.
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('page', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('param', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('visits', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('timespend', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('firstaccess', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('lastaccess', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('useragent', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('useros', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('userlang', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('userip', XMLDB_TYPE_CHAR, '100', null, null, null, null);

		// Adding keys to table local_intelliboard_tracking.
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

		// Conditionally launch create table for local_intelliboard_tracking.
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}
		upgrade_plugin_savepoint(true, 2015020900, 'local', 'intelliboard');
	}
	if ($oldversion < 2016011300) {
		$table = new xmldb_table('local_intelliboard_totals');
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('sessions', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('courses', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('visits', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('timespend', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('timepoint', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}

		$table = new xmldb_table('local_intelliboard_logs');
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('trackid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('visits', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('timespend', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('timepoint', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}
		upgrade_plugin_savepoint(true, 2016011300, 'local', 'intelliboard');
	}
	if ($oldversion < 2016030700) {
		$table = new xmldb_table('local_intelliboard_tracking');

		$field = new xmldb_field('useragent');
		$field->set_attributes(XMLDB_TYPE_CHAR, '100', null, null, null, null);
		try {
			$dbman->change_field_type($table, $field);
		} catch (moodle_exception $e) {}

		$field = new xmldb_field('useros');
		$field->set_attributes(XMLDB_TYPE_CHAR, '100', null, null, null, null);
		try {
			$dbman->change_field_type($table, $field);
		} catch (moodle_exception $e) {}

		$field = new xmldb_field('userlang');
		$field->set_attributes(XMLDB_TYPE_CHAR, '100', null, null, null, null);
		try {
			$dbman->change_field_type($table, $field);
		} catch (moodle_exception $e) {}

		$field = new xmldb_field('userip');
		$field->set_attributes(XMLDB_TYPE_CHAR, '100', null, null, null, null);
		try {
			$dbman->change_field_type($table, $field);
		} catch (moodle_exception $e) {}

		upgrade_plugin_savepoint(true, 2016030700, 'local', 'intelliboard');
	}

	if ($oldversion < 2016090900) {
		// Add index to local_intelliboard_tracking
		$table = new xmldb_table('local_intelliboard_tracking');
		$index = new xmldb_index('userid_page_param_idx', XMLDB_INDEX_NOTUNIQUE, array('userid', 'page', 'param'));
		if (!$dbman->index_exists($table, $index)) {
			$dbman->add_index($table, $index);
		}
		// Add index to local_intelliboard_logs
		$table = new xmldb_table('local_intelliboard_logs');
		$index = new xmldb_index('trackid_timepoint_idx', XMLDB_INDEX_NOTUNIQUE, array('trackid', 'timepoint'));
		if (!$dbman->index_exists($table, $index)) {
			$dbman->add_index($table, $index);
		}
		// Add index to local_intelliboard_totals
		$table = new xmldb_table('local_intelliboard_totals');
		$index = new xmldb_index('timepoint_idx', XMLDB_INDEX_NOTUNIQUE, array('timepoint'));
		if (!$dbman->index_exists($table, $index)) {
			$dbman->add_index($table, $index);
		}
		upgrade_plugin_savepoint(true, 2016090900, 'local', 'intelliboard');
	}

	if ($oldversion < 2017072304) {
		$table = new xmldb_table('local_intelliboard_details');
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('logid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('visits', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('timespend', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('timepoint', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}

		// Add index to local_intelliboard_details
		$table = new xmldb_table('local_intelliboard_details');
		$index = new xmldb_index('logid_timepoint_idx', XMLDB_INDEX_NOTUNIQUE, array('logid', 'timepoint'));
		if (!$dbman->index_exists($table, $index)) {
			$dbman->add_index($table, $index);
		}
		upgrade_plugin_savepoint(true, 2017072304, 'local', 'intelliboard');
	}

	return true;
}
