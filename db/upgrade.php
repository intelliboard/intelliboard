<?php
// IntelliBoard.net
//
// IntelliBoard.net is built to work with any LMS designed in Moodle 
// with the goal to deliver educational data analytics to single dashboard instantly. 
// With power to turn this analytical data into simple and easy to read reports, 
// IntelliBoard.net will become your primary reporting tool.
//
// Moodle
// 
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// IntelliBoard.net is built as a plugin for Moodle.

/**
 * IntelliBoard.net
 *
 *
 * @package    	local_intelliboard
 * @copyright  	2014-2015 SEBALE LLC
 * @license    	http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @created by	SEBALE LLC
 * @website		www.intelliboard.net
 */

function xmldb_local_intelliboard_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

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

    return true;
}
