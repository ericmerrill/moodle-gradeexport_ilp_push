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
 * @module     gradeexport_ilp_push/row_control
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {

    var PageInfo = {
        failGrades: false,
        requireLastAttendGrades: false,
        incompleteGrades: false,
        defaultIncomplete: false,
        lastAttendDates: false,
        incompleteDeadlineDates: false,
        gradeModes: false,

        init: function(failGrades, requireLastAttendGrades, incompleteGrades, defaultIncomplete, deadlineDates, lastAttendDates) {
            this.failGrades = failGrades;
            this.requireLastAttendGrades = requireLastAttendGrades;
            this.incompleteGrades = incompleteGrades;
            this.defaultIncomplete = defaultIncomplete;

            var start = deadlineDates.start;
            if (start !== false) {
                start = new Date(start);
            }
            var end = deadlineDates.end;
            if (end !== false) {
                end = new Date(end);
            }

            this.incompleteDeadlineDates = {start: start,
                                            userstart: deadlineDates.userstart,
                                            end: end,
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

        gradeRequiresLastAttend: function(key) {
            if (this.requireLastAttendGrades.hasOwnProperty(key)) {
                return true;
            } else {
                return false;
            }
        },

        isAllowedIncompleteGrade: function(key) {
            if (!key || this.gradeIsIncomplete(key)) {
                return false;
            }

            return true;
        },

        isAllowedIncompleteDeadline: function(value) {
            var date = new Date(value).getTime();
            var st = this.incompleteDeadlineDates.start.getTime();

            if ((this.incompleteDeadlineDates.start !== false) && (date < st)) {
                return false;
            }

            if ((this.incompleteDeadlineDates.end !== false) && (date > this.incompleteDeadlineDates.end.getTime())) {
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

        getIncompleteDeadlineStringName: function() {
            if (this.incompleteDeadlineDates.start === false) {
                return 'invalid_incomplete_date_end';
            }
            if (this.incompleteDeadlineDates.end === false) {
                return 'invalid_incomplete_date_start';
            }
            if (this.incompleteDeadlineDates.start.getTime() === this.incompleteDeadlineDates.end.getTime()) {
                return 'invalid_incomplete_date_single';
            }

            return 'invalid_incomplete_date_range';
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
