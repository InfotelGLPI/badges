/**
 * -------------------------------------------------------------------------
 * badges plugin for GLPI
 * Copyright (C) 2015-2026 by the badges Development Team.
 *
 * https://github.com/InfotelGLPI/badges
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of badges.
 *
 * badges is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * badges is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with badges. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

function badges_initJs(root_doc) {
    this.usedBadges = new Array();
    this.root_doc = root_doc;
}

/**
 * badges_execInlineScripts : run the <script> blocks embedded in an AJAX HTML
 * fragment WITHOUT eval(). Each script body is executed by appending a fresh
 * DOM <script> element (same technique as jQuery's globalEval), which removes
 * the eval() anti-pattern while preserving the dropdown init behaviour.
 *
 * @param html the raw HTML fragment returned by the server
 */
this.badges_execInlineScripts = function (html) {
    var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
    while (scripts = scriptsFinder.exec(html)) {
        var script = document.createElement('script');
        script.text = scripts[1];
        document.head.appendChild(script);
        document.head.removeChild(script);
    }
};

/**
 * badges_add_custom_values : add text input
 *
 * @param action
 * @param toobserve
 * @param toupdate
 */
this.badges_addToCart = function (action, toobserve, toupdate) {

    var object = this;

    var formInput = getFormData(toobserve);

    $.ajax({
         url: object.root_doc + '/ajax/request.php',
         type: "POST",
         dataType: "json",
         data: 'action=' + action + '&' + formInput,
         success: function (data) {
            if (data.success) {
                var item_bloc = $('#' + toupdate);

                // Security (DOM XSS): build the cart row with DOM nodes and
                // text()/val() rather than HTML string concatenation, so the
                // visitor-supplied fields (firstname, realname, society) can
                // never be parsed as markup. Values are kept raw here; they are
                // stored via addBadges() and re-displayed through auto-escaping
                // Twig templates, so no server-side escaping is applied (it
                // would otherwise be persisted and double-escaped).
                var $row = $('<tr>', {id: 'badges_cartRow' + data.rowId});

                // Insert row in cart
                $.each(data.fields, function (index, row) {
                    var $cell = $('<td>').text(row.label);
                    $('<input>', {
                        type: 'hidden',
                        id:   index,
                        name: 'badges_cart[' + data.rowId + '][' + index + ']'
                    }).val(row.value).appendTo($cell);
                    $row.append($cell);

                    // Push used badges
                    if (index == 'badges_id' && row.value != 0) {
                        object.usedBadges.push(row.value);
                    }
                });

                var $removeCell = $('<td>');
                $('<a>', {href: '#'})
                    .on('click', function () {
                        badges_removeCart('badges_cartRow' + data.rowId);
                        return false;
                    })
                    .append($('<i>', {'class': 'ti ti-circle-x fa-2x'}).css('color', 'darkred'))
                    .appendTo($removeCell);
                $row.append($removeCell);

                item_bloc.append($row);
                item_bloc.css({"display": 'table'});

                // Reload badge list
                badges_reloadAvailableBadges();

            } else {
               glpi_html_dialog({
                  title: __("Add to cart", "badges"),
                  body: data.message,
                  id: 'add_badges',
                  buttons: [{
                     label: __("Close"),
                     click: function(event) {
                        window.location.reload();
                     }
                  }],
               })
            }
         }
      });
};

/**
 * Add badges
 *
 * @param action
 * @param toobserve
 */
this.badges_addBadges = function (action, toobserve) {

    var object = this;

    var formInput = getFormData(toobserve);

    $.ajax({
         type: "POST",
         dataType: "json",
         url: object.root_doc + '/ajax/request.php',
         data: 'action=' + action + '&' + formInput,
         success: function (data) {
            glpi_html_dialog({
               title: __("Add to cart", "badges"),
               body: data.message,
               id: 'add_badges',
               buttons: [{
                  label: __("Close"),
                  click: function(event) {
                     window.location.reload();
                  }
               }],
            })
         },
       fail: function (data) {
          glpi_html_dialog({
             title: __("Add to cart", "badges"),
             body: data.message,
             id: 'add_badges',
             buttons: [{
                label: __("Close"),
                click: function(event) {
                   window.location.reload();
                }
             }],
          })
       }
      });
};

/**
 * Return badges
 *
 * @param action
 * @param toobserve
 */
this.badges_returnBadges = function (action, toobserve) {

    var object = this;

    var formInput = getFormData(toobserve);

    $.ajax({
         type: "POST",
         dataType: "json",
         url: object.root_doc + '/ajax/request.php',
         data: 'action=' + action + '&' + formInput,
         success: function (data) {
            glpi_html_dialog({
               title: __("Badge return", "badges"),
               body: data.message,
               id: 'return_badges',
               buttons: [{
                  label: __("Close"),
                  click: function(event) {
                     window.location.reload();
                  }
               }],
            })
         }
      });
};

/**
 * Search badges
 *
 * @param action
 * @param toobserve
 * @param toupdate
 */
this.badges_searchBadges = function (action, toobserve, toupdate) {
    var formInput = getFormData(toobserve);

    $.ajax({
         type: "POST",
         dataType: "json",
         url: object.root_doc + '/ajax/request.php',
         data: 'action=' + action + '&' + formInput,
         success: function (data) {
            var result = data.message;
            var item_bloc = $('#' + toupdate);
            item_bloc.html(result);

            object.badges_execInlineScripts(result);
         }
      });
};

/**
 * Reload available badges
 *
 */
this.badges_reloadAvailableBadges = function () {

    var object = this;

    $.ajax({
         type: "POST",
         url: object.root_doc + '/ajax/request.php',
         data: {
            'action': 'reloadAvailableBadges',
            'used': object.usedBadges
         },
         success: function (result) {
            var item_bloc = $('#badges_available');
            item_bloc.html(result);

            object.badges_execInlineScripts(result);
         }
      });
};

/**
 * badges_removeCart : delete text input
 *
 * @param field_id
 */
this.badges_removeCart = function (field_id) {

    var object = this;

    var value = $("tr[id=" + field_id + "] input[id=badges_id]").val();

    // Remove element from used badges variable
   for (var i = 0; i < object.usedBadges.length; i++) {
      if (object.usedBadges[i] === value) {
          object.usedBadges.splice(i, 1);
      }
   }
    // Reload badge list
    badges_reloadAvailableBadges();

    var item_bloc = $('#' + field_id);

    // Cart not visible if no data
   if (object.usedBadges.length === 0) {
       item_bloc.parent('table').css({'display': 'none'});
   }

    // Remove cart row
    $('#' + field_id).remove();
};

/**
 * Cancel wizard
 *
 * @param url
 */
this.badges_cancel = function (url) {
    window.location.href = url;
};

/**
 *  Get the form values and construct data url
 *
 * @param object form
 */
this.getFormData = function (form) {
   if (typeof (form) !== 'object') {
       var form = $('#' + form);
   }

    return encodeParameters(form[0]);
};

/**
 * Encode form parameters for URL
 *
 * @param elements
 */
this.encodeParameters = function (elements) {
    var kvpairs = [];

    $.each(elements, function (index, e) {
      if (e.name != '') {
         switch (e.type) {
            case 'radio':
            case 'checkbox':
               if (e.checked) {
                   kvpairs.push(encodeURIComponent(e.name) + "=" + encodeURIComponent(e.value));
               }
                  break;
            case 'select-multiple':
               var name = e.name.replace("[", "").replace("]", "");
               $.each(e.selectedOptions, function (index, option) {
                   kvpairs.push(encodeURIComponent(name + '[' + option.index + ']') + '=' + encodeURIComponent(option.value));
               });
                  break;
            default:
               kvpairs.push(encodeURIComponent(e.name) + "=" + encodeURIComponent(e.value));
                  break;
         }
      }
    });

    return kvpairs.join("&");
};
