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

namespace local_kent\manager;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . "/classes/string_manager.php");
require_once($CFG->libdir . "/classes/string_manager_standard.php");

/**
 * Kent's string_manager implementation
 *
 * Implements string_manager with getting and printing localised strings
 */
class string extends \core_string_manager_standard
{
    /**
     * Create new instance of string manager
     *
     * @param string $otherroot location of downloaded lang packs - usually $CFG->dataroot/lang
     * @param string $localroot usually the same as $otherroot
     * @param array $translist limit list of visible translations
     */
    public function __construct($otherroot, $localroot, $translist) {
        $this->otherroot = $otherroot;
        $this->localroot = $localroot;

        if ($translist) {
            $this->translist = array_combine($translist, $translist);
        } else {
            $this->translist = array();
        }

        $this->cache = array();

        // Load language.
        $language = file_get_contents($CFG->alternative_lang_cache);
        if (empty($language)) {
            $this->build_global_cache();
        } else {
            $this->cache['language'] = unserialize($language);

            // Load deprecated.
            $deprecated = file_get_contents($CFG->alternative_deprecated_lang_cache);
            if (!empty($deprecated)) {
                $this->cache['deprecated'] = unserialize($deprecated);
            } else {
                $this->cache['deprecated'] = array();
            }
        }
    }

    /**
     * Build string list from file.
     */
    private function build_string_list($filename) {
        $string = array();
        include($filename);
        return $string;
    }

    /**
     * Build a massive file with all language strings in it.
     */
    public final function build_global_cache() {
        global $CFG;

        // Okay, first build a giant array of language.
        // Component => array (k => v).
        $language = array(
            "en" => array(),
            "en_local" => array()
        );

        // First, core.
        $files = scandir("{$CFG->dirroot}/lang/en/");
        foreach ($files as $file) {
            if (strpos($file, '.php') === false) {
                continue;
            }

            $component = substr($file, 0, -4);

            $language["en"][$component] = $this->build_string_list("{$CFG->dirroot}/lang/en/{$file}");

            // Is there an override in local?
            if (file_exists("{$CFG->dirroot}/lang/en_local/{$file}")) {
                $language["en_local"][$component] = $this->build_string_list("{$CFG->dirroot}/lang/en_local/{$file}");
            }
        }

        // Now plugins.
        foreach (\core_component::get_plugin_types() as $plugintype => $plugintypedir) {
            foreach (\core_component::get_plugin_list($plugintype) as $pluginname => $plugindir) {
                if (!($location = \core_component::get_plugin_directory($plugintype, $pluginname)) || !is_dir($location)) {
                    continue;
                }

                $file = $pluginname;
                if ($plugintype !== 'mod') {
                    $file = $plugintype . '_' . $pluginname;
                }

                if (file_exists("$location/lang/en/$file.php")) {
                    $language["en"][$file] = $this->build_string_list("$location/lang/en/$file.php");
                }

                if (file_exists("{$CFG->dirroot}/lang/en_local/$file.php")) {
                    $language["en_local"][$file] = $this->build_string_list("{$CFG->dirroot}/lang/en_local/$file.php");
                }
            }
        }

        // Write all this to a file.
        file_put_contents($CFG->alternative_lang_cache, serialize($language));

        // Cleanup.
        $this->cache['language'] = $language;
        unset($language);

        // Now deprecated strings.
        $content = '';
        $filename = $CFG->dirroot . '/lang/en/deprecated.txt';
        if (file_exists($filename)) {
            $content .= file_get_contents($filename);
        }
        foreach (\core_component::get_plugin_types() as $plugintype => $plugintypedir) {
            foreach (\core_component::get_plugin_list($plugintype) as $pluginname => $plugindir) {
                $filename = $plugindir . '/lang/en/deprecated.txt';
                if (file_exists($filename)) {
                    $content .= "\n" . file_get_contents($filename);
                }
            }
        }

        $strings = preg_split('/\s*\n\s*/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $strings = array_flip($strings);
        file_put_contents($CFG->alternative_deprecated_lang_cache, serialize($strings));

        $this->cache['deprecated'] = $strings;
    }

    /**
     * Load all strings for one component
     *
     * @param string $component The module the string is associated with
     * @param string $lang
     * @param bool $disablecache Do not use caches, force fetching the strings from sources
     * @param bool $disablelocal Do not use customized strings in xx_local language packs
     * @return array of all string for given component and lang
     */
    public function load_component_strings($component, $lang, $disablecache = false, $disablelocal = false) {
        global $CFG;

        $language = $this->cache['language'];

        list($plugintype, $pluginname) = \core_component::normalize_component($component);

        // What component are we using?
        $file = $pluginname;
        if ($plugintype !== 'mod') {
            if ($plugintype === 'core') {
                if ($file === null) {
                    $file = 'moodle';
                }
            } else {
                $file = $plugintype . '_' . $pluginname;
            }
        }

        if (!isset($language['en'][$file])) {
            return array();
        }

        $result = $language['en'][$file];
        if (!$disablelocal && isset($language['en_local'][$file])) {
            $result = array_merge($result, $language['en_local'][$file]);
        }

        return $result;
    }

    /**
     * Parses all deprecated.txt in all plugins lang locations and returns the list of deprecated strings.
     *
     * Static variable is used for caching, this function is only called in dev environment.
     *
     * @return array of deprecated strings in the same format they appear in deprecated.txt files: "identifier,component"
     *     where component is a normalised component (i.e. "core_moodle", "mod_assign", etc.)
     */
    protected function load_deprecated_strings() {
        return $this->cache['deprecated'];
    }

    /**
     * Returns list of all explicit parent languages for the given language.
     *
     * English (en) is considered as the top implicit parent of all language packs
     * and is not included in the returned list. The language itself is appended to the
     * end of the list. The method is aware of circular dependency risk.
     *
     * @param string $lang the code of the language
     * @return array all explicit parent languages with the lang itself appended
     */
    public function get_language_dependencies($lang) {
        return array();
    }

    /**
     * Checks if the translation exists for the language
     *
     * @param string $lang moodle translation language code
     * @param bool $includeall include also disabled translations
     * @return bool true if exists
     */
    public function translation_exists($lang, $includeall = true) {
        return $lang == 'en';
    }

    /**
     * Returns localised list of installed translations
     *
     * @param bool $returnall return all or just enabled
     * @return array moodle translation code => localised translation name
     */
    public function get_list_of_translations($returnall = false) {
        return array('en' => 'English');
    }

    /**
     * Clears both in-memory and on-disk caches
     * @param bool $phpunitreset true means called from our PHPUnit integration test reset
     */
    public function reset_caches($phpunitreset = false) {
        // Clear the on-disk disk with aggregated string files.
        $this->build_global_cache();

        if (!$phpunitreset) {
            // Increment the revision counter.
            $langrev = get_config('core', 'langrev');
            $next = time();
            if ($langrev !== false and $next <= $langrev and $langrev - $next < 60 * 60) {
                // This resolves problems when reset is requested repeatedly within 1s,
                // the < 1h condition prevents accidental switching to future dates
                // because we might not recover from it.
                $next = $langrev + 1;
            }
            set_config('langrev', $next);
        }

        // Lang packs use PHP files in dataroot, it is better to invalidate opcode caches.
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
}