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
 * @author Sergey Vidusov <sergey.vidusov@androgogic.com>
 * @package totara_contentmarketplace
 */

namespace mod_goone;

defined('MOODLE_INTERNAL') || die();

final class api {

    const ENDPOINT = 'https://api.goone.com/v2';
    const MAX_PAGE_SIZE = 50;
    const MAX_AVAILABLE_RESULTS = 10000;

    /** @var oauth_rest_client $client */
    private $client;
    /** @var cache $learningobjectcache internal cache for learning objects */
    private $learningobjectcache;

    public function __construct($config = null) {
        $config = isset($config) ? $config : new config_db_storage();
        $oauth = new oauth($config);
        $this->client = new oauth_rest_client(self::ENDPOINT, $oauth);
        $this->learningobjectcache = \cache::make('contentmarketplace_goone', 'goonewslearningobject');
    }

    public function get_learning_object($id) {
        $data = $this->learningobjectcache->get($id);
        if (!$data) {
            $data = $this->client->get('learning-objects/' . $id);
            $this->learningobjectcache->set($id, $data);
        }
        
        return $data;
    }

    public function get_scorm($id) {
        $url = 'learning-objects/' . $id . '/scorm';
        $headers = [
            'Accept: application/zip',
        ];
        return $this->client->get($url, [], $headers);
    }

    public function get_learning_objects(array $params = []) {
        $params['event'] = "false"; // Exclude events from API calls as we can't really handle them in the UI.
        $data = $this->client->get('learning-objects', $params);
        if (!isset($data->total) or !is_number($data->total)) {
            throw new \Exception('Response from goone API for "learning-objects" is missing expected value for "total"');
        }
        foreach ($data->hits as $hit) {
            $hit->id = (string)$hit->id;
            $this->learningobjectcache->set($hit->id, $hit);
        }
        return $data;
    }

    public function get_account() {
        $account = $this->client->get('account');
        // Populate any missing non-guaranteed properties with null.
        $account->plan->active_user_count = isset($account->plan->active_user_count) ? $account->plan->active_user_count : null;
        $account->plan->licensed_user_count = isset($account->plan->licensed_user_count) ? $account->plan->licensed_user_count : null;
        $account->plan->pricing = isset($account->plan->pricing) ? $account->plan->pricing : new \stdClass();
        $account->plan->pricing->currency = isset($account->plan->pricing->currency) ? $account->plan->pricing->currency : null;
        $account->plan->pricing->price = isset($account->plan->pricing->price) ? $account->plan->pricing->price : null;
        $account->plan->pricing->tax = isset($account->plan->pricing->tax) ? $account->plan->pricing->tax : null;
        $account->plan->pricing->tax_included = isset($account->plan->pricing->tax_included) ? $account->plan->pricing->tax_included : null;
        $account->plan->region = isset($account->plan->region) ? $account->plan->region : null;
        $account->plan->renewal_date = isset($account->plan->renewal_date) ? $account->plan->renewal_date : null;
        $account->plan->type = isset($account->plan->type) ? $account->plan->type : null;
        return $account;
    }

    public function get_configuration() {
        $configuration = $this->client->get('configuration');
        // Populate any missing non-guaranteed properties with null.
        $configuration->pay_per_seat = isset($configuration->pay_per_seat) ? $configuration->pay_per_seat : null;
        return $configuration;
    }

    public function save_configuration($data) {
        return $this->client->put('configuration', $data);
    }

    /**
     * @return int The total number of all packages for this account
     */
    public function get_learning_objects_total_count($params = array()) {
        unset($params["subscribed"]);
        unset($params["collection"]);
        $params["limit"] = 0;
        return $this->get_learning_objects($params)->total;
    }

    /**
     * @return int The total number of subscribed packages for this account
     */
    public function get_learning_objects_subscribed_count($params = array()) {
        $params["subscribed"] = "true";
        unset($params["collection"]);
        $params["limit"] = 0;
        return $this->get_learning_objects($params)->total;
    }

    /**
     * @return int The total number of packages for the given collection
     */
    public function get_learning_objects_collection_count($params = array(), $collectionid = 'default') {
        unset($params["subscribed"]);
        $params['collection'] = $collectionid;
        $params["limit"] = 0;
        return $this->get_learning_objects($params)->total;
    }


    /**
     * @return array Listing of all the learning objects id's for the given filter
     */
    public function list_ids_for_all_learning_objects($params = []) {
        $ids = [];
        for ($page = 0; $page < self::MAX_AVAILABLE_RESULTS/self::MAX_PAGE_SIZE; $page += 1) {
            $params['offset'] = $page * self::MAX_PAGE_SIZE;
            $params['limit'] = self::MAX_PAGE_SIZE;
            $response = $this->get_learning_objects($params);
            foreach ($response->hits as $hit) {
                $ids[] = $hit->id;
            }
            if (count($ids) >= $response->total) {
                break;
            }
        }
        return $ids;
    }

    private function update_collection($operation, $items, $collectionid) {
        if (empty($items)) {
            return;
        }

        $this->learningobjectcache->delete_many($items);
        $items = array_map(function($value) {return (int)$value;}, $items);
        for ($n = 0; $n < count($items); $n += self::MAX_PAGE_SIZE) {
            $data = [
                'lo' => array_slice($items, $n, self::MAX_PAGE_SIZE),
            ];
            $this->client->post('collections/' . $collectionid . '/items/' . $operation, $data);
        }
    }

    /**
     * Adds items to collection.
     *
     * @param array of item IDs.
     * @param string $collection_id Collection ID ("default" by default).
     * @return type
     */
    public function add_to_collection($items, $collectionid = 'default') {
        $this->learningobjectcache->delete_many($items);
        return $this->update_collection('add', $items, $collectionid);
    }

    /**
     * Removes items from collection.
     *
     * @param array of item IDs.
     * @param string $collection_id Collection ID ("default" by default).
     * @return type
     */
    public function remove_from_collection($items, $collectionid = 'default') {
        $this->learningobjectcache->delete_many($items);
        return $this->update_collection('remove', $items, $collectionid);
    }

}
