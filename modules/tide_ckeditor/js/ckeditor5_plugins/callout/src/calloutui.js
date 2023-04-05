import {Plugin} from 'ckeditor5/src/core';
import {ButtonView} from 'ckeditor5/src/ui';
import icon from '../../../../icons/callout.svg';

export default class CalloutUI extends Plugin {

  /**
   * @inheritDoc
   */
  init() {
    const editor = this.editor;
    const t = editor.t;
    editor.ui.componentFactory.add('Callout', locale => {
      const command = editor.commands.get('CalloutCommand');
      const buttonView = new ButtonView(locale);
      buttonView.set({
        label: t('Callout'),
        icon: icon,
        tooltip: true,
        isToggleable: true
      });
      buttonView.bind('isOn', 'isEnabled').to(command, 'value', 'isEnabled');
      this.listenTo(buttonView, 'execute', () => {
        editor.execute('CalloutCommand');
        editor.editing.view.focus();
      });
      return buttonView;
    });
  }
}