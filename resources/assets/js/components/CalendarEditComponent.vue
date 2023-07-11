<template>
    <div>
        <loading-component :loading="loading" />
        <div class="row">
            <div class="col-md-12 text-right">
                <label class="form-control-static" v-show="(rmUndefinedBlank(lockdata.id) != '')">ロック日時：{{ lockdata.lock_dt|datetime_format }}&emsp;</label>
                <label class="form-control-static" v-show="(rmUndefinedBlank(lockdata.id) != '')">ロック者：{{ lockdata.lock_user_name }}&emsp;</label>
                <button type="button" class="btn btn-danger pull-right btn-unlock" v-on:click="unlock" v-show="isLocked">ロック解除</button>
                <button type="button" class="btn btn-primary pull-right btn-edit" v-on:click="edit" v-show="isShowEditBtn">編集</button>
                <div class="pull-right">
                    <p class="btn btn-default btn-editing" v-show="(!isReadOnly)">編集中</p>
                </div>
            </div>
        </div>

        <div class="grid-area">
            <div class="row" style="margin-bottom:5px">
                <div class="col-md-4" >
                    <div class="input-group tree-grid-operation-input">
                        <div class="input-group-addon">
                            <span class="glyphicon glyphicon-search"></span>
                        </div>
                        <input v-model="filterText" class="form-control" @input="filterGrid()" v-bind:disabled="isReadOnly">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div v-bind:id="'calendarGrid'"></div>
                </div>
            </div>
        </div>

        <div class="main-body col-md-12">
            <div class="form-horizontal save-form">
                <form id="saveForm">
                    <!-- メイン -->
                    <div class="row">
                        <div class="col-xs-5 col-md-2">
                            <label class="control-label">ID</label>
                            <input type="number" class="form-control" v-bind:readonly="true" v-model="editRowData.id">
                            <p class=""></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12" v-bind:class="{'has-error': (errors.kbn != '')}">
                            <div>
                                <label class="control-label">区分</label>
                            </div>
                            <el-radio-group v-model="editRowData.kbn" v-bind:disabled="isReadOnly">
                                <div class="radio radio-ex" v-for="calendarKbn in RADIO_LIST.calendarKbn" :key="calendarKbn.value">
                                    <el-radio :label="calendarKbn.value.toString()">{{ calendarKbn.text }}</el-radio>
                                </div>
                            </el-radio-group>
                            <p class="text-danger">{{ errors.kbn }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6" v-bind:class="{'has-error': (errors.holiday_name != '')}">
                            <label class="control-label">休日名</label>
                            <input type="text" class="form-control" v-bind:readonly="isReadOnly" v-model="editRowData.holiday_name">
                            <p class="text-danger">{{ errors.holiday_name }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12" v-bind:class="{'has-error': (errors.repeat_kbn != '')}">
                            <div>
                                <label class="control-label">繰り返し</label>
                            </div>
                            <el-radio-group v-model="editRowData.repeat_kbn" v-bind:disabled="isReadOnly || editRowData.id !== 0">
                                <div class="radio radio-ex" v-for="calendarRepeatKbn in RADIO_LIST.calendarRepeatKbn" :key="calendarRepeatKbn.value">
                                    <el-radio :label="calendarRepeatKbn.value" v-on:change="changeRepeatKbn">{{ calendarRepeatKbn.text }}</el-radio>
                                </div>
                            </el-radio-group>
                            <p class="text-danger">{{ errors.repeat_kbn }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2" v-bind:class="{'has-error': (errors.year != '')}">
                            <label class="control-label">年</label>
                            <input type="number" class="form-control" v-bind:readonly="isReadOnly || inputDisableList.year" v-model="editRowData.year">
                            <p class="text-danger">{{ errors.year }}</p>
                        </div>
                        <div class="col-md-2" v-bind:class="{'has-error': (errors.month != '')}">
                            <label class="control-label">月</label>
                            <input type="number" class="form-control" v-bind:readonly="isReadOnly || inputDisableList.month" v-model="editRowData.month">
                            <p class="text-danger">{{ errors.month }}</p>
                        </div>
                        <div class="col-md-2" v-bind:class="{'has-error': (errors.day != '')}">
                            <label class="control-label">日</label>
                            <input type="number" class="form-control" v-bind:readonly="isReadOnly || inputDisableList.day" v-model="editRowData.day">
                            <p class="text-danger">{{ errors.day }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2" v-bind:class="{'has-error': (errors.week_number != '')}">
                            <label class="control-label">週指定</label>
                            <input type="number" class="form-control" v-bind:readonly="isReadOnly || inputDisableList.week_number" v-model="editRowData.week_number">
                            <p class="text-danger">{{ errors.week_number }}</p>
                        </div>
                        <div class="col-md-10" v-bind:class="{'has-error': (errors.week != '')}">
                            <div>
                                <label class="control-label">曜日</label>
                            </div>
                            <el-radio-group v-model="editRowData.week" v-bind:disabled="isReadOnly || inputDisableList.week">
                                <div class="radio radio-ex" v-for="calendarWeek in RADIO_LIST.calendarWeek" :key="calendarWeek.value">
                                    <el-radio :label="calendarWeek.value">{{ calendarWeek.text }}</el-radio>
                                </div>
                            </el-radio-group>
                            <p class="text-danger">{{ errors.week }}</p>
                        </div>
                    </div>
                        
                    <div class="row btn-area">
                        <div class="col-md-offset-4 col-md-6 col">
                            <button type="button" class="btn btn-info" v-on:click="clear" v-bind:disabled="isReadOnly">クリア</button>
                            <button type="button" class="btn btn-save" v-on:click="save" v-bind:disabled="isReadOnly">登録</button>
                            <button type="button" class="btn btn-danger" v-on:click="calendarDelete" v-bind:disabled="isReadOnly || editRowData.id === 0">削除</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div>
            <div class="col-md-offset-11 col-md-1">
                <button type="button" class="btn bnt-sm btn-back" v-on:click="back">戻る</button>
            </div>
        </div>
    </div>
</template>

<script>

import * as wjNav from '@grapecity/wijmo.nav';
import * as wjCore from '@grapecity/wijmo';
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjMultiRow from '@grapecity/wijmo.grid.multirow';
export default {
    data: () => ({
        loading: false,
        isReadOnly: false,
        isShowEditBtn: false,
        isLocked: false,

        FORMAT_UPDATE_AT: 'YYYY/MM/DD HH:mm',

        INIT_EDIT_ROW_DATA: {
            id: 0,
            kbn: '0',       // 休みの区分
            holiday_name: '',   // 休日名
            year: 0,        // 年
            month: 0,       // 月
            day: 0,         // 日
            week: 0,        // 曜日
            repeat_kbn: 0,  // 繰り返し区分
            week_number: 0  // 週指定
        },

        // 編集中のデータ
        editRowData: {},

        // 項目のdisable制御
        inputDisableList: {
            year: false,
            month: false,
            day: false,
            week: false,
            week_number: false,
        },

        // ラジオボタンの選択肢
        RADIO_LIST: {
            calendarKbn: {},
            calendarRepeatKbn: {},
            calendarWeek: {},
        },

        errors: {
            kbn: '',
            holiday_name: '',
            year: '',
            month: '',
            day: '',
            week: '',
            repeat_kbn: '',
            week_number: ''
        },

        calendarGridCtrl: null,
        filterText: '',
    }),
    props: {
        hasAuth: {},
        isOwnLock: Number,
        lockdata: {},
        lockKey: Number,
        calendarKbn: {},
        calendarRepeatKbn: {},
        calendarWeek: {},
        repeatKbnCtrl: {},
        gridData: Array,
    },
    created() {
        // ロックされているか
      
        // 編集
        if(this.rmUndefinedZero(this.lockdata.id) !== 0){
            if(this.isOwnLock === this.FLG_ON){
                // 自分がロックしている
                this.isShowEditBtn  = false;
                this.isReadOnly     = false;
                this.isLocked       = false;
            }else{
                this.isShowEditBtn  = false;
                this.isReadOnly     = true;
                this.isLocked       = true;
            }
        }else{
            // ロックしていない場合は読み取り専用で編集ボタン表示
            this.isShowEditBtn  = true;
            this.isReadOnly     = true;
        }

        if(!this.hasAuth['edit']){
            // 編集権限がない場合
            this.isShowEditBtn  = false;
            this.isReadOnly     = true;
        }
        
        this.editRowData = Vue.util.extend({}, this.INIT_EDIT_ROW_DATA);
        this.changeRepeatKbn();
    },
    mounted() {
        // 照会モードの場合
        if (this.isReadOnly) {
            window.onbeforeunload = null;
        }

        /* ラジオボタンのフォーマットに変換 */
        this.RADIO_LIST.calendarKbn         = this.toRadioFormat(this.calendarKbn);
        this.RADIO_LIST.calendarRepeatKbn   = this.toRadioFormat(this.calendarRepeatKbn);
        this.RADIO_LIST.calendarWeek        = this.toRadioFormat(this.calendarWeek);

        this.$nextTick(function() {
            for(var i in this.gridData){
                // ラジオボタンのチェックを外す
                this.gridData[i]['rdo'] = false;
            }
            var ctrl = this.createGridCtrl('#calendarGrid', this.gridData);
            this.calendarGridCtrl = ctrl;
        });
    },
    methods: {
        // グリッド生成
        createGridCtrl(targetGridDivId, gridItemSource) {
            var gridCtrl = new wjMultiRow.MultiRow(targetGridDivId, {
                itemsSource: gridItemSource,
                layoutDefinition: this.getGridLayout(),
                showSort: false,
                allowSorting: false,
                keyActionEnter:wjGrid.KeyAction.Cycle,
                keyActionTab:wjGrid.KeyAction.Cycle,
            });

            gridCtrl.headersVisibility = wjGrid.HeadersVisibility.Column;

            gridCtrl.isReadOnly = this.isReadOnly;


            gridCtrl.itemFormatter = function(panel, r, c, cell) {
                // 列ヘッダ中央寄せ
                if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                    cell.style.textAlign = 'center';
                    
                }else if (panel.cellType == wjGrid.CellType.Cell) {
                    var col = gridCtrl.getBindingColumn(panel, r, c);
                    var dataItem = panel.rows[r].dataItem;
                    cell.style.color = '';

                    cell.style.textAlign = 'left';
                    if(cell.classList.contains('text-right')){
                        cell.style.textAlign = 'right';
                    }else if(cell.classList.contains('text-center')){
                        cell.style.textAlign = 'center';
                    }
                    var repeatKbn = this.calendarRepeatKbn.val.default;
                    if(dataItem !== undefined){
                        repeatKbn = dataItem.repeat_kbn;
                    }

                    switch(col.name){
                        case 'rdo':
                            // グリッドのラジオボタン
                            var checkBox = '<input type="radio" name="rdoName">';
                            if(this.isReadOnly){
                                checkBox = '<input type="radio" name="rdoName" disabled="true">';
                            }
                            cell.innerHTML = '<div style="text-align:center">' + checkBox + '</div>';
                            var radio = cell.children[0];                      
                            radio.childNodes['0'].checked = dataItem.rdo;
                            break;
                        case 'kbn':
                            if(dataItem !== undefined){
                                cell.innerHTML = this.calendarKbn.list[dataItem.kbn];
                            }
                            break;
                        case 'repeat_kbn':
                            if(dataItem !== undefined){
                                cell.innerHTML = this.calendarRepeatKbn.list[dataItem.repeat_kbn];
                            }
                            break;
                        case 'week':
                            if(dataItem !== undefined){
                                cell.innerHTML = this.getWeekText(dataItem.week, repeatKbn);
                            }
                            break;
                        case 'year':
                            if(dataItem !== undefined){
                                if(this.rmUndefinedZero(dataItem.year) !== 0){
                                    cell.innerHTML = dataItem.year;
                                }else{
                                    cell.innerHTML = '';
                                }
                            }
                            break;
                        case 'month':
                            if(dataItem !== undefined){
                                if(this.rmUndefinedZero(dataItem.month) !== 0){
                                    cell.innerHTML = dataItem.month;
                                }else{
                                    cell.innerHTML = '';
                                }
                            }
                            break;
                        case 'day':
                            if(dataItem !== undefined){
                                if(this.rmUndefinedZero(dataItem.day) !== 0){
                                    cell.innerHTML = dataItem.day;
                                }else{
                                    cell.innerHTML = '';
                                }
                            }
                            break;
                        case 'week_number':
                            if(dataItem !== undefined){
                                if(this.rmUndefinedZero(dataItem.week_number) !== 0){
                                    cell.innerHTML = dataItem.week_number;
                                }else{
                                    cell.innerHTML = '';
                                }
                            }
                            break;
                        case 'update_at':
                            if(dataItem !== undefined){
                                if(this.rmUndefinedBlank(dataItem.update_at) !== ''){
                                    cell.innerHTML = moment(dataItem.update_at).format(this.FORMAT_UPDATE_AT);
                                }else{
                                    cell.innerHTML = '';
                                }
                            }
                            break;
                            
                    }
                }
            }.bind(this);

            // 変更
            gridCtrl.hostElement.addEventListener('mouseup', e => {
                if (e.target instanceof HTMLInputElement && e.target.type == 'radio' && e.target.name == 'rdoName') {
                    var selectedCellRange = gridCtrl.selection;
                    var col = gridCtrl.getBindingColumn(gridCtrl, selectedCellRange.row, selectedCellRange.col);
                    var dataItem = gridCtrl.rows[selectedCellRange.row].dataItem;
                    switch(col.name){
                        case 'rdo':
                            var isChecked = dataItem.rdo;
                            this.gridRadioAllClear();
                            if(!isChecked){
                                dataItem.rdo = true;
                            }
                            gridCtrl.refresh();
                            // 編集モードに入る
                            this.setEditData(dataItem);
                            break;
                    }
                }
            });
            // グリッドのキーダウン
            gridCtrl.hostElement.addEventListener('keydown', function (e) {
                if (e.keyCode == 32) {
                    // スペース
                    var selectedCellRange = gridCtrl.selection;
                    var col = gridCtrl.getBindingColumn(gridCtrl, selectedCellRange.row, selectedCellRange.col);
                    var dataItem = gridCtrl.rows[selectedCellRange.row].dataItem;
                    switch(col.name){
                        case 'rdo':
                            var isChecked = dataItem.rdo;
                            this.gridRadioAllClear();
                            if(!isChecked){
                                dataItem.rdo = true;
                            }
                            //gridCtrl.refresh();
                            // 編集モードに入る
                            this.setEditData(dataItem);
                            break;
                    }
                }
            }.bind(this));
        
            return gridCtrl;
        },
        /**
         * グリッドのフィルター
         */
        filterGrid(){
            var filter = this.filterText.toLowerCase();
            this.calendarGridCtrl.collectionView.filter = detail => {
                var result = false;
                var weekText = this.getWeekText(detail.week, detail.repeat_kbn);
                // toLowerCaseは文字列が対象の為、NULLの除外と要素の文字キャスト
                result =
                (filter.length == 0) ||
                (detail.id != null && (detail.id).toString().toLowerCase().indexOf(filter) > -1) || 
                (detail.kbn != null && (this.calendarKbn.list[detail.kbn]).toString().toLowerCase().indexOf(filter) > -1) || 
                (detail.holiday_name != null && (detail.holiday_name).toString().toLowerCase().indexOf(filter) > -1) || 
                (detail.repeat_kbn != null && (this.calendarRepeatKbn.list[detail.repeat_kbn]).toString().toLowerCase().indexOf(filter) > -1) || 
                (detail.year != null && this.rmUndefinedZero(detail.year) != 0 && (detail.year).toString().toLowerCase().indexOf(filter) > -1) || 
                (detail.month != null && this.rmUndefinedZero(detail.month) != 0 && (detail.month).toString().toLowerCase().indexOf(filter) > -1) || 
                (detail.day != null && this.rmUndefinedZero(detail.day) != 0 && (detail.day).toString().toLowerCase().indexOf(filter) > -1) || 
                (detail.week_number != null && this.rmUndefinedZero(detail.week_number) != 0 && (detail.week_number).toString().toLowerCase().indexOf(filter) > -1) || 
                (weekText.toLowerCase().indexOf(filter) > -1) 
                return result;
            };
        },
        getGridLayout () {
            return [
                { cells: [{ name: 'id', binding: 'id', header: 'ID', width: 80, isReadOnly: true, cssClass: 'text-right' }] },
                { cells: [{ name: 'rdo', binding: 'rdo', header: ' ', width: 30, isReadOnly: false }] },
                { cells: [{ name: 'kbn', binding: 'kbn', header: '区分', width: 100, isReadOnly: true, cssClass: 'text-center' }] },
                { cells: [{ name: 'holiday_name', binding: 'holiday_name', header: '休日名', width: 250, isReadOnly: true }] },
                { cells: [{ name: 'repeat_kbn', binding: 'repeat_kbn', header: '繰返し', width: 180, isReadOnly: true}] },
                { cells: [{ name: 'year', binding: 'year', header: '年', width: 80, isReadOnly: true, cssClass: 'text-right'}] },
                { cells: [{ name: 'month', binding: 'month', header: '月', width: 80, isReadOnly: true, cssClass: 'text-right' }] },
                { cells: [{ name: 'day', binding: 'day', header: '日', width: 80, isReadOnly: true, cssClass: 'text-right'}] },
                { cells: [{ name: 'week_number', binding: 'week_number', header: '週指定', width: 80, isReadOnly: true, cssClass: 'text-right'}] },
                { cells: [{ name: 'week', binding: 'week', header: '曜日', width: 60, isReadOnly: true, cssClass: 'text-center' }] },
                { cells: [{ name: 'update_at', binding: 'update_at', header: '更新日時', width: 150, isReadOnly: true}] },
            ];
        },
        /**
         * グリッドのラジオボタンを全てクリアする
         * 呼び出し元でgridCtrl.refresh();
         */
        gridRadioAllClear(){
            for(let i in this.calendarGridCtrl.collectionView.sourceCollection){
                this.calendarGridCtrl.collectionView.sourceCollection[i].rdo = false;
            }
        },
        /**
         * 編集するグリッドの行をセットする
         */
        setEditData(gridRow){
            // エラーの初期化
            this.initErr(this.errors);

            this.editRowData = Vue.util.extend({}, this.INIT_EDIT_ROW_DATA);
            if(!gridRow.rdo){
                // 新規
            }else{
                // 編集
                this.editRowData.id         = gridRow.id;
                this.editRowData.kbn        = gridRow.kbn;
                this.editRowData.holiday_name    = gridRow.holiday_name;
                this.editRowData.year       = gridRow.year;
                this.editRowData.month      = gridRow.month;
                this.editRowData.day        = gridRow.day;
                this.editRowData.week       = gridRow.week;
                this.editRowData.repeat_kbn = gridRow.repeat_kbn;
                this.editRowData.week_number= gridRow.week_number;
                
            }
            this.setRepeatKbnDisable(this.editRowData.repeat_kbn);
        },
        /**
         * 繰り返し区分による入力制御を行う
         * @param repeatKbn
         */
        setRepeatKbnDisable(repeatKbn){
            var repeatKbnctrlInfo = this.getRepeatKbnCtrlInfo(repeatKbn);
            for(var inputName in repeatKbnctrlInfo){
                this.inputDisableList[inputName] = repeatKbnctrlInfo[inputName] === this.FLG_OFF;
            }
        },
        /**
         * 繰り返し区分による入力制御情報を返す
         * @param repeatKbn
         */
        getRepeatKbnCtrlInfo(repeatKbn){
            var result = this.repeatKbnCtrl.default;
            switch(repeatKbn){
                case this.calendarRepeatKbn.val.default:
                    // なし
                    result = this.repeatKbnCtrl.default;
                    break;
                case this.calendarRepeatKbn.val.week:
                    // 毎週　◆例：毎週土曜日
                    result = this.repeatKbnCtrl.week;
                    break;
                case this.calendarRepeatKbn.val.month:
                    // 毎月(日) ◆例：毎月10日
                    result = this.repeatKbnCtrl.month;
                    break;
                case this.calendarRepeatKbn.val.month_week_day:
                    // 毎月(曜日) ◆例：毎月 第2土曜日
                    result = this.repeatKbnCtrl.month_week_day;
                    break;
                case this.calendarRepeatKbn.val.year:
                    // 毎年(月日) ◆例：毎年1月1日
                    result = this.repeatKbnCtrl.year;
                    break;
                case this.calendarRepeatKbn.val.year_month_week_day:
                    // 毎年(月/曜日) ◆例：毎年1月の第2の月曜日
                    result = this.repeatKbnCtrl.year_month_week_day;
                    break;
            }
            return result;
        },
        /**
         * 曜日を日本語化する
         * 繰り返し区分に曜日指定がない場合はブランクを返す
         * @param week      曜日番号
         * @param repeatKbn 繰り返し区分
         */
        getWeekText(week, repeatKbn){
            var weekText = '';
            if(this.rmUndefinedZero(week) !== 0){
                weekText = this.calendarWeek.list[week];
            }else{
                var repeatKbnctrlInfo = this.getRepeatKbnCtrlInfo(repeatKbn);
                if(repeatKbnctrlInfo.week){
                    weekText = this.calendarWeek.list[week];
                }
            }
            return weekText;
        },
        /**
         * 繰り返し区分のラジオボタン変更イベント
         */
        changeRepeatKbn(){
            this.setRepeatKbnDisable(this.editRowData.repeat_kbn);
        },

        /**
         * ラジオボタンのフォーマットに変換する
         * @param constList
         */
        toRadioFormat(constList){
            var result = [];
            for(var key in constList.val){
                var val = constList.val[key];
                result.push(
                    {
                        text: constList.list[val],
                        value: val,
                    }
                );
            }
            return result;
        },


        // クリア
        clear(){
            // グリッドの選択クリア
            this.gridRadioAllClear();
            this.calendarGridCtrl.refresh();
            // 編集データクリア
            this.editRowData = Vue.util.extend({}, this.INIT_EDIT_ROW_DATA);
            // 入力制御クリア
            this.setRepeatKbnDisable(this.editRowData.repeat_kbn);
        },
        // 保存
        save() {
            if (!confirm(MSG_CONFIRM_SAVE)) {
                return;
            }
            this.loading = true
            
            // エラーの初期化
            this.initErr(this.errors);
            var params = new URLSearchParams();
            params.append('edit_row_data', JSON.stringify(this.editRowData));

            axios.post('/calendar-edit/save', params)
            .then( function (response) {
                this.loading = false

                if (response.data.status == true) {
                    // 成功
                    window.onbeforeunload = null;
                    location.reload();
                } else {
                    // 失敗
                    if (response.data.message) {
                        var msg = response.data.message;
                        if(typeof msg === 'string'){
                            alert(msg);
                        }else{
                            // validationチェックでエラーになった場合
                            this.showErrMsg(msg, this.errors);
                        }
                    } else {
                        alert(MSG_ERROR);
                    }
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
        // 削除
        calendarDelete(){
            if (!confirm(MSG_CONFIRM_DELETE)) {
                return;
            }
            this.loading = true
            
            // エラーの初期化
            this.initErr(this.errors);
            var params = new URLSearchParams();
            params.append('calendar_id', this.rmUndefinedZero(this.editRowData.id));

            axios.post('/calendar-edit/delete', params)
            .then( function (response) {
                this.loading = false

                if (response.data.status == true) {
                    // 成功
                    window.onbeforeunload = null;
                    location.reload();
                } else {
                    // 失敗
                    if (response.data.message) {
                        alert(response.data.message);
                    } else {
                        alert(MSG_ERROR);
                    }
                }
            }.bind(this))

            .catch(function (error) {
                if (error.response.data.message) {
                    alert(error.response.data.message)
                } else {
                    alert(MSG_ERROR)
                }
                this.loading = false
            }.bind(this))
        },


        // 編集モード
        edit() {
            this.loading = true
            var params = new URLSearchParams();
            params.append('screen', 'calendar-edit');
            params.append('keys', this.lockKey);
            axios.post('/common/lock', params)

            .then( function (response) {
                this.loading = false
                if (response.data.status) {
                    if (response.data.isLocked) {
                        alert(MSG_EDITING);
                        location.reload();
                    } else {
                        window.onbeforeunload = null;
                        location.reload();
                    }
                } else {
                    window.onbeforeunload = null;
                    location.reload()
                }
            }.bind(this))
            .catch(function (error) {
                this.loading = false
                if (error.response.data.message) {
                    alert(error.response.data.message)
                } else {
                    alert(MSG_ERROR)
                }
            }.bind(this))
        },
        // ロック解除
        unlock() {
            if (!confirm(MSG_CONFIRM_UNLOCK)) {
                return;
            }
            this.loading = true
            var params = new URLSearchParams();
            params.append('screen', 'calendar-edit');
            params.append('keys', this.lockKey);
            axios.post('/common/gain-lock', params)

            .then( function (response) {
                this.loading = false
                if (response.data.status) {
                    window.onbeforeunload = null;
                    location.reload();
                } else {
                    // ロック取得失敗
                    window.onbeforeunload = null;
                    location.reload()
                }
            }.bind(this))
            .catch(function (error) {
                this.loading = false
                if (error.response.data.message) {
                    alert(error.response.data.message)
                } else {
                    alert(MSG_ERROR)
                }
            }.bind(this))
        },
        // 戻る
        back() {
            var listUrl = '/calendar-data';
            if (!this.isReadOnly) {
                this.loading = true;

                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'calendar-edit');
                params.append('keys', this.lockKey);
                axios.post('/common/release-lock', params)

                .then( function (response) {
                    this.loading = false
                    if (response.data) {
                        window.onbeforeunload = null;
                        location.href = listUrl;
                    } else {
                        window.onbeforeunload = null;
                        location.reload();
                    }
                }.bind(this))
                .catch(function (error) {
                    this.loading = false
                    if (error.response.data.message) {
                        alert(error.response.data.message)
                    } else {
                        alert(MSG_ERROR)
                    }
                }.bind(this))
            } else {
                window.onbeforeunload = null;
                location.href = listUrl
            }
        },
    }
};
</script>
<style>
.grid-area {
    width: 100%;
    background: #ffffff;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    margin-bottom: 10px;
}
#calendarGrid {
    height: 335px;
}
.main-body {
    width: 100%;
    background: #ffffff;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.radio-ex {
    display: inline-block !important;
}
.btn-area > .col {
    margin-top: 5px;
}
button.btn-back {
    margin-top: 10px;
}
</style>
