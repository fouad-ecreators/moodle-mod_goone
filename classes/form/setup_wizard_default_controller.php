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

namespace contentmarketplace_goone\form;

use contentmarketplace_goone\api;
use contentmarketplace_goone\config_session_storage;
use totara_contentmarketplace\local;

final class setup_wizard_default_controller extends \totara_form\form_controller {

    /** @var \totara_form\form\testform\group_wizard $form */
    protected $form;

    /**
     * This method is responsible for:
     *  - access control
     *  - getting of current data
     *  - getting of parameters
     *
     * and returning of the form instance.
     *
     * @param string $idsuffix string extra for identifier to allow repeated forms on one page
     * @return form
     */
    public function get_ajax_form_instance($idsuffix) {
        // Access control first.
        require_login();
        require_sesskey();
        $syscontext = \context_system::instance();
        require_capability('totara/contentmarketplace:config', $syscontext);

        $api = new api(new config_session_storage());
        $configuration = $api->get_configuration();
        $all = $api->get_learning_objects_total_count();
        $subscribed = $api->get_learning_objects_subscribed_count();
        $collection = $api->get_learning_objects_collection_count();
        $pay_per_seat = $configuration->pay_per_seat;
        $pay_per_seat = isset($pay_per_seat) ? (int) $pay_per_seat : null;

        $currentdata = [
            'creators' => 'all',
            'pay_per_seat' => $pay_per_seat,
        ];
        $parameters = [
            'courses_all' => (!empty($all)) ? local::format_integer($all) : 'N/A',
            'courses_subscribed' => (!empty($subscribed)) ? local::format_integer($subscribed) : 'N/A',
            'courses_collection' => (!empty($collection)) ? local::format_integer($collection) : 'N/A',
        ];
        $this->form = new setup_form($currentdata, $parameters, $idsuffix);

        return $this->form;
    }

    /**
     * Process the submitted form.
     *
     * @return array processed data
     */
    public function process_ajax_data() {
        $result = array();
        $result['data'] = (array)$this->form->get_data();
        return $result;
    }
}
