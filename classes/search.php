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
 * @package totara_contentmarketplace
 */

namespace contentmarketplace_goone;

use totara_contentmarketplace\local;

defined('MOODLE_INTERNAL') || die();

final class search extends \totara_contentmarketplace\local\contentmarketplace\search {

    // Max API limit for search results is 50. However 48 happens to be a better fit for a grid of search results.
    // 48 divides by 4, 3, and 2 so then a full page of results will always finishes with a complete row.
    const SEARCH_PAGE_SIZE = 48;

    /**
     * List sorting options aviable for the search results.
     *
     * @return array
     */
    public function sort_options() {
        $options = array(
            'created:desc',
            'relevance',
            'popularity',
            'price',
            'price:desc',
            'title',
        );
        return $options;
    }

    public function query($query, $sort, $filter, $page, $isfirstquerywithdefaultsort, $mode) {
        $api = new api();
        $hits = array();

        if ($isfirstquerywithdefaultsort) {
            $sort = 'relevance';
        }

        $params = array(
            "keyword" => $query,
            "sort" => $sort,
            "offset" => $page * self::SEARCH_PAGE_SIZE,
            "limit" => self::SEARCH_PAGE_SIZE,
            "facets" => "tag,language,instance",
        );
        foreach (array("tags", "language", "provider") as $name) {
            if (key_exists($name, $filter)) {
                $params[$name] = $filter[$name];
            }
        }
        $availability_selection = $this->availability_selection($filter, $mode);
        $params += $this->availability_query($availability_selection);

        $response = $api->get_learning_objects($params);
        foreach ($response->hits as $hit) {

            $delivery = array();
            if ($hit->delivery->duration > 0) {
                $title = self::duration($hit);
                $delivery[] = array("title" => $title);
            }
            if ($hit->delivery->mode) {
                $title = $hit->delivery->mode;
                $delivery[] = array("title" => $title);
            }
            if (!empty($delivery)) {
                $delivery[count($delivery) - 1]["last"] = true;
            }

            $hits[] = array(
                "id" => $hit->id,
                "title" => $hit->title,
                "image" => $hit->image,
                "provider" => array(
                    "name" => $hit->provider->name,
                ),
                "delivery" => $delivery,
                "delivery_has_items" => !empty($delivery),
                "price" => self::price($hit),
                "is_in_collection" => $hit->portal_collection,
            );
        }

        $results = new local\contentmarketplace\search_results();
        $results->hits = $hits;

        $results->filters = array();

        $availability_filter = $this->availability_filter($availability_selection, $params);
        if (isset($availability_filter)) {
            $results->filters[] = $availability_filter;
        }

        $tags = array();
        $nonzerofilters = array();
        foreach ($response->facets->tag->buckets as $bucket) {
            $checked = (key_exists("tags", $filter) and in_array($bucket->key, $filter["tags"]));
            if ($checked) {
                $nonzerofilters[] = $bucket->key;
            }
            $tags[] = array(
                "value" => $bucket->key,
                "label" => $bucket->key,
                "formatcount" => local::format_integer($bucket->doc_count),
                "count" => $bucket->doc_count,
                "checked" => $checked,
            );
        }
        if (key_exists("tags", $filter)) {
            foreach ($filter["tags"] as $value) {
                if (!in_array($value, $nonzerofilters)) {
                    $tags[] = array(
                        "value" => $value,
                        "label" => $value,
                        "formatcount" => local::format_integer(0),
                        "count" => 0,
                        "checked" => true,
                    );
                }
            }
        }
        $tag_filter = array(
            "name" => "tags",
            "label" => get_string('filter:tags', 'contentmarketplace_goone'),
            "template" => "totara_contentmarketplace/filter_checkboxes",
            "paginated_options" => self::paginate(self::sort($tags)),
        );
        $results->filters[] = $tag_filter;

        $providers = array();
        $nonzerofilters = array();
        foreach ($response->facets->instance->buckets as $bucket) {
            $checked = (key_exists("provider", $filter) and in_array($bucket->key, $filter["provider"]));
            if ($checked) {
                $nonzerofilters[] = $bucket->key;
            }
            $providers[] = array(
                "value" => $bucket->key,
                "label" => $bucket->name,
                "formatcount" => local::format_integer($bucket->doc_count),
                "count" => $bucket->doc_count,
                "checked" => $checked,
            );
        }
        if (key_exists("provider", $filter)) {
            foreach ($filter["provider"] as $value) {
                if (!in_array($value, $nonzerofilters)) {
                    $providers[] = array(
                        "value" => $value,
                        "label" => $value,
                        "formatcount" => local::format_integer(0),
                        "count" => 0,
                        "checked" => true,
                    );
                }
            }
        }
        $provider_filter = array(
            "name" => "provider",
            "label" => get_string('filter:provider', 'contentmarketplace_goone'),
            "template" => "totara_contentmarketplace/filter_checkboxes",
            "paginated_options" => self::paginate(self::sort($providers)),
        );
        $results->filters[] = $provider_filter;

        $languages = array();
        $nonzerofilters = array();
        $stringmanager = new string_manager();
        foreach ($response->facets->language->buckets as $bucket) {
            $checked = (key_exists("language", $filter) and in_array($bucket->key, $filter["language"]));
            if ($checked) {
                $nonzerofilters[] = $bucket->key;
            }
            $label = $stringmanager->get_language($bucket->key);
            $languages[] = array(
                "value" => $bucket->key,
                "label" => $label,
                "formatcount" => local::format_integer($bucket->doc_count),
                "count" => $bucket->doc_count,
                "checked" => $checked,
            );
        }
        if (key_exists("language", $filter)) {
            foreach ($filter["language"] as $value) {
                if (!in_array($value, $nonzerofilters)) {
                    $label = $stringmanager->get_language($value);
                    $languages[] = array(
                        "value" => $value,
                        "label" => $label,
                        "formatcount" => local::format_integer(0),
                        "count" => 0,
                        "checked" => true,
                    );
                }
            }
        }
        $language_filter = array(
            "name" => "language",
            "label" => get_string('filter:language', 'contentmarketplace_goone'),
            "template" => "totara_contentmarketplace/filter_checkboxes",
            "paginated_options" => self::paginate(self::sort($languages)),
        );
        $results->filters[] = $language_filter;

        $results->total = $response->total;

        $results->more = $response->total > ($page + 1) * self::SEARCH_PAGE_SIZE;
        $results->sort = $sort;

        if (!empty($params['collection'])) {
            $results->selectionmode = 'remove';
        } else {
            $results->selectionmode = 'add';
        }

        return $results;
    }

    public function availability_filter($selection, $params) {
        $api = new api();

        $all = array(
            "value" => "all",
            "label" => get_string("availability-filter:all", "contentmarketplace_goone"),
            "formatcount" => local::format_integer($api->get_learning_objects_total_count($params)),
            "checked" => $selection === "all",
        );

        $subscribed = array(
            "value" => "subscribed",
            "label" => get_string("availability-filter:subscription", "contentmarketplace_goone"),
            "formatcount" => local::format_integer($api->get_learning_objects_subscribed_count($params)),
            "checked" => $selection === "subscribed",
        );

        $collection = array(
            "value" => "collection",
            "label" => get_string("availability-filter:collection", "contentmarketplace_goone"),
            "formatcount" => local::format_integer($api->get_learning_objects_collection_count($params)),
            "checked" => $selection === "collection",
        );

        $content_settings = get_config('contentmarketplace_goone', 'content_settings_creators');
        $context = \context_system::instance();
        if (has_capability('totara/contentmarketplace:config', $context)) {
            $options = array($all, $subscribed, $collection);
        } elseif (has_capability('totara/contentmarketplace:add', $context)) {
            switch ($content_settings) {
                case "all":
                    $options = array($all, $subscribed, $collection);
                    break;
                case "subscribed":
                    $options = array($subscribed, $collection);
                    break;
                default:
                    return null;
            }
        } else {
            return null;
        }

        $filter = array(
            "name" => "availability",
            "label" => get_string('filter:availability', 'contentmarketplace_goone'),
            "template" => "totara_contentmarketplace/filter_radios",
            "options" => $options,
        );
        return $filter;
    }

    public function availability_selection($filter, $mode) {
        if (key_exists("availability", $filter)) {
            $selection = $filter["availability"];
            if (!in_array($selection, array("all", "subscribed", "collection"))) {
                $selection = null;
            }
        } else if ($mode == 'explore-collection') {
            $selection = 'collection';
        } else {
            $selection = null;
        }

        $context = \context_system::instance();
        if (has_capability('totara/contentmarketplace:config', $context)) {
            if (!isset($selection)) {
                $selection = "all";
            }
        } else if (has_capability('totara/contentmarketplace:add', $context)) {
            if (!isset($selection)) {
                $selection = "all";
            }
            $contentsettingscreators = get_config('contentmarketplace_goone', 'content_settings_creators');
            switch ($contentsettingscreators) {
                case "subscribed":
                    if ($selection === "all") {
                        $selection = "subscribed";
                    }
                    break;
                case "collection":
                    $selection = "collection";
                    break;
            }
        } else {
            $selection = null;
        }

        return $selection;
    }

    public function availability_query($selection) {
        switch ($selection) {
            case 'subscribed':
                $query = ["subscribed" => "true"];
                break;
            case 'collection':
                $query = ["collection" => "default"];
                break;
            default:
                $query = [];
        }
        return $query;
    }

    public function select_all($query, $filter, $mode) {
        $params = array(
            "keyword" => $query,
        );
        foreach (array("tags", "language", "provider") as $name) {
            if (key_exists($name, $filter)) {
                $params[$name] = $filter[$name];
            }
        }
        $availability_selection = $this->availability_selection($filter, $mode);
        $params += $this->availability_query($availability_selection);

        $api = new api();
        return $api->list_ids_for_all_learning_objects($params);
    }

    static public function price($course) {
        if (!empty($course->subscription) and ($course->subscription->licenses === -1 or $course->subscription->licenses > 0)) {
            return get_string('price:included', 'contentmarketplace_goone');
        }
        if ($course->pricing->price === 0) {
            return get_string('price:free', 'contentmarketplace_goone');
        }
        $price = local::format_money($course->pricing->price, $course->pricing->currency);
        if (!$course->pricing->tax_included and $course->pricing->tax > 0) {
            $price .= " (+{$course->pricing->tax}% " . get_string('price:tax', 'contentmarketplace_goone') . ")";
        }
        return $price;
    }

    static public function duration($course) {
        if (empty($course->delivery) || empty($course->delivery->duration)) {
            return '';
        }
        return get_string('duration', 'contentmarketplace_goone', $course->delivery->duration);
    }

    public function get_details($id) {
        try {
            $api = new api();
            $lo = $api->get_learning_object($id);
        } catch (\Exception $ex) {
            debugging($ex->getMessage());
            return;
        }

        return $lo;
    }
}
