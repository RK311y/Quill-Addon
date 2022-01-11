// QuillJs Addon - Grid
(function (w) {
    "use_strict";

    Grid.bind("quill", "display", function(cell)
    {
        let input = $('input.quill-input', cell).first().get(0);
        if (input === 'undefined' || EE.Quill.inputs.indexOf(input) > -1) {
            return;
        }
        EE.Quill.inputs.push(input);
        EE.Quill.editors[EE.Quill.inputs.indexOf(input)] = EE.Quill.makeQuillEditor(input);
    });

    Grid.bind("quill", "remove", function(cell)
    {
        let input = $('input.quill-input', cell).first().get(0);
        let i = EE.Quill.inputs.indexOf(input);
        if (input === 'undefined' || i < 0) {
            return;
        }
        EE.Quill.inputs.splice(i, 1);
        EE.Quill.editors.splice(i, 1);
    });

})(window);

// END OF
