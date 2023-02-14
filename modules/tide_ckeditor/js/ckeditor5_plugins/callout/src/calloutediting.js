import { Plugin } from 'ckeditor5/src/core';
import { Paragraph } from 'ckeditor5/src/paragraph';
import { priorities } from 'ckeditor5/src/utils';
import CalloutCommand from "./calloutcommand";

export default class CalloutEditing extends Plugin {

  constructor( editor ) {
    super( editor );
  }

  static get requires() {
    return [Paragraph];
  }

  init() {
    const editor = this.editor;
    editor.model.schema.register('tidecallout',{
      inheritAllFrom: '$block'
    });
    editor.conversion.elementToElement({
      model: 'tidecallout',
      view: {
        name: 'div',
        classes: 'callout-wrapper'
      }
    });
    this._addDivConversion(editor)
    editor.commands.add('CalloutCommand', new CalloutCommand(this.editor));
  }

  _addDivConversion(editor) {
    editor.conversion.for( 'upcast' ).elementToElement( {
      model: 'tidecallout',
      view: 'div',
      converterPriority: priorities.get( 'low' ) + 1
    } );
  }

}
