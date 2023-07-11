// import 'bootstrap.css';
import '@grapecity/wijmo.styles/wijmo.css';
// import './styles.css';
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjInput from '@grapecity/wijmo.input';
import * as wjCore from '@grapecity/wijmo';

export class CustomGridEditor {
    /**
     * Initializes a new instance of a CustomGridEditor.
     * 
     * グリッド, 列名, クラス, クラスオプション, 多段数, n段目, 結合段数
     */
    constructor(flex, binding, edtClass, options, multirows, row, rowspan) {
        // save references
        this._grid = flex;
        this._col = flex.columns.getColumn(binding);
        this._multirows = multirows;
        this._row = row;
        this._rowspan = rowspan;
        this._beforeText = null;
        this._beforeSelectedItem = null;
        // 非同期通で状態管理するための領域
        this._asyncManagement = {
            temporarySourceCollection: [],
            loadingFlg: false,
        };

        // create editor
        this._ctl = new edtClass(document.createElement('div'), options);
        if('selectedItem' in this.control){
            this.control.selectedItem = null;
        }
        // connect grid events
        flex.beginningEdit.addHandler(this._beginningEdit, this);
        flex.sortingColumn.addHandler(() => {
            this._commitRowEdits();
        });
        flex.scrollPositionChanged.addHandler(() => {
            if (this._ctl.containsFocus()) {
                flex.focus();
            }
        });
        flex.selectionChanging.addHandler((s, e) => {
            if (e.row != s.selection.row) {
                this._commitRowEdits();
            }
        });
        // connect editor events
        this._ctl.addEventListener(this._ctl.hostElement, 'keydown', (e) => {
            switch (e.keyCode) {
                case wjCore.Key.Tab:
                case wjCore.Key.Enter:
                    e.preventDefault(); // TFS 255685
                    this._closeEditor(true);
                    this._grid.focus();
                    // forward event to the grid so it will move the selection
                    var evt = document.createEvent('HTMLEvents');
                    evt.initEvent('keydown', true, true);
                    'altKey,metaKey,ctrlKey,shiftKey,keyCode'.split(',').forEach((prop) => {
                        evt[prop] = e[prop];
                    });
                    this._grid.hostElement.dispatchEvent(evt);
                    break;
                case wjCore.Key.Escape:
                    this._closeEditor(false);
                    this._grid.focus();
                    break;
            }
        });
        // close the editor when it loses focus
        this._ctl.lostFocus.addHandler(() => {
            setTimeout(() => {
                if (!this._ctl.containsFocus()) {
                    this._closeEditor(true); // apply edits and close editor
                    this._grid.onLostFocus(); // commit item edits if the grid lost focus
                }
            });
        });
        // commit edits when grid loses focus
        this._grid.lostFocus.addHandler(() => {
            setTimeout(() => {
                if (!this._grid.containsFocus() && !CustomGridEditor._isEditing) {
                    this._commitRowEdits();
                }
            });
        });
        // open drop-down on f4/alt-down
        this._grid.addEventListener(this._grid.hostElement, 'keydown', (e) => {
            // open drop-down on f4/alt-down
            this._openDropDown = false;
            if (e.keyCode == wjCore.Key.F4 ||
                (e.altKey && (e.keyCode == wjCore.Key.Down || e.keyCode == wjCore.Key.Up))) {
                var colIndex = this._grid.selection.col;
                var rowIndex = this._grid.selection.row;
                if (colIndex > -1 && this._grid.columns[colIndex] == this._col 
                    && (rowIndex % this._multirows) == (this._row - 1)) {             // 多段グリッド対応
                    this._openDropDown = true;
                    this._grid.startEditing(true);
                    e.preventDefault();
                }
            }
            // commit edits on Enter (in case we're at the last row, TFS 268944)
            if (e.keyCode == wjCore.Key.Enter) {
                this._commitRowEdits();
            }
        }, true);
        // close editor when user resizes the window
        // REVIEW: hides editor when soft keyboard pops up (TFS 326875)
        window.addEventListener('resize', () => {
            if (this._ctl.containsFocus()) {
                this._closeEditor(true);
                this._grid.focus();
            }
        });
    }
    // gets an instance of the control being hosted by this grid editor
    get control() {
        return this._ctl;
    }
    // handle the grid's beginningEdit event by canceling the built-in editor,
    // initializing the custom editor and giving it the focus.
    _beginningEdit(grid, args) {
        // check that this is our column
        if (grid.columns[args.col] != this._col) {
            return;
        }
        // 多段グリッド対応
        if ((args.row % this._multirows) != (this._row - 1)) {
            return;
        }
        // check that this is not the Delete key
        // (which is used to clear cells and should not be messed with)
        var evt = args.data;
        if (evt && evt.keyCode == wjCore.Key.Delete) {
            return;
        }
        // cancel built-in editor
        args.cancel = true;
        // save cell being edited
        this._rng = args.range;
        CustomGridEditor._isEditing = true;
        // initialize editor host
        var rcCell = grid.getCellBoundingRect(args.row, args.col), rcBody = document.body.getBoundingClientRect(), ptOffset = new wjCore.Point(-rcBody.left, -rcBody.top), zIndex = (args.row < grid.frozenRows || args.col < grid.frozenColumns) ? '3' : '';
        // 多段グリッド対応
        var divHeight = grid.rows[args.row].renderHeight + 1;
        if (this._rowspan > 1) {
            for (var rowCnt = 0; rowCnt < this._rowspan - 1; rowCnt++) {
                divHeight += grid.rows[args.row + 1].renderHeight; 
            }
        }
        wjCore.setCss(this._ctl.hostElement, {
            position: 'absolute',
            left: rcCell.left - 1 + ptOffset.x,
            top: rcCell.top - 1 + ptOffset.y,
            width: rcCell.width + 1,
            height: divHeight,
            borderRadius: '0px',
            zIndex: zIndex,
        });
        // initialize editor content
        if (!wjCore.isUndefined(this._ctl['text'])) {
            // TODO:原因不明だが2回処理を通さないと、正常に動作しない
            // 1:グリッドセル（仮登録商品）を押下し編集モードに入る
            // 2:何もせずにグリッドセル外を押下して編集モードを抜ける
            // 3:グリッドセル（登録済商品）を押下して編集もモードに入る
            // 結果⇒3のグリッドセル内が空になる
            // ※QuoteEditComponent.vue等で使用しているtextChangedメソッド内のsetAutoCompleteValueメソッドをCOすると上記の現象は再現しなくなる
            this._ctl['text'] = grid.getCellData(this._rng.row, this._rng.col, true);
            this._ctl['text'] = grid.getCellData(this._rng.row, this._rng.col, true);   // TODO:暫定対応
        }
        else {
            throw 'Can\'t set editor value/text...';
        }
        // start editing item
        var ecv = grid.editableCollectionView, item = grid.rows[args.row].dataItem;
        if (ecv && item && item != ecv.currentEditItem) {
            setTimeout(function () {
                grid.onRowEditStarting(args);
                ecv.editItem(item);
                grid.onRowEditStarted(args);
            }, 50); // wait for the grid to commit edits after losing focus
        }
        // activate editor
        document.body.appendChild(this._ctl.hostElement);
        this._ctl.focus();
        this._beforeText = this._ctl.text;
        this._beforeSelectedItem = this.selectedItem;
        setTimeout(() => {
            // get the key that triggered the editor
            var key = (evt && evt.charCode > 32)
                ? String.fromCharCode(evt.charCode)
                : null;
            // get input element in the control
            var input = this._ctl.hostElement.querySelector('input');
            // send key to editor
            if (input) {
                if (key) {
                    input.value = key;
                    wjCore.setSelectionRange(input, key.length, key.length);
                    var evtInput = document.createEvent('HTMLEvents');
                    evtInput.initEvent('input', true, false);
                    input.dispatchEvent(evtInput);
                }
                else {
                    input.select();
                }
            }
            // give the control focus
            if (!input && !this._openDropDown) {
                this._ctl.focus();
            }
            // open drop-down on F4/alt-down
            if (this._openDropDown && this._ctl instanceof wjInput.DropDown) {
                this._ctl.isDroppedDown = true;
                this._ctl.dropDown.focus();
            }
        }, 50);
    }
    // close the custom editor, optionally saving the edits back to the grid
    _closeEditor(saveEdits) {
        if (this._rng) {
            var flexGrid = this._grid, ctl = this._ctl, host = ctl.hostElement;
            // raise grid's cellEditEnding event
            var e = new wjGrid.CellEditEndingEventArgs(flexGrid.cells, this._rng);
            flexGrid.onCellEditEnding(e);
            // save editor value into grid
            if (saveEdits) {
                if (!wjCore.isUndefined(ctl['value'])) {
                    this._grid.setCellData(this._rng.row, this._rng.col, ctl['value']);
                }
                else if (!wjCore.isUndefined(ctl['text'])) {
                    this._grid.setCellData(this._rng.row, this._rng.col, ctl['text']);
                }
                else {
                    throw 'Can\'t get editor value/text...';
                }
                this._grid.invalidate();
            }
            // close editor and remove it from the DOM
            if (ctl instanceof wjInput.DropDown) {
                ctl.isDroppedDown = false;
            }
            host.parentElement.removeChild(host);
            this._rng = null;
            CustomGridEditor._isEditing = false;
            // raise grid's cellEditEnded event
            flexGrid.onCellEditEnded(e);
        }
    }
    // commit row edits, fire row edit end events (TFS 339615)
    _commitRowEdits() {
        var flexGrid = this._grid, ecv = flexGrid.editableCollectionView;
        this._closeEditor(true);
        if (ecv && ecv.currentEditItem) {
            var e = new wjGrid.CellEditEndingEventArgs(flexGrid.cells, flexGrid.selection);
            ecv.commitEdit();
            setTimeout(() => {
                flexGrid.onRowEditEnding(e);
                flexGrid.onRowEditEnded(e);
                flexGrid.invalidate();
            });
        }
    }
    // selectedItemプロパティがある場合のみ返す
    get selectedItem(){
        var result = null;
        if('selectedItem' in this.control){
            result = this.control.selectedItem;
        }
        return result;
    }
    get beforeText(){
        return this._beforeText;
    }
    // selectedItemプロパティがある場合のみ返す
    get beforeSelectedItem(){
        return this._beforeSelectedItem;
    }
    // 非同期通信管理用
    get asyncManagement(){
        return this._asyncManagement;
    }
    get temporarySourceCollection(){
        return this._asyncManagement.temporarySourceCollection;
    }
    set temporarySourceCollection(temporarySourceCollection){
        this._asyncManagement.temporarySourceCollection = temporarySourceCollection;
    }
    get loadingFlg(){
        return this._asyncManagement.loadingFlg;
    }
    set loadingFlg(loadingFlg){
        this._asyncManagement.loadingFlg = loadingFlg;
    }
    // itemsSourceプロパティがある場合のみセットする
    changeItemsSource(list, useRefresh){
        useRefresh = typeof useRefresh === 'undefined' ? false : useRefresh;
        if('itemsSource' in this.control){
            this.control.itemsSource = list;
            this.control.maxItems = Array.isArray(list) ? list.length : 0;
            if(useRefresh){
                this.control.collectionView.refresh();
            }
        }
    }
    get query(){
        var result = null;
        if ('_query' in this.control) {
            result = this.control._query;
        }
        return result;
    }
}