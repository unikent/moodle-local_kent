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

namespace local_kent;

defined('MOODLE_INTERNAL') || die();

/**
 * Google Analytics class.
 */
class GA
{
    /**
     * Inject GA code into page.
     */
    public static function inject() {
        global $CFG;

        $ga = new static();

        $CFG->additionalhtmlfooter .= $ga->get_code();
    }

    /**
     * Function to return google analytics with code, only if the code is set via the config
     */
    private function get_code() {
        global $CFG;

        if (empty($CFG->google_analytics_code) || !$this->can_log()) {
            return "";
        }

        $dimensions = $this->get_dimensions();
        $tracker = $this->get_tracker();

        // Grab the GA Code.
        return <<<GACODE
    <!-- Start of Google Analytics -->
    <script>
        (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
        (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
        m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

        ga('create', '{$CFG->google_analytics_code}', 'kent.ac.uk');
        ga('require', 'displayfeatures');
        {$tracker}
        ga('send', 'pageview', {
        {$dimensions}
        });

    </script>
    <!-- End of Google Analytics -->
GACODE;
    }

    /**
     * Returns true if this request should be logged.
     */
    private function can_log() {
        global $CFG, $PAGE;

        // Disable analytics on admin pages.
        $url = substr($PAGE->url, strlen($CFG->wwwroot));
        $path = substr($url, 0, 7);
        if ($path == "/local/" || $path == "/admin/" || $path == "/report") {
            return false;
        }

        return true;
    }

    /**
     * Build dimensions.
     */
    private function get_dimensions() {
        global $CFG;

        // Build dimensions.
        $dimensions = array(
            "'dimension1': '{$CFG->kent->platform}'",
            "'dimension2': '{$CFG->kent->distribution}'"
        );

        // Add current user details to dimensions.
        $usertype = $this->get_user_type();
        if ($usertype !== null) {
            $dimensions[] = "'dimension3': '{$usertype}'";
        }

        // Add hostname.
        $dimensions[] = "'dimension4': '{$CFG->kent->hostname}'";

        // Join it up.
        return join(",", $dimensions);
    }

    /**
     * Build tracker.
     */
    private function get_tracker() {
        global $USER;

        $tracker = "";

        // Setup user tracking if logged in.
        if (isloggedin()) {
            $tracker = "ga('set', '&uid', {$USER->id});";
        }

        return $tracker;
    }

    /**
     * Returns user information.
     */
    private function get_user_type() {
        global $SESSION;

        // Cant do much if we arent logged in.
        if (!isloggedin() || isguestuser()) {
            return "guest";
        }

        return isset($SESSION->account_type) ? s($SESSION->account_type) : null;
    }
}