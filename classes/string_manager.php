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

final class string_manager {

    private $manager;
    private $languages;

    public function __construct($config = null) {
        $this->manager = get_string_manager();
        $this->languages = $this->manager->get_list_of_languages();
    }

    public function get_language($lang) {
        if (array_key_exists($lang, $this->languages)) {
            return $this->languages[$lang];
        }
        if (strpos($lang, '-') > 0) {
            list($langcode, $countrycode) = explode('-', $lang, 2);
            if (array_key_exists($langcode, $this->languages)) {
                $string = $this->languages[$langcode];
                $countrycode = clean_param(strtoupper($countrycode), PARAM_STRINGID);
                if ($this->manager->string_exists($countrycode, 'core_countries')) {
                    return $string . " (" . get_string($countrycode, 'core_countries') . ")";
                }
            }
        }
        if (empty($lang)) {
            return get_string('unknownlanguage', 'contentmarketplace_goone');
        }
        return $lang;
    }

    public function get_region($region) {
        if (empty($region)) {
            return '';
        }
        $identifier = 'region:' . clean_param($region, PARAM_STRINGID);
        if ($this->manager->string_exists($identifier, 'contentmarketplace_goone')) {
            return get_string($identifier, 'contentmarketplace_goone');
        }
        return $region;
    }

}
