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

use totara_form\form\element\radios;

defined('MOODLE_INTERNAL') || die();

final class content_settings_form extends \totara_form\form {

    public function get_action_url() {
        return new \moodle_url('/totara/contentmarketplace/marketplaces.php', array(
            'id' => 'goone',
            'tab' => 'content_settings',
        ));
    }

    protected function definition() {

        $explorecollectionurl = new \moodle_url('/totara/contentmarketplace/explorer.php', [
            'marketplace' => 'goone',
            'mode' => 'explore-collection',
        ]);
        $this->model->add(new radios(
            'creators',
            get_string('content_creators', 'contentmarketplace_goone'),
            array(
                'all' => s(get_string(
                    'all_content',
                    'contentmarketplace_goone',
                    $this->parameters['courses_all']
                )),
                'subscribed' => s(get_string(
                    'subscribed_content',
                    'contentmarketplace_goone',
                    $this->parameters['courses_subscribed']
                )),
                'collection' => s(get_string(
                    'collection_content',
                    'contentmarketplace_goone',
                    $this->parameters['courses_collection']
                )) . ' ' . \html_writer::link($explorecollectionurl, get_string('explore', 'totara_contentmarketplace')),
            )
        ))->add_help_button('content_creators', 'contentmarketplace_goone');

        // This one stays disabled until further notice.
        /*
        $this->model->add(new radios(
            'learners',
            get_string('learners', 'contentmarketplace_goone'),
            array(
                'all' => s(get_string('all_content', 'contentmarketplace_goone', $this->parameters['courses_all'])),
                'subscribed' => s(get_string('subscribed_content', 'contentmarketplace_goone', $this->parameters['courses_subscribed'])),
                'specific' => s(get_string('specific_collection', 'contentmarketplace_goone')),
                'none' => s(get_string('no_content', 'contentmarketplace_goone')),
            )
        ));
         */

        $payperseat = new radios(
            'pay_per_seat',
            get_string('pay_per_seat', 'contentmarketplace_goone'),
            array(
                '0' => s(get_string('pay_per_seat:learner', 'contentmarketplace_goone')),
                '1' => s(get_string('pay_per_seat:admin', 'contentmarketplace_goone')),
            )
        );
        $payperseat->add_help_button('pay_per_seat', 'contentmarketplace_goone');
        $payperseat->set_frozen(true);
        $this->model->add($payperseat);

        $this->model->add_action_buttons(false);
    }

}
