<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Michael Dunstan <michael.dunstan@androgogic.com>
 * @package contentmarketplace_goone
 */

namespace contentmarketplace_goone;

defined('MOODLE_INTERNAL') || die();

class rest_client {

    private $endpoint = null;

    /** @var curl $curl */
    private $curl = null;

    function __construct($endpoint, $curl = null) {
        $this->endpoint = $endpoint;

        if (isset($curl)) {
            $this->curl = $curl;
        } elseif (defined('BEHAT_SITE_RUNNING')) {
            $this->curl = self::get_mock_curl();
        } else {
            $this->curl = new curl();
        }
    }

    static private function get_mock_curl() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/contentmarketplace/contentmarketplaces/goone/tests/fixtures/mock_curl.php');
        return new mock_curl();
    }

    public function get($resourcename, $params = [], $headers = [], $options = []) {
        $url = $this->build_url($resourcename, $params);
        $options['HTTPGET'] = 1;
        return $this->request($url, $headers, $options);
    }

    public function post($resourcename, $params = [], $headers = [], $options = []) {
        $url = $this->build_url($resourcename);
        $options['POST'] = 1;
        $options['POSTFIELDS'] = json_encode($params);

        $iscontenttypeheader = function($header) {
            return strpos(strtolower($header), 'content-type: ') === 0;
        };
        if (count(array_filter($headers, $iscontenttypeheader)) === 0) {
            $headers[] = 'Content-Type: application/json';
        }

        return $this->request($url, $headers, $options);
    }

    public function put($resourcename, $params = [], $headers = [], $options = []) {
        $url = $this->build_url($resourcename);
        $options['CUSTOMREQUEST'] = "PUT";
        $options['POSTFIELDS'] = json_encode($params);

        $iscontenttypeheader = function($header) {
            return strpos(strtolower($header), 'content-type: ') === 0;
        };
        if (count(array_filter($headers, $iscontenttypeheader)) === 0) {
            $headers[] = 'Content-Type: application/json';
        }

        return $this->request($url, $headers, $options);
    }

    private function build_url($resourcename, $params = []) {
        $url = $this->endpoint . '/' . $resourcename;

        $params = array_filter($params, function($value) {
            return isset($value) && $value !== '';
        });
        if (!empty($params)) {
            $url .= '?' . http_build_query($params, '', '&');
        }

        return $url;
    }

    public function request($url, $headers, $options) {
        $isacceptheader = function($header) {
            return strpos(strtolower($header), 'accept: ') === 0;
        };
        if (count(array_filter($headers, $isacceptheader)) === 0) {
            $headers[] = 'Accept: application/json';
        }

        $options['HEADER'] = 0;
        $options['HTTPHEADER'] = $headers;
        $options['FRESH_CONNECT'] = true;
        $options['RETURNTRANSFER'] = true;
        $options['FORBID_REUSE'] = true;
        $options['SSL_VERIFYPEER'] = true;
        $options['SSL_VERIFYHOST'] = 2;
        $options['CONNECTTIMEOUT'] = 0;
        $options['TIMEOUT'] = 20;

        $response = $this->curl->request($url, $options);

        if ($this->curl->errno !== CURLE_OK) {
            if ($this->curl->errno == CURLE_OPERATION_TIMEOUTED) {
                throw new rest_client_timeout_exception($url);
            } else {
                $error = empty($this->curl->error) ? $response : "{$this->curl->error} ({$this->curl->errno})";
                debugging($error);
                throw new \Exception("Error calling goone API: " . $error . " (Called URL $url)");
            }
        }

        $info = $this->curl->get_info();

        if ($info['http_code'] == 200) {
            if (self::is_content_type_json($info['content_type'])) {
                $data = json_decode($response);
                if (json_last_error() == 0) {
                    if (empty($data) or !is_object($data)) {
                        throw new \Exception("Empty response returned from goone API (Called URL $url)");
                    }
                    return $data;
                } else {
                    $error = json_last_error_msg();
                    debugging($response);
                    throw new \Exception("JSON error parsing response from goone API: $error (Called URL $url)");
                }
            } else {
                return $response;
            }
        } else if ($info['http_code'] == 204) {
            // No content
            return null;
        } else if ($info['http_code'] == 401) {
            throw new invalid_token_exception($url);
        } else {
            $message = "Unexpected response from goone API. Received " . $info['http_code'];
            if ($info['content_type'] === 'application/json') {
                $data = json_decode($response);
                if (json_last_error() == 0 and isset($data->message)) {
                    $message .= " $data->message";
                } else {
                    $message .= " $response";
                }
            }
            $message .= " (Called URL $url)";
            throw new \Exception($message);
        }

    }

    static public function is_content_type_json($contenttype) {
        $contenttype = strtolower($contenttype);
        if ($contenttype === 'application/json' || $contenttype === 'application/json; charset=utf-8') {
            return true;
        } else {
            return false;
        }
    }
}
