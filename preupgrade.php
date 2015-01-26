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

if (!defined('KENT_MOODLE')) {
    define('CLI_SCRIPT', true);
    require_once(dirname(__FILE__) . '/../../config.php');
}

/**
 * Anything that should run before an upgrade can be put here.
 */

\local_nagios\Core::regenerate_list();

// Do we need to downgrade hotpot extras?
$version = get_config('hotpot', 'version');
if ($version <= 2015010655) {
    set_config('version', 2010012400, 'hotpotattempt_hp');
    set_config('version', 2010012400, 'hotpotattempt_html');
    set_config('version', 2010012400, 'hotpotattempt_qedoc');
    set_config('version', 2010012400, 'hotpotreport_analysis');
    set_config('version', 2010012400, 'hotpotreport_clicktrail');
    set_config('version', 2010012400, 'hotpotreport_overview');
    set_config('version', 2010012400, 'hotpotreport_responses');
    set_config('version', 2010012400, 'hotpotreport_scores');
    set_config('version', 2010012400, 'hotpotsource_hp');
    set_config('version', 2010012400, 'hotpotsource_html');
    set_config('version', 2010012400, 'hotpotsource_qedoc');
}
