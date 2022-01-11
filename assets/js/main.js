// QuillJs Addon
(function (w) {
    "use_strict";

    if (! w.hasOwnProperty('Quill')) {
        console.error('QuillJs library is not available.');
        return;
    };

    if (! w.hasOwnProperty('EE')) {
        w['EE'] = {}
    };

    let default_settings = {
        theme: 'snow',
        modules: {
            toolbar: [
                [
                    // { 'header': [1, 2, 3, 4, 5, 6, false] },
                    { 'size': ['small', false, 'large', 'huge'] },
                    { 'header': 1 },
                    { 'header': 2 },
                    // { 'header': '3' },
                    { 'font': [] },
                ],
                // [{ 'font': [] }],
                ['bold', 'italic', 'underline', 'strike'],        // toggled buttons
                ['link', 'image', 'video'],
                // [{ 'header': 1 }, { 'header': 2 }, { 'header': 3 }],               // custom button values
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'script': 'sub' }, { 'script': 'super' }],      // superscript/subscript
                [{ 'indent': '-1' }, { 'indent': '+1' }, { 'align': [] }],          // outdent/indent
                [{ 'color': [] }, { 'background': [] }],          // dropdown with defaults from theme
                // [{ 'direction': 'rtl' }],                         // text direction
                ['blockquote', 'code-block'],
                ['clean']                                         // remove formatting button
            ]
        }
    };

    let prepFieldDOM = function(input) {

        if (! input.parentNode.classList.contains('quill-field')) {
            let parent = document.createElement('div');
            parent.classList.add('quill-field');
            input.after(parent);
            parent.appendChild(input);
        }

        // remove any possible existing items
        input.parentNode.childNodes.forEach(function (child) {
            if (child !== input) {
                child.remove();
            }
        });

        let container = document.createElement('div');
        container.classList.add('quill-editor-container');
        input.after(container);

        let editor = document.createElement('div');
        editor.classList.add('quill-editor');
        container.appendChild(editor);

        return editor;
    };

    let updateEditorInputValue = function (input) {
        let i = EE.Quill.inputs.indexOf(input);

        if (i < 0) {
            return;
        }

        let value = {
            'ops': EE.Quill.editors[i].getContents()['ops'],
            'text': EE.Quill.editors[i].getText(0, EE.Quill.editors[i].getLength())
        };

        value = JSON.stringify(value);
        input.value = value;

        return input;
    };

    let setInputEditorContents = function (input, quill) {
        if (input['value'] === 'undefined' || input['value'] === null || input['value'] === '') {
            return;
        }
        let value = input.value;
        if (typeof value === 'string') {
            if (value.length == 0) {
                return;
            }
            let obj_value = null;
            try {
                obj_value = JSON.parse(atob(value));
            } catch (e) {
                obj_value = null;
            }
            if(obj_value === null) {
                try {
                    obj_value = JSON.parse(value);
                } catch (e) {
                    obj_value = null;
                }
            }
            if (obj_value !== null && typeof obj_value === 'object') {
                if (obj_value.hasOwnProperty('ops')) {
                    let ops = obj_value['ops'];
                    if (typeof ops === 'string') {
                        try {
                            ops = JSON.parse(ops);
                        } catch (e) {
                            ;
                        }
                    }
                    if (typeof ops === 'object') {
                        quill.setContents(ops);
                        return;
                    }
                }
            }
        }
    };

    let Quill = {
        inputs: [],
        editors: [],
        renderFields: function () {
            let q = this;
            q.inputs.map(updateEditorInputValue);
            q.inputs = [].slice.call(document.querySelectorAll('input.quill-input:not([disabled="disabled"])'));
            q.editors = q.inputs.map(q.makeQuillEditor);
        },
        updateQuillInputFieldValues: function () {
            this.inputs.map(updateEditorInputValue);
        },
        makeQuillEditor: function (input) {
            if(input.constructor.name == 'n') {
                input = input.first.get(0);
            }
            let elem = prepFieldDOM(input);
            let settings = default_settings;
            let quill = new w['Quill'](elem, settings);
            setInputEditorContents(input, quill);
            return quill;
        }
    };

    w['EE']['Quill'] = Quill;

    w.addEventListener("DOMContentLoaded", function () {
        Quill.renderFields();
        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function() {
                Quill.updateQuillInputFieldValues();
            });
        });
    });

})(window);
