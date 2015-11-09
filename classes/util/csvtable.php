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
 * Local stuff for Moodle Kent
 *
 * @package    local_kent
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kent\util;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * CSV Table helper.
 */
class csvtable extends \table_sql {
	/**
	 * Setup the table.
	 */
	public function setup() {
		global $PAGE;

		$download = optional_param('download', '', \PARAM_ALPHA);
		$this->is_downloading($download, $this->uniqueid, $this->uniqueid);

		// Copy columns into headers if we have no columns defined.
		if (empty($this->columns) && !empty($this->headers)) {
			$columns = array_map(function($header) {
				$header = preg_replace('/[^a-zA-Z _]/', '', $header);
				return str_replace(' ', '_', strtolower($header));
			}, $this->headers);
			$this->define_columns($columns);
		}

		// Default baseurl to page URL.
		if (empty($this->baseurl)) {
			$this->define_baseurl($PAGE->url);
		}

		$this->show_download_buttons_at(array(\TABLE_P_BOTTOM));

		parent::setup();
	}
}