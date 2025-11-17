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
 * @package    local_intelliboard
 * @copyright  2017 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

namespace local_intelliboard\helpers;

defined('MOODLE_INTERNAL') || die();

class theming
{
    const DEFAULT_LAYOUT = "report";
    const CONFIG_NAME = "pagelayout";
    public static function get_theme_page_layouts()
    {
        global $PAGE;

        $PAGE->set_context(\context_system::instance());
        $layouts = [self::DEFAULT_LAYOUT => self::DEFAULT_LAYOUT];
        if (!empty($PAGE->theme->layouts)) {
            foreach (array_keys($PAGE->theme->layouts) as $layout) {
                $layouts[$layout] = $layout;
            }
        }
        return $layouts;
    }

    public static function get_page_layout()
    {
        $layout = get_config('local_intelliboard', self::CONFIG_NAME);
        $layouts = self::get_theme_page_layouts();
        if ($layout && isset($layouts[$layout])) {
            return $layout;
        }
        return self::DEFAULT_LAYOUT;
    }
}
