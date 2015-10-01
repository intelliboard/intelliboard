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
// IntelliBoard.net is built as a local plugin for Moodle.

/**
 * IntelliBoard.net
 *
 *
 * @package    	intelliboard
 * @copyright  	2015 IntelliBoard, Inc
 * @license    	http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @created by	IntelliBoard, Inc
 * @website		www.intelliboard.net
 */

// We defined the web service functions to install.
$functions = array(
        'local_intelliboard_database_query' => array(
                'classname'   => 'local_intelliboard_external',
                'methodname'  => 'database_query',
                'classpath'   => 'local/intelliboard/externallib.php',
                'description' => 'Return JSON db records',
                'type'        => 'read',
        )
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
        'IntelliBoard service' => array(
                'functions' => array ('local_intelliboard_database_query'),
                'restrictedusers' => 0,
                'enabled'=>1,
        )
);
