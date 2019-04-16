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
 * Javascript dealing with each grading row.
 *
 * @module     gradeexport_push_ilp/row_control
 * @package    gradeexport_push_ilp
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {

    var PageInfo = {
        failGrades: false,
        incompleteGrades: false,
        defaultIncomplete: false,
        lastAttendDates: false,
        incompleteDeadlineDates: false,

        init: function(failGrades, incompleteGrades, defaultIncomplete, deadlineDates, lastAttendDates) {
            this.failGrades = failGrades;
            this.incompleteGrades = incompleteGrades;
            this.defaultIncomplete = defaultIncomplete;
            this.incompleteDeadlineDates = {start: new Date(deadlineDates.start),
                                            userstart: deadlineDates.userstart,
                                            end: new Date(deadlineDates.end),
                                            userend: deadlineDates.userend};
            this.lastAttendDates = {start: new Date(lastAttendDates.start),
                                    userstart: lastAttendDates.userstart,
                                    end: new Date(lastAttendDates.end),
                                    userend: lastAttendDates.userend};
        },

        gradeIsIncomplete: function(key) {
            if (this.incompleteGrades.hasOwnProperty(key)) {
                return true;
            } else {
                return false;
            }
        },

        gradeIsFailure: function(key) {
            if (this.failGrades.hasOwnProperty(key)) {
                return true;
            } else {
                return false;
            }
        },

        isAllowedIncompleteGrade: function(key) {
            if (key == this.defaultIncomplete) {
                return true;
            }

            return false;
        },

        isAllowedIncompleteDeadline: function(value) {
            var date = new Date(value);

            if (date < this.incompleteDeadlineDates.start) {
                return false;
            }

            if (date > this.incompleteDeadlineDates.end) {
                return false;
            }

            return true;
        },

        isAllowedLastAttendDate: function(value) {
            var date = new Date(value);

            if (date < this.lastAttendDates.start) {
                return false;
            }

            if (date > this.lastAttendDates.end) {
                return false;
            }

            return true;
        },

        getStringLastAttendDates: function() {
            return {start: this.lastAttendDates.userstart,
                    end: this.lastAttendDates.userend};
        },

        getStringIncompleteDeadline: function() {
            return {start: this.incompleteDeadlineDates.userstart,
                    end: this.incompleteDeadlineDates.userend};
        },

        getIncompleteGrade: function() {
            // TODO.
            return "F";
        }
    };

    return PageInfo;
});
