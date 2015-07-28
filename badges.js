(function ($) {
    $.fn.badges = function (options) {

        var object = this;
        init();

        // Start the plugin
        function init() {
            object.usedBadges = new Array();
            object.params = new Array();
            object.params['root_doc'] = '';

            if (options != undefined) {
                $.each(options, function (index, val) {
                    if (val != undefined && val != null) {
                        object.params[index] = val;
                    }
                });
            }
        }

        /**
         * badges_add_custom_values : add text input 
         * 
         * @param action  
         * @param toobserve  
         */
        this.badges_addToCart = function (action, toobserve, toupdate) {
            var formInput = object.getFormData(toobserve);

            $.ajax({
                url: object.params['root_doc'] + '/plugins/badges/ajax/request.php',
                type: "POST",
                dataType: "json",
                data: 'action='+action+'&' + formInput,
                success: function (data) {
                    if (data.success) {
                        var item_bloc = $('#' + toupdate);
                        var result = "<tr id='badges_cartRow" + data.rowId + "'>\n";

                        // Insert row in cart
                        $.each(data.fields, function (index, row) {
                            result += "<td>" + row.label.replace(/\\["|']/g, '"') + "<input type='hidden' id='" + index + "' name='badges_cart[" + data.rowId + "][" + index + "]' value='" + row.value + "'></td>\n";

                            // Push used badges
                            if (index == 'badges_id' && row.value != 0) {
                                object.usedBadges.push(row.value);
                            }
                        });
                        result += "<td><img style=\"cursor:pointer\" src=\"" + object.params['root_doc'] + "/plugins/badges/pics/delete.png\" onclick=\"badges.badges_removeCart('badges_cartRow" + data.rowId + "')\"></td></tr>";
                        item_bloc.append(result);
                        item_bloc.css({"display": 'table'});

                        // Reload badge list
                        object.badges_reloadAvailableBadges();

                    } else {
                        $("#dialog-confirm").html(data.message);
                        $("#dialog-confirm").dialog({
                            resizable: false,
                            height: 180,
                            width: 350,
                            modal: true,
                            buttons: {
                                OK: function () {
                                    $(this).dialog("close");
                                }
                            }
                        });
                    }
                }
            });
        }

        /**
         * Add badges
         * 
         * @param action  
         * @param toobserve                
         */
        this.badges_addBadges = function (action, toobserve) {
            var formInput = object.getFormData(toobserve);

            $.ajax({
                type: "POST",
                dataType: "json",
                url: object.params['root_doc'] + '/plugins/badges/ajax/request.php',
                data: 'action='+action+'&' + formInput,
                success: function (data) {
                    $("#dialog-confirm").html(data.message);
                    $("#dialog-confirm").dialog({
                        resizable: false,
                        height: 180,
                        width: 350,
                        modal: true,
                        buttons: {
                            OK: function () {
                                $(this).dialog("close");
                                window.location.reload();
                            }
                        }
                    });
                }
            });
        }
        
        /**
         * Return badges
         * 
         * @param action  
         * @param toobserve                
         */
        this.badges_returnBadges = function(action, toobserve) {
            var formInput = object.getFormData(toobserve);

            $.ajax({
                type: "POST",
                dataType: "json",
                url: object.params['root_doc'] + '/plugins/badges/ajax/request.php',
                data: 'action='+action+'&' + formInput,
                success: function (data) {
                    $("#dialog-confirm").html(data.message);
                    $("#dialog-confirm").dialog({
                        resizable: false,
                        height: 180,
                        width: 350,
                        modal: true,
                        buttons: {
                            OK: function () {
                                $(this).dialog("close");
                                window.location.reload();
                            }
                        }
                    });
                }
            });
        }
        
        /**
         * Search badges
         * 
         * @param field_id  
         * @param toobserve  
         * @param toupdate                  
         */
        this.badges_searchBadges = function(action, toobserve, toupdate) {
            var formInput = object.getFormData(toobserve);

            $.ajax({
                type: "POST",
                dataType: "json",
                url: object.params['root_doc'] + '/plugins/badges/ajax/request.php',
                data: 'action='+action+'&' + formInput,
                success: function (data) {
                    var result = data.message;
                    var item_bloc = $('#' + toupdate);
                    item_bloc.html(result);

                    var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
                    while (scripts = scriptsFinder.exec(result)) {
                        eval(scripts[1]);
                    }
                }
            });
        }
        
        /**
         * Reload available badges
         * 
         * @param field_id  
         */
        this.badges_reloadAvailableBadges = function() {
            $.ajax({
                type: "POST",
                url: object.params['root_doc'] + '/plugins/badges/ajax/request.php',
                data: {
                    'action': 'reloadAvailableBadges',
                    'used': object.usedBadges
                },
                success: function (result) {
                    var item_bloc = $('#badges_available');
                    item_bloc.html(result);

                    var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
                    while (scripts = scriptsFinder.exec(result)) {
                        eval(scripts[1]);
                    }
                }
            });
        }

        /**
         * badges_removeCart : delete text input 
         * 
         * @param field_id  
         */
        this.badges_removeCart = function(field_id) {
            var value = $("tr[id=" + field_id + "] input[id=badges_id]").val();

            // Remove element from used badges variable
            for (var i = 0; i < this.usedBadges.length; i++) {
                if (this.usedBadges[i] === value)
                    this.usedBadges.splice(i, 1);
            }
            // Reload badge list
            object.badges_reloadAvailableBadges();

            var item_bloc = $('#' + field_id);

            // Cart not visible if no data
            if (this.usedBadges.length === 0) {
                item_bloc.parent('table').css({'display': 'none'});
            }

            // Remove cart row
            $('#' + field_id).remove();
        }

        /**
         * Cancel wizard
         * 
         * @param field_id  
         */
        this.badges_cancel = function(url) {
            window.location.href = url;
        }

        /** 
         *  Get the form values and construct data url
         * 
         * @param object form
         */
        this.getFormData = function(form) {
            if (typeof (form) !== 'object') {
                var form = $('#' + form);
            }

            return object.encodeParameters(form[0]);
        }

        /** 
         * Encode form parameters for URL
         * 
         * @param array elements
         */
        this.encodeParameters = function(elements) {
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
        }

        return this;
    }
}(jQuery));


