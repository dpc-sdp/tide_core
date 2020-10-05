/**
 * @file
 * Callout plugin definition.
 */

CKEDITOR.config.contentsCss = '/css/ckeditor_overrides.css' ;
CKEDITOR.plugins.add('TideCallout', {
  init: function(editor) {
    'use strict';

    editor.addCommand('callout_template', {
      exec: function(editor) {
        var selectedHtml = "";
        var selection = editor.getSelection();
        if (selection) {
          selectedHtml = getSelectionHtml(selection);
        }
        editor.insertHtml('<div class="callout-wrapper">' + selectedHtml + '</div>');
      }
    });

    editor.ui.addButton('TideCallout', {
      label: 'Callout (WYSIWYG)',
      toolbar: 'insert',
      command: 'callout_template',
      icon: this.path + 'images/icon.png'
    });
  }
});

/**
 Get HTML of a selection.
 */
function getSelectionHtml(selection) {
  var ranges = selection.getRanges();
  var html = '';
  for (var i = 0; i < ranges.length; i++) {
    var content = ranges[i].extractContents();
    html += content.getHtml();
  }
  return html;
}
