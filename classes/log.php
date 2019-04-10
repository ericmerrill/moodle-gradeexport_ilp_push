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
 * Class for logging.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_ilp_push;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for logging.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class log {
    /** @var logging The singular instances of logging */
    protected static $instance;

    /** @var int Our nesting depth */
    protected $depth = 0;

    /** @var int Our nesting depth */
    protected $buffer = array();

    /** @var int The current error level */
    protected $errorlevel = self::ERROR_NONE;

    /** @var int The error level setting */
    protected $outputerrorlevel = self::ERROR_NONE;

    /** @var bool If true, don't output to the std out */
    protected $silencestdout = false;

    /** @var resource File pointer to the log file */
    protected $logfilehandle = false;

    /**
     * No current error.
     */
    const ERROR_NONE = 0;

    /**
     * Notice/debugging style notices.
     */
    const ERROR_NOTICE = 1;

    /**
     * Warning level errors.
     */
    const ERROR_WARN = 2;

    /**
     * A major error has occurred.
     */
    const ERROR_MAJOR = 3;

    /**
     * Factory method that returns the singular instance of logging.
     *
     * @return logging The logging object
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * Protected constructor, please use the static instance method.
     */
    protected function __construct() {
        $this->outputerrorlevel = settings::get_setting('logginglevel');

        $path = settings::get_setting('logpath');
        if (!empty($path)) {
            $handle = fopen($path, 'a');
            if ($handle) {
                $this->logfilehandle = $handle;
                // Make sure the log file gets closed properly when we are done.
                \core_shutdown_manager::register_function(array($this, 'close_log_file'));
            }
        }
    }

    /**
     * Close the log file resource handle.
     */
    public function close_log_file() {
        if (!empty($this->logfilehandle)) {
            fclose($this->logfilehandle);
        }

        $this->logfilehandle = false;
    }

    public function set_logging_level($level) {
        $this->outputerrorlevel = $level;
    }

    /**
     * Change the silence standard out setting.
     *
     * @param bool $silence
     */
    public function set_silence_std_out($silence = true) {
        $this->silencestdout = (bool)$silence;
    }

    // Log a line with an optional error level.
    public function log_line($line, $error = self::ERROR_NONE, $object = null) {
        $this->set_error_level($error);

        if (!is_null($object)) {
            $line .= "\n\n".var_export($object, true);
        }

        // If above threashold, output now, otherwise buffer.
        if ($this->errorlevel >= $this->outputerrorlevel) {
            $this->output_line($line, $error);
        } else {
            $this->buffer_line($line, $error);
        }
    }

    // Set the error level, and output buffere if we should.
    public function set_error_level($error) {
        if ($error > $this->errorlevel) {
             $this->errorlevel = $error;
        }

        // If error level above threashold, flush the buffer.
        if ($this->errorlevel >= $this->outputerrorlevel) {
            $this->flush_buffer();
        }
    }

    // Output the start of a message block then increase level.
    public function start_message($line) {
        $this->log_line($line);
        $this->start_level();
    }

    // Add a depth level.
    public function start_level() {
        $this->depth++;
    }

    // End a depth level.
    public function end_level() {
        if ($this->depth <= 0) {
            $this->depth = 0;
            return;
        }
        $this->depth--;
    }

    // End a complete message and reset.
    public function end_message($error = self::ERROR_NONE) {
        $this->set_error_level($error);

        $this->end_level();
        $this->purge_buffer();
        $this->errorlevel = self::ERROR_NONE;
    }

    public function log_exception(\Exception $e, $level = self::ERROR_WARN) {
        $info = get_exception_info($e);

        $message = $info->message;
        $message .= "\n".$info->debuginfo;
        $message .= "\n\n".$e->getTraceAsString();

        $this->log_line($message, $level);
    }

    // Get the indenting prefix of the line.
    protected function get_line_prefix() {
        return str_repeat('  ', $this->depth);
    }

    protected function render_line($line, $error = self::ERROR_NONE) {
        $prefix = '';
        switch ($error) {
            case self::ERROR_NOTICE:
                $prefix = "NOTICE: ";
                break;
            case self::ERROR_WARN:
                $prefix = "WARNING: ";
                break;
            case self::ERROR_MAJOR:
                $prefix = "FATAL: ";
                break;
        }

        return $this->get_line_prefix().$prefix.$line;
    }

    // Output a line to the user.
    protected function output_line($line, $error = self::ERROR_NONE) {
        $line = $this->render_line($line, $error);

        $this->print_line($line);
    }

    protected function print_line($line) {
        if (!$this->silencestdout) {
            mtrace($line);
        }

        if ($this->logfilehandle) {
            // TODO - Date/time format.
            fwrite($this->logfilehandle, date('Y-m-d\TH:i:s - ') . $line . "\n");
        }
    }

    // Add a line to the message buffer.
    protected function buffer_line($line, $error = self::ERROR_NONE) {
        $this->buffer[] = $this->render_line($line, $error);
    }

    // Output and clear the buffer.
    protected function flush_buffer() {
        foreach ($this->buffer as $line) {
            $this->print_line($line);
        }

        $this->purge_buffer();
    }

    // Clear the buffer.
    protected function purge_buffer() {
        $this->buffer = array();
    }
}

