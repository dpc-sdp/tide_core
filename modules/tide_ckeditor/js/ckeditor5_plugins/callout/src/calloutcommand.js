import { Command } from 'ckeditor5/src/core';
import { first } from 'ckeditor5/src/utils';

export default class CalloutCommand extends Command {

  refresh() {
    this.value = this._getValue();
    this.isEnabled = this._checkEnabled();
  }

  execute(options = {}) {
    const model = this.editor.model;
    const schema = model.schema;
    const selection = model.document.selection;
    const blocks = Array.from(selection.getSelectedBlocks());
    const value = (options.forceValue === undefined) ? !this.value : options.forceValue;
    model.change(writer => {
      if (!value) {
        this._removeCallOut(writer, blocks.filter(this._findCallOut));
      }
      else {
        const blocksToCallOut = blocks.filter(block => {
          return this._findCallOut(block) || this._checkCanBeCallOut(schema, block);
        });
        this._applyCallOut(writer, blocksToCallOut);
      }
    });
  }

  _getValue() {
    const selection = this.editor.model.document.selection;
    const firstBlock = first(selection.getSelectedBlocks());
    return !!(firstBlock && this._findCallOut(firstBlock));
  }

  _checkEnabled() {
    if (this.value) {
      return true;
    }
    const selection = this.editor.model.document.selection;
    const schema = this.editor.model.schema;
    const firstBlock = first(selection.getSelectedBlocks());
    if (!firstBlock) {
      return false;
    }
    return this._checkCanBeCallOut(schema, firstBlock);
  }

  _removeCallOut(writer, blocks) {
    this._getRangesOfCallOutGroups(writer, blocks).reverse().forEach(groupRange => {
      if (groupRange.start.isAtStart && groupRange.end.isAtEnd) {
        writer.unwrap(groupRange.start.parent);
        return;
      }
      if (groupRange.start.isAtStart) {
        const positionBefore = writer.createPositionBefore(groupRange.start.parent);
        writer.move(groupRange, positionBefore);
        return;
      }
      if (!groupRange.end.isAtEnd) {
        writer.split(groupRange.end);
      }
      const positionAfter = writer.createPositionAfter(groupRange.end.parent);
      writer.move(groupRange, positionAfter);
    });
  }

  _applyCallOut(writer, blocks) {
    const callOutsToMerge = [];
    this._getRangesOfCallOutGroups(writer, blocks).reverse().forEach(groupRange => {
      let callout = this._findCallOut(groupRange.start);
      if (!callout) {
        callout = writer.createElement('callOut');
        writer.wrap(groupRange, callout);
      }
      callOutsToMerge.push(callout);
    });
    callOutsToMerge.reverse().reduce((currentCallout, nextCallout) => {
      if (currentCallout.nextSibling == nextCallout) {
        writer.merge(writer.createPositionAfter(currentCallout));
        return currentCallout;
      }
      return nextCallout;
    });
  }

  _findCallOut(elementOrPosition) {
    return elementOrPosition.parent.name == 'callOut' ? elementOrPosition.parent : null;
  }

  _getRangesOfCallOutGroups(writer, blocks) {
    let startPosition;
    let i = 0;
    const ranges = [];
    while (i < blocks.length) {
      const block = blocks[i];
      const nextBlock = blocks[i + 1];
      if (!startPosition) {
        startPosition = writer.createPositionBefore(block);
      }
      if (!nextBlock || block.nextSibling != nextBlock) {
        ranges.push(writer.createRange(startPosition, writer.createPositionAfter(block)));
        startPosition = null;
      }
      i++;
    }
    return ranges;
  }

  _checkCanBeCallOut(schema, block) {
    const isCOAllowed = schema.checkChild(block.parent, 'callOut');
    const isBlockAllowedInCO = schema.checkChild(['$root', 'callOut'], block);
    return isCOAllowed && isBlockAllowedInCO;
  }
}