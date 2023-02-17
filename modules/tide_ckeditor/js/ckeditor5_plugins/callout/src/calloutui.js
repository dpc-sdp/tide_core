import {Plugin} from 'ckeditor5/src/core';
import {ButtonView} from 'ckeditor5/src/ui';
import icon from '../../../../icons/callout.svg';

export default class CalloutUI extends Plugin {
  init() {
    const editor = this.editor;
    editor.ui.componentFactory.add('Callout', (locale) => {
      const command = editor.commands.get('CalloutCommand');
      const buttonView = new ButtonView(locale);
      buttonView.set({
        label: editor.t('Callout'),
        icon,
        tooltip: true,
      });
      buttonView.bind('isOn', 'isEnabled').to(command, 'value', 'isEnabled');
      this.listenTo(buttonView, 'execute', () => editor.execute('CalloutCommand'));
      return buttonView;
    });
  }
}
