/***** 共通 *****/
export default {

    data: () => ({
        // 階層パスの区切文字
        TREE_PATH_SEPARATOR: '_',
        // 工事区分の深さ
        QUOTE_CONSTRUCTION_DEPTH: 0,
        // 2段グリッドのコピペ用定数
        WJ_MULTI_ROW_CLIPBOARD_CTRL_OPTION: {
            DELIMITER: '\t',    // TSV形式
            LINE_FEED: '\n',    // 改行
        },
        // 多段数
        WJ_MULTI_ROW_CNT: 2,
        // グリッドの色
        TREE_GRID_COLOR_CODE: {
            SALES_USE_ROW:      'seashell',     // 一式と販売額利用行
            CHILD_PARTS_ROW:    '#CCF7FA',      // 発注詳細の子部品行
            PRODUCT_AUTO_CELL:  'turquoise',    // 商品IDがない明細行の本登録フラグのセル
        },
        // チェック処理の結果
        TREE_GRID_CHK_KBN_LIST: {
            VALID: 0,
            IN_VALID_1: 1,
            IN_VALID_2: 2,
            IN_VALID_3: 3,
            IN_VALID_4: 4,
            IN_VALID_5: 5,
            IN_VALID_6: 6,
        },
        // 標準の仕入販売単価の区分
        NORMAL_PRODUCT_PRICE_KBN: 0,
        
        // プライベートな変数はこの中に記述する
        privateProperty: {
            // フィルターパスの末尾の数字を管理する
            filterTreeNumberManagement: [],
        },
        // 右クリックに必要な設定やデータ
        rightClickInfo: {
            layout: [
                //{ header: '<span class="glyphicon glyphicon-floppy-disk"></span>&nbsp;&nbsp;コピー', cmd: 'copy' },
                { header: '<span class=""></span>&nbsp;&nbsp;コピー', cmd: 'copy' },
                { header: '<span class=""></span>&nbsp;&nbsp;貼付け（上書き）', cmd: 'paste' },
                { header: '<span class=""></span>&nbsp;&nbsp;コピーした行を上に挿入', cmd: 'upAddRowPaste' },
                { header: '<span class=""></span>&nbsp;&nbsp;コピーした行を下に挿入', cmd: 'downAddRowPaste' },
                { header: '<span class="wj-separator"></span>' },
                { header: '<span class=""></span>&nbsp;&nbsp;階層作成', cmd: 'toLayer' },
                { header: '<span class=""></span>&nbsp;&nbsp;一式作成', cmd: 'toSet' },
                { header: '<span class=""></span>&nbsp;&nbsp;空の行を上に挿入', cmd: 'addRow' },
                { header: '<span class=""></span>&nbsp;&nbsp;行を削除', cmd: 'deleteRow' },
                { header: '<span class="wj-separator"></span>' },
                { header: '<span class=""></span>&nbsp;&nbsp;閉じる', cmd: 'close' }
            ],
            layoutQuoteZero: [
                //{ header: '<span class="glyphicon glyphicon-floppy-disk"></span>&nbsp;&nbsp;コピー', cmd: 'copy' },
                { header: '<span class=""></span>&nbsp;&nbsp;コピー', cmd: 'copy' },
                { header: '<span class="wj-separator"></span>' },
                { header: '<span class=""></span>&nbsp;&nbsp;閉じる', cmd: 'close' }
            ],
            layoutOrder: [
                { header: '<span class=""></span>&nbsp;&nbsp;コピー', cmd: 'copy' },
                { header: '<span class=""></span>&nbsp;&nbsp;貼付け（上書き）', cmd: 'paste' },
                { header: '<span class=""></span>&nbsp;&nbsp;コピーした行を上に挿入', cmd: 'upAddRowPaste' },
                { header: '<span class=""></span>&nbsp;&nbsp;コピーした行を下に挿入', cmd: 'downAddRowPaste' },
                { header: '<span class="wj-separator"></span>' },
                { header: '<span class=""></span>&nbsp;&nbsp;階層作成', cmd: 'toLayer' },
                { header: '<span class=""></span>&nbsp;&nbsp;空の行を上に挿入', cmd: 'addRow' },
                { header: '<span class=""></span>&nbsp;&nbsp;行を削除', cmd: 'deleteRow' },
                { header: '<span class="wj-separator"></span>' },
                { header: '<span class=""></span>&nbsp;&nbsp;閉じる', cmd: 'close' }
            ],
            // クリップボードの値を保持する
            clipboardText: '',
        }
    }),
    methods: {
        /**
         * ツリーを検索する
         * @param {*} items     TreeView.itemsSource
         * @param {*} selectKey ツリーのプロパティ
         * @param {*} selectValue   ツリーの値
         */
        findTree(items, selectKey, selectValue) {
            var result = null;
            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                if (item[selectKey] == selectValue) {
                    result = item;
                    break;
                }
                if (item.items) {
                    item = this.findTree(item.items, selectKey, selectValue);
                    if (item) {
                        result = item;
                        break;
                    }
                }
            }
            return result;
        },
        
        /**
         * 指定したツリーのプロパティに一致する階層を選択させる
         * @param {*} wjTreeViewControl ツリーのインスタンス
         * @param {*} selectKey         ツリーのプロパティ
         * @param {*} selectValue       ツリーの値
         */
        selectTree(wjTreeViewControl, selectKey, selectValue){
            var result = false;
            var theItem = this.findTree(wjTreeViewControl.itemsSource, selectKey, selectValue);
            var theNode = wjTreeViewControl.getNode(theItem);
            if(theNode !== null){
                theNode.select();
                result = true;
            }
            return result;
        },

        /**
         * グリッドのreadOnlyセルをグレーにする
         * @param {*} gridCtrl 
         * @param {*} r 
         * @param {*} c 
         * @param {*} cell 
         */
        setGridCellReadOnlyColor(gridCtrl, r, c, cell){
            if(this.gridIsReadOnlyCell(gridCtrl, r, c)){
                cell.style.backgroundColor = 'lightgrey';
            }else{
                cell.style.backgroundColor = '';
            }
        },

        /**
         * グリッドの選択セルが読み取り専用か
         * @param {*} gridCtrl 
         * @param {*} r 
         * @param {*} c 
         */
        gridIsReadOnlyCell(gridCtrl, r, c){
            var result = false;
            var header = gridCtrl.layoutDefinition;
            if(typeof header[c].cells[r%2] !== 'undefined' && header[c].cells[r%2].isReadOnly){
                result = true;
            }
            return result;
        },

        /**
         * タブとエンターキーによる読み取り専用セルのスキップ(2段グリッド)
         * keyActionEnter プロパティを wjGrid.KeyAction.None にしてください 
         * グリッドのhostElement に対するkeydownイベント内で使用して下さい
         * @param {*} gridCtrl  グリッドのインスタンス
         * @param {*} e 
         * @param {*} wjGrid    @grapecity/wijmo.grid
         */
        gridKeyDownSkipReadOnlyCell(gridCtrl, e, wjGrid){
            if (e.keyCode == 9 || e.keyCode == 13) {
                var row = gridCtrl.selection.row;
                var col = gridCtrl.selection.col;
                var tmp = null;
                if(event.shiftKey){
                    tmp = this._gridSearchPrevSelectCell(gridCtrl, row, col);
                    
                }else{
                    tmp = this._gridSearchNextSelectCell(gridCtrl, row, col);
                }
                if (Object.keys(tmp).length === 2) {
                    gridCtrl.select(new wjGrid.CellRange(tmp['r'], tmp['c'], tmp['r'], tmp['c']), true);
                    
                }
                e.preventDefault();
            }
        },
        _gridSearchNextSelectCell(gridCtrl, r, c){
            var result = [];
            var isFind = false;
            var header = gridCtrl.layoutDefinition;
            
            var recordCnt = c;
            var multirowsCnt = r;
            var row = 0;
            var recordChangeCnt = 0;
            if(r % 2 === 1){
                recordCnt++;
                multirowsCnt--;
            }else{
                multirowsCnt++;
                row = 1;
            }

            all_break:
            for(; multirowsCnt<gridCtrl.rows.length;multirowsCnt++){
                for(; recordCnt < header.length ; recordCnt++){
                    
                    if(typeof header[recordCnt] !== 'undefined' && typeof header[recordCnt].cells[row] !== 'undefined'){
                        if((!header[recordCnt].cells[row].isReadOnly) && (header[recordCnt].cells[row].visible !== false)){
                            isFind = true;
                            break all_break; 
                        }
                    }
                    if(row === 1){
                        row = 0;
                        continue;
                    }else{
                        row = 1;
                    }
                        
                    if(typeof header[recordCnt] !== 'undefined' && typeof header[recordCnt].cells[row] !== 'undefined'){
                        if((!header[recordCnt].cells[row].isReadOnly) && (header[recordCnt].cells[row].visible !== false)){
                            isFind = true;
                            break all_break; 
                        }
                    }
                    row = 0;
                        
                }
                recordCnt=0;
                recordChangeCnt++;
                if(multirowsCnt % 2 === 0){
                    multirowsCnt++;
                    recordChangeCnt++;
                }
                
            }
            if(r % 2 === 1 && row === 1 && recordChangeCnt % 2 === 0){
                multirowsCnt++;
            }else if(r % 2 === 1 && row === 0 && recordChangeCnt % 2 === 1 ){
                multirowsCnt--;
            }else if(r % 2 === 0 && row === 0 && recordChangeCnt % 2 === 0 ){
                multirowsCnt--;
            }else if(r % 2 === 0 && row === 1 && recordChangeCnt % 2 === 1 ){
                multirowsCnt++;
            }
                
            if(isFind){
                result['c'] = recordCnt;
                result['r'] = multirowsCnt;
            }
            return result;
        },
        _gridSearchPrevSelectCell(gridCtrl, r, c){
            var result = [];
            var isFind = false;
            var header = gridCtrl.layoutDefinition;
            
            var recordCnt = c;
            var multirowsCnt = r;
            var row = 1;
            var recordChangeCnt = 0;
            if(r % 2 === 1){
                multirowsCnt--;
                row = 0;
            }else{
                recordCnt--;
                multirowsCnt++;
            }

            all_break:
            for(; multirowsCnt >= 0; multirowsCnt--){
                for(; recordCnt >= 0 ; recordCnt--){
                    if(typeof header[recordCnt] !== 'undefined' && typeof header[recordCnt].cells[row] !== 'undefined'){
                        if((!header[recordCnt].cells[row].isReadOnly) && (header[recordCnt].cells[row].visible !== false)){
                            isFind = true;
                            break all_break; 
                        }
                    }
                    if(row === 1){
                        row = 0;
                    }else{
                        row = 1;
                        continue;
                    }
                        
                    if(typeof header[recordCnt] !== 'undefined' && typeof header[recordCnt].cells[row] !== 'undefined'){
                        if((!header[recordCnt].cells[row].isReadOnly) && (header[recordCnt].cells[row].visible !== false)){
                            isFind = true;
                            break all_break; 
                        }
                    }
                    row = 1;
                        
                }
                recordCnt = header.length-1;
                recordChangeCnt++;
                if(multirowsCnt % 2 === 1){
                    multirowsCnt--;
                    recordChangeCnt++;
                }
                
            }
            
            if(r % 2 === 1 && row === 1 && recordChangeCnt % 2 === 0){
                multirowsCnt++;
            }else if(r % 2 === 1 && row === 0 && recordChangeCnt % 2 === 1 ){
                multirowsCnt--;
            }else if(r % 2 === 0 && row === 0 && recordChangeCnt % 2 === 0 ){
                multirowsCnt--;
            }else if(r % 2 === 0 && row === 1 && recordChangeCnt % 2 === 1 ){
                multirowsCnt++;
            }
            
            if(isFind){
                result['c'] = recordCnt;
                result['r'] = multirowsCnt;
            }
            return result;
        },

        /**
         * グリッドの行が非表示対象かどうか
         * フィルター時に使用する
         * @param {*} item      collectionView.filter(グリッドの)レコード
         * @param {*} topFlg    今開いている階層が持っているトップフラグ
         * @param {*} depth     今開いている階層が持っている深さ
         * @param {*} filterTreePath    今開いている階層が持っているフィルターパス
         */
        isTreeGridVisibleTarget(item, topFlg, depth, filterTreePath){
            var result = false;
            if(topFlg === this.FLG_ON){
                if(item.depth === this.QUOTE_CONSTRUCTION_DEPTH){
                    result = true;
                }
            }else{
                if(depth+1 === item.depth && item.filter_tree_path.indexOf(filterTreePath + this.TREE_PATH_SEPARATOR) === 0){
                    result = true;
                }
            }
            return result;
        },

        /**
         * グリッドの非表示項目にフィルターなどの情報をセットする
         * @param {*} s                         グリッドのrowAddedイベントの第一引数
         * @param {*} wjTreeViewControl         ツリーのインスタンス
         */
        setTreeGridInvisibleData(s, wjTreeViewControl){
            var row = s.collectionView.currentAddItem;
            //var gridData = s.itemsSource.items;

            var currentTree = wjTreeViewControl.selectedItem; 

            var gridData = this.getChildGridDataList(s, currentTree.filter_tree_path, (currentTree.depth + 1));

            var seqNo = 0;
            var endFilterTreePath = 0;
            if(gridData.length >= 1){
                // 連番を振りなおす
                for(var i=0; i<gridData.length; i++){
                    seqNo = i+1;
                    gridData[i].seq_no = seqNo;
                    var currentItem = this.findTree(wjTreeViewControl.itemsSource, 'filter_tree_path', gridData[i].filter_tree_path);
                    if(currentItem !== null){
                        currentItem.seq_no = gridData[i].seq_no;
                    }
                }
                // 最大値を取得
                var tmpFilterTreePath = gridData.reduce((a,b)=>this.getEndFilterTreePath(a.filter_tree_path) > this.getEndFilterTreePath(b.filter_tree_path)?a:b).filter_tree_path;
                endFilterTreePath = this.getEndFilterTreePath(tmpFilterTreePath) + 1;
            }
            row.seq_no = seqNo + 1;
            
            row.depth = currentTree.depth + 1;
            
            row.construction_id = currentTree.construction_id;
            row.filter_tree_path = currentTree.filter_tree_path + this.TREE_PATH_SEPARATOR + endFilterTreePath;
            row.add_flg = currentTree.add_flg;
        },

        /**
         * 階層のフィルターパスを振りなおしたグリッドのデータを返す
         * フィルターパスを振りなおしたグリッドデータを返す
         * @param {*} wjMultiRowControle    グリッドのインスタンス
         * @param {*} wjTreeViewControl     階層のインスタンス
         */
        getUpdateFilterTreePathData(wjMultiRowControle, wjTreeViewControl){
            var gridData = [];
            // クリア
            this.privateProperty.filterTreeNumberManagement = [];
            // 階層のフィルターパスをセット グリッドのフィルターパスをセットした新しいグリッドデータを返す
            this._updateFilterTreePath(wjMultiRowControle, wjTreeViewControl.nodes[0].dataItem['items'], '', gridData);
            // シーケンス番号の昇順に並び変える
            gridData.sort(function(a,b){
                if(a.depth < b.depth) return -1;
                if(a.depth > b.depth) return 1;
                if(a.seq_no < b.seq_no) return -1;
                if(a.seq_no > b.seq_no) return 1;
                if(a.filter_tree_path < b.filter_tree_path) return -1;
                if(a.filter_tree_path > b.filter_tree_path) return 1;
                return 0;
            });

            return gridData;
        },

        /**
         * 階層とグリッドのフィルター用のキーを振りなおす(階層より明細が上にくる ※)
         * 例.
         *  トップ
         * 　　　├ 工事区分１　(0)
         * 　　　│　　├ 明細１　(0_0)　←※
         * 　　　│　　├ 階層１　(0_1)　←※
         * 　　　│　　│　　├ 明細１　(0_1_0)
         * 　　　│　　│　　└ 明細２　(0_1_1)
         * 　　　│　　└ 階層２　(0_2)
         * 　　　└ 追加部材　　(1)
         * @param {*} wjMultiRowControle    グリッドのインスタンス
         * @param {*} treeItems             階層のitemsプロパティ
         * @param {*} filterTreePath        親のフィルターパス
         * @param {*} gridData              グリッドのCollectionViewにセットするデータリスト
         */
        _updateFilterTreePath(wjMultiRowControle, treeItems, filterTreePath, gridData){
            for(var i =0; i<treeItems.length; i++){
                var newFilterTreePath = '';
                var newDepth = '';
                var beforeFilterPath = treeItems[i]['filter_tree_path'];
                var beforeDepth = treeItems[i]['depth'];

                // 階層を取得
                var record = Vue.util.extend({}, wjMultiRowControle.collectionView.sourceCollection.find((rec) => {
                    return (rec.filter_tree_path === beforeFilterPath);
                }));

                if(treeItems[i]['depth'] === this.QUOTE_CONSTRUCTION_DEPTH){
                    newFilterTreePath = i.toString();
                    newDepth = this.QUOTE_CONSTRUCTION_DEPTH;
                }else{
                    newFilterTreePath = filterTreePath + this.TREE_PATH_SEPARATOR + this._getFilterTreeNumber(filterTreePath);
                    newDepth = newFilterTreePath.split(this.TREE_PATH_SEPARATOR).length -1;
                }

                record.filter_tree_path = newFilterTreePath;
                record.depth = newDepth;
                record.seq_no = (i+1);
                treeItems[i]['filter_tree_path'] = newFilterTreePath;
                treeItems[i]['depth'] = newDepth;
                treeItems[i]['seq_no'] = (i+1);

                gridData.push(record);

                // 直下の明細を取得
                var gridDetailList = this.getChildGridDataList(wjMultiRowControle, beforeFilterPath, (beforeDepth + 1), this.FLG_OFF);

                for(var j=0; j<gridDetailList.length; j++){
                    var gridDetail = Vue.util.extend({}, gridDetailList[j]);
                    var detailNewFilterTreePath = newFilterTreePath + this.TREE_PATH_SEPARATOR + this._getFilterTreeNumber(newFilterTreePath);
                    gridDetail.filter_tree_path = detailNewFilterTreePath;
                    gridDetail.depth = detailNewFilterTreePath.split(this.TREE_PATH_SEPARATOR).length -1;
                    gridData.push(gridDetail);
                }
                
                this._updateFilterTreePath(wjMultiRowControle, treeItems[i]['items'], newFilterTreePath, gridData);
            }
        },

        /**
         * フィルターパスの末尾の数字を生成して返す
         * @param {*} filterTreePath 新たに生成したい階層のフィルターパス
         */
        _getFilterTreeNumber(filterTreePath){
            var filterTreeNumberManagement = this.privateProperty.filterTreeNumberManagement;
            if(typeof filterTreeNumberManagement[filterTreePath] === 'undefined'){
                filterTreeNumberManagement[filterTreePath] = 0;
            }else{
                filterTreeNumberManagement[filterTreePath] += 1;
            }
            return filterTreeNumberManagement[filterTreePath];
        },

        /**
         * グリッドの並び替え用のボタンクリック
         * @param {*} gridCtrl          グリッドのインスタンス
         * @param {*} wjTreeViewControl 階層のインスタンス
         * @param {*} record            今選択中の行
         * @param {*} isUp              上に移動ボタンか
         * @returns {*} swapFlg         入れ替えを行ったかどうか
         */
        treeGridUpDownBtnClick(gridCtrl, wjTreeViewControl, record, isUp){
            var swapFlg = false;
            var currentGridData = gridCtrl.itemsSource.items;
            var currentNo = 0;
            for(let i in currentGridData){
                if(currentGridData[i].filter_tree_path === record.filter_tree_path){
                    currentNo = parseInt(i);
                    break;
                }
            }
            
            var parentTree = null;
            if(isUp){
                if(currentNo >= 1){
                    currentGridData = this.arraySwap(currentGridData, currentNo, currentNo-1);
                    swapFlg = true;
                }
            }else{
                if(currentNo < currentGridData.length-1){
                    currentGridData = this.arraySwap(currentGridData, currentNo, currentNo+1);
                    swapFlg = true;
                }
            }

            if(swapFlg){
                for (var cnt = 0; cnt < currentGridData.length; cnt++) {
                    currentGridData[cnt].seq_no = cnt + 1;
                    var item = this.findTree(wjTreeViewControl.itemsSource, 'filter_tree_path', currentGridData[cnt].filter_tree_path);
                    if(item !== null){
                        var treeNode = wjTreeViewControl.getNode(item);
                        var dataItem = treeNode.dataItem;
                        dataItem.seq_no = currentGridData[cnt].seq_no;
                        if(parentTree === null){
                            parentTree = treeNode.parentNode;
                        }
                    }
                }
                gridCtrl.collectionView.sourceCollection.sort(function(a,b){
                    if(a.depth < b.depth) return -1;
                    if(a.depth > b.depth) return 1;
                    if(a.seq_no < b.seq_no) return -1;
                    if(a.seq_no > b.seq_no) return 1;
                    if(a.filter_tree_path < b.filter_tree_path) return -1;
                    if(a.filter_tree_path > b.filter_tree_path) return 1;
                    return 0;
                });
                if(parentTree !== null){
                    parentTree.dataItem['items'].sort(function(a,b){
                        if(a['depth'] < b['depth']) return -1;
                        if(a['depth'] > b['depth']) return 1;
                        if(a['seq_no'] < b['seq_no']) return -1;
                        if(a['seq_no'] > b['seq_no']) return 1;
                        if(a['filter_tree_path'] < b['filter_tree_path']) return -1;
                        if(a['filter_tree_path'] > b['filter_tree_path']) return 1;
                        return 0;
                    });
                }
            }
            return swapFlg;
        },

        /**
         * チェックが付いたグリッドにデータを追加する
         * @param {*} wjMultiRowControle    グリッドのインスタンス 
         * @param {*} wjTreeViewControl     ツリーのインスタンス 
         * @param {*} depth                 今開いている階層が持っている深さ
         * @param {*} filterTreePath        今開いている階層が持っているフィルターパス
         * @param {*} INIT_ROW              初期値
         * @param {*} isAddUnder            true：チェックを付けた行の下に追加する
         * @param {*} rowList               対象の行リスト(右クリック時のみ必須)
         * @returns {*} result              追加した行のリスト
         */
        addTreeGrid(wjMultiRowControle, wjTreeViewControl, depth, filterTreePath, INIT_ROW, isAddUnder, rowList){
            // 追加した行のリスト
            var result = [];

            var gridData = wjMultiRowControle.collectionView.sourceCollection;

            // 今開いている階層のチェックが付いたレコードを取得
            if(typeof rowList === 'undefined'){
                rowList = [];
                for (var i = 0; i < gridData.length; i++) {
                    if (gridData[i].chk && gridData[i].depth === (depth+1) && gridData[i].filter_tree_path.indexOf(filterTreePath + this.TREE_PATH_SEPARATOR) === 0) {
                        rowList[i] = gridData[i];
                    }
                }
            }

            // 挿入した階層のパス
            var parentFilterTreePathList = [];

            var addCnt = 0;
            if(isAddUnder){
                // 選択行の下に追加する場合
                addCnt = 1;
            }

            for(let i in rowList) {
                var emptyRow = Vue.util.extend({}, INIT_ROW);
                var record = rowList[i];

                // チェックを付けた行の親のフィルターパスを取得
                var parentFilterTreePath = this.getParentFilterTreePath(record.filter_tree_path);
                // チェックを付けた行と同じ階層のグリッドデータのリストを取得する
                var currentGridDataList = this.getChildGridDataList(wjMultiRowControle, parentFilterTreePath, record.depth);

                var tmpFilterTreePath = currentGridDataList.reduce((a,b)=>this.getEndFilterTreePath(a.filter_tree_path)>this.getEndFilterTreePath(b.filter_tree_path)?a:b).filter_tree_path;
                var endFilterTreePath = this.getEndFilterTreePath(tmpFilterTreePath) + 1;
                
                emptyRow.depth = record.depth;      
                emptyRow.construction_id = record.construction_id;
                emptyRow.filter_tree_path = parentFilterTreePath + this.TREE_PATH_SEPARATOR + endFilterTreePath;
                emptyRow.add_flg = record.add_flg;
                // 挿入位置：現在の添え字 + 追加した行数
                wjMultiRowControle.collectionView.sourceCollection.splice((parseInt(i) + addCnt), 0, emptyRow);

                // 追加した行のリスト
                result.push(emptyRow);

                if (!(parentFilterTreePath in parentFilterTreePathList)) {
                    parentFilterTreePathList[parentFilterTreePath] = record;
                }
                addCnt++;
            }

            // 連番を振りなおす
            for (let parentFilterTreePath in parentFilterTreePathList) {
                var record = parentFilterTreePathList[parentFilterTreePath];
                var currentGridDataList = this.getChildGridDataList(wjMultiRowControle, parentFilterTreePath, record.depth);
                for(var j=0; j < currentGridDataList.length; j++){
                    currentGridDataList[j].seq_no = (j+1);
                    var currentItem = this.findTree(wjTreeViewControl.itemsSource, 'filter_tree_path', currentGridDataList[j].filter_tree_path);
                    if(currentItem !== null){
                        currentItem.seq_no = currentGridDataList[j].seq_no;
                    }
                }
            }
            return result;
        },

        /**
         * 右クリックで行追加する
         * @param {*} wjMultiRowControle    グリッドのインスタンス 
         * @param {*} wjTreeViewControl     ツリーのインスタンス
         * @param {*} targetRow             右クリックした行
         * @param {*} targetRowIndx         右クリックした行の行番号(全体)
         * @param {*} clipboardData         クリップボードのテキストの配列(toWjMultiRowPasteTextFormat関数の戻り値)
         * @param {*} nonPastingCols        貼付対象から外す列のリスト
         * @param {*} INIT_ROW              初期値のリスト
         * @param {*} isAddUnder            true：チェックを付けた行の下に追加する
         */
        addTreeGridByRightClick(wjMultiRowControle, wjTreeViewControl, targetRow, targetRowIndx, clipboardData, nonPastingCols, INIT_ROW, isAddUnder){
            // 追加した行のリスト
            var result = [];

            // 挿入した階層のパス
            var parentFilterTreePathList = [];

            var addCnt = 0;
            if(isAddUnder){
                // 選択行の下に追加する場合
                addCnt = 1;
            }

            var layount = wjMultiRowControle.layoutDefinition;

            for(var i=0; i<clipboardData.length; i++) {
                if(i%this.WJ_MULTI_ROW_CNT !== 0){
                    // 2段目はスキップ
                    continue;
                }
                var emptyRow = Vue.util.extend({}, INIT_ROW);
                this.wjMultiRowSetClipboardData(clipboardData, layount, emptyRow, i, nonPastingCols);

                // チェックを付けた行の親のフィルターパスを取得
                var parentFilterTreePath = this.getParentFilterTreePath(targetRow.filter_tree_path);
                // チェックを付けた行と同じ階層のグリッドデータのリストを取得する
                var currentGridDataList = this.getChildGridDataList(wjMultiRowControle, parentFilterTreePath, targetRow.depth);

                var tmpFilterTreePath = currentGridDataList.reduce((a,b)=>this.getEndFilterTreePath(a.filter_tree_path)>this.getEndFilterTreePath(b.filter_tree_path)?a:b).filter_tree_path;
                var endFilterTreePath = this.getEndFilterTreePath(tmpFilterTreePath) + 1;
                
                emptyRow.depth              = targetRow.depth;      
                emptyRow.construction_id    = targetRow.construction_id;
                emptyRow.filter_tree_path   = parentFilterTreePath + this.TREE_PATH_SEPARATOR + endFilterTreePath;
                emptyRow.add_flg            = targetRow.add_flg;
                // 挿入位置：現在の添え字 + 追加した行数
                wjMultiRowControle.collectionView.sourceCollection.splice((parseInt(targetRowIndx) + addCnt), 0, emptyRow);

                // 追加した行のリスト
                result.push(emptyRow);

                if (!(parentFilterTreePath in parentFilterTreePathList)) {
                    parentFilterTreePathList[parentFilterTreePath] = targetRow;
                }
                addCnt++;
            }

            // 連番を振りなおす
            for (let parentFilterTreePath in parentFilterTreePathList) {
                var record = parentFilterTreePathList[parentFilterTreePath];
                var currentGridDataList = this.getChildGridDataList(wjMultiRowControle, parentFilterTreePath, record.depth);
                for(var j=0; j < currentGridDataList.length; j++){
                    currentGridDataList[j].seq_no = (j+1);
                    var currentItem = this.findTree(wjTreeViewControl.itemsSource, 'filter_tree_path', currentGridDataList[j].filter_tree_path);
                    if(currentItem !== null){
                        currentItem.seq_no = currentGridDataList[j].seq_no;
                    }
                }
            }
            return result;
        },

        /**
         * 選択されているツリーに対して行追加を行う
         * @param {*} wjMultiRowControle    グリッドのインスタンス 
         * @param {*} wjTreeViewControl     ツリーのインスタンス 
         * @param {*} rowList               追加行
         * @returns {*} result              追加した行のリスト
         */
        addSelectTreeGrid(wjMultiRowControle, wjTreeViewControl, rowList){
            // 追加した行のリスト
            var result = [];

            var currentTree = wjTreeViewControl.selectedItem;

            for(let i in rowList) {
                var record = Vue.util.extend({}, rowList[i]);

                var seqNo = 0;
                var tmpFilterTreePath = 0;
                var endFilterTreePath = 0;

                // 選択されているツリーのグリッドデータを取得
                var currentGridDataList = this.getChildGridDataList(wjMultiRowControle, currentTree.filter_tree_path, (currentTree.depth + 1));
                if (currentGridDataList.length > 0) {
                    seqNo = Math.max.apply(null,currentGridDataList.map(function(o){return o.seq_no;}));
                    tmpFilterTreePath = currentGridDataList.reduce((a,b)=>this.getEndFilterTreePath(a.filter_tree_path)>this.getEndFilterTreePath(b.filter_tree_path)?a:b).filter_tree_path;
                    endFilterTreePath = this.getEndFilterTreePath(tmpFilterTreePath) + 1;
                }

                record.seq_no = ++seqNo;
                record.depth = (currentTree.depth + 1);      
                record.construction_id = currentTree.construction_id;;
                record.filter_tree_path = currentTree.filter_tree_path + this.TREE_PATH_SEPARATOR + endFilterTreePath;
                record.add_flg = currentTree.add_flg;
                // 挿入位置：現在の添え字 + 追加した行数
                wjMultiRowControle.collectionView.sourceCollection.splice((wjMultiRowControle.collectionView.sourceCollection.length + 1), 0, record);

                // 追加した行のリスト
                result.push(record);
            }
            return result;
        },

        /**
         * 今開いている階層で選択したグリッドレコードを削除する
         * @param {*} wjMultiRowControle    グリッドのインスタンス
         * @param {*} wjTreeViewControl     階層のインスタンス
         * @param {*} topFlg                今開いている階層が持っているトップフラグ
         * @param {*} depth                 今開いている階層が持っている深さ
         * @param {*} filterTreePath        今開いている階層が持っているフィルターパス
         */
        deleteTreeGridByGrid(wjMultiRowControle, wjTreeViewControl, topFlg, depth, filterTreePath){
            var gridData = wjMultiRowControle.collectionView.sourceCollection;
            var i = 0;
            while(i < gridData.length){
                if (gridData[i].chk) {
                    // 今開いている階層のチェックを付けたグリッドのみを対象とする
                    if((topFlg === this.FLG_ON && gridData[i].depth === this.QUOTE_CONSTRUCTION_DEPTH) || 
                    　(gridData[i].depth === (depth+1) && gridData[i].filter_tree_path.indexOf(filterTreePath + this.TREE_PATH_SEPARATOR) === 0)){

                        // チェックを付けたグリッドレコードのフィルターパス
                        var deleteFilterTreePath = gridData[i].filter_tree_path;
                        this.deleteTreeGridRow(gridData, wjTreeViewControl, deleteFilterTreePath);
                        i=0;
                    }else{
                        i++;
                    }
                }else{
                    i++;
                }
            
            }
        },

        /**
         * 選択したグリッドの行を削除する
         * @param {*} sourceCollection      グリッドインスタンスのsourceCollection
         * @param {*} wjTreeViewControl     階層のインスタンス
         * @param {*} deleteFilterTreePath  削除対象のフィルターパス
         */
        deleteTreeGridRow(sourceCollection, wjTreeViewControl, deleteFilterTreePath){
            // 階層を取得
            var currentItem = this.findTree(wjTreeViewControl.itemsSource, 'filter_tree_path', deleteFilterTreePath);
            
            if(currentItem !== null){
                // 階層を選択していた場合、ツリービューから削除
                var treeNode = wjTreeViewControl.getNode(currentItem);
                var parentItem = treeNode.parentNode.dataItem[wjTreeViewControl.childItemsPath];
                var index = parentItem.indexOf(currentItem);
                if(index >= 0){
                    parentItem.splice(index, 1);
                }
            }
            
            // グリッドから削除　選択したレコードが階層の場合、配下を含めて削除
            for (var j = 0; j < sourceCollection.length; j++) {
                if(sourceCollection[j].filter_tree_path === deleteFilterTreePath || sourceCollection[j].filter_tree_path.indexOf(deleteFilterTreePath + this.TREE_PATH_SEPARATOR) === 0){
                    sourceCollection.splice(j, 1);
                    j--;
                }
            }
        },

        /**
         * 明細から階層にする
         * @param {*} itemsSource       グリッドインスタンスのitemsSource.items
         * @param {*} wjTreeViewControl 階層のインスタンス
         * @param {*} rowList           階層化する明細のリスト(右クリック時のみ必須)
         */
        treeGridDetailRecordToLayer(itemsSource, wjTreeViewControl, rowList){
            var parentNodeList = [];    // 階層化したレコードが入る親の階層リスト(itemsSource.itemsがパラメータの場合は1件)
            if(typeof rowList === 'undefined'){
                rowList = [];
                for (var i = 0; i < itemsSource.length; i++) {
                    if (itemsSource[i].chk && itemsSource[i].layer_flg !== this.FLG_ON) {
                        rowList.push(itemsSource[i]);
                    }
                }
            }
            
            for (var i = 0; i < rowList.length; i++) {
                var record = rowList[i];
                record.layer_flg = this.FLG_ON;
                //record.chk = false;

                var filterTreePath = record.filter_tree_path;
                var currentTreeFilterPath = filterTreePath.slice(0, filterTreePath.lastIndexOf(this.TREE_PATH_SEPARATOR));
                var item = this.findTree(wjTreeViewControl.itemsSource, 'filter_tree_path', currentTreeFilterPath);
                var treeNode = wjTreeViewControl.getNode(item)
                var treeData = {
                    id: 0,
                    construction_id:record.construction_id,
                    layer_flg:      this.FLG_ON,
                    parent_quote_detail_id: record.parent_quote_detail_id,
                    seq_no:         record.seq_no,
                    depth:          record.depth,
                    tree_path:      '',
                    sales_use_flg:  record.sales_use_flg,
                    product_name:   record.product_name,
                    add_flg:        record.add_flg,
                    top_flg:        this.FLG_OFF,
                    header:         record.product_name,
                    filter_tree_path:   record.filter_tree_path,
                    to_layer_flg:    this.FLG_ON,
                    set_flg:        record.set_flg,
                    items: [],
                };
                treeNode.addChildNode(treeNode.dataItem[wjTreeViewControl.childItemsPath].length, treeData);
                parentNodeList[currentTreeFilterPath] = treeNode;
            }

            for(let i in parentNodeList){
                parentNodeList[i].dataItem['items'].sort(function(a,b){
                    if(a['depth'] < b['depth']) return -1;
                    if(a['depth'] > b['depth']) return 1;
                    if(a['seq_no'] < b['seq_no']) return -1;
                    if(a['seq_no'] > b['seq_no']) return 1;
                    if(a['filter_tree_path'] < b['filter_tree_path']) return -1;
                    if(a['filter_tree_path'] > b['filter_tree_path']) return 1;
                    return 0;
                });
            }
            
        },

        /**
         * 一式にする
         * 「treeGridDetailRecordChkToSet」を使用し不整合が無い状態で呼んでください
         * @param {*} itemsSource       グリッドインスタンスのitemsSource.items
         * @param {*} wjTreeViewControl 階層のインスタンス
         * @param {*} rowList           階層化する明細のリスト(右クリック時のみ必須)
         */
        treeGridDetailRecordToSet(itemsSource, wjTreeViewControl, rowList){
            if(typeof rowList === 'undefined'){
                rowList = [];
                for (var i = 0; i < itemsSource.length; i++) {
                    if (itemsSource[i].chk && itemsSource[i].set_flg !== this.FLG_ON) {
                        rowList.push(itemsSource[i]);
                    }
                }
            }
            
            for (var i = 0; i < rowList.length; i++) {
                var record = rowList[i];
                record.set_flg = this.FLG_ON;
                if(record.layer_flg === this.FLG_ON){
                    record.sales_use_flg = true;
                    var filterTreePath = record.filter_tree_path;
                    var item = this.findTree(wjTreeViewControl.itemsSource, 'filter_tree_path', filterTreePath);
                    // 既に階層化してある場合は階層の一式フラグを立てる
                    if(item !== null){
                        item.set_flg = record.set_flg;
                        item.sales_use_flg = record.sales_use_flg;
                    }
                }
            }
        },

        /**
         * 保存する時に一式の数量が1になっているかチェックする
         * @param {*} row               行データ
         * @param {*} totalQuantity     数量
         * @returns result              結果
         */
        treeGridSetProductSaveIsValid(row, totalQuantity){
            // 一式の発注は1個のみ
            var result = true;
            if(row.set_flg === this.FLG_ON){
                var quantity = parseFloat(this.rmUndefinedZero(totalQuantity));
                if(!this.bigNumberEq(quantity, 1) && !this.bigNumberEq(quantity, 0)){
                    result = false;
                }
            }
            return result;
        },

        /**
         * 【定価変更時】に呼び出す
         * cost_unit_price(仕入単価)を計算してセットする
         * sales_unit_price(販売単価)を計算してセットする
         * 
         * 掛率が0の場合は単価から掛率を計算する
         * 定価が0の場合は掛率を0にする
         * @param {*} row 
         */
        calcTreeGridChangeRegularPrice(row){
            row.regular_price = this.roundDecimalStandardPrice(row.regular_price);
            if(this.bigNumberGt(row.regular_price, 0)){
                if(this.bigNumberEq(row.cost_makeup_rate, 0)){
                    this.calcTreeGridChangeUnitPrice(row, true);
                }else{
                    // 仕入単価 = 定価 * 仕入掛率 / 100
                    row.cost_unit_price = this.roundDecimalStandardPrice(this.bigNumberDiv(this.bigNumberTimes(row.regular_price, row.cost_makeup_rate, true), 100, true));
                }
                if(this.bigNumberEq(row.sales_makeup_rate, 0)){
                    this.calcTreeGridChangeUnitPrice(row, false);
                }else{
                    // 販売単価 = 定価 * 販売掛率 / 100
                    row.sales_unit_price = this.roundDecimalSalesPrice(this.bigNumberDiv(this.bigNumberTimes(row.regular_price, row.sales_makeup_rate, true), 100, true));
                }
            }else{
                row.cost_makeup_rate = 0;
                row.sales_makeup_rate = 0;
            }
        },
        /**
         * 【販売区分変更時】に呼び出す 削除予定
         * cost_unit_price(仕入単価)を取得してセットする
         * sales_unit_price(販売単価)を取得してセットする
         * @param {*} row           グリッドの行データ
         * @param {*} isCost        仕入区分か
         * @param {*} defaultSetFlg 選択した区分が無ければ標準の区分をセットするか
         */
        setTreeGridUnitPrice(row, isCost, defaultSetFlg){
            // 標準の単価を格納する
            var normalUnitPrice = 0;
            // 最終的にセットする単価を格納する
            var unitPrice = 0;

            var productPriceList = this.salesProductPriceList;
            var colNormalUnitPrice = 'normal_sales_price';
            var colUnitPrice = 'sales_unit_price';
            var colKbn = 'sales_kbn';

            if(isCost){
                var productPriceList = this.costProductPriceList;
                var colNormalUnitPrice = 'normal_purchase_price';
                var colUnitPrice = 'cost_unit_price';
                var colKbn = 'cost_kbn';
                // 仕入のみ標準の優先度が　立米 > 入荷時の仕入単価マスタ > 商品マスタの標準仕入単価
                // 立米 と 入荷時の仕入単価マスタはproductPriceListにあるため先に取得しておく
                if(typeof productPriceList[row.product_id] !== 'undefined' && typeof productPriceList[row.product_id][this.NORMAL_PRODUCT_PRICE_KBN] !== 'undefined'){
                    normalUnitPrice  = productPriceList[row.product_id][this.NORMAL_PRODUCT_PRICE_KBN]['price'];
                }
            }

            var newLine = this.productList.filter(function(item, index){
                if (item['product_id'] === row.product_id) return true;
            });
            if(newLine.length === 1 && normalUnitPrice === 0){
                normalUnitPrice = newLine[0][colNormalUnitPrice];
            }

            if(row[colKbn] === this.NORMAL_PRODUCT_PRICE_KBN){
                unitPrice = normalUnitPrice;
            }else{
                if(typeof productPriceList[row.product_id] !== 'undefined' && typeof productPriceList[row.product_id][row[colKbn]] !== 'undefined'){
                    unitPrice = productPriceList[row.product_id][row[colKbn]]['price'];
                }else{
                    if(defaultSetFlg){
                        unitPrice = normalUnitPrice;
                        row[colKbn] = this.NORMAL_PRODUCT_PRICE_KBN;
                    }else{
                        unitPrice = 0;
                    }
                }
            }

            row[colUnitPrice] = parseInt(this.rmUndefinedZero(unitPrice));
        },

        /**
         * 【販売区分変更時】に呼び出す
         * cost_unit_price(仕入単価)を取得してセットする
         * sales_unit_price(販売単価)を取得してセットする
         * @param {*} row           グリッドの行データ
         * @param {*} isCost        仕入区分か
         * @param {*} productPriceList  商品単価リスト
         * @param {*} defaultSetFlg 選択した区分が無ければ標準の区分をセットするか
         */
        setTreeGridUnitPriceNew(row, isCost, productPriceList, defaultSetFlg){
            // 最終的にセットする単価を格納する
            var unitPrice = 0;

            var colUnitPrice = 'sales_unit_price';
            var colKbn = 'sales_kbn';

            if(isCost){
                var colUnitPrice = 'cost_unit_price';
                var colKbn = 'cost_kbn';
            }

            if(productPriceList[row[colKbn]] !== undefined){
                unitPrice = productPriceList[row[colKbn]]['price'];
            }else{
                if(defaultSetFlg){
                    unitPrice = productPriceList[this.NORMAL_PRODUCT_PRICE_KBN]['price'];
                    row[colKbn] = this.NORMAL_PRODUCT_PRICE_KBN;
                }
            }
            
            row[colUnitPrice] = parseInt(this.rmUndefinedZero(unitPrice));
        },
        /**
         * 【単価変更時】に呼び出す
         * cost_makeup_rate(仕入掛率)を計算してセットする
         * sales_makeup_rate(販売掛率)を計算してセットする
         * @param {*} row       グリッドの行データ
         * @param {*} isCost    仕入単価か
         */
        calcTreeGridChangeUnitPrice(row, isCost){
            if(isCost){
                row.cost_unit_price = this.roundDecimalStandardPrice(row.cost_unit_price);
                // 仕入掛率 =  仕入単価 / 定価 * 100
                row.cost_makeup_rate = this.roundDecimalRate(this.bigNumberTimes(this.bigNumberDiv(row.cost_unit_price, row.regular_price, true), 100, true));
            }else{
                row.sales_unit_price = this.roundDecimalSalesPrice(row.sales_unit_price);
                // 販売掛率 = 販売単価 / 定価 * 100
                row.sales_makeup_rate = this.roundDecimalRate(this.bigNumberTimes(this.bigNumberDiv(row.sales_unit_price, row.regular_price, true), 100, true));
            }
        },
        /**
         * 【掛率変更時】に呼び出す
         * cost_unit_price(仕入単価)を計算してセットする
         * sales_unit_price(販売単価)を計算してセットする
         * @param {*} row       グリッドの行データ
         * @param {*} isCost    仕入掛率か
         */
        calcTreeGridChangeMakeupRate(row, isCost){
            if(isCost){
                // 仕入単価 = 定価 * (仕入掛率 / 100)
                row.cost_makeup_rate = this.roundDecimalRate(row.cost_makeup_rate);
                row.cost_unit_price = this.roundDecimalStandardPrice(this.bigNumberTimes(row.regular_price, this.bigNumberDiv(row.cost_makeup_rate, 100, true), true));
            }else{
                // 販売単価 = 定価 * (販売掛率 / 100)
                row.sales_makeup_rate = this.roundDecimalRate(row.sales_makeup_rate);
                row.sales_unit_price = this.roundDecimalSalesPrice(this.bigNumberTimes(row.regular_price, this.bigNumberDiv(row.sales_makeup_rate, 100, true), true));
            }
        },
        /**
         * 【粗利率変更時】に呼び出す
         * sales_unit_price(販売単価)を計算してセットする
         * @param {*} row   グリッドの行データ
         */
        calcTreeGridChangeGrossProfitRate(row){
            // 100以上の値は0に変換する
            var grossProfitRate = 0;
            row.gross_profit_rate = this.roundDecimalRate(row.gross_profit_rate);
            if (this.bigNumberGt(row.gross_profit_rate, 99)) {
                row.gross_profit_rate = 0;
            }else{
                // 100% - 粗利率
                grossProfitRate = this.bigNumberMinus(100, row.gross_profit_rate, true);
            }
            // 販売単価 = 仕入単価 / (1－(粗利率/100))
            row.sales_unit_price = this.roundDecimalSalesPrice(this.bigNumberTimes(this.bigNumberDiv(row.cost_unit_price, grossProfitRate, true), 100, true));
            
            // 販売総額 = 仕入総額 / (1 - (粗利率 / 100))
            // row.sales_total = this.rmInvalidNumZero(this.roundPrice(this.rmUndefinedZero(row.cost_total) / (1 - (this.rmUndefinedZero(row.gross_profit_rate) / 100))));
            // // 粗利総額 = 販売総額 - 仕入総額
            // row.profit_total = this.rmInvalidNumZero(this.roundPrice(this.rmUndefinedZero(row.sales_total) - this.rmUndefinedZero(row.cost_total)));
            // // 仕入単価 = 仕入総額 / 数量
            // row.cost_unit_price = this.rmInvalidNumZero(this.roundPrice(this.rmUndefinedZero(row.cost_total) / this.rmUndefinedZero(row.order_quantity)));
            // // 販売単価 = 販売総額 / 数量
            // row.sales_unit_price = this.rmInvalidNumZero(this.roundPrice(this.rmUndefinedZero(row.sales_total) / this.rmUndefinedZero(row.order_quantity)));
        },
        /**
         * 数量変更や商品変更時など通常の行に対する計算処理
         * @param {*} row           グリッドの行データ
         * @param {*} quantityColNm 数量を表すカラム名
         */
        calcTreeGridRowData(row, quantityColNm){
            // 管理数 = 数量 * 最小単位数
            row[quantityColNm] = this.rmUndefinedZero(row[quantityColNm]);
            var tmpStockQuantity = this.rmInvalidNumZero(this.bigNumberDiv(row[quantityColNm], row.min_quantity));
            if(Number.isInteger(tmpStockQuantity)){
                row.stock_quantity = tmpStockQuantity;
            }else{
                // TODO
                row[quantityColNm] = 0;
                row.stock_quantity = 0;
            }
            // 仕入総額 = 数量 * 仕入単価
            row.cost_total = this.roundDecimalStandardPrice(this.bigNumberTimes(row[quantityColNm], row.cost_unit_price, true));
            // 販売総額 = 数量 * 販売単価
            row.sales_total = this.roundDecimalSalesPrice(this.bigNumberTimes(row[quantityColNm], row.sales_unit_price, true));
            
            // 粗利総額 = 販売総額 - 仕入総額
            row.profit_total = this.rmInvalidNumZero(this.bigNumberMinus(row.sales_total, row.cost_total));
            // 粗利率 = 粗利総額 / 販売総額 * 100
            row.gross_profit_rate = this.roundDecimalRate(this.bigNumberTimes(this.bigNumberDiv(row.profit_total, row.sales_total, true), 100, true)); 
        },
        /**
         * 階層の仕入総額・販売総額・粗利総額・粗利率を計算する
         * グリッドの販売額利用フラグの名前は「sales_use_flg」
         * @param {*} wjMultiRowControle    グリッドのインスタンス
         * @param {*} treeItems             階層のitemsプロパティ
         * @param string quantityColNm      数量を表すカラム名
         */
        calcTreeGridCostSalesTotal(wjMultiRowControle, treeItems, quantityColNm){
            // 同階層の合計(明細を含まない)
            var total = [];
            total['cost'] = 0;
            total['sales'] = 0;
            for(var i =0; i<treeItems.length; i++){
                var filterPath = treeItems[i]['filter_tree_path'];
                var depth = treeItems[i]['depth'];

                // 階層を取得
                var record = wjMultiRowControle.collectionView.sourceCollection.find((rec) => {
                    return (rec.filter_tree_path === filterPath);
                });
                
                record.cost_unit_price = 0;
                record.cost_total = 0;
                record.sales_unit_price = this.roundDecimalSalesPrice(record.sales_unit_price);
                record.sales_total = this.roundDecimalSalesPrice(record.sales_total);
                if(!record.sales_use_flg){
                    record.sales_unit_price = 0;
                    record.sales_total = 0;
                }

                // 直下の明細を取得
                var gridDetailList = this.getChildGridDataList(wjMultiRowControle, filterPath, (depth + 1), this.FLG_OFF);
                
                for(var j=0; j<gridDetailList.length; j++){
                    // 明細の合計金額を計算
                    var gridDetail = gridDetailList[j];
                    record.cost_total += this.roundDecimalStandardPrice(gridDetail.cost_total);

                    if(!record.sales_use_flg){
                        record.sales_total += this.roundDecimalSalesPrice(gridDetail.sales_total);
                    }
                }
                
                var tmp = this.calcTreeGridCostSalesTotal(wjMultiRowControle, treeItems[i]['items'], quantityColNm);
                record.cost_total += tmp['cost'];
                record.cost_unit_price = this.roundDecimalStandardPrice(this.bigNumberDiv(record.cost_total, record[quantityColNm], true));
                record.cost_makeup_rate = this.roundDecimalRate(this.bigNumberTimes(this.bigNumberDiv(record.cost_unit_price, record.regular_price, true), 100, true));
                total['cost'] += record.cost_total;

                
                if(!record.sales_use_flg){
                    record.sales_total += tmp['sales'];
                    record.sales_unit_price = this.roundDecimalSalesPrice(this.bigNumberDiv(record.sales_total, record[quantityColNm], true));
                    record.sales_makeup_rate = this.roundDecimalRate(this.bigNumberTimes(this.bigNumberDiv(record.sales_unit_price, record.regular_price, true), 100, true));
                    total['sales'] += record.sales_total;
                }else{
                    total['sales'] += record.sales_total;
                }


                // 粗利総額
                record.profit_total = this.rmInvalidNumZero(this.bigNumberMinus(record.sales_total, record.cost_total));
                // 粗利率
                record.gross_profit_rate = this.roundDecimalRate(this.bigNumberTimes(this.bigNumberDiv(record.profit_total, record.sales_total, true), 100, true)); 
                
            }
            return total;
        },

        /**
         * 第一引数が第二引数の倍数かチェックする
         * @param {*} quantity  数量
         * @param {*} multiple  倍数
         */
        treeGridQuantityIsMultiple(quantity, multiple){
            quantity = this.rmUndefinedZero(quantity);
            multiple = this.rmUndefinedZero(multiple);
            return this.bigNumberEq(this.bigNumberMod(quantity, multiple, true), 0);
        },

        /**
         * 1回限り登録／本登録 対象か
         * 階層でない && 一式でない && 商品IDがない && 商品コードの入力がある && （1回限り登録フラグが立っている　OR　本登録フラグが立っている）
         * @row     グリッドの行
         */
        isProductFormalRegistrationTarget(row){
            var result = false;
            if(row.layer_flg === this.FLG_OFF){
                if(row.set_flg !== this.FLG_ON){
                    if(this.rmUndefinedZero(row.product_id) === 0 && this.rmUndefinedBlank(row.product_code) !== ''){
                        if(row.product_auto_flg || row.product_definitive_flg){
                            result = true;
                        }
                    }
                }
            }
            return result;
        },

        /**
         * 2段グリッドのコピペ制御
         * autoClipboard プロパティを false にしてください
         * グリッドのhostElement に対するkeydownイベント内で使用して下さい
         * @param {*} clipboard                 @grapecity/wijmo/Clipboard
         * @param {*} wjMultiRowControle        グリッドのインスタンス
         * @param {*} nonPastingCols            貼付対象から外す列のリスト
         * @param {*} pastingValidationFunc     入力チェックの関数(任意)            【pastingValidationFunc(wjMultiRowControle, text): boolean】
         * @param {*} pastedFunc                貼り付け完了時に呼ぶ関数(任意)      【pastedFunc(pastedRowList): void】
         */
        wjMultiRowClipboardCtrl(clipboard, wjMultiRowControle, nonPastingCols, pastingValidationFunc, pastedFunc){
            if(event.ctrlKey){
                if (event.keyCode == 67) {
                    // 行選択した場合のみ行コピーの処理を実行
                    if(wjMultiRowControle.selectedRanges[0].columnSpan === wjMultiRowControle.columns.length){
                        // クリップボードへコピー
                        this.wjMultiRowCopyClipboard(clipboard, wjMultiRowControle, wjMultiRowControle.selectedRows);
                    }
                }else if (event.keyCode == 86) {
                    // クリップボードの文字をグリッドに貼り付け
                    clipboard.paste(function (text) {
                        var pastedRowList = [];

                        var selectedRowList = wjMultiRowControle.selectedRows;
                        var clipboardData = this.toWjMultiRowPasteTextFormat(text);

                        // 未選択 or 一部のみ選択
                        if(selectedRowList.length === 0){
                            return;
                        }else if(selectedRowList.length%2 === 0){
                            if(typeof pastingValidationFunc === 'function'){
                                // 画面固有の入力チェック
                                if(!pastingValidationFunc(wjMultiRowControle, text)){
                                    return;
                                }
                            }
                            // 貼り付け
                            pastedRowList = this.wjMultiRowPasteClipboard(clipboardData, wjMultiRowControle, nonPastingCols);
                            
                            if(typeof pastedFunc === 'function'){
                                pastedFunc(pastedRowList);
                            }
                        }
                    }.bind(this));
                }
            }
        },
        /**
         * 右クリックでクリップボードにコピー
         * @param {*} clipboard             @grapecity/wijmo/Clipboard
         * @param {*} wjMultiRowControle    グリッドのインスタンス
         * @param {*} rowList               対象の行
         */
        wjMultiRowCopyClipboard(clipboard, wjMultiRowControle, rowList){
            // クリップボードへコピー
            var copyData = '';
            var layount = wjMultiRowControle.layoutDefinition;

            var tmpRowList = [];
            for(var i=0; i< rowList.length; i++){
                var rec = rowList[i].dataItem;
                if(typeof rec === 'undefined'){
                    continue;
                }
                tmpRowList.push(rowList[i]);
            }

            for(var i=0; i< tmpRowList.length; i++){
                var rec = tmpRowList[i].dataItem;
                if(typeof rec === 'undefined'){
                    continue;
                }
                var multiCnt = i%this.WJ_MULTI_ROW_CNT;
                for(var j=0; j<layount.length; j++){
                    var key = layount[j].cells[multiCnt];
                    if(typeof key !== 'undefined'){
                        copyData += this.rmUndefinedBlank(rec[key.binding]) + this.WJ_MULTI_ROW_CLIPBOARD_CTRL_OPTION.DELIMITER;
                    }else{
                        copyData += this.rmUndefinedBlank(rec[layount[j].cells[0].binding]) + this.WJ_MULTI_ROW_CLIPBOARD_CTRL_OPTION.DELIMITER;
                    }
                }
                // 行末尾の \t を削除
                copyData = copyData.slice(0, -1);
                if(i+1< tmpRowList.length){
                    copyData += this.WJ_MULTI_ROW_CLIPBOARD_CTRL_OPTION.LINE_FEED;
                }
                
            }
            this.rightClickInfo.clipboardText = copyData;
            clipboard.copy(copyData);
        },

        /**
         * クリップボードの文字列を配列にして返す
         * @param {*} text  クリップボードの文字列
         */
        toWjMultiRowPasteTextFormat(text){
            var clipboardData = text.split(this.WJ_MULTI_ROW_CLIPBOARD_CTRL_OPTION.LINE_FEED);
            if(clipboardData.length%2 !== 0 && clipboardData[clipboardData.length-1] === ""){
                // 末尾の改行対応
                clipboardData.pop();
            }
            return clipboardData;
        },

        /**
         * 貼り付け処理
         * @param {*} clipboardData         クリップボードのテキストの配列(toWjMultiRowPasteTextFormat関数の戻り値)
         * @param {*} wjMultiRowControle    グリッドのインスタンス
         * @param {*} nonPastingCols        貼付対象から外す列のリスト
         */
        wjMultiRowPasteClipboard(clipboardData, wjMultiRowControle, nonPastingCols){
            var pastedRowList = [];
            var selectedRowList = wjMultiRowControle.selectedRows;
            var layount = wjMultiRowControle.layoutDefinition;
            for(var i=0;i<selectedRowList.length;i++){
                if(i%this.WJ_MULTI_ROW_CNT !== 0){
                    // selectedRowsは1行選択につき2段取得されるため2段目はスキップ
                    continue;
                }
                if(typeof selectedRowList[i].dataItem === 'undefined'){
                    continue;
                }
                // 選択したグリッド行をソースコレクションから取得(自身の行を取得)
                var targetGridRecord = this.getChildGridDataList(wjMultiRowControle, selectedRowList[i].dataItem.filter_tree_path, selectedRowList[i].dataItem.depth);
                // 貼り付け
                this.wjMultiRowSetClipboardData(clipboardData, layount, targetGridRecord[0], i, nonPastingCols);
                // 貼り付け完了したグリッド行
                pastedRowList.push(targetGridRecord[0]);
            }
            return pastedRowList;
        },

        /**
         * クリップボードのテキストを変換して貼り付ける
         * @param {*} clipboardData     クリップボードのテキストの配列(toWjMultiRowPasteTextFormat関数の戻り値)
         * @param {*} layount           グリッドのレイアウト
         * @param {*} targetGridRecord  貼り付け対象の行
         * @param {*} rowCnt            selectedRowsの添え字
         * @param {*} nonPastingCols    貼付対象から外す列のリスト
         */
        wjMultiRowSetClipboardData(clipboardData, layount, targetGridRecord, rowCnt, nonPastingCols){
            for(var multiCnt=0; multiCnt<this.WJ_MULTI_ROW_CNT; multiCnt++){
                if(typeof clipboardData[rowCnt + multiCnt] !== 'undefined'){
                    var clipboardDataRecord = clipboardData[rowCnt + multiCnt].split(this.WJ_MULTI_ROW_CLIPBOARD_CTRL_OPTION.DELIMITER);
                    for(var j=0; j<layount.length; j++){
                        if(typeof layount[j].cells[multiCnt] !== 'undefined'){
                            // グリッドのカラム名取得
                            var bindingName = layount[j].cells[multiCnt].binding;
                            // 貼り付け対象か
                            if(nonPastingCols.indexOf(bindingName) === -1){
                                var cellValue = clipboardDataRecord[j];
                                // 型変換
                                if(cellValue == 'true' || cellValue == 'false' || cellValue == 'TRUE' || cellValue == 'FALSE'){
                                    cellValue = (cellValue == 'true' || cellValue == 'TRUE')
                                }else if(!isNaN(cellValue) && this.rmUndefinedBlank(cellValue) !== ''){
                                    switch(bindingName){
                                        case 'quote_no':
                                        case 'product_code':
                                        case 'product_name':
                                        case 'model':
                                        case 'maker_name':
                                        case 'supplier_name':
                                        case 'stock_quantity':
                                        case 'stock_unit':
                                        case 'memo':
                                        case 'tree_path':
                                        case 'filter_tree_path':
                                            break;
                                        default:
                                            cellValue = parseFloat(cellValue);
                                            break;
                                    }
                                    
                                }
                                targetGridRecord[bindingName] = cellValue;
                            }
                        }
                    }
                }
            }
        },
        
        /**
         * 行ヘッダー右クリックでメニューを表示するようにする
         * @param {*} wjGrid        @grapecity/wijmo.grid
         * @param {*} wjcInput      @grapecity/wijmo.input
         * @param {*} gridCtrl      グリッドのインスタンス
         * @param {*} layoutName    レイアウトの名前(任意)　rightClickInfo.layout
         * @returns contextMenu     この戻り値に対して呼び出し元でイベントを紐づける【contextMenu.itemClicked.addHandler】
         */
        setTreeGridRightCtrl(wjGrid, wjcInput, gridCtrl, layoutName){
            if(typeof layoutName === 'undefined'){
                layoutName = 'layout'
            }
            let contextMenu = new wjcInput.Menu(document.createElement('div'), {
                displayMemberPath: 'header',
                selectedValuePath: 'cmd',
                dropDownCssClass: 'ctx-menu',
                itemsSource: this.rightClickInfo[layoutName],
            });
            
            // 右クリック
            gridCtrl.hostElement.addEventListener('contextmenu', function (e) {
                let ht = gridCtrl.hitTest(e);
                if (ht.cellType == wjGrid.CellType.RowHeader) {
                    contextMenu.owner = gridCtrl.hostElement;
                    contextMenu.gridCtrl = gridCtrl;
                    
                    contextMenu.row = ht.panel.rows[ht.row].dataItem;
                    // 確定済みの行ヘッダー以外はメニューを表示しない
                    if (contextMenu.owner && typeof contextMenu.row !== 'undefined' && !gridCtrl.isReadOnly) {
                        var rowIndex = gridCtrl.collectionView.sourceCollection.findIndex((rec) => {
                            return (rec.filter_tree_path === contextMenu.row.filter_tree_path);
                        });
                        contextMenu.rowIndex = rowIndex;
                        e.preventDefault();
                        contextMenu.show(e);
                    }
                }
            });
            return contextMenu;
        },

        /**
         * 一式化できるか
         * TREE_GRID_CHK_KBN_LIST.VALID:0       一式化できる
         * TREE_GRID_CHK_KBN_LIST.IN_VALID_1:1  一式化できない(一式化済み)
         * TREE_GRID_CHK_KBN_LIST.IN_VALID_2:2  一式化できない(工事区分)
         * TREE_GRID_CHK_KBN_LIST.IN_VALID_3:3  一式化できない(子に階層がある)
         * TREE_GRID_CHK_KBN_LIST.IN_VALID_4:4  一式化できない(子に一式がある)
         * TREE_GRID_CHK_KBN_LIST.IN_VALID_5:5  一式化できない(親に一式がある)
         * TREE_GRID_CHK_KBN_LIST.IN_VALID_6:6  一式化できない(追加部材階層)
         * 
         * @param {*} gridCtrl  グリッドのインスタンス
         * @param {*} row       対象の行
         * @returns {*} resultKbn 
         */
        treeGridDetailRecordChkToSet(gridCtrl, row){
            var resultKbn = this.TREE_GRID_CHK_KBN_LIST.VALID;
            if(row.depth !== this.QUOTE_CONSTRUCTION_DEPTH){
                if(row.set_flg !== this.FLG_ON){
                    var childRowList = this.getChildGridDataList(gridCtrl, row.filter_tree_path, (row.depth + 1));
                    for(let i in childRowList){
                        if(childRowList[i].layer_flg === this.FLG_ON){
                            // 子に階層がある
                            resultKbn = this.TREE_GRID_CHK_KBN_LIST.IN_VALID_3;
                            break;
                        }
                        if(childRowList[i].set_flg === this.FLG_ON){
                            // 子に一式がある
                            resultKbn = this.TREE_GRID_CHK_KBN_LIST.IN_VALID_4;
                            break;
                        }
                    }
                    if(this.treeGridDetailRecordParentIsSetFlg(gridCtrl, row)){
                        // 親が一式
                        resultKbn = this.TREE_GRID_CHK_KBN_LIST.IN_VALID_5;
                    }else if(this.treeGridDetailRecordConstructionIsAddLayer(gridCtrl, row)){
                        // 追加部材
                        resultKbn = this.TREE_GRID_CHK_KBN_LIST.IN_VALID_6;
                    }
                }else{
                    // 選択した行が既に一式
                    resultKbn = this.TREE_GRID_CHK_KBN_LIST.IN_VALID_1;
                }
            }else{
                // 工事区分を選択
                resultKbn = this.TREE_GRID_CHK_KBN_LIST.IN_VALID_2;
            }
            return resultKbn;
        },

        /**
         * 渡した行の親が一式かどうか(再帰処理)
         * @param {*} gridCtrl      グリッドのインスタンス
         * @param {*} childRow      対象の行
         */
        treeGridDetailRecordParentIsSetFlg(gridCtrl, row){
            var result = false;
            if(row.depth !== this.QUOTE_CONSTRUCTION_DEPTH){
                var filterTreePath = this.getParentFilterTreePath(row.filter_tree_path);
                var parentRow = gridCtrl.collectionView.sourceCollection.find((rec) => {
                    return (rec.filter_tree_path === filterTreePath);
                });
                if(parentRow.set_flg === this.FLG_ON){
                    result = true;
                }else{
                    result = this.treeGridDetailRecordParentIsSetFlg(gridCtrl, parentRow);
                }
            }else{
                result = row.set_flg === this.FLG_ON;
            }
            return result;
        },
        /**
         * 渡した行の工事区分が追加部材かどうか
         * @param {*} gridCtrl      グリッドのインスタンス
         * @param {*} row           対象の行
         */
        treeGridDetailRecordConstructionIsAddLayer(gridCtrl, row){
            var result = false;
            if(row.depth !== this.QUOTE_CONSTRUCTION_DEPTH){
                var rowFilterTreePath = row.filter_tree_path;
                var constructionFilterTreePath = rowFilterTreePath.slice(0, rowFilterTreePath.indexOf(this.TREE_PATH_SEPARATOR));
                var constructionRow = gridCtrl.collectionView.sourceCollection.find((rec) => {
                    return (rec.filter_tree_path === constructionFilterTreePath);
                });
                result = constructionRow.add_flg === this.FLG_ON;
            }else{
                result = row.add_flg === this.FLG_ON;
            }
            return result;
        },

        /**
         * グリッドの行データを初期化する
         * 商品をブランクにした場合などに使うためフィルターパスなどは初期化しない
         * 初期化カラムを増やしたい場合は、addClearRowListにプロパティ名を渡す
         * @param {*} row               行
         * @param {*} INIT_ROW          初期値のリスト
         * @param {*} addClearRowList   クリアしたいプロパティ名の配列(任意)
         */
        clearTreeGridRow(row, INIT_ROW, addClearRowList){
            for(let col in row){
                var value = INIT_ROW[col];
                if(typeof value !== undefined){
                    switch(col){
                        case 'product_id':
                        case 'model':
                        case 'stock_unit':
                        case 'min_quantity':
                        case 'order_lot_quantity':
                        case 'regular_price':
                        case 'sales_kbn':
                        case 'unit':
                        case 'cost_makeup_rate':
                        case 'cost_unit_price':
                        case 'sales_makeup_rate':
                        case 'sales_unit_price':
                        // case 'maker_id':
                        // case 'maker_name':
                        case 'supplier_id':
                        case 'supplier_name':
                            row[col] = value;
                            break;
                        default :
                            if (Array.isArray(addClearRowList) && addClearRowList.indexOf(col) >= 0){
                                row[col] = value;
                            }
                            break;
                    }
                }
            }
        },

        /**
         * 親階層のフィルターパスを返す
         * 例：0_0_1_0 ⇒ 0_0_1
         * @param {*} filterTreePath 
         */
        getParentFilterTreePath(filterTreePath){
            return filterTreePath.slice(0, filterTreePath.lastIndexOf(this.TREE_PATH_SEPARATOR));
        },

         /**
         * フィルターパスの末尾の数字を返す
         * 例：0_0_1_0 ⇒ 0
         * @param {*} filterTreePath 
         */
        getEndFilterTreePath(filterTreePath){
            var result = null;
            var index = filterTreePath.lastIndexOf(this.TREE_PATH_SEPARATOR);
            if(index >= 0){
                result = parseInt(filterTreePath.slice(index + 1, filterTreePath.length));
            }else{
                result = this.rmInvalidNumZero(parseInt(filterTreePath));
            }
            return result;
        },

        /**
         * 下の階層にあるグリッドデータを返す
         * @param wjMultiRowControle    グリッドのインスタンス
         * @param filterTreePath        親のフィルターパス
         * @param depth                 取得したい深さ
         * @param layerFlg              1:階層のみを返す　0:明細のみを返す　なし:両方返す
         */
        getChildGridDataList(wjMultiRowControle, filterTreePath, depth, layerFlg){
            var currentGridDataList = wjMultiRowControle.collectionView.sourceCollection.filter((rec) => {
                if(typeof layerFlg !== 'undefined'){
                    if(depth === rec.depth && (rec.filter_tree_path === filterTreePath || rec.filter_tree_path.indexOf(filterTreePath + this.TREE_PATH_SEPARATOR) === 0) && rec.layer_flg === layerFlg){
                        return true;
                    }else{
                        return false;
                    }
                }else{
                    if(depth === rec.depth && (rec.filter_tree_path === filterTreePath || rec.filter_tree_path.indexOf(filterTreePath + this.TREE_PATH_SEPARATOR) === 0)){
                        return true;
                    }else{
                        return false;
                    }
                }
            });
            return currentGridDataList;
        },

        /**
         * 配列を入れ替える
         * @param {*} array         対象の配列 
         * @param {*} beforeIndex   対象の添え字
         * @param {*} afterIndex    動かしたい位置の添え字
         */
        arraySwap(array, beforeIndex, afterIndex){
            array[beforeIndex]=[array[afterIndex],array[afterIndex]=array[beforeIndex]][0];
            return array;
        },

        /**
         * 商品情報取得
         * @param productId
         * @param customerId
         */
        getProductInfo(productId, customerId) {
            // 入力値の取得
            this.loading = true;
            var params = new URLSearchParams();
            params.append('product_id', productId);
            params.append('customer_id', customerId);
            var promise = axios.post('/common/get-product-info', params)
            .then( function (response) {
                if (response.data) {
                    // 成功
                    return response.data;
                } else {
                    // 失敗
                    alert(MSG_ERROR);
                }
            }.bind(this))
            .catch(function (error) {
                alert(MSG_ERROR); 
            }.bind(this))
            .finally(function () {
                this.loading = false;
            }.bind(this));
            return promise;
        },

        /**
         * 商品単価情報取得
         * @param productId
         * @param customerId
         */
        getUnitPrice(productId, customerId) {
            // 入力値の取得
            this.loading = true;
            var params = new URLSearchParams();
            params.append('product_id', productId);
            params.append('customer_id', customerId);
            var promise = axios.post('/common/get-product-unit-price-list', params)
            .then( function (response) {
                if (response.data) {
                    // 成功
                    return response.data;
                } else {
                    // 失敗
                    alert(MSG_ERROR);
                }
            }.bind(this))
            .catch(function (error) {
                alert(MSG_ERROR); 
            }.bind(this))
            .finally(function () {
                this.loading = false;
            }.bind(this));
            return promise;
        },


    },
    
}


