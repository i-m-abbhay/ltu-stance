<template>
    <div>
        <loading-component :loading="loading" />
        <!-- 検索条件 -->
        <div class="search-form search-body col-sm-12 col-md-12" id="searchForm">
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                <div class="row">
                    <div class="col-sm-3">
                        <label>部門名</label>
                        <wj-auto-complete class="form-control" id="acDepartment"
                            search-member-path="department_name"
                            display-member-path="department_name"
                            selected-value-path="id"
                            :initialized="initDepartment"
                            :selectedIndexChanged="changeIdxDepartment"
                            :selected-index="-1"
                            :selected-value="searchParams.department_id"
                            :is-required="false"
                            :max-items="departmentData.length"
                            :items-source="departmentData">
                        </wj-auto-complete>
                    </div>
                    <div class="col-sm-3">
                        <label>担当者名</label>
                        <wj-auto-complete class="form-control" id="acStaff"
                            search-member-path="staff_name"
                            display-member-path="staff_name"
                            selected-value-path="id"
                            :initialized="initStaff"
                            :selected-index="-1"
                            :selected-value="searchParams.staff_id"
                            :is-required="false"
                            :max-items="staffData.length"
                            :items-source="staffData">
                        </wj-auto-complete>
                    </div>
                </div>
                <br>
                <div class="row row-center-item">
                    <div class="col-sm-12">
                        <button type="submit" class="btn btn-search btn-md">　検索　</button>
                        &emsp;
                        <button type="button" class="btn btn-clear btn-md" v-on:click="clearSearch">クリア</button>
                    </div>
                </div>
                <div class="clearfix"></div>
            </form>
        </div>
        <br>
        <!-- 検索結果グリッド -->
        <div class="col-sm-12 result-body">
            <div class="container-fluid">
                <div class="row">
                    <p class="col-md-12 col-xs-12 pull-right search-count" style="text-align:right;">検索結果：{{ tableRowCnt }}件</p>
                </div>
                <div id="authorityGrid" class="wj-flexgrid"></div>
            </div>
        </div>
        <div class="col-sm-12 col-md-10">
            <p>※全承認で承認できる機能は、見積承認／得意先別担当者設定の申請承認／返品承認 です。</p>
        </div>
        <div class="col-sm-12 col-md-2">
            <button type="button" class="btn btn-save pull-right" v-bind:disabled="tableRowCnt===0" v-on:click="save">保存</button>
        </div>
    </div>
</template>

<script>
/* TODO:app.jsで読み込んでいるので、出来るなら二重インポートしたくない */
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjCore from '@grapecity/wijmo';

export default {
    props: {
        authorityBindingPrefix: String,
        departmentData: Array,
        staffData: Array,
        staffDepartmentData: Object,
        authorityData: Array,
    },
    data: () => ({
        loading: false,
        tableRowCnt: 0,
        // クエリパラメータ復帰時,初回表示にしか使うな（created⇒initialized以降値を変更してもwijmoに反映されたりされなかったり）
        searchParams: {
            department_id: null,
            staff_id: null,
        },
        // 以下,initializedで紐づける変数
        wjAuthorityGrid: null,
        wjSearchObj: {
            staff: {},
            department: {},
        },
    }),
    created: function() {
    },
    mounted: function() {
        var layout = this.getGridLayout(this.authorityData);
        this.wjAuthorityGrid = this.createGridCtrl('#authorityGrid', layout, []);

        this.queryParam = window.location.search;
        if (this.queryParam.length > 1) {
            // 検索条件セット
            this.setSearchParams(this.queryParam, this.searchParams);
            this.$nextTick(function() {
                this.search();
            });
        }
    },
    methods: {  
        initDepartment: function(sender){
            this.wjSearchObj.department = sender;
        },
        initStaff: function(sender){
            this.wjSearchObj.staff = sender;
        },
        changeIdxDepartment: function(sender){
            this.wjSearchObj.department = sender;
            // 部門を変更したら担当者を絞り込む
            var tmpStaff = this.staffData;
            if (sender.selectedValue) {
                tmpStaff = [];
                if (this.staffDepartmentData[sender.selectedValue]) {
                    tmpStaff = this.staffData.filter(rec => {
                        return (this.staffDepartmentData[sender.selectedValue].indexOf(rec.id) !== -1)
                    });
                }
            }
            this.wjSearchObj.staff.itemsSource = tmpStaff;
            this.wjSearchObj.staff.selectedIndex = -1;
        },
        // 検索条件クリア(searchParamsの値を変更しても1回目しかリセットが反応しない為wijmoの値を変更する)
        clearSearch() {
            var wjSearchObj = this.wjSearchObj;
            Object.keys(wjSearchObj).forEach(function (key) {
                wjSearchObj[key].selectedValue = null;
                wjSearchObj[key].value = null;
            });
        },
        createGridCtrl(targetGridDivId, layout, gridItemSource) {
            var gridCtrl = new wjGrid.FlexGrid(targetGridDivId, {
                autoGenerateColumns: false,
                headersVisibility: wjGrid.HeadersVisibility.Column,
                allowSorting: false,            // ソート禁止
                frozenColumns: 2,               // 列固定
                columns: layout,                // 列レイアウト
                itemsSource: gridItemSource,    // データ
                loadedRows: function(s, e) {
                    s.autoSizeColumns();
                }
            });
            gridCtrl.itemFormatter = function (panel, r, c, cell) {
                var col = panel.columns[c];
                if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                    // 列ヘッダのセンタリング
                    cell.style.textAlign = 'center';
                    // ヘッダーテキスト削除
                    if (col.binding == 'chk') {
                        cell.innerHTML = '';
                    }
                    // 全チェック用のチェックボックス生成
                    if (gridCtrl.itemsSource.length > 0 && col.binding.indexOf(this.authorityBindingPrefix) == 0) {
                        var checkedCount = 0;
                        for (var i = 0; i < gridCtrl.rows.length; i++) {
                            if (gridCtrl.getCellData(i, c) == true) checkedCount++;
                        }

                        // ヘッダ部にチェックボックス追加
                        var checkBox = '&nbsp;<input type="checkbox">';
                        cell.innerHTML += checkBox;

                        var checkBox = cell.childNodes[1];
                        checkBox.checked = checkedCount > 0;
                        checkBox.indeterminate = checkedCount > 0 && checkedCount < gridCtrl.rows.length;

                        // 明細行にチェック状態を反映
                        checkBox.addEventListener('click', function (e) {
                            gridCtrl.beginUpdate();
                            for (var i = 0; i < gridCtrl.rows.length; i++) {
                                gridCtrl.setCellData(i, c, checkBox.checked);
                            }
                            gridCtrl.endUpdate();
                        });
                    }
                }

                // セルごとの設定
                if (panel.cellType == wjGrid.CellType.Cell) {
                    var dataItem = panel.rows[r].dataItem;
                    if (col.binding === 'chk') {
                        var checkBox = cell.firstChild;
                        var maxChkCount = 0;
                        var checkedCount = 0;
                        for (const key in dataItem) {
                            if (key.indexOf(this.authorityBindingPrefix) === 0) {
                                maxChkCount++;
                                if (dataItem[key]) {
                                    checkedCount++;
                                }
                            }
                        }
                        checkBox.checked = checkedCount > 0;
                        checkBox.indeterminate = checkedCount > 0 && checkedCount < maxChkCount;
                    }
                }
            }.bind(this),
            gridCtrl.cellEditEnded.addHandler(function(s, e) {
                var currentItem = s.collectionView.currentItem;
                var col = e.panel.columns[e.col];
                switch (col.binding) {
                    case 'chk':
                        for (const key in currentItem) {
                            if (key.indexOf(this.authorityBindingPrefix) === 0) {
                                currentItem[key] = currentItem['chk'];
                            }
                        }
                        break;
                }
                gridCtrl.collectionView.commitEdit();
            }.bind(this));
            return gridCtrl;
        },
        // 検索
        search() {
            this.loading = true

            var params = new URLSearchParams();
            params.append('department_id', this.rmUndefinedZero(this.wjSearchObj.department.selectedValue));    // 部門ID
            params.append('staff_id', this.rmUndefinedZero(this.wjSearchObj.staff.selectedValue));              // 担当者ID
            axios.post('/authority-edit/search', params)
                .then( function (response) {
                    if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'department_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.department.selectedValue));
                    this.urlparam += '&' + 'staff_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.staff.selectedValue));

                        var itemsSource = this.getGridData(response.data);

                        this.tableRowCnt = itemsSource.length;
                        this.wjAuthorityGrid.itemsSource = itemsSource;

                        // 描画更新
                        this.wjAuthorityGrid.refresh();
                        
                    }
                    this.loading = false
                }.bind(this))

                .catch(function (error) {
                    this.loading = false
                    if (error.response.data.message) {
                        alert(error.response.data.message)
                    } else {
                        alert(MSG_ERROR)
                    }
                    location.reload()
                }.bind(this))
        },
        // 保存
        save() {
            var result = window.confirm(MSG_CONFIRM_SAVE);
            if (!result) {
                return;
            }

            this.loading = true
            
            // エラーの初期化
            var params = new URLSearchParams();
            var gridData = JSON.stringify(this.wjAuthorityGrid.collectionView.sourceCollection);
            params.append('grid_data', JSON.stringify(this.wjAuthorityGrid.collectionView.sourceCollection));

            axios.post('/authority-edit/save', params)
                .then( function (response) {
                    this.loading = false

                    if (response.data.status == true) {
                        // 成功
                        var listUrl = '/authority-edit/' + this.urlparam;
                        location.href = (listUrl);
                    } else {
                        // 失敗
                        alert(MSG_ERROR)
                    }
                    
                }.bind(this))
                .catch(function (error) {
                    if (error.response.data.errors) {
                        // エラーメッセージ表示
                        this.showErrMsg(error.response.data.errors, this.errors)
                    } else {
                        if (error.response.data.message) {
                            alert(error.response.data.message)
                        } else {
                            alert(MSG_ERROR)
                        }
                    }
                    this.loading = false
                }.bind(this))
        },        
        getGridData(data){
            var gridData = [];
            for (const key1 in data) {
                const staff = data[key1];
                var objStaffAuthority = {};
                objStaffAuthority['staff_id'] = staff.id;
                objStaffAuthority['staff_name'] = staff.staff_name;
                objStaffAuthority['chk'] = false;
                for (const key2 in this.authorityData) {
                    const auth = this.authorityData[key2];
                    objStaffAuthority[this.authorityBindingPrefix + auth.value_code] = true;
                    if (staff.authority_codes.indexOf(auth.value_code) === -1) {
                        objStaffAuthority[this.authorityBindingPrefix + auth.value_code] = false;
                    }
                }   
                gridData.push(objStaffAuthority);
            }
            return gridData;
        },
        // グリッドレイアウト
        getGridLayout(data) {
            var gridlayout = [];
            gridlayout.push(
                { binding: 'staff_name', header: '担当者名', isReadOnly: true },
                { binding: 'chk', header: '', width: 40 },
                { binding: 'staff_id', header: '担当者ID', visible: false },
            );
            for (const key in data) {
                const item = data[key];
                gridlayout.push({
                    binding: this.authorityBindingPrefix + item.value_code,
                    header: item.value_text_1,
                    // width: 100,
                });
            }
            return gridlayout;
        }
    }
};
</script>

<style>
.search-body {
    width: 100%;
    background: #ffffff;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.result-body {
    width: 100%;
    background: #ffffff;
    margin-top: 30px;
    margin-bottom: 10px;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}

.container-fluid > .wj-flexgrid {
    height: 450px;
    margin: 6px 0;
}
/*********************************
    その他
**********************************/
.row-center-item{
    text-align: center;
}
</style>