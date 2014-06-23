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
class NewYear
{
    /**
     * Inject GA code into page.
     */
    public static function inject() {
        global $CFG;

        $ny = new static();

        $CFG->additionalhtmlfooter .= $ny->get_code();
    }

    /**
     * Function to return google analytics with code, only if the code is set via the config
     */
    private function get_code() {
        global $CFG;

        $enabled = get_config("local_kent", "enable_new_year");
        $isflt = has_capability('moodle/site:config', \context_system::instance());
        if (empty($CFG->new_year_cutoff) || time() > $CFG->new_year_cutoff || !$enabled || (!is_siteadmin() && !$isflt)) {
            return "";
        }

        // Grab the GA Code.
        return <<<HTML
    <!-- Start of New Year -->
    <script>
        $.getScript('//cdnjs.cloudflare.com/ajax/libs/kineticjs/5.0.6/kinetic.min.js', function() {
            $("#logowrap").append('<div id="ny_container" style="float: right; margin-left: -35px;"></div>');
            function ny_animate(animatedLayer, star, frame) {
              // 20% slow down per second
              var angularFriction = 0.2;
              var angularVelocityChange = star.angularVelocity * frame.timeDiff * (1 - angularFriction) / 1000;
              star.angularVelocity -= angularVelocityChange;

              if(star.controlled) {
                star.angularVelocity = (star.rotation() - star.lastRotation) * 1000 / frame.timeDiff;
              }
              else {
                star.rotate(frame.timeDiff * star.angularVelocity / 1000);
              }

              star.lastRotation = star.rotation();
            }
            var stage = new Kinetic.Stage({
              container: 'ny_container',
              width: 80,
              height: 80
            });

            var animatedLayer = new Kinetic.Layer();

            var star = new Kinetic.Star({
              x: stage.width() / 2,
              y: stage.height() / 2,
              outerRadius: 30,
              innerRadius: 18,
              stroke: '#ffffff',
              fill: '#CC0000',
              strokeWidth: 2,
              numPoints: 7,
              lineJoin: 'round',
              shadowOffset: {x:6,y:6},
              shadowBlur: 10,
              shadowColor: 'black',
              shadowOpacity: 0.1,
            });

            // custom properties
            star.lastRotation = 0;
            star.angularVelocity = 360;
            star.controlled = false;

            star.on('mousedown', function(evt) {
              this.angularVelocity = 0;
              this.controlled = true;
            });

            animatedLayer.add(star);

            var text = new Kinetic.Text({
             x: stage.width() / 2,
             y: (stage.height() / 2),
             text: 'NEW',
             fontSize: 12,
             fontFamily: 'sans-serif',
             fontStyle: 'bold',
             fill: 'white'
            });
            text.offsetX(text.width()/2);
            text.offsetY(text.height()/2);
            text.align('center');

            animatedLayer.add(text);

            // add listeners to container
            stage.on('contentMouseup', function() {
              star.controlled = false;
            });

            stage.on('contentMousemove', function() {
              if(star.controlled) {
                var mousePos = stage.getPointerPosition();
                var x = star.x() - mousePos.x;
                var y = star.y() - mousePos.y;
                star.rotation((0.5 * Math.PI + Math.atan(y / x)) * (180 / Math.PI));

                if(mousePos.x <= stage.width() / 2) {
                  star.rotate(180);
                }
              }
            });

            stage.add(animatedLayer);

            var anim = new Kinetic.Animation(function(frame) {
              ny_animate(animatedLayer, star, frame);
            }, animatedLayer);

            setTimeout(function() {
              anim.start();
            }, 400);
        });
    </script>
    <!-- End of New Year -->
HTML;
    }

}
