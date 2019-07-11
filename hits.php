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
 * Retrieves and renders GO1 search result items
 *
 * @package   mod_goone
 * @copyright 2019, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/goone/lib.php');
require_login();

$config = get_config('mod_goone');

$mode = required_param('mode', PARAM_TEXT);
$id = required_param('id', PARAM_INT);
$keyword = optional_param('keyword', '', PARAM_RAW);
$provider = optional_param('provider', '', PARAM_RAW);
$provider = explode(',', $provider);
$language = optional_param('language', '', PARAM_RAW);
$language = explode(',', $language);
$tag = optional_param('tag', '', PARAM_RAW);
$tag = explode(',', $tag);
$price = optional_param('price', '', PARAM_RAW);
$type = optional_param('type', '', PARAM_RAW);
$sub = optional_param('subscribed', '', PARAM_RAW);
$page = optional_param('page', '', PARAM_RAW);
$sort = optional_param('sort', '', PARAM_RAW);
$offset = optional_param('offset', '', PARAM_RAW);
$limit = 20;

goone_check_capabilities($mode, $id);

if (!$page) {
    $page = 0;
}
if ($config->filtersel == 1) {
    $sub = "true";
}
if ($config->filtersel == 2) {
    $coll = "default";
    $sub = "";
}
if ($config->filtersel == 0) {
    $sub = "";
    $coll = "";
}

$data = array (
    'keyword' => $keyword,
    'price%5Bmax%5D' => $price,
    'type' => $type,
    'subscribed' => $sub,
    'offset' => $offset,
    'collection' => $coll,
    'sort' => $sort,
    'limit' => $limit);

$params = "";
foreach ($data as $key => $value) {
    if (!$value == '') {
        $params .= $key.'='.$value.'&';
    }
}
$params = trim($params, '&');

foreach ($language as $lang) {
    if ($lang) {
        $params .= "&language%5B%5D=".$lang;
    }
}
foreach ($tag as $ta) {
    if ($ta) {
        $params .= "&tags%5B%5D=".$ta;
    }
}
foreach ($provider as $prov) {
    if ($prov) {
        $params .= "&provider%5B%5D=".$prov;
    }
}

$response = goone_get_hits($params);
$response = json_decode($response, true);

foreach ($response['hits'] as &$obj) {
    $obj['description'] = goone_clean_hits($obj['description']);
    $obj['pricing']['price'] = '$'.$obj['pricing']['price'];
    // Set the "Included" or "Free" flag on each result.
    if (!empty($obj['subscription']) and ($obj['subscription']['licenses'] === -1 or $obj['subscription']['licenses'] > 0)) {
        $obj['pricing']['price'] = get_string('included', 'goone');
    }
    if ($obj['pricing']['price'] === "$0") {
        $obj['pricing']['price'] = get_string('free', 'goone');
    }
}


$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('embedded');
echo $OUTPUT->render_from_template('mod_goone/hits', $response);

