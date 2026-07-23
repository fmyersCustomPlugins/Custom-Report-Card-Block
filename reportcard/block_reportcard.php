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
 * block_reportcard.php
 *
 * How the mentor/mentee relationship works in Moodle:
 *   An admin goes to a STUDENT's profile -> Preferences -> "This user's role
 *   assignments" and assigns the parent/mentor's account to a role there.
 *   That role assignment lives at CONTEXT_USER level (the student's user
 *   context), which is exactly what the query below looks for.
 *
 * What this block does, in plain English:
 *   1. If the logged-in user IS a student, show only their own report card.
 *      (Prevents a student from accidentally seeing a classmate's grades.)
 *   2. Otherwise (parent/mentor or teacher), find every student whose user
 *      context has a role assignment pointing to this user - i.e. every
 *      student this person is a mentor for.
 *   3. For each of those students, show a report card section: their name
 *      as a heading, then a table of course name + final grade pulled live
 *      from the Moodle gradebook.
 *   4. If no mentees are found, show a "no grades available" message.
 *
 * @package   block_reportcard
 * @copyright 2026 Finley Myers <finleymwork@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// These two files together give us the grade_item and grade_grade classes,
// which are stable across all recent Moodle versions. We use them instead
// of grade_get_course_grade() which moved between files in Moodle 4.x/5.x.
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/querylib.php');
require_once($CFG->dirroot . '/grade/lib.php');

class block_reportcard extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_reportcard');
    }

    public function applicable_formats() {
        return array(
            'my'     => true,  // Dashboard only
            'course' => false,
            'site'   => false,
        );
    }

    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Check whether a user holds a "student" role anywhere on the site.
     * Students always see only their own report card, never a classmate's.
     *
     * @param int $userid
     * @return bool
     */
    private function is_student($userid) {
        global $DB;

        $sql = "SELECT 1
                  FROM {role_assignments} ra
                  JOIN {role} r ON r.id = ra.roleid AND r.archetype = 'student'
                 WHERE ra.userid = :userid";

        return $DB->record_exists_sql($sql, array('userid' => $userid));
    }

    /**
     * Find every student this user is a mentor for.
     *
     * In Moodle, a mentor relationship is stored as a role assignment at
     * CONTEXT_USER level (contextlevel = 30). When an admin assigns Parent A
     * as a mentor of Student AA, a row is created in {role_assignments} with:
     *   - userid     = Parent A's id  (the person being granted the role)
     *   - contextid  = the context row for Student AA's user account
     *
     * So the query below says: "find every user context where this person
     * has a role assignment, then return the user that context belongs to,
     * as long as that user is a student."
     *
     * @param int $mentorid The logged-in user's id.
     * @return array of stdClass user records (id, firstname, lastname).
     */
    private function get_mentees($mentorid) {
        global $DB;

        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname
                  FROM {role_assignments} ra
                  JOIN {context} ctx
                       ON ctx.id = ra.contextid
                      AND ctx.contextlevel = :contextlevel
                  JOIN {user} u ON u.id = ctx.instanceid
                  JOIN {role_assignments} ra2 ON ra2.userid = u.id
                  JOIN {role} r ON r.id = ra2.roleid AND r.archetype = 'student'
                 WHERE ra.userid = :mentorid
                   AND u.deleted = 0";

        return $DB->get_records_sql($sql, array(
            'contextlevel' => CONTEXT_USER,
            'mentorid'     => $mentorid,
        ));
    }

    /**
     * Check whether a "hidden" field value (from grade_items.hidden or
     * grade_grades.hidden) currently means "hidden right now".
     *
     * Moodle encodes both fields the same way:
     *   0             = not hidden.
     *   1             = permanently hidden.
     *   timestamp > 1 = hidden until that unix time.
     *
     * @param int $hiddenvalue
     * @return bool
     */
    private function is_hidden_value($hiddenvalue) {
        $hiddenvalue = (int) $hiddenvalue;

        if ($hiddenvalue == 1) {
            return true;
        }

        if ($hiddenvalue > 1 && $hiddenvalue > time()) {
            return true;
        }

        return false;
    }

    /**
     * Build an HTML table of course name + final grade for one student.
     *
     * @param int $studentid
     * @return string HTML
     */
private function build_report_table($studentid) {
    global $DB;

    $allcourses = enrol_get_users_courses($studentid, true, array('id', 'fullname', 'visible'));

    // Skip courses the teacher/admin has hidden - their grades shouldn't
    // be surfaced here either.
    $courses = array_filter($allcourses, function($course) {
        return !empty($course->visible);
    });

    if (empty($courses)) {
        return html_writer::tag('p', get_string('nocourses', 'block_reportcard'));
    }

    $table = new html_table();
    $table->head = array(
        get_string('coursename', 'block_reportcard'),
        get_string('quarter1', 'block_reportcard'),
        get_string('quarter2', 'block_reportcard'),
        get_string('quarter3', 'block_reportcard'),
        get_string('quarter4', 'block_reportcard'),
        get_string('finalgrade', 'block_reportcard')
    );

    $table->attributes['class'] = 'generaltable block-reportcard-table';

    foreach ($courses as $course) {

        $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
        $coursename = html_writer::link($courseurl, format_string($course->fullname));

        $grades = array(
            'Quarter 1' => '-',
            'Quarter 2' => '-',
            'Quarter 3' => '-',
            'Quarter 4' => '-',
            'Final Grade' => '-'
        );

        // ---------------------------
        // QUARTER CATEGORY GRADES
        // ---------------------------
        $categories = $DB->get_records('grade_categories', array(
            'courseid' => $course->id
        ));

        foreach ($categories as $cat) {

            $name = strtolower(trim($cat->fullname));

            $key = null;

            if (strpos($name, 'quarter 1') !== false) $key = 'Quarter 1';
            else if (strpos($name, 'quarter 2') !== false) $key = 'Quarter 2';
            else if (strpos($name, 'quarter 3') !== false) $key = 'Quarter 3';
            else if (strpos($name, 'quarter 4') !== false) $key = 'Quarter 4';

            if (!$key) {
                continue;
            }

            $gradeitem = $DB->get_record('grade_items', array(
                'courseid' => $course->id,
                'itemtype' => 'category',
                'iteminstance' => $cat->id
            ));

            if (!$gradeitem) {
                continue;
            }

            $grade = $DB->get_record('grade_grades', array(
                'itemid' => $gradeitem->id,
                'userid' => $studentid
            ));

            if ($grade && $grade->finalgrade !== null) {

                // Don't reveal a grade the teacher has hidden - not even
                // to a parent/mentor - since that would defeat the point
                // of hiding it.
                if ($this->is_hidden_value($gradeitem->hidden) || $this->is_hidden_value($grade->hidden)) {
                    continue;
                }

                $gradeitemobj = new grade_item($gradeitem);

                $grades[$key] = grade_format_gradevalue(
                    $grade->finalgrade,
                    $gradeitemobj
                );
            }
        }

        // ---------------------------
        // FINAL COURSE GRADE
        // ---------------------------
        $finalitem = $DB->get_record('grade_items', array(
            'courseid' => $course->id,
            'itemtype' => 'course'
        ));

        if ($finalitem) {

            $finalgrade = $DB->get_record('grade_grades', array(
                'itemid' => $finalitem->id,
                'userid' => $studentid
            ));

            if ($finalgrade && $finalgrade->finalgrade !== null) {

                // Same hidden-grade rule as the quarter categories above.
                if ($this->is_hidden_value($finalitem->hidden) || $this->is_hidden_value($finalgrade->hidden)) {
                    // Leave $grades['Final Grade'] as '-'.
                } else {
                    $grades['Final Grade'] = grade_format_gradevalue(
                        $finalgrade->finalgrade,
                        new grade_item($finalitem)
                    );
                }
            }
        }

        // ---------------------------
        // ROW OUTPUT
        // ---------------------------
        $table->data[] = array(
            $coursename,
            $grades['Quarter 1'],
            $grades['Quarter 2'],
            $grades['Quarter 3'],
            $grades['Quarter 4'],
            $grades['Final Grade']
        );
    }

    return html_writer::table($table);
}

    /**
     * Main entry point - Moodle calls this to get the block's HTML content.
     */
    public function get_content() {
        global $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content         = new stdClass();
        $this->content->text   = '';
        $this->content->footer = '';

        $output = '';

        // Students always see only their own report card.
        if ($this->is_student($USER->id)) {
            $this->content->text = $this->build_report_table($USER->id);
            return $this->content;
        }

        // Parents / mentors / teachers: find every student they are
        // assigned as a mentor for and show one section per child.
        $mentees = $this->get_mentees($USER->id);

        if (!empty($mentees)) {
            foreach ($mentees as $mentee) {
                $output .= html_writer::tag('h5', fullname($mentee));
                $output .= $this->build_report_table($mentee->id);
            }
        } else {
            $output = get_string('nocourses', 'block_reportcard');
        }

        $this->content->text = $output;

        return $this->content;
    }
}
