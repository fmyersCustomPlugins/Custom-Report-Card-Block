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
 * version.php - tells Moodle what this plugin is and which version it is.
 *
 * @package   block_reportcard
 * @copyright 2026 Finley Myers <finleymwork@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'block_reportcard';   // Full name of the plugin (used for diagnostics)
$plugin->version   = 2026072302;           // YYYYMMDDXX format - bump this every time you make a change
$plugin->requires  = 2024100700;           // Minimum Moodle version required (matches Moodle 4.5)
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0.0';
