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

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/adminlib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'dry' => false,
        'list' => false,
        'fix' => false, // Same as `--clean --downgrade`.
        'clean' => false,
        'downgrade' => false
    ),
    array(
        'l' => 'list',
        'f' => 'fix'
    )
);

if ($options['dry']) {
    echo "~ Running in DRY mode ~\n";
}

if ($options['fix']) {
    $options['clean'] = true;
    $options['downgrade'] = true;
}

$pluginman = core_plugin_manager::instance();
$plugininfo = $pluginman->get_plugins();
foreach ($plugininfo as $type => $plugins) {
    foreach ($plugins as $name => $plugin) {
    	$status = $plugin->get_status();

        switch ($status) {
            case core_plugin_manager::PLUGIN_STATUS_DOWNGRADE:
                if ($options['list']) {
                    echo "    '{$name}' requires downgrading.\n";
                }

                if ($options['downgrade']) {
                    echo "    Downgrading '{$name}'...";

                    if (!$options['dry']) {
                        set_config('version', ((int)$plugin->versiondisk) - 1, $plugin->component);
                    }

                    echo "done!\n";
                }
            break;

            case core_plugin_manager::PLUGIN_STATUS_DELETE:
            case core_plugin_manager::PLUGIN_STATUS_MISSING:
                if ($options['list']) {
                    echo "    '{$name}' requires uninstalling.\n";
                }

                // Uninstall, if we can.
                if ($options['clean'] && $pluginman->can_uninstall_plugin($plugin->component)) {
                    echo "    Uninstalling '{$name}'...";

                    if (!$options['dry']) {
                        $progress = new progress_trace_buffer(new text_progress_trace(), false);
                        $pluginman->uninstall_plugin($plugin->component, $progress);
                        $progress->finished();
                    }

                    echo "done!\n";
                }
            break;
        }
    }
}