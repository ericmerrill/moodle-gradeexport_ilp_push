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
 * Object to get settings.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_ilp_push;

defined('MOODLE_INTERNAL') || die();

/**
 * Object to get settings.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings {
    protected static $settingobj = null;

    protected $settings = null;

    /**
     * A static factory for the settings object.
     *
     * @return settings
     */
    public static function get_settings() {
        if (empty(static::$settingobj)) {
            static::$settingobj = new static();
        }

        return static::$settingobj;
    }

    protected function __construct() {
        $this->settings = get_config('gradeexport_ilp_push');
    }

    /**
     * Get the value of a setting, null if not set.
     *
     * @param string $key The setting key
     * @return mixed
     */
    public function get($key) {
        if (!isset($this->settings->$key)) {
            return null;
        }

        return $this->settings->$key;
    }

    /**
     * Get the value of a setting, null if not set.
     *
     * @param string $key The setting key
     * @return mixed
     */
    public static function get_setting($key) {
        $settings = static::get_settings();

        return $settings->get($key);
    }

    public function __get($key) {
        return $this->get($key);
    }

    public function __isset($key) {
        return isset($this->settings->$key);
    }
}


