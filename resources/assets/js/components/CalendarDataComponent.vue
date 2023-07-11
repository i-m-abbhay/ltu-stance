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
        <div class="main-body col-md-12">
            <div class="form-horizontal save-form">
                <form id="saveForm">
                    <!-- メイン -->
                    <div class="row">
                        <div class="col-md-3">
                            <label class="control-label">年</label>
                            <wj-combo-box
                                class="form-control"
                                :itemsSource="yearList"
                                :is-required="true"
                                :initialized="initWjYear"
                            >
                            </wj-combo-box>
                            <p class="text-danger" style="margin: 0px;" v-show="notDataFlg">登録データがありません</p>
                        </div>
                        <div class="btn-area">
                            <div class="col-md-2 col-btn">
                                <button type="button" class="btn btn-save" v-on:click="getScheduleData">表示</button>
                            </div>
                            <div class="col-md-offset-4 col-md-3 col-btn">
                                <button type="button" class="btn btn-save" v-on:click="calendarEdit" v-bind:disabled="!hasAuth['inquiry']">マスタ編集</button>
                                <button type="button" class="btn btn-danger" v-on:click="calendarDelete" v-bind:disabled="isReadOnly || !isNextYear || notDataFlg">初期化</button>
                                <button type="button" class="btn btn-save" v-on:click="save" v-bind:disabled="isReadOnly || isPreYear">保存</button>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div id="calendar_here">
                                    <div class="dhx_cal_navline">
                                </div>
                            </div> 
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- 休み追加ダイアログ -->
        <el-dialog class="form-horizontal" title="詳細" :visible.sync="dialog.showDlgSchedule" :closeOnClickModal=false>
            <div class="row">
                <div class="col-md-12">
                    <label class="control-label">日付</label>
                    <input type="text" class="form-control" v-model="dialog.calendar_date" v-bind:readonly="true">
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <label class="control-label">営業日区分</label>
                    <div>
                        <el-radio-group v-model="dialog.businessday" v-bind:disabled="(isReadOnly || isCalendarPreDate)">
                            <el-radio :label="businessdayKbn.val.businessday">{{businessdayKbn.list[businessdayKbn.val.businessday]}}</el-radio>
                            <el-radio :label="businessdayKbn.val.holiday">{{businessdayKbn.list[businessdayKbn.val.holiday]}}</el-radio>
                            <el-radio :label="businessdayKbn.val.vacation">{{businessdayKbn.list[businessdayKbn.val.vacation]}}</el-radio>
                        </el-radio-group>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <label class="control-label">休日名</label>
                    <input type="text" class="form-control" maxlength="50" v-model="dialog.comment" v-bind:readonly="isReadOnly || isCalendarPreDate">
                </div>
            </div>
            <span slot="footer" class="dialog-footer">
                <el-button @click="btnCreateSchedule" class="btn-create" v-bind:disabled="isReadOnly || isCalendarPreDate">確定</el-button>
                <el-button @click="btnCloseDlgSchedule" class="btn-cancel">キャンセル</el-button>
            </span>
        </el-dialog>
    </div>
</template>

<script>
export default {
    data: () => ({
        loading: false,
        isReadOnly: false,
        isShowEditBtn: false,
        isLocked: false,
        isPreYear: false,           // 表示しているカレンダーが去年以前か
        isNextYear: false,          // 表示しているカレンダーが来年以降か
        isCalendarPreDate: false,   // 選択した日付が今日以前か

        wjObj: {
            year: null
        },
        // ダイアログ
        dialog: {
            showDlgSchedule: false,
            event_id: 0,
            calendar_id: 0,
            calendar_date: '',
            holiday_flg: 0,
            calendar_week: 0,
            businessday: 1,
            comment: '',
        },
        INIT_SCHEDULE: {
            calendar_id: 0,
            calendar_date: '',
            holiday_flg: 0,
            calendar_week: 0,
            businessday: 1,
            comment: '',

            start_date:'',
            end_date:'',
        },
        notDataFlg: false,
        searchCalendarYear: '',
    }),
    props: {
        hasAuth: {},
        isOwnLock: Number,
        lockdata: {},
        lockKey: Number,
        yearList: Array,
        nowYear: Number,
        businessdayKbn: {},
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
        
    },
    mounted() {

        this.wjObj.year.selectedValue = this.nowYear;
        
        // 照会モードの場合
        if (this.isReadOnly) {
            window.onbeforeunload = null;
        }

        /**************************/
        /****** スケジューラ ******/
        /************************/
        scheduler.config.header = [
            "day",
            "week",
            "month",
            "date",
            "prev",
            "today",
            "next"
        ];
        scheduler.xy.nav_height = 0;
        scheduler.config.dblclick_create = false;
        scheduler.config.start_on_monday = false;
        scheduler.config.readonly        = this.isReadOnly;
        scheduler.init("calendar_here", new Date(), "year");

        /**
         * セルの色
         */
        scheduler.templates.event_class = function(start, end, ev){
            var cssClass = 'bg-businessday';
            switch(ev.businessday){
                case this.businessdayKbn.val.holiday :
                    cssClass = 'bg-holiday';
                break;
                case this.businessdayKbn.val.vacation : 
                    cssClass = 'bg-vacation';
                break;
            }
            return cssClass;
        }.bind(this);

        /**
         * スケジュール表示(変更)
         */
        scheduler.showLightbox = function(eventId){

        }.bind(this);

        /**
         * スケジュール表示(新規)
         */
        scheduler.attachEvent("onEmptyClick", function (date, e){
            if(date !== null){
                var calendarDate = moment(date).format(FORMAT_DATE);
                var evs = scheduler.getEvents(); 
                var event = evs.find((rec) => {
                    return (rec.calendar_date === calendarDate);
                });
                if(event === undefined){
                    this.dialog.event_id        = 0;
                    this.dialog.calendar_id     = 0;
                    this.dialog.calendar_date   = calendarDate;
                    this.holiday_flg            = this.FLG_OFF;
                    this.dialog.calendar_week   = moment(date).day();
                    this.dialog.businessday     = 1;
                    this.dialog.comment         = '';
                }else{
                    // 通らない想定
                    this.setEventToDialog(event);
                }
                // 今日以前か
                this.isCalendarPreDate = this.isPreDate(this.dialog.calendar_date);
                this.dialog.showDlgSchedule = true;
            }
        }.bind(this));

        /**
         * スケジュール表示(編集)
         */
        scheduler.attachEvent("onClick", function (eventId, e){
            var event = scheduler.getEvent(eventId);
            this.setEventToDialog(event);
            // 今日以前か
            this.isCalendarPreDate = this.isPreDate(this.dialog.calendar_date);
            this.dialog.showDlgSchedule = true;
            return true;
        }.bind(this));

        /**
         * ツールチップ
         * 表示するかどうか
         */
        scheduler.attachEvent("onBeforeTooltip", function (eventId){
            var result = false;
            var event = scheduler.getEvent(eventId);
            if(event.comment !== ''){
                result = true;
            }
            return result;
        });
        
        /**
         * ツールチップ
         * 表示する文字列
         */
        dhtmlXTooltip.config.timeout_to_display = 30;
        dhtmlXTooltip.config.timeout_to_hide    = 20;
        scheduler.templates.tooltip_text = function(start,end,event) {
            return event.comment;
        };

        //_/_/_/_/_/_/_/_/_/_/_/_
        //_/ DHX終了
        //_/_/_/_/_/_/_/_/_/_/_/_

        this.getScheduleData();
    },
    methods: {
        setTextChanged: function(sender) {
            this.setAutoCompleteValue(sender);
        },
        initWjYear: function(sender){
            this.wjObj.year = sender;
        },

        /**
         * スケジュール作成
         */
        btnCreateSchedule(){
            if(this.dialog.event_id === 0){
                // 新規
                var row = Vue.util.extend({}, this.INIT_SCHEDULE);
                this.setEvent(row);
                row.calendar_date   = this.dialog.calendar_date;
                row.start_date      = new Date(this.dialog.calendar_date);
                row.end_date        = new Date(this.dialog.calendar_date);
                scheduler.addEvent(
                    row
                );
            }else{
                // 更新
                var row = scheduler.getEvent(this.dialog.event_id);
                this.setEvent(row);
                scheduler.updateEvent(this.dialog.event_id);
            }
            scheduler.render();
            this.dialog.showDlgSchedule = false;
        },
        /**
         * ダイアログの内容を初期化行にセットする
         * @param row
         */
        setEvent(row){
            row.calendar_id     = this.dialog.calendar_id
            row.holiday_flg     = this.dialog.holiday_flg;
            row.calendar_week   = this.dialog.calendar_week;  
            row.businessday     = this.dialog.businessday;
            row.comment         = this.dialog.comment;
        },
        /**
         * 編集の場合にイベントをダイアログにセットする
         * @param event
         */
        setEventToDialog(event){
            this.dialog.event_id        = event.id;
            this.dialog.calendar_id     = event.calendar_id;
            this.dialog.calendar_date   = event.calendar_date;
            this.dialog.holiday_flg     = event.holiday_flg;
            this.dialog.calendar_week   = event.calendar_week;
            this.dialog.businessday     = event.businessday;
            this.dialog.comment         = event.comment;
        },

        /**
         * 閉じる
         */
        btnCloseDlgSchedule(){
            this.dialog.showDlgSchedule = false;
        },

        /**
         * 引数の日付が今日以前か
         */
        isPreDate(calendarDate){
            var result = true;
            var toDay = this.strToTime(moment(new Date()).format(FORMAT_DATE));
            calendarDate = this.strToTime(calendarDate);
            if(toDay < calendarDate){
                result = false;
            }
            return result;
        },

        /**
         * カレンダーデータを取得する
         */
        getScheduleData(){
            this.loading = true
            this.notDataFlg = false;
            this.searchCalendarYear = '';
            
            var selectYear = this.wjObj.year.selectedValue;
            var params = new URLSearchParams();
            params.append('calendar_year', selectYear);

            axios.post('/calendar-data/get-schedule-data', params)
            .then( function (response) {
                this.loading = false
                if (response.data.status == true) {
                    // 成功
                    this.searchCalendarYear = selectYear;
                    var calendarData = response.data['calendar'];

                    var parseCalendarData = [];
                    scheduler.clearAll();

                    for(var i=0; i<calendarData.length; i++){
                        var row = calendarData[i];
                        var initRow = Vue.util.extend({}, this.INIT_SCHEDULE);

                        initRow.calendar_id     = row.id
                        initRow.calendar_date   = row.calendar_date;
                        initRow.holiday_flg     = row.holiday_flg;
                        initRow.calendar_week   = row.calendar_week;
                        initRow.businessday     = row.businessday;
                        initRow.comment         = row.comment;
                        initRow.start_date      = initRow.calendar_date;
                        initRow.end_date        = initRow.calendar_date;
                        parseCalendarData.push(initRow);
                    }
                    // データが登録されていない場合
                    this.notDataFlg = response.data.not_data_flg;

                    scheduler.parse(parseCalendarData,"json");
                    // 指定年の1月1日をセットする
                    scheduler.setCurrentView(new Date(selectYear,1,1));
                    // 過去の年を選択している場合は保存ボタンは押しちゃだめ
                    if(this.nowYear <= selectYear){
                        this.isPreYear = false;
                    }else{
                        this.isPreYear = true;
                    }
                    // 未来の年を選択している場合は初期化ボタンが有効
                    if(this.nowYear < selectYear){
                        this.isNextYear = true;
                    }else{
                        this.isNextYear = false;
                    }
                    

                } else {
                    alert(MSG_ERROR);
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


        /**
         * カレンダー保存
         */
        save() {
            if (!confirm(MSG_CONFIRM_SAVE)) {
                return;
            }
            this.loading = true
            
            var events = scheduler.getEvents();
            var params = new URLSearchParams();
            params.append('calendar_year',      this.searchCalendarYear);
            params.append('calendar_data_list', JSON.stringify(events));

            axios.post('/calendar-data/save', params)
            .then( function (response) {
                this.loading = false

                if (response.data.status == true) {
                    // 成功
                    window.onbeforeunload = null;
                    location.reload();
                } else {
                    // 失敗
                    if (response.data.message) {
                        alert(response.data.message)
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
                // location.reload()
                this.loading = false
            }.bind(this))
        },
        
        /**
         * 削除
         */
        calendarDelete(){
            if (!confirm(MSG_CONFIRM_INITIALIZE)) {
                return;
            }
            this.loading = true
            
            var params = new URLSearchParams();
            params.append('calendar_year',  this.searchCalendarYear);

            axios.post('/calendar-data/delete', params)
            .then( function (response) {
                this.loading = false

                if (response.data.status == true) {
                    // 成功
                    window.onbeforeunload = null;
                    location.reload();
                } else {
                    // 失敗
                    if (response.data.message) {
                        alert(response.data.message)
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

        /**
         * カレンダーマスタ編集画面に遷移
         */
        calendarEdit(){
            // カレンダーマスタ編集画面に遷移 TODO：ロック開放は必要？
            location.href = '/calendar-edit';
        },
        // 編集モード
        edit() {
            this.loading = true
            var params = new URLSearchParams();
            params.append('screen', 'calendar-data');
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
            params.append('screen', 'calendar-data');
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
    }
};
</script>
<style>
.main-body {
    width: 100%;
    background: #ffffff;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.btn-area {
    margin-top:20px;
}
.btn-area > .col-btn{
     margin-top:9px;
}
#calendar_here {
    margin-top: 15px;
    height: 900px;
}
.bg-businessday {
    background: transparent !important;
    color: rgba(0,0,0,.54) !important;
}
.bg-holiday {
    background: tomato !important;
}
.bg-vacation {
    background: wheat !important;
}
.dhx_year_tooltip {
    display: none !important;
}
.dhx_year_body > table tbody > tr > .dhx_before, .dhx_year_body > table tbody > tr > .dhx_after {
    visibility: hidden !important;
}
</style>
