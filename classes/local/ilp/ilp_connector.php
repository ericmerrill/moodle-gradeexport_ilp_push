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
 * Connector that interacts with ILP.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_ilp_push\local\ilp;

defined('MOODLE_INTERNAL') || die();

use gradeexport_ilp_push\settings;

/**
 * Connector that interacts with ILP.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ilp_connector {

    $endpoints = ['grades' => 'api/coursesection/grades'];

    /**
     * Get an array of headers to be used with curl.
     *
     * @return string[]
     */
    protected function get_connection_headers() {
        $settings = settings::get_settings();

        $creds = $settings->ilpid.':'.$settings->ilppassword;

        $credentials = base64_encode($creds);
        $auth = "Basic " . $credentials;

        $headers = ['Authorization: '. $auth,
                    'Content-Type: application/json',
                    'Accept: application/json'];

        return $headers
    }

    /**
     * Returns an array of curl options.
     *
     * @return mixed[]
     */
    protected function get_curl_settings($endpoint) {
        $options = [CURLOPT_HEADER => false,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_POST => 1,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_HTTPHEADER => $this->get_connection_headers()];

        // Set the URL endpoint.
        if (isset($this->endpoints[$endpoint])) {
            $url = settings::get_setting('ilpurl') . '/' . $this->endpoints[$endpoint];
            $options[CURLOPT_URL] = $url;
        }

        return $options;
    }


    public function send_request($endpoint, $body) {
        $curl = curl_init();

        curl_setopt_array($curl, $this->get_curl_settings($endpoint));

        curl_setopt($ch, CURLOPT_POSTFIELDS, $contents);


        if (!$response = curl_exec($ch)) {

        } else {

        }



        curl_close($curl);
    }
}


