/**
 * Quill
 *
 */

(function ($, w) {
    "use strict";

    var quill = {
        // init()
        inputs_arr: [],
        quill_fields_dict: {},
        init: function () {
            var e = this;

            const addFromDynamicParent = function(elem)
            {
                var input = $('input.quill-field-input', elem).get(0);
                e.make(input);
            };

            const removeFromDynamicParent = function(elem) {
                var input = $('input.quill-field-input', elem).get(0),
                input_index = e.inputs_arr.indexOf(input);
                delete e.inputs_arr[input_index];
                delete e.quill_fields_dict[input_index];
            };

            Grid.bind("quill", "display", addFromDynamicParent);
            Grid.bind("quill", "remove", removeFromDynamicParent);
            FluidField.on("quill", "add", addFromDynamicParent);
            FluidField.on("quill", "remove", removeFromDynamicParent);

            $('form').each(function () {

                var form = this;

                $(form).on('submit', function() {
                    for (let key in e.quill_fields_dict) {
                        let ft = e.quill_fields_dict[key],
                        ft_form = ft.html_elems.form;

                        if(form !== ft_form) {
                            continue;
                        }

                        e.setInputValue(ft);
                    }

                });

                $('input.quill-field-input', form).not(':disabled').each(function()
                {
                    e.make(this);
                });
            });

        },
        make: function (i) {

            var e = this,
            input = $(i).get(0);

            if(e.inputs_arr.includes(input)) {
                return;
            }

            e.inputs_arr.push(input);

            var input_value = e.parseInputValue(input),
            input_index = e.inputs_arr.indexOf(input),
            form = $(input).parents('form').first().get(0),
            ops = e.getOpsFromInputValue(input_value),
            settings = e.parseInputSettings(input),
            field_con = $(input).parent().get(0),
            editor_elem = e.buildEditorElem('editor'),
            editor_con = e.buildEditorElem('editor-con', editor_elem);

            // clean items
            $(field_con).children().not(input).remove();
            $(field_con).append(editor_con);

            var quill = new Quill(editor_elem, settings);

            /* quill.on('text-change', function(delta, oldDelta, source) {
                if (source == 'api') {
                    console.log("An API call triggered this change.");
                } else if (source == 'user') {
                    console.log("A user action triggered this change.");
                }

                var range = quill.getSelection();
                console.log('User cursor is on', range);
            });

            quill.on('selection-change', function(range, oldRange, source) {
                if (range) {
                    if (range.length == 0) {
                        console.log('User cursor is on', range.index);
                    } else {
                    var text = quill.getText(range.index, range.length);
                        console.log('User has highlighted', text);
                    }
                } else {
                    console.log('Cursor not in the editor');
                }
            }); */

            var quill_ft = {
                input: input,
                quill: quill,
                html_elems: {
                    field_con: field_con,
                    editor_con: editor_con,
                    editor_elem: editor_elem,
                    form: form
                },
                setInputValueTimeout: null,
            };


            if (ops != null && typeof ops == 'object') {
                quill_ft.quill.setContents(ops);
                e.setInputValue(quill_ft);
            }

            // if($(form).hasClass('ajax-validate')) {
            //     quill_ft.quill.on('selection-change', function(range) {
            //         if(!range) {
            //             //s = window.getSelection();
            //             //quill_ft.html_elems.editor_elem.scrollIntoView();
            //             //window.scrollTo(0, 500);
            //             //console.log('validate');
            //             e.setInputValue(quill_ft);
            //             EE.cp.formValidation._sendAjaxRequest($(quill_ft.input));
            //         }
            //     });
            // }

            e.quill_fields_dict[input_index] = quill_ft;

            // console.log(quill_ft.input);
        },
        parseBase64Value: function(raw) {
            var value = null;
            try {
                value = JSON.parse(atob(raw));
            } catch (error) {
                console.warn(error);
            }
            return value;
        },
        parseInputValue: function(i) {
            var e = this,
            raw_value = $(i).val();
            return e.parseBase64Value(raw_value);
        },
        parseInputSettings: function(i) {

            var e = this,
            attr = $(i).attr('data-quill-settings');

            if(typeof attr === 'undefined' || attr === false) {
                return e.default_settings;
            }

            var settings = e.parseBase64Value(attr);

            settings = (settings != null) ? settings : e.default_settings;

            return settings;
        },
        getOpsFromInputValue: function(val) {
            var ops = null;
            if(val == null) {
                return ops;
            }

            if(typeof val === 'object' && val.hasOwnProperty('ops')) {
                ops = val['ops'];
            }
            
            return ops;
        },
        buildEditorElem: function(elem_class) {

            var el_class = "quill-" + elem_class;

            var elem = $('<div></div>').addClass(el_class).get(0);

            if(arguments.length > 1) {
                for(var i = 1; i < arguments.length; i++) {
                    $(elem).append(arguments[i]);
                }
            }

            return elem;
        },
        updateQuillFieldInputValue: function(ft) {
            var e = this;

            clearTimeout(ft.setInputValueTimeout);

            ft.setInputValueTimeout = setTimeout(function() {
                e.setInputValue(ft);
            }, e.default_input_value_update_timeout);

        },
        setInputValue: function(ft) {
            clearTimeout(ft.setInputValueTimeout);

            var e = this,
            value = e.inputValFromField(ft.quill);

            $(ft.input).val(value);
        },
        inputValFromField: function(quill) {

            var len = quill.getLength(),
            text = quill.getText(0, len),
            ops = quill.getContents()['ops'],
            value = {
                text: text,
                ops: ops,
                length: len
            };

            value = btoa(JSON.stringify(value));

            return value;
        },
        events: {
            textChange: function(delta, oldDelta, source) {
                if (source == 'api') {
                    console.log("An API call triggered this change.");
                } else if (source == 'user') {
                    console.log("A user action triggered this change.");
                }

                console.log(arguments);
                console.log(this);
            },
            selectionChange: function(range, oldRange, source) {
                if (range) {
                    if (range.length == 0) {
                        console.log('User cursor is on', range.index);
                    } else {
                        var text = quill.getText(range.index, range.length);
                        console.log('User has highlighted', text);
                    }
                } else {
                    console.log('Cursor not in the editor');
                }

                console.log(arguments);
                console.log(this);
            }
        },
    };
    
    if (!w['EE'].hasOwnProperty('Quill')) {
        w['EE']['Quill'] = {};
    }
    
    w['EE']['Quill'] = Object.assign(w['EE']['Quill'], quill);
    
    $(document).ready(function () {
        w['EE']['Quill'].init();
    });

})(jQuery, window);
