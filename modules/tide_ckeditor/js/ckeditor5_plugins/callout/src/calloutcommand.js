import { Plugin } from 'ckeditor5/src/core';

export default class CalloutCommand extends Plugin {

    execute(){
        const model = this.editor.model;
        const document = model.document;
        model.change(writer => {
            const blocks = Array.from(document.selection.getSelectedBlocks())
            for ( const block of blocks ) {
                if ( !block.is( 'element', 'tidecallout' ) ) {
                    writer.rename( block, 'tidecallout' );
                }
            }
        });
    }
}