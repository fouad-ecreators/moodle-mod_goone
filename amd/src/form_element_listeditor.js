/**
 * This file is part of Totara LMS
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
 * @author  Greg Newton <greg.newton@androgogic.com>
 * @package totara_form
 */

/**
 * @module  totara_form/form_element_radios
 * @class   Listeditor
 * @author  Greg Newton <greg.newton@androgogic.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'totara_form/form'], function($, Form) {

    /**
     * Listeditor constructor
     *
     * @class
     * @constructor
     * @augments Form.Element
     *
     * @param {(Form|Group)} parent
     * @param {string} type
     * @param {string} id
     * @param {HTMLElement} node
     */
    function ListeditorElement(parent, type, id, node) {

        if (!(this instanceof ListeditorElement)) {
            return new ListeditorElement(parent, type, id, node);
        }

        Form.Element.apply(this, arguments);

        this.container = null;
        this.inputs = null;
        this.validationerroradded = false;

    }

    ListeditorElement.prototype = Object.create(Form.Element.prototype);
    ListeditorElement.prototype.constructor = ListeditorElement;

    /**
     * Returns a string describing this object.
     * @returns {string}
     */
    ListeditorElement.prototype.toString = function() {
        return '[object ListeditorElement]';
    };

    /**
     * Initialises a new instance of this element.
     * @param {Function} done
     */
    ListeditorElement.prototype.init = function(done) {
        var id = this.id,
            container = $('#' + id),
            rows = $('#' + id + ' tr');
        rows.find('a').click($.proxy(this.removeItem, this));
        if (rows.length === 1) {
            $('a', container).hide();
        }
        this.container = container;
        done();
    };

    // Reverted this to an older version for Totara 11 and earier as T12 was upgraded
    // to support arrays in validators.
    ListeditorElement.prototype.getValue = function() {

        // Find the hidden fields and concat their values together.
        var id = this.id,
            inputs = $('#' + id + ' input[type=hidden]');

        var value = '';
        for (var i = 0, len = inputs.length; i < len; i++) {
            if (i > 0) {
                value = value + ",";
            }
            value = value + $(inputs[i]).val();
        }

        return value;
    };

    ListeditorElement.prototype.showLoading = function() {
        this.container.find('.tf_update_pending .tf_loading').show();
    };

    ListeditorElement.prototype.hideLoading = function () {
        this.container.find('.tf_loading').hide();
    };

    ListeditorElement.prototype.removeItem = function(event) {
        event.preventDefault();
        var row = $(event.target).closest('tr');
        row.addClass('tf_update_pending');
        row.find('input').remove();
        this.changed();
    };


    return ListeditorElement;
});