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

define(['jquery', 'core/log'],
        function($, log) {

    var FormController = {
        init: function() {
            var form = $('.gradingoptions').eq(0);
            log.debug("init");
            form.change(function() {
                // TODO - need to check for change in the grading form before trying this.
                form.submit();
            });
        }

    };

    return FormController;
});
