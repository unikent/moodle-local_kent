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
        // prepend to top of body
        $CFG->additionalhtmltopofbody = $ga->get_tagmanager_code() . $CFG->additionalhtmltopofbody;
    }

    /**
     * Function to return google analytics with code, only if the code is set via the config
     */
    private function get_code() {
        global $CFG;

        if (empty($CFG->google_analytics_global_code)) {
            return "";
        }

        $uid = $this->get_uid();
        $dimensions = $this->get_dimensions();
        $tracker = $this->get_tracker();

        // Grab the GA Code.
        return <<<HTML
    <!-- Start of Google Analytics -->
    <script>
        (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
        (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
        m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

        ga('create', '{$CFG->google_analytics_global_code}', {
            'name': 'kentmoodle',
            'cookieDomain': 'kent.ac.uk',
            'siteSpeedSampleRate': 5
        });

        ga('kentmoodle.require', 'displayfeatures');
        {$tracker}
        ga('kentmoodle.send', 'pageview', {
            {$dimensions}
        });

        kent_moodle_ga_uid = '{$uid}';
        kent_moodle_ga_dimensions = {{$dimensions}};

    </script>
    <!-- End of Google Analytics -->
HTML;
    }

    /**
     * Function to return google tag manager code, only if the analytics is enabled
     */
    private function get_tagmanager_code() {
        global $CFG;

        if (empty($CFG->google_analytics_global_code)) {
            return "";
        }

        return <<<HTML
        <!-- Google Tag Manager -->
        <noscript><iframe src="//www.googletagmanager.com/ns.html?id=GTM-PK6HFD"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        '//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','GTM-PK6HFD');</script>
        <!-- End Google Tag Manager -->
HTML;
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
        $tracker = "";

        // Setup user tracking if logged in.
        if (isloggedin()) {
            $ident = $this->get_uid();
            $tracker = "ga('kentmoodle.set', '&uid', '{$ident}');";
        }

        return $tracker;
    }

    /**
     * Return uid for user if logged in.
     *
     * @return string sha1 of username
     */
    private function get_uid() {
        global $USER;

        $ident = "";

        // Setup user tracking if logged in.
        if (isloggedin()) {
            $ident = sha1($USER->username);
        }

        return $ident;
    }

    /**
     * Returns user information.
     */
    private function get_user_type() {
        global $USER;

        // Cant do much if we arent logged in.
        if (!isloggedin() || isguestuser()) {
            return "guest";
        }

        return isset($USER->profile) && isset($USER->profile['kentacctype']) ? s($USER->profile['kentacctype']) : null;
    }
}
