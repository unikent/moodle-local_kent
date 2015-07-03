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

/*
 * @package    local_kent
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * @module local_kent/user
  */
define(['jquery', 'core/ajax'], function($, ajax) {
	var prefs, infodata;

	function ensure_prefs_cached(callback) {
    	if (typeof prefs !== "undefined") {
    		callback();
    		return;
    	}

        var ajaxpromises = ajax.call([{
            methodname: 'local_kent_user_get_my_prefs',
            args: {}
        }]);

        ajaxpromises[0].done(function(data) {
            prefs = [];
            $.each(data, function(i) {
            	var dict = data[i];
            	prefs[dict.name] = dict.value == '1';
            });

    		callback();
        });

        ajaxpromises[0].fail(function(ex) {
        	console.log(ex);
        });
	}

    function ensure_infodata_cached(callback) {
        if (typeof infodata !== "undefined") {
            callback();
            return;
        }

        var ajaxpromises = ajax.call([{
            methodname: 'local_kent_user_get_my_info_data',
            args: {}
        }]);

        ajaxpromises[0].done(function(data) {
            infodata = [];
            $.each(data, function(i) {
                var dict = data[i];
                infodata[dict.name] = dict.value == '1';
            });

            callback();
        });

        ajaxpromises[0].fail(function(ex) {
            console.log(ex);
        });
    }

    return {
        get_prefs: function(callback) {
        	ensure_prefs_cached(function() {
        		callback(prefs);
        	});
        },

        get_pref: function(name, callback) {
        	ensure_prefs_cached(function() {
        		callback(prefs[name]);
        	});
        },

        get_all_info: function(callback) {
            ensure_infodata_cached(function() {
                callback(infodata);
            });
        },

        get_info: function(name, callback) {
            ensure_infodata_cached(function() {
                callback(infodata[name]);
            });
        }
    };
});
