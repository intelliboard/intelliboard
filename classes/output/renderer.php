<?php
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

namespace local_intelliboard\output;
defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;

/**
 * Renderer file.
 *
 * @package    local_intelliboard
 * @author     Intelliboard
 * @copyright  2019
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

/**
 * Standard HTML output renderer for intelliboard
 */
class renderer extends plugin_renderer_base {
    /**
     * Constructor method, calls the parent constructor
     *
     * @param \moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(\moodle_page $page, $target) {
        parent::__construct($page, $target);
    }

    /**
     * Return the dashboard content for the intellicart.
     *
     * @param student_menu $studentmenu
     * @return string HTML string
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function render_student_menu(student_menu $studentmenu) {
        return $this->render_from_template(
            'local_intelliboard/student_menu', $studentmenu->export_for_template($this)
        );
    }
}
