import CalloutUI from './calloutui';
import {Plugin} from 'ckeditor5/src/core';
import CalloutEditing from "./calloutediting";

export default class Callout extends Plugin {
  static get requires() {
    return [CalloutEditing, CalloutUI];
  }
}
