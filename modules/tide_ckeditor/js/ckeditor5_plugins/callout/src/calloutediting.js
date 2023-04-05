import {Plugin} from 'ckeditor5/src/core';
import { Enter } from 'ckeditor5/src/enter';
import { Delete } from 'ckeditor5/src/typing';
import CalloutCommand from "./calloutcommand";

export default class CalloutEditing extends Plugin {

  static get requires() {
    return [Enter, Delete];
  }

  init() {
    const editor = this.editor;
    const schema = editor.model.schema;
    editor.commands.add('CalloutCommand', new CalloutCommand(editor));
    schema.register('callOut', {
      inheritAllFrom: '$container'
    });
    editor.conversion.elementToElement({ model: 'callOut', view: {name: 'div', classes: 'callout-wrapper'} });
    editor.model.document.registerPostFixer(writer => {
      const changes = editor.model.document.differ.getChanges();
      for (const entry of changes) {
        if (entry.type == 'insert') {
          const element = entry.position.nodeAfter;
          if (!element) {
            continue;
          }
          if (element.is('element', 'callOut') && element.isEmpty) {
            writer.remove(element);
            return true;
          }
          else if (element.is('element', 'callOut') && !schema.checkChild(entry.position, element)) {
            writer.unwrap(element);
            return true;
          }
          else if (element.is('element')) {
            const range = writer.createRangeIn(element);
            for (const child of range.getItems()) {
              if (child.is('element', 'callOut') &&
                  !schema.checkChild(writer.createPositionBefore(child), child)) {
                writer.unwrap(child);
                return true;
              }
            }
          }
        }
        else if (entry.type == 'remove') {
          const parent = entry.position.parent;
          if (parent.is('element', 'callOut') && parent.isEmpty) {
            writer.remove(parent);
            return true;
          }
        }
      }
      return false;
    });
    const viewDocument = this.editor.editing.view.document;
    const selection = editor.model.document.selection;
    const callOutCommand = editor.commands.get('callOut');
    this.listenTo(viewDocument, 'enter', (evt, data) => {
      if (!selection.isCollapsed || !callOutCommand.value) {
        return;
      }
      const positionParent = selection.getLastPosition().parent;
      if (positionParent.isEmpty) {
        editor.execute('callOut');
        editor.editing.view.scrollToTheSelection();
        data.preventDefault();
        evt.stop();
      }
    }, { context: 'callout' });
    this.listenTo(viewDocument, 'delete', (evt, data) => {
      if (data.direction != 'backward' || !selection.isCollapsed || !callOutCommand.value) {
        return;
      }
      const positionParent = selection.getLastPosition().parent;
      if (positionParent.isEmpty && !positionParent.previousSibling) {
        editor.execute('callOut');
        editor.editing.view.scrollToTheSelection();
        data.preventDefault();
        evt.stop();
      }
    }, { context: 'callout' });
  }

}
