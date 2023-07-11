<template>
    <div>
        <loading-component :loading="loading" />
        <div class="form-horizontal">
            <!-- 排他ボタン -->
            <div class="row">
                <div class="col-md-12 text-right">
                    <label class="form-control-static" v-show="(rmUndefinedBlank(pLockData.id) != '')">ロック日時：{{ pLockData.lock_dt|datetime_format }}&emsp;</label>
                    <label class="form-control-static" v-show="(rmUndefinedBlank(pLockData.id) != '')">ロック者：{{ pLockData.lock_user_name }}&emsp;</label>
                    <button type="button" class="btn btn-danger pull-right btn-unlock" v-on:click="unlock" v-show="isLocked" v-bind:disabled="mainData.complete_flg == FLG_ON">ロック解除</button>
                    <button type="button" class="btn btn-primary pull-right btn-edit" v-on:click="edit" v-show="isShowEditBtn" v-bind:disabled="mainData.complete_flg == FLG_ON">編集</button>
                    <div class="pull-right">
                        <p class="btn btn-default btn-editing" v-on:click="releaseLock()" v-show="(!isLocked && !isShowEditBtn)">編集中</p>
                    </div>
                </div>
            </div>
            <div class="save-form">
                <!-- 案件情報, 週間カレンダー -->
                <div class="container-fluid matter-detail-content-top">
                    <div class="row">
                        <!-- 案件情報 -->
                        <div class="col-md-6">
                            <div>案件情報</div>
                            <div class="col-md-12 matter-info-area">
                                <!-- 案件情報 左半分 -->
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label class="control-label">案件</label>
                                            <input type="text" class="form-control" v-model="mainData.matter_name" v-bind:readonly="true" >
                                        </div>
                                        <div class="col-md-12">
                                            <label class="control-label">得意先</label>
                                            <input type="text" class="form-control" v-model="mainData.customer_name" v-bind:readonly="true" >
                                        </div>
                                        <div class="col-md-6">
                                            <label class="control-label">案件番号</label>
                                            <input type="text" class="form-control" v-model="mainData.matter_no" v-bind:readonly="true" >
                                        </div>
                                        <div class="col-md-6">
                                            <label class="control-label">見積番号</label>
                                            <input type="text" class="form-control" v-model="mainData.quote_no" v-bind:readonly="true" >
                                        </div>
                                        <div class="col-md-6">
                                            <label class="control-label">担当部門</label>
                                            <input type="text" class="form-control" v-model="mainData.department_name" v-bind:readonly="true" >
                                        </div>
                                        <div class="col-md-6">
                                            <label class="control-label">担当者</label>
                                            <input type="text" class="form-control" v-model="mainData.staff_name" v-bind:readonly="true" >
                                        </div>
                                        <div class="col-md-12">
                                            <label class="control-label">郵便番号</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" v-model="mainData.zipcode" v-bind:readonly="true" >
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-save form-control-static" v-on:click="inputMatterAddress">住所登録</button>
                                                </span>
                                                <span class="input-group-btn">
                                                    <el-button icon="el-icon-refresh" circle class="input-group-circle-btn" v-on:click="reloadAddress()"></el-button>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="control-label">住所1</label>
                                            <input type="text" class="form-control" v-model="mainData.address1" v-bind:readonly="true" >
                                        </div>
                                        <div class="col-md-12">
                                            <label class="control-label">住所2</label>
                                            <input type="text" class="form-control" v-model="mainData.address2" v-bind:readonly="true" >
                                        </div>   
                                    </div>
                                </div>
                                <!-- 案件情報 右半分 -->
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label class="control-label">明細（見積）</label>
                                            <input style="display:none;" type="file" multiple="multiple" id="file-up" v-bind:readonly="true" >
                                            <div class="file-operation-area well-sm" @dragleave.prevent @dragover.prevent @drop.prevent="onDrop">
                                                <div v-for="(item, iCnt) in pQuoteVerFileList" :key="iCnt">
                                                    <div class="form-group">
                                                        <div class="col-md-12">
                                                            <label class="file-row col-md-10 col-sm-8 col-xs-8">{{ item.file_name }}</label>
                                                            <el-button type="success" icon="el-icon-download" circle size="mini" @click="downloadFile(DOWNLOAD_KBN.QUOTE, item.id, item.file_name)"></el-button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="control-label">明細（発注）</label>
                                            <input style="display:none;" type="file" multiple="multiple" id="file-up" v-bind:readonly="true" >
                                            <div class="file-operation-area well-sm" @dragleave.prevent @dragover.prevent @drop.prevent="onDrop">
                                                <div v-for="(item, iCnt) in pOrderFileList" :key="iCnt">
                                                    <div class="form-group">
                                                        <div class="col-md-12">
                                                            <label class="file-row col-md-10 col-sm-8 col-xs-8">{{ item.file_name }}</label>
                                                            <el-button type="success" icon="el-icon-download" circle size="mini" @click="downloadFile(DOWNLOAD_KBN.ORDER, item.id, item.file_name)"></el-button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- 週間カレンダー -->
                        <div class="col-md-6">
                            <div>週間カレンダー</div>
                            <div class="scheduler-area col-md-12" v-loading="loadingScheduler">
                                <div id="scheduler_here" class="dhx_cal_container">
                                    <div class="dhx_cal_navline"></div>
                                    <div class="dhx_cal_header"></div>
                                    <div class="dhx_cal_data"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <br>
                <!-- 工程表 -->
                <div class="container-fluid matter-detail-content-bottom">
                    <div class="row">
                        <!-- 工程表 -->
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-1">工程表</div>
                            </div>
                            
                            <div class="col-md-12 gantt-chart-area" v-loading="loadingGantt">
                                <div class="row ps-gantt-menu-bar-1">
                                    <div class="col-md-2" v-bind:class="{'has-error': (errors.construction_date != '')}">
                                        <label>着工日</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" v-model="mainData.construction_date" :readonly="true">
                                            <span class="input-group-btn">
                                                <button type="button" class="btn btn-default" v-bind:disabled="isReadOnly" @click="showGanttConstructionDateDlg(true)">入力</button>
                                            </span>
                                        </div>
                                        <p class="text-danger">{{ errors.construction_date }}</p>
                                    </div>
                                    <div class="col-md-2" v-bind:class="{'has-error': (errors.ridgepole_raising_date != '')}">
                                        <label>上棟日</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" v-model="mainData.ridgepole_raising_date" :readonly="true">
                                            <span class="input-group-btn">
                                                <button type="button" class="btn btn-default" v-bind:disabled="isReadOnly" @click="showGanttRidgepoleRaisingDateDlg(true)">入力</button>
                                            </span>
                                        </div>
                                        <p class="text-danger">{{ errors.ridgepole_raising_date }}</p>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="col-md-12">&nbsp;</label>
                                        <button type="button" class="btn btn-save" v-bind:disabled="isReadOnly" @click="saveCustomerStandard">得意先標準登録</button>
                                        <button type="button" class="btn btn-save" v-bind:disabled="isReadOnly || (!mainData.construction_date && !mainData.ridgepole_raising_date)" @click="showCallChartDlg(true)">チャート呼出</button>
                                        <button type="button" class="btn btn-save" v-bind:disabled="isReadOnly || (!mainData.construction_date && !mainData.ridgepole_raising_date)" @click="initGantt()">初期化</button>
                                    </div>
                                    <div class="col-md-3 text-right">
                                        <label class="col-md-12">&nbsp;</label>
                                        <button type="button" class="btn btn-save" v-bind:disabled="isReadOnly" @click="save()">保存</button>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="control-label">&emsp;</label>
                                        <div id="gantt_here"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <!-- ボタン -->
                <div class="col-md-12 text-right">
                    <button type="button" class="btn btn-delete" v-bind:disabled="isReadOnly" @click="deleteMatter()">削除</button>
                    <button v-show="mainData.complete_flg == FLG_OFF" type="button" class="btn btn-delete" v-bind:disabled="isReadOnly" @click="completeMatter()">案件完了</button>
                    <button v-show="mainData.complete_flg == FLG_ON" type="button" class="btn btn-delete" @click="cancelCompleteMatter()">案件完了解除</button>
                    &emsp;
                    <button type="button" class="btn btn-warning btn-back" @click="back()">戻る</button>
                </div>
            </div>
        </div>
        <!-------------------------------- 
        ------------ ダイアログ -----------
        ---------------------------------->
        <!-- 工程表：着工日入力 -->
        <el-dialog title="着工日を入力" width="20%" :visible.sync="dlgGanttConstructionDate.show"  :showClose="false" :closeOnClickModal="false" :closeOnPressEscape="false">
            <label class="control-label">着工日</label>

            <wj-input-date class="form-control"
                :value="null"
                :isRequired="false"
                :initialized="function(sender){
                    dlgGanttConstructionDate.wjDate = sender;
                }"
            ></wj-input-date>
            <p class="text-danger">{{ dlgGanttConstructionDate.error }}</p>

            <span slot="footer" class="dialog-footer">
                <!-- <el-button class="btn-default" @click="calculateGantt">計算</el-button> -->
                <el-button class="btn-default" @click="setGanttConstructionDate()">計算</el-button>
                <el-button class="btn-default" @click="showGanttConstructionDateDlg(false)">キャンセル</el-button>
            </span>
        </el-dialog>
        <!-- 工程表：上棟日入力 -->
        <el-dialog title="上棟日を入力" width="20%" :visible.sync="dlgGanttRidgepoleRaisingDate.show"  :showClose="false" :closeOnClickModal="false" :closeOnPressEscape="false">
            <label class="control-label">上棟日</label>
            <wj-input-date class="form-control"
                :value="null"
                :isRequired="false"
                :initialized="function(sender){
                    dlgGanttRidgepoleRaisingDate.wjDate = sender;
                }"
            ></wj-input-date>
            <p class="text-danger">{{ dlgGanttRidgepoleRaisingDate.error }}</p>

            <span slot="footer" class="dialog-footer">
                <!-- <el-button class="btn-default" @click="calculateGantt">計算</el-button> -->
                <el-button class="btn-default" @click="setGanttRidgepoleRaisingDate()">計算</el-button>
                <el-button class="btn-default" @click="showGanttRidgepoleRaisingDateDlg(false)">キャンセル</el-button>
            </span>
        </el-dialog>
        <!-- 工程表：チャート呼出 -->
        <el-dialog width="40%" title="チャート呼出" :visible.sync="dlgCallChart.show" :showClose="false" :closeOnClickModal="false" :closeOnPressEscape="false">
            <el-radio-group v-model="dlgCallChart.kbnValue">
                <el-radio class="form-group col-md-10" :label="dlgCallChart.KBN.CUSTOMER_STANDARD">
                    <label @click="dlgCallChart.kbnValue=dlgCallChart.KBN.CUSTOMER_STANDARD">得意先標準</label>
                </el-radio>
                <el-radio class="form-group col-md-10" :label="dlgCallChart.KBN.MATTER">
                    <label @click="dlgCallChart.kbnValue=dlgCallChart.KBN.MATTER">案件</label>&emsp;
                    <wj-auto-complete class="form-control"
                        search-member-path="matter_name" 
                        display-member-path="matter_name"
                        selected-value-path="id" 
                        :selected-index="-1"
                        :items-source="pMatterComboData" 
                        :is-required="false"
                        :initialized="function(sender){
                            dlgCallChart.wjMatterCombo = sender;
                        }"
                        :max-items="pMatterComboData.length"
                    >
                    </wj-auto-complete>
                </el-radio>
            </el-radio-group>
            <p class="text-danger">{{ dlgCallChart.error }}</p>
            <br>
            <div class="text-right">
                <el-button class="btn btn-save" @click="callChart()">呼出</el-button>
                <el-button class="btn btn-default" @click="dlgCallChart.show=false">キャンセル</el-button>
            </div>
            <!-- <p class="text-danger">{{  }}</p> -->
        </el-dialog>
        <!-- 工程表：雨延期 -->
        <el-dialog width="30%" title="雨延期" :visible.sync="dlgRain.show" :showClose="false" :closeOnClickModal="false" :closeOnPressEscape="false">
            <div class="form-horizontal">
                <div class="form-group">
                    <div class="col-md-6">
                        <wj-input-date class="form-control"
                            :value="null"
                            :isRequired="false"
                            :initialized="function(sender){
                                dlgRain.wjRainDate = sender;
                            }"
                        ></wj-input-date>
                    </div>
                    <div class="col-md-5">
                        <el-button class="btn btn-save" @click="addRainDateList()">追加</el-button>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-6">
                        <wj-list-box
                            class="rain-list-box"
                            displayMemberPath="rain_date"
                            :itemsSource="JSON.parse(JSON.stringify(matterRainData))"
                            :initialized="function(sender){
                                dlgRain.wjRainDateList = sender;
                            }"
                        ></wj-list-box>
                    </div>
                    <div class="col-md-5">
                        <el-button class="btn btn-delete" @click="removeRainDateList()">削除</el-button>
                    </div>
                </div>
                <br>
                <div class="text-right">
                    <el-button class="btn btn-save" @click="applyRainDateList">適用</el-button>
                    <el-button class="btn btn-default" @click="showRainDlg(false)">キャンセル</el-button>
                </div>
            </div>
        </el-dialog>
        <!-- 受注確定商品一括移動ダイアログ -->
        <el-dialog width="50%" title="商品移動" :visible.sync="dlgGanttReceivedTaskMoveDlg.show" :showClose="false" :closeOnClickModal="false" :closeOnPressEscape="false">
            <div class="form-horizontal">
                <div class="form-group">
                    <div class="col-md-3">
                        <label>工事区分</label>
                        <div>&nbsp;{{ dlgGanttReceivedTaskMoveDlg.constructionName }}</div>
                    </div>
                    <div class="col-md-4">
                        <label>工程</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="text"
                            display-member-path="text"
                            selected-value-path="id"
                            :is-required="false"
                            :initialized="function(sender){
                                dlgGanttReceivedTaskMoveDlg.wjFilterProcessCombo = sender;
                            }"
                            :textChanged="function(s, e){ filterGanttReceivedTask(); }"
                        >
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-4">
                        <label>階層</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="text"
                            display-member-path="text"
                            selected-value-path="text"
                            :is-required="false"
                            :initialized="function(sender){
                                dlgGanttReceivedTaskMoveDlg.wjFilterLayerCombo = sender;
                            }"
                            :textChanged="function(s, e){ filterGanttReceivedTask(); }"
                        >
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-12">
                        <div class="dialog-grid" id="productMoveGrid"></div>
                        <p class="text-danger">{{ dlgGanttReceivedTaskMoveDlg.errors.grid }}</p>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-5">
                        <label>移動先工程</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="text"
                            display-member-path="text"
                            selected-value-path="id"
                            :is-required="false"
                            :initialized="function(sender){
                                dlgGanttReceivedTaskMoveDlg.wjMoveToProcessCombo = sender;
                            }"
                        >
                        </wj-auto-complete>
                        <p class="text-danger">{{ dlgGanttReceivedTaskMoveDlg.errors.move_to_process }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <el-button class="btn btn-save" @click="moveGanttReceivedTask()">移動</el-button>
                    <el-button class="btn btn-default" @click="showGanttReceivedTaskMoveDlg(false)">キャンセル</el-button>
                </div>
            </div>
        </el-dialog>
        <!-- 工程表：タスク追加 -->
        <el-dialog id="task-form" width="40%" :visible.sync="dlgGanttTaskBox.show" :showClose="false" :closeOnClickModal="false" :closeOnPressEscape="false">
            <div class="form-horizontal">
                <div class="form-group">
                    <div class="col-md-12"><label class="control-label">工事区分名称</label></div>
                    <div class="col-md-12"><label>{{ dlgGanttTaskBox.content.construction_name }}</label></div>
                </div>
                <div class="form-group">
                    <div class="col-md-12"><label class="control-label">工程名称</label></div>
                    <div class="col-md-12"><input type="text" class="form-control" v-model="dlgGanttTaskBox.content.task_name">
                        <p class="text-danger">{{ dlgGanttTaskBox.errors.task_name }}</p>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-5">
                        <label>開始日</label>
                        <div class="input-group">
                            <wj-input-date class="form-control"
                                :value="null"
                                :isRequired="false"
                                :itemsSource="[]"
                                :initialized="function(sender){
                                    dlgGanttTaskBox.content.wj_start_date = sender;
                                }"
                            ></wj-input-date>
                        </div>
                        <p class="text-danger">{{ dlgGanttTaskBox.errors.start_date }}</p>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-4">
                        <label class="control-label">工期日数</label>
                        <div class="input-group">
                            <input type="text" class="form-control" v-model="dlgGanttTaskBox.content.duration">
                            <span class="input-group-addon lbl-addon-ex">日間</span>
                        </div>
                        <p class="text-danger">{{ dlgGanttTaskBox.errors.duration }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="control-label">&nbsp;</label>
                        <div>
                            <el-checkbox v-model="dlgGanttTaskBox.content.date_calc_flg" :true-label="FLG_ON" :false-label="FLG_OFF">営業日のみ</el-checkbox>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-4">
                        <label class="control-label">標準発注タイミング</label>
                        <div class="input-group">
                            <input type="text" class="form-control" v-model="dlgGanttTaskBox.content.order_timing">
                            <span class="input-group-addon lbl-addon-ex">日前</span>
                        </div>
                        <p class="text-danger">{{ dlgGanttTaskBox.errors.order_timing }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="control-label">&nbsp;</label>
                        <div>
                            <el-checkbox v-model="dlgGanttTaskBox.content.rain_flg" :true-label="FLG_ON" :false-label="FLG_OFF">雨延期</el-checkbox>
                        </div>
                    </div>
                </div>
                <br>
                <div class="text-right">
                    <el-button v-if="!dlgGanttTaskBox.isNew" class="btn btn-delete" @click="deleteGanttTask(dlgGanttTaskBox.taskId)">削除</el-button>
                    <el-button v-if="dlgGanttTaskBox.isNew" class="btn btn-save" @click="saveGanttTask(dlgGanttTaskBox.taskId)">追加</el-button>
                    <el-button v-else class="btn btn-save" @click="saveGanttTask(dlgGanttTaskBox.taskId)">変更</el-button>
                    <el-button class="btn btn-default" @click="showGanttTaskDlg(false)">キャンセル</el-button>
                </div>
            </div>
        </el-dialog>
    </div>
</template>

<style>
/*********************************
    枠サイズ等
**********************************/
.save-form{
    width: 100%;
    background: #ffffff;
    padding-top: 10px;
    margin-top: 5px;
    margin-bottom: 15px;

    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.matter-info-area, .scheduler-area {
    padding-right: 0px;
    padding-left: 0px;
    padding-top: 10px;
    padding-bottom: 10px;
    background-color:white;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
#scheduler_here{
    height: 460px;
}
.gantt-chart-area {
    width: 100%;
    background: #ffffff;
    padding: 15px;
    margin-bottom: 20px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
#gantt_here{
    height: 750px;
}
.rain-list-box{
    width: 100%;
    min-height: 150px;
    max-height: 150px;
}
.dialog-grid{
    background: #ffffff;
    height: 300px;
    margin-top: 20px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
/*********************************
    案件情報
**********************************/
/* 住所表示更新ボタン */
.input-group-circle-btn{
    font-size: 26px;
    padding: 4px !important;
    border: none;
}
/*********************************
    スケジューラ
    ※『ex-cal-～』ユーザ定義CSS
**********************************/
/* ヘッダ（from~to） */
.dhx_cal_date{
    font-size: 25px !important;
}
/* イベント日時 */
.dhx_event_move.dhx_title{
    display: none;
}
/* イベント */
.dhx_body{
    height: 100% !important;
    padding: 0 !important;
    background-color: white !important;
}
/* リサイズを行う部分を非表示 */
.dhx_event_resize.dhx_footer{
    display: none;
}
.ex-cal-event > .icon {
    float: left;
    margin-right: 2px;
    height: 100%;
    width: 50%;
    background-color: transparent;
}
/* アイコンの色変更-完了（アイコン） */
.ex-cal-event > .icon > svg.finished{
    color: mediumseagreen;
}
/* アイコンの色変更-未完了（アイコン） */
.ex-cal-event > .icon > svg.unfinished{
    color: gold;
}
/* アイコンの色変更-超過（アイコン） */
.ex-cal-event > .icon > svg.exceed{
    color: red;
}
.ex-cal-event > .text {
    color: black;
    padding-top: 2px;
}
.dhx_scale_holder, .dhx_scale_holder_now {
    background-image: none;
}
/*********************************
    工程表内
    ※『ps-gantt-～』
**********************************/

/*********************************
    工程表内-ガントチャート
    ※『ex-gantt-～』ユーザ定義CSS
**********************************/
/* グリッドヘッダの＋ボタン削除 */
.gantt_grid_head_add {
    display: none;
}
/* フォルダとファイルのアイコンを削除 */
.gantt_tree_icon.gantt_folder_open,
.gantt_tree_icon.gantt_folder_closed,
.gantt_tree_icon.gantt_file {
    display: none !important;
}
/* タスクの枠線を非表示 */
.gantt_task_line {
    border: none !important;
}
/* レイアウト拡張部分 */
.ex-gantt-layout-cell{
    border: none;
}
/* レイアウト拡張部分のbutton要素 */
.ex-gantt-layout-cell button{
    outline: none;
    border:none;
    background-color:transparent
}
/* 休日の背景色を変更 */
.gantt_scale_cell.ex-gantt-holiday{
    background-color: #ffe5e5;
}
/* 雨天日の背景色を変更 */
.gantt_scale_cell.ex-rain{
    background-color: #e5e5ff;
}
/* 会社休日の文字色を変更 */
.gantt_scale_cell.ex-company-holiday{
    color: red !important;
}
/* グリッド（工事区分） */
.ex-gantt-row-construction {
    background-color: #E0FFFF !important;
}
/* グリッド（工程） */
.ex-gantt-row-process {
    background-color: #FFFFE0 !important;
}
/* グリッド（受注確定商品） */
.ex-gantt-row-received {
    background-color: #e0ffe0 !important;
}
/* タスクの（作業） */
/* .ex-gantt-row-work {
} */

/* グリッド行の+ボタン削除 */
.ex-gantt-row-milestone-parent .gantt_add,
.ex-gantt-row-milestone-construction-date .gantt_add,
.ex-gantt-row-milestone-ridgepole-raising-date .gantt_add,
.ex-gantt-row-process .gantt_add,
.ex-gantt-row-received .gantt_add,
.ex-gantt-row-work .gantt_add {
    display: none;
}

/* タスクの背景色変更（工事区分） */
.ex-gantt-bar-construction {
    background-color: #1e90ff !important;
}
/* タスクの背景色変更（工程） */
.ex-gantt-bar-process {
    background-color: #ffa500 !important;
}
/* タスクの背景色変更（受注確定商品） */
.ex-gantt-bar-received {
    background-color: #32cd32 !important;
}
/* splitタスクを閉じている際に背景色を透過させる（マイルストーン親, 受注確定商品） */
.gantt_split_parent.ex-gantt-bar-milestone-parent, .gantt_split_parent.ex-gantt-bar-received{
    background-color: transparent !important;
}
/* アイコン（左） */
.ex-gantt-icon-bar > .left-icon {
   text-align: left;
   float: left;
}
/* アイコン（右） */
.ex-gantt-icon-bar > .right-icon {
   text-align: right;
}
/* アイコン */
.ex-gantt-icon-bar > .icon > svg,
.ex-gantt-icon-bar > .left-icon > svg,
.ex-gantt-icon-bar > .right-icon > svg
{
    height: 100%;
}
/* アイコンの色変更-完了（アイコン） */
.ex-gantt-icon-bar > .icon > svg.finished,
.ex-gantt-icon-bar > .left-icon > svg.finished,
.ex-gantt-icon-bar > .right-icon > svg.finished
{
    color: mediumseagreen;
}
/* アイコンの色変更-未完了（アイコン） */
.ex-gantt-icon-bar > .icon > svg.unfinished,
.ex-gantt-icon-bar > .left-icon > svg.unfinished,
.ex-gantt-icon-bar > .right-icon > svg.unfinished
{
    color: gold;
}
/* アイコンの色変更-超過（アイコン） */
.ex-gantt-icon-bar > .icon > svg.exceed,
.ex-gantt-icon-bar > .left-icon > svg.exceed,
.ex-gantt-icon-bar > .right-icon > svg.exceed
{
    color: red;
}
/* 拡張レイアウトのモードボタン色変更 */
.ex-gantt-layout-cell .ex-gantt-mode-on{
    color: red;
}
/* マイルストーンのリンク色変更 */
.gantt_task_link.ex-gantt-milestone-link .gantt_line_wrapper div{
   background-color:#D33dAF;
}
/* 
 リンクアクティブで無い場合はリンクポイント非表示（=全画面表示じゃ無い時）
 工事区分、受注確定、作業タスクのリンクポイントを非表示
 マイルストーンタスクの左側リンクポイントを非表示
*/
.gantt_bar_milestone:not(.ex-gantt-link-activate) > .gantt_link_control > .gantt_link_point,
.gantt_bar_task:not(.ex-gantt-link-activate) > .gantt_link_control > .gantt_link_point,
.ex-gantt-bar-milestone-parent > .gantt_link_control > .gantt_link_point,
.ex-gantt-bar-construction > .gantt_link_control > .gantt_link_point,
.ex-gantt-bar-received > .gantt_link_control > .gantt_link_point,
.ex-gantt-bar-work > .gantt_link_control > .gantt_link_point,
.ex-gantt-bar-construction > .gantt_link_control > .gantt_link_point,
.ex-gantt-bar-milestone-construction-date > .gantt_link_control.task_left.task_start_date > .gantt_link_point,
.ex-gantt-bar-milestone-ridgepole-raising-date > .gantt_link_control.task_left.task_start_date > .gantt_link_point
{
    display: none !important;
}
/* タスクを畳んでいる時に完了アイコンを見えなくする */
/* .gantt_split_child svg.finish{
    display: none;
} */
/*********************************
    ダイアログ
**********************************/
#task-form .el-checkbox {
    margin-top: 5px;
}
/*********************************
    添付ファイル
**********************************/
.file-up-icon {
    height: 25px !important;
    width: 25px !important;
    cursor: pointer;
}
.file-up {
    margin-bottom: 0px;
    vertical-align: bottom;
}
.file-operation-area {
    padding: 5px;
    border: 1px solid #ccd0d2;
    height: 200px;
    overflow-x: hidden;
    overflow-y: scroll;
}
.file-row {
    background: #ddd;
    padding: 3px 15px;
    display: inline-block;
    position: relative;
    border-radius: 5%;
    box-shadow: 0 2px 6px rgba(146, 156, 146, 0.8);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
/*********************************
    その他
**********************************/
.lbl-addon-ex {
    border: none;
    background: none;
}

</style>

<script>
/** 
  同名のカラムでも意味が異なるので注意。
  [工期日数]m_class_small.construction_period_days != [工期日数]t_matter_detail.construction_period_days
  [工期日数]m_class_small.construction_period_days == [期間]t_matter_detail.duration
  ※[案件詳細.工期日数]は開始～終了の期間の日数。[案件詳細.期間]は工程に必要な日数。
*/
import * as wjGrid from '@grapecity/wijmo.grid';
export default {
    data: () => ({
        loading: false,
        loadingScheduler: false,
        loadingGantt: false,

        isReadOnly: true,
        isShowEditBtn: false,
        isLocked: false,
        isGanttAutoScheduling: false,

        NO_PROCESS_SETTING: '工程なし',

        errors: {
            construction_date: '',
            ridgepole_raising_date: ''
        }, 

        // 案件情報：ダウンロード区分（見積-発注）
        DOWNLOAD_KBN: {
            QUOTE: 0,
            ORDER: 1,
        },
        // 工程表：着工日, 上棟日
        dlgGanttConstructionDate: {
            show: false,
            wjDate: null,
            error: '',
        },
        dlgGanttRidgepoleRaisingDate: {
            show: false,
            wjDate: null,
            error: '',
        },
        dlgCallChart: {
            KBN: {
                CUSTOMER_STANDARD: 0,
                MATTER: 1,
            },
            show: false,
            kbnValue: 0,
            wjMatterCombo: null,
            error: '',
        },
        // 工程表：雨延期ダイアログ
        dlgRain: {
            show: false,
            wjRainDate: null,
            wjRainDateList: null,
        },
        // 工程表：受注確定タスク所属設定ダイアログ
        dlgGanttReceivedTaskMoveDlg: {
            show: false,
            taskId: null,
            constructionName: null,
            wjFilterProcessCombo: null,
            wjFilterLayerCombo: null,
            wjMoveToProcessCombo: null,
            wjGrid: null,
            errors: {
                grid: '',
                move_to_process: '',
            }
        },
        // 工程表：工程タスク追加編集ダイアログ
        dlgGanttTaskBox: {
            show: false,
            isNew: true,
            taskId: null,
            content: {
                construction_name: null,
                task_name: null,
                wj_start_date: null,
                duration: null,
                order_timing: 0,
                date_calc_flg: 0,
                rain_flg: 0,
            },
            errors: {
                task_name: '',
                start_date: '',
                duration: '',
                order_timing: '',
            }
        },

        // GETパラメータ
        getQuery: '',

        mainData: {},
        matterRainData: {},
        lockData: {},
        ganttHolidayList: [],
        companyHolidayList: [],

        GANTT_TASK_KBN: {
            MILESTONE_PARENT: 0,
            MILESTONE_CONSTRUCTION_DATE: 1,
            MILESTONE_RIDGEPOLE_RAISING_DATE: 2,
            CONSTRUCTION: 3,
            PROCESS: 4,
            RECEIVED: 5,
            WORK: 6,
        },
        ganttTaskProperty: {
            id: null, text: null,
            start_date: null, duration: 0,
            parent: 0, type: gantt.config.types.task,
            render: null,
            calendar_id: null,
            ex_property: null,
        },
        ganttTaskExProperty: {
            server_task_type: null,             // サーバタスクタイプ（NULLのものは保存されない）
            task_kbn: 0,                        // タスク区分
            add_flg: 0,                         // 追加工程フラグ
            construction_id: 0,                 // 工事区分ID
            class_small_id: 0,                  // 小分類ID
            quote_detail_id: 0,                 // 見積明細ID
            date_calc_flg: 0,                   // 営業日のみフラグ
            order_timing: 0,                    // 標準発注タイミング
            rain_flg: 0,                        // 雨延期フラグ
            start_date_calc_days: 0,            // 開始日計算日数
            icon_info: null,                    // アイコン
            s_icon_info: null,                  // アイコン ※始点
            e_icon_info: null,                  // アイコン ※終点
            base_date_type: 0,                  // 基準日種別
            is_date_ambiguous: false,           // 日付曖昧フラグ（trueにしても良いのは作業タスク & サーバタスクタイプがnullでないデータのみ）
        },
        GANTT_INFO: {
            ROW_HEIGHT: 35,
            SCALE_WIDTH: 40,
        },
        GANTT_CALENDAR_KBN: {
            NO_CALENDAR: 'no_calendar',
            GLOBAL: 'global',
            RAIN: 'rain',
            GLOBAL_AND_RAIN: 'global_and_rain',
        }
    }),
    props: {
        pScreenName: String,
        pConst: {},
        pMessage: {},
        pIsOwnLock: Number,
        pLockData: {},
        pMainData: {},
        pMatterRainData: {},
        pMatterComboData: {},
        pParentQuoteLayerNameData: {},
        pConstructionData: {},
        pGanttHolidayList: {},
        pCompanyHolidayList: {},
        pQuoteVerFileList: Array,
        pOrderFileList: Array,
    },
    created: function() {
        this.mainData = this.pMainData;
        this.matterRainData = this.pMatterRainData;
        this.lockData = this.pLockData;
        this.ganttHolidayList = this.pGanttHolidayList;
        this.companyHolidayList = this.pCompanyHolidayList;
        this.ganttTaskProperty.calendar_id = this.GANTT_CALENDAR_KBN.NO_CALENDAR;
        this.ganttTaskProperty.ex_property = this.ganttTaskExProperty;
        this.ganttTaskProperty = JSON.stringify(this.ganttTaskProperty);

        // 編集可
        this.isShowEditBtn = true;
        if (this.mainData.matter_id == null) {
            this.isShowEditBtn = false;
            this.isReadOnly = false;
        }

        // ロック中判定
        if (this.rmUndefinedBlank(this.lockData.id) != '' && this.pIsOwnLock != this.FLG_ON) {
            this.isLocked = true;
            this.isShowEditBtn = false;
            this.isReadOnly = true;
        }
    },
    mounted: function() {
        const today = new Date(moment().format(FORMAT_DATE));

        // GETパラメータ
        this.getQuery = window.location.search;

        if (this.isLocked) {
            // ロック中
            this.isShowEditBtn = false;
            this.isReadOnly = true;
        } else {
            // 編集モードで開くか判定
            var query = window.location.search;
            if (this.pIsOwnLock == this.FLG_ON || this.isEditMode(query, this.isReadOnly, this.isEditable)) {
                this.isReadOnly = false;
                this.isShowEditBtn = false;
            }
        }

        // 照会モードの場合
        if (this.isReadOnly) {
            window.onbeforeunload = null;
        }

        /**************************/
        /****** スケジューラ ******/
        /************************/
        scheduler.setLoadMode("week");
        scheduler.config.date_format = "%Y/%m/%d %H:%i:%s";
        scheduler.config.hour_size_px = 60;

        scheduler.locale.labels.dhx_cal_today_button = '今日';

        scheduler.xy.scale_width = 0;

        scheduler.xy.nav_height = 80;
        scheduler.config.header = {
            rows: [
                {
                    cols: [
                        "spacer",
                        { html:"<i class='el-icon-d-arrow-left'></i>前週", css:"dhx_cal_tab_first", click:function(){
                            var prevWeekDate = moment(scheduler.getState().date).subtract(7, 'd').toDate();
                            scheduler.setCurrentView(prevWeekDate);
                        }},
                        { html:"<i class='el-icon-arrow-left'></i>前日", click:function(){
                            var prevDate = moment(scheduler.getState().date).subtract(1, 'd').toDate();
                            scheduler.setCurrentView(prevDate);
                        }},
                        "today",
                        { html:"翌日<i class='el-icon-arrow-right'></i>", click:function(){
                            var nextDate = moment(scheduler.getState().date).add(1, 'd').toDate();
                            scheduler.setCurrentView(nextDate);
                        }},
                        { html:"翌週<i class='el-icon-d-arrow-right'></i>", css:"dhx_cal_tab_last", click:function(){
                            var nextWeekDate = moment(scheduler.getState().date).add(7, 'd').toDate();
                            scheduler.setCurrentView(nextWeekDate);
                        }},
                    ]
                },
                {
                    cols: [
                        "date",
                    ]
                }
            ]
        };

        // マウスカーソルを高速でエリア外に動かした際、ツールチップが消えないことがある為対応
        var elem = document.getElementById('scheduler_here');
        elem.addEventListener('mouseleave', e => {
            dhtmlXTooltip.hide();
        });

        // 【イベント】スケジューラーの上にカーソルをドラッグして新しいイベントを作成すると発生 ※トリガー判定
        scheduler.attachEvent("onBeforeEventCreated", function (e){
            // スケジューラ上でイベントを作成させない
            return false;
        });

        // 【イベント】ドラッグ/サイズ変更操作を開始すると発生 ※トリガー判定
        scheduler.attachEvent("onBeforeDrag", function (id, mode, e){
            // スケジューラ上でのD&D禁止
            return false;
        });

        // 【イベント】イベントがドラッグアンドドロップによって変更されたが、変更がまだ保存されていない場合に発生 ※トリガー判定
        scheduler.attachEvent("onBeforeEventChanged", function(ev, e, is_new, original){
            // スケジューラ上でイベントを作成させない
            return false;
        });

        // 【イベント】イベントをダブルクリックすると発生 ※トリガー判定
        scheduler.attachEvent("onDblClick", function (id, e){
            // イベントを編集モードに入らせないようにする
            return false;
        })

        // // 【イベント】データ項目のツールチップが表示される前に発生
        // scheduler.attachEvent("onBeforeTooltip", function (id){
        //     var result = true;
        //     return result;
        // });

        // 【テンプレート】DayビューとUnitsビューのヘッダー
        scheduler.templates.day_date = function(date){
            var formatFunc = scheduler.date.date_to_str('%Y年%n月%j日');
            return formatFunc(date);
        };

        // 【テンプレート】ビューのヘッダー
        scheduler.templates.week_date = function(start, end){
            return scheduler.templates.day_date(start)+" &ndash; "+
            scheduler.templates.day_date(scheduler.date.add(end,-1,"day"));
        };

        // 【テンプレート】ビューのサブヘッダー
        scheduler.templates.week_scale_date = function(date){
            var formatFunc = scheduler.date.date_to_str('%n月%j日');
            var elemClass = 'ex-dhx-scale-bar-';
            switch (moment(date).day()) {
                case 0:
                    elemClass += 'sun';
                    break;
                case 1:
                    elemClass += 'mon';
                    break;
                case 2:
                    elemClass += 'tue';
                    break;
                case 3:
                    elemClass += 'wed';
                    break;
                case 4:
                    elemClass += 'thu';
                    break;
                case 5:
                    elemClass += 'fri';
                    break;
                case 6:
                    elemClass += 'sat';
                    break;
                default:
                    break;
            }
            if (today.getTime() == date.getTime()) {
                elemClass = elemClass + ' ' + 'ex-dhx-scale-bar-now'
            }
            this.$nextTick(function() {
                var elem = document.getElementsByClassName('ex-dhx-scale-bar-now');
                if (elem.length > 0) {
                    var parentElem = elem[0].parentNode;
                    parentElem.style.backgroundColor = '#ffcc66';
                }
            });
            return "<div class='" + elemClass + "'>" + formatFunc(date) + "<div>";
        }.bind(this);

        // 【テンプレート】イベントのテキスト
        scheduler.templates.event_text=function(start, end, event){
            return "<div class='ex-cal-event'>\
                        <div class='icon'>\
                            <svg class='" + event.ex_property.status + "' style='width:100%;height:100%;'><use xlink:href='#" + event.ex_property.icon + "'></use></svg>\
                        </div>\
                        <div class='text'>\
                            <div><strong>" + event.ex_property.construction_name + "</strong></div>\
                            <div>" + event.ex_property.process_name + "</div>\
                        </div>\
                    </div>";
        }

        // 【テンプレート】ツールチップのテキストを指定
        scheduler.templates.tooltip_text = function(start, end, event) {
            var htmlText = "<b>種　　別：</b>" + event.ex_property.type_text;
            if (this.rmUndefinedBlank(event.ex_property.construction_name) != '') {
                htmlText += "</br><b>工事区分：</b>" + event.ex_property.construction_name;
            }
            if (this.rmUndefinedBlank(event.ex_property.process_name) != '') {
                htmlText += "<br/><b>工　　程：</b>"+event.ex_property.process_name;
            }
            return htmlText;
        }.bind(this);

        // 週カレンダーの始まりを今日にする
        scheduler.date.week_start = function(date){
            return this.date_part(this.add(date, 0, "day"));
        }
        scheduler.init("scheduler_here");
        this.getSchedulerData();


        /**************************/
        /********* 工程表 *********/
        /*************************/
        /**
         * プラグイン
         */
        gantt.plugins({
            fullscreen: true,       // 全画面表示機能
            auto_scheduling: true,  // 自動スケジューリング
            marker: true,           // マーカー
        });

        /**
         * 言語
         */ 
        gantt.i18n.setLocale("jp");             // 言語対応 ※DHTMLXは日本語部分対応
        gantt.locale.labels.new_task = '';      // 新規タスクのタスク名を空に変更
        gantt.locale.labels.link = 'リンク';
        gantt.locale.labels.confirm_link_deleting = '削除します';

        /**
         * 設定
         */ 
        if (this.isReadOnly) {
            gantt.config.readonly = true;
        }
        // gantt.config.sort = true;    // グリッドカラムヘッダのソート
        gantt.config.drag_progress = false;     // 進捗設定不可
        gantt.config.order_branch = "marker";   // 並び替え可
        gantt.config.order_branch_free = true;  // 並び替え可(D&D)
        gantt.config.open_split_tasks = true;   // 分割タスク
        gantt.config.row_height = this.GANTT_INFO.ROW_HEIGHT;           // 行の高さ
        gantt.config.min_column_width = this.GANTT_INFO.SCALE_WIDTH;     // タイムライン領域の列の最小幅
        gantt.config.columns = [                // グリッド部分のレイアウト
            { name: "text", label: "工程", width: "*", tree: true, template: function(task) {
                // グリッド名横の工事区分アイコン
                var htmlText = task.text;
                if (task.ex_property.task_kbn == this.GANTT_TASK_KBN.CONSTRUCTION || task.ex_property.task_kbn == this.GANTT_TASK_KBN.PROCESS) {
                    var icon = this.pConstructionData[task.ex_property.construction_id].icon;
                    htmlText = 
                        '<div style="display:inline-flex"><div style="padding:2px;width:40px;height:' + this.GANTT_INFO.ROW_HEIGHT + 'px;"><svg style="width:100%;height:100%;color:#696969;">\
                            <use xlink:href="#' + icon + '"></use></svg>\
                        </div>&nbsp;' + task.text + '</div>';
                }
                return htmlText;
            }.bind(this)},
            { name: "start_date", label: "開始予定日", align: "center" },
            { name: "end_date", label: "完了予定日", align: "center", template: function(task){
                // グリッド名横の工事区分アイコン
                var htmlText = task.end_date;
                if (task.ex_property.task_kbn == this.GANTT_TASK_KBN.CONSTRUCTION
                    || task.ex_property.task_kbn == this.GANTT_TASK_KBN.PROCESS
                    || task.ex_property.task_kbn == this.GANTT_TASK_KBN.RECEIVED
                    || task.ex_property.task_kbn == this.GANTT_TASK_KBN.WORK
                ) {
                    htmlText = moment(task.end_date).subtract(1, 'd').startOf('day').toDate();
                }
                return htmlText;
            }.bind(this)},
            { name: "add", label: "", width: 44 }
        ];

        /**
         * 設定（日付）
         */
        // 日付単位（日本の日付標準にする）
        gantt.config.date_format = "%Y/%m/%d";
        gantt.config.work_time = true; // これだけで土日が休日になってるかも
        gantt.config.correct_work_time = true;

        // スケール部分のレイアウト（全て縮小, 全て展開, etc...）　※50分割（0～49）
        var tmpAddItems = {
            // 全て縮小,全て展開
            0: {
                colspan: 5,
                html: '\
                    <div style="line-height:32px;">\
                        <button id="btnCollapseTask" style="" type="button"><span class="glyphicon glyphicon-eject" aria-hidden="true"></span>全て縮小</button>\
                        <button id="btnExpandTask" style="border:none;background-color:transparent" type="button"><span class="glyphicon glyphicon-eject" style="transform: scale(1,-1);" aria-hidden="true"></span>全て展開</button>\
                    </div>\
                ',
            },
            // 今日へ移動
            5: {
                colspan: 3,
                html: '\
                    <div style="line-height:32px;">\
                        <button id="btnMoveToday" type="button"><span class="glyphicon glyphicon-flag" aria-hidden="true"></span>移動(今日)</button>\
                    </div>\
                ',
            },
            // 自動スケジューリング
            8: {
                colspan: 5,
                html: '\
                    <div style="line-height:32px;">\
                        <button id="btnAutoScheduling" type="button"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span>自動スケジュール</button>\
                    </div>\
                ',
            },
            // 雨延期
            13: {
                colspan: 3,
                html: '\
                        <div>\
                            <button id="btnRain" type="button">\
                                <svg style="width:25px;height:25px;"><use xlink:href="#rainIcon2"></use></svg>雨延期\
                            </button>\
                        </div>',
            },
            // 全画面表示
            47: {
                colspan: 3,
                html: '\
                        <div style="line-height:32px;">\
                            <button id="btnDisplaySize" type="button">\
                                <span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>全画面表示\
                            </button>\
                        </div>',
            }
        };

        var addItems = [];
        for (var i=0,skipCnt=0,cntMax=50; i<cntMax; i++) {
            if (skipCnt > 0) {
                skipCnt = (skipCnt - 1 == 0) ? skipCnt=0:skipCnt-1;
                continue;
            }

            var width = 35;
            var html = '<div></div>';
            var item = tmpAddItems[i];
            if (skipCnt==0 && item && item.colspan > 1) {
                skipCnt = (item.colspan-1);
            }
            if (item) {
                html = item.html;
                width = width * item.colspan;
            }
            addItems.push(
                { html: html, css: 'ex-gantt-layout-cell', width: width }
            );
        }
        var customLayouts = {
            height: this.GANTT_INFO.ROW_HEIGHT,
            cols: [],
        }
        for (const key in addItems) {
            const item = addItems[key];
            customLayouts.cols.push({ html: item.html, css: item.css, width: item.width },);
        }
        gantt.config.layout.rows.unshift(customLayouts);
        this.$nextTick(function() {
            // 全て縮小
            const funcBtnCollapseTask = () => { 
                gantt.eachTask(function(task){ task.$open = false; });
                gantt.render();
            };
            var elemBtnCollapseTask = document.getElementById('btnCollapseTask');
            elemBtnCollapseTask.addEventListener('click', funcBtnCollapseTask, false);

            // 全て展開
            const funcBtnExpandTask = () => {
                gantt.eachTask(function(task){ task.$open = true; });
                gantt.render();
            };
            var elemBtnExpandTask = document.getElementById('btnExpandTask');
            elemBtnExpandTask.addEventListener('click', funcBtnExpandTask, false);

            // 移動(今日)
            const funcBtnMoveToday = () => {
                this.moveGanttDate(moment().startOf('day').subtract(1, 'd').toDate());
            };
            var elemBtnMoveToday = document.getElementById('btnMoveToday');
            elemBtnMoveToday.addEventListener('click', funcBtnMoveToday, false);

            // 自動スケジュール
            const funcBtnAutoScheduling = () => {
                if (!this.isReadOnly) {
                    var elem = document.getElementById('btnAutoScheduling');
                    if (this.isGanttAutoScheduling) {
                        this.isGanttAutoScheduling = false;
                        elem.classList.remove('ex-gantt-mode-on');
                    }else{
                        this.isGanttAutoScheduling = true;
                        elem.classList.add('ex-gantt-mode-on');
                    }
                }
            };
            var elemBtnAutoScheduling = document.getElementById('btnAutoScheduling');
            elemBtnAutoScheduling.addEventListener('click', funcBtnAutoScheduling, false);

            // 雨延期
            const funcBtnRain = () => { 
                if (!this.isReadOnly) {
                    this.showRainDlg(true);
                }
            };
            var elemBtnRain = document.getElementById('btnRain');
            elemBtnRain.addEventListener('click', funcBtnRain, false);

            // 全画面表示
            const funcBtnDisplaySize = ()=>{
                if (gantt.getState().fullscreen) {
                    gantt.collapse();
                }else{
                    gantt.expand();
                }
            };
            var elemBtnDisplaySize = document.getElementById('btnDisplaySize');
            elemBtnDisplaySize.addEventListener('click', funcBtnDisplaySize, false);
        }.bind(this));

        // スケール部分のレイアウト　※日月
        gantt.config.scales = [
            {unit: "month", step: 1, format: "%n月"},
            {unit: "day", step: 1, format: "%j日", css: function(date){
                var htmlClass = '';
                if (!gantt.isWorkTime(date)) {
                    htmlClass = 'ex-gantt-holiday';
                    // 会社休業日の場合
                    if (this.companyHolidayList.indexOf(moment(date).format(FORMAT_DATE)) != -1) {
                        htmlClass = 'ex-gantt-holiday ex-company-holiday';
                    }
                }else if(!gantt.getCalendar(this.GANTT_CALENDAR_KBN.RAIN).isWorkTime(date)){
                    return 'ex-rain';
                }
                return htmlClass;
            }.bind(this)}
        ];

        // 【イベント】通常⇒全画面の直前 ※トリガー判定
        gantt.attachEvent("onBeforeExpand",function(){
            this.switchDisplaySize();   // onExpandよりonBeforeExpandのが通常から全画面への遷移が綺麗
            return true;
        }.bind(this));

        // 【イベント】通常⇒全画面
        gantt.attachEvent("onExpand", function (){
            var elem = document.getElementById('btnDisplaySize');
            elem.classList.add('ex-gantt-mode-on');
        }.bind(this));

        // 【イベント】全画面⇒通常
        // ※onBeforeCollapseの方はESCやF11で発火しないので使用する際は注意
        gantt.attachEvent("onCollapse", function (){
            this.switchDisplaySize();
            var elem = document.getElementById('btnDisplaySize');
            elem.classList.remove('ex-gantt-mode-on');
        }.bind(this));

        // 【イベント】タスクが作成された直後
        // テンプレート等で拡張用のプロパティ等を使用している場合はここで設定しておく（でないと落ちる）
        gantt.attachEvent('onTaskCreated', function(task){
            // 初期値設定等
            var parentTask = gantt.getTask(task.parent);
            
            task.ex_property = JSON.parse(JSON.stringify(this.ganttTaskExProperty));  // 拡張プロパティセット
            task.ex_property.server_task_type = this.pConst.SERVER_TASK_TYPE.process;
            task.ex_property.task_kbn = this.GANTT_TASK_KBN.PROCESS;  // タスク区分セット
            task.ex_property.construction_id = this.pConstructionData[parentTask.ex_property.construction_id].id;  // iconセット
            task.ex_property.add_flg = this.FLG_ON;  // 追加工程フラグ
            return true;
        }.bind(this));
        
        // 【イベント】ユーザーがグリッドの行をドラッグする前に発生 ※トリガー判定
        // onBeforeRowDragMoveより速く発火する（onBeforeRowDragMoveではfalseにしても動かす様子が見えるので不十分）
        gantt.attachEvent("onRowDragStart", function(id, target, e) {
            var result = true;

            var task = gantt.getTask(id);

            // 通常画面の場合は禁止
            if (result && !gantt.getState().fullscreen) {
                result = false;
            }

            // 工程と受注確定商品以外はNG
            if (result && !(task.ex_property.task_kbn == this.GANTT_TASK_KBN.PROCESS || task.ex_property.task_kbn == this.GANTT_TASK_KBN.RECEIVED)) {
                result = false;
            }
            return result;
        }.bind(this));

        // 【イベント】グリッド行移動中 ※トリガー判定
        // gantt.attachEvent("onBeforeRowDragMove", function(id, mode, e){
        // }.bind(this));

        // 【イベント】グリッド行移動後 ※トリガー判定
        gantt.attachEvent("onBeforeRowDragEnd", function(id, parent, tindex){
            var result = true;

            var task = gantt.getTask(id);

            if (parent == 0) {
                // 枠外に配置しようとした時にparent=0になる
                // 階層でトップに存在するタスクはparent=0だが、仕様的に動かせないのでparent=0を禁止とする
                result = false;
            }

            // 工程と受注確定商品以外の場合はNG
            // ※『onBeforeRowDragMove』で潰しているので見た目上は動かないが、ドラッグ終了時に当イベントが発生するため
            if (result && !(task.ex_property.task_kbn == this.GANTT_TASK_KBN.PROCESS || task.ex_property.task_kbn == this.GANTT_TASK_KBN.RECEIVED)) {
                result = false;
            }

            if (result) {
                var beforeParentTask = gantt.getTask(task.parent);
                var AfterParentTask = gantt.getTask(parent);

                // 工事区分IDが変わることはあり得ない
                if (result && beforeParentTask.ex_property.construction_id != AfterParentTask.ex_property.construction_id) {
                    result = false;
                }

                if (task.ex_property.task_kbn == this.GANTT_TASK_KBN.PROCESS) {
                    // 工程の移動
                    // 変更前と変更後で親が変わった場合はNG
                    if (result && task.parent != parent) {
                        result = false;
                    }
                }else{
                    // 受注確定商品の移動
                    // 変更後の親が工程以外の場合はNG
                    if (result && AfterParentTask.ex_property.task_kbn != this.GANTT_TASK_KBN.PROCESS) {
                        result = false;
                    }
                }
            }

            return result;
        }.bind(this));

        // 【イベント】グリッド行移動後（直後）
        gantt.attachEvent("onRowDragEnd", function(id, target) {
            var task = gantt.getTask(id);

            // 受注確定商品を移動させた際は、小分類IDを書き換える(受注確定商品配下は1階層のみ)
            if (task.ex_property.task_kbn == this.GANTT_TASK_KBN.RECEIVED) {
                var parentTask = gantt.getTask(task.parent);
                task.ex_property.class_small_id = parentTask.ex_property.class_small_id;
                gantt.updateTask(task.id);

                var childrenTaskId = gantt.getChildren(task.id);
                for (const key in childrenTaskId) {
                    const childTaskId = childrenTaskId[key];
                    var childTask = gantt.getTask(childTaskId);
                    childTask.ex_property.class_small_id = task.ex_property.class_small_id;
                    gantt.updateTask(childTask.id);
                }
            }
        }.bind(this));

        // 【イベント】タスク移動直前 ※トリガー判定
        var beforeTaskDragTask = null; // 移動直前のタスク
        gantt.attachEvent("onBeforeTaskDrag", function(id, mode, event){
            var result = true;
            var task = gantt.getTask(id);

            // 通常画面の場合は禁止
            if (result && !gantt.getState().fullscreen) {
                result = false;
            }

            // リサイズ
            if (mode == gantt.config.drag_mode.resize) {
                // 工程タスク以外禁止
                if (task.ex_property.task_kbn != this.GANTT_TASK_KBN.PROCESS) {
                    result = false;
                }
            }
            // 移動
            if (mode == gantt.config.drag_mode.move) {
                // 工程タスクと作業タスク以外禁止
                if (!(task.ex_property.task_kbn == this.GANTT_TASK_KBN.PROCESS || task.ex_property.task_kbn == this.GANTT_TASK_KBN.WORK)){
                    result = false;
                }
                if (task.ex_property.task_kbn == this.GANTT_TASK_KBN.WORK) {
                    // サーバ側への保存対象でないデータは禁止
                    if (result && this.rmUndefinedBlank(task.ex_property.server_task_type) == '') {
                        result = false;
                    }
                }
            }

            beforeTaskDragTask = gantt.copy(task);
            return result;
        }.bind(this));

        // 【イベント】タスク移動中 ※引数originalはonBeforeTaskDrag時のタスクではない
        gantt.attachEvent("onTaskDrag", function(id, mode, task, original, e){
            //------【移動】------
            if (mode == gantt.config.drag_mode.move) {
                if (task.ex_property.task_kbn == this.GANTT_TASK_KBN.PROCESS) {
                    this.calcGanttConstructionPeriod(task.parent);
                }
                if (task.ex_property.task_kbn == this.GANTT_TASK_KBN.WORK) {
                    // タスクが移動できる範囲を制限する
                    if (beforeTaskDragTask.start_date.getTime() > today.getTime()) {
                        // 移動前タスクの開始日付がシステム日付以降
                        // 移動中タスクが過去に移動している && 移動中タスクの開始日付がシステム日付より小さくなった場合
                        if (task.start_date.getTime() < beforeTaskDragTask.start_date.getTime() && task.start_date.getTime() < today.getTime()) {
                            task.start_date = today;
                            task.end_date = moment(today).add(1, 'd').toDate();
                        }
                    }else{
                        // 移動前タスクの開始日付がシステムよりも前
                        // 移動中タスクの開始日付が移動前タスクの開始日付より小さくなった場合
                        if (task.start_date.getTime() < beforeTaskDragTask.start_date.getTime()) {
                            task.start_date = beforeTaskDragTask.start_date;
                            task.end_date = beforeTaskDragTask.end_date;
                        }
                    }
                }
            }
            //------【リサイズ】------
            if(mode == gantt.config.drag_mode.resize){
                if (task.ex_property.task_kbn == this.GANTT_TASK_KBN.PROCESS) {
                    var diffDate = moment(task.end_date).diff(task.start_date, 'days');
                    if (diffDate < 1) {
                        // 期間が1日のタスクを縮小できないようにする(task.durationが0になってからでは遅い)
                        if (beforeTaskDragTask.start_date.getTime() == task.start_date.getTime()) {
                            // 開始日が同じ（=終了日をリサイズで動かしている）
                            task.end_date = moment(task.start_date).add(1, 'day').toDate();
                        }else{
                            // 終了日が同じ（=開始日をリサイズで動かしている）
                            task.start_date = moment(task.end_date).subtract(1, 'day').toDate();
                        }   
                    }else{
                        this.calcGanttConstructionPeriod(task.parent);  // 自タスクの工事区分タスクのみ再計算
                    }    
                }
            }
        }.bind(this));

        // ※作業タスクは元に戻るが、受注確定タスク(type:projectは期間を子に依存している)が元に戻らない為、処理を取り消す際は変更確定後に行う（onAfterTaskDrag）
        // 【イベント】タスク内容変更前 ※アクションの発火 or キャンセル
        // gantt.attachEvent("onBeforeTaskChanged", function(id, mode, task){
        //     return false;
        // }.bind(this));

        // 【イベント】タスク移動後の処理
        gantt.attachEvent("onAfterTaskDrag", function(id, mode, e){
            var task = gantt.getTask(id);
            //------【移動】------
            if (mode == gantt.config.drag_mode.move) {
                if (task.ex_property.task_kbn == this.GANTT_TASK_KBN.PROCESS) {
                    if (this.isGanttAutoScheduling) {
                        this.calcGanttProcessAutoSchedule(task.id);     // 自動スケジューリング
                    }
                    this.calcGanttStartDateCalcDays(task);  // 開始日計算日数の更新
                    this.calcGanttConstructionPeriod();  // 全工事区分再計算（自動スケジューリングで更新されるデータが同一工事区分とは限らない）
                }
                if (task.ex_property.task_kbn == this.GANTT_TASK_KBN.WORK) {
                    // ↓操作性が悪いため、一旦CO
                    // // 移動後タスクの開始日付がシステム日付より前の場合は移動前タスクの日付で更新する
                    // if (task.start_date.getTime() < today.getTime()) {
                    //     task.start_date = beforeTaskDragTask.start_date;
                    //     task.end_date = beforeTaskDragTask.end_date;
                    //     gantt.updateTask(task.id);
                    // }
                }
            }
            //------【リサイズ】------
            if(mode == gantt.config.drag_mode.resize){
                if (task.ex_property.task_kbn == this.GANTT_TASK_KBN.PROCESS) {
                    if (this.isGanttAutoScheduling) {
                        this.calcGanttProcessAutoSchedule(task.id);     // 自動スケジューリング
                    }
                    this.calcGanttStartDateCalcDays(task);  // 開始日計算日数の更新
                    this.calcGanttConstructionPeriod();  // 全工事区分再計算（自動スケジューリングで更新されるデータが同一工事区分とは限らない）
                }
            }

        }.bind(this));

        // 【イベント】データ描画時(直前)
        gantt.attachEvent("onParse", function(){
            var dateToStr = gantt.date.date_to_str(gantt.config.task_date);
            var markerId = gantt.addMarker({  
                start_date: today, 
                css: "today", 
                text: "今日", 
                title: dateToStr(today) 
            });

            // なぜかDOMの更新後でないと落ちる処理があった為
            this.$nextTick(function() {
                // 工程タスクのスケジューリングを行う
                this.calcGanttProcessAutoSchedule();
                // 工程タスクを元に工事区分タスクの期間をセットする
                this.calcGanttConstructionPeriod();
                // 期間指定がされていない作業タスクの期間(開始日付～終了日付)を設定する
                this.calcGanttWorkPeriod();
                // ソートを行う
                this.sortGanttTask();
            }.bind(this));
        }.bind(this));

        // 【イベント】ユーザーがリンクをダブルクリックすると発生 ※トリガー判定
        gantt.attachEvent("onLinkDblClick", function(id, e){
            var result = true;

            // 通常画面の場合は禁止
            if (result && !gantt.getState().fullscreen) {
                result = false;
            }

            return result;
        });

        // 【イベント】リンク追加直前 ※トリガー判定
        gantt.attachEvent("onBeforeLinkAdd", function(id, link){
            var result = true;
            var sourceTask = gantt.getTask(link.source);
            var targetTask = gantt.getTask(link.target);

            // 『finish to start』のみ ※終点から始点
            if (result && link.type != gantt.config.links.finish_to_start) {
                result = false;
            }

            // リンク元はマイルストーンタスクと工程タスクのみ
            if (result && !(
                sourceTask.ex_property.task_kbn == this.GANTT_TASK_KBN.MILESTONE_CONSTRUCTION_DATE
                || sourceTask.ex_property.task_kbn == this.GANTT_TASK_KBN.MILESTONE_RIDGEPOLE_RAISING_DATE
                || sourceTask.ex_property.task_kbn == this.GANTT_TASK_KBN.PROCESS)
            ) {
                result = false;
            }

            // リンク先は工程タスクのみ
            if (result && targetTask.ex_property.task_kbn != this.GANTT_TASK_KBN.PROCESS) {
                result = false;
            }

            // 1タスクから複数のタスクにリンクがある⇒〇
            // 1タスクに対して複数のタスクからリンクがある⇒×
            if (result && targetTask.$target.length > 0) {
                result = false;
            }

            if (result) {
                if (sourceTask.ex_property.task_kbn == this.GANTT_TASK_KBN.MILESTONE_CONSTRUCTION_DATE
                    || targetTask.ex_property.task_kbn == this.GANTT_TASK_KBN.RIDGEPOLE_RAISING_DATE
                ) {
                    // マイルストーンタスク（リンク元）
                    // リンク先の工程タスクに対してリンクが張られている場合はNG
                    if (targetTask.$target.length > 0) {
                        result = false;
                    }
                }else if (sourceTask.ex_property.task_kbn == this.GANTT_TASK_KBN.PROCESS) {
                    // 工程タスク（リンク元）
                    var sourceBaseDateType = sourceTask.ex_property.base_date_type;
                    var targetBaseDateType = targetTask.ex_property.base_date_type;
                    // 『基準日種別=着工日』-『基準日種別=工程以外』リンクはNG
                    if (sourceBaseDateType == this.pConst.BASE_DATE_TYPE.construction_date && targetBaseDateType != 0) {
                        result = false;
                    }
                    // 『基準日種別=上棟日』-『基準日種別=工程以外』リンクはNG
                    if (sourceBaseDateType == this.pConst.BASE_DATE_TYPE.ridgepole_raising_date && targetBaseDateType != 0) {
                        result = false;
                    }
                    // 『基準日種別=工程』-『基準日種別=工程以外』リンクはNG
                    if (sourceBaseDateType == 0 && targetBaseDateType != 0) {
                        result = false;
                    }
                }

            }

            return result;
        }.bind(this));

        // 【イベント】リンク追加後
        gantt.attachEvent("onAfterLinkAdd", function(id, item){
            this.updateBaseDateTypeByTask(item.target); // 基準日種別を更新

            var targetTask = gantt.getTask(item.target);
            this.calcGanttStartDateCalcDays(targetTask);
        }.bind(this));

        // 【イベント】リンク削除後
        gantt.attachEvent("onAfterLinkDelete", function(id, item){
            this.updateBaseDateTypeByTask(item.target); // 基準日種別を更新

            var targetTask = gantt.getTask(item.target);
            this.calcGanttStartDateCalcDays(targetTask);  
        }.bind(this));

        // 【関数再定義】タスクのダイアログ表示時
        gantt.showLightbox = function(taskId){
            var task = gantt.getTask(taskId);
            switch (task.ex_property.task_kbn) {
                case this.GANTT_TASK_KBN.CONSTRUCTION:
                    // 工事区分
                    this.dlgGanttReceivedTaskMoveDlg.taskId = taskId;
                    this.showGanttReceivedTaskMoveDlg(true);
                    break;
                case this.GANTT_TASK_KBN.PROCESS:
                    // 工程
                    this.dlgGanttTaskBox.taskId = taskId;
                    this.showGanttTaskDlg(true);
                    break;
                default:
                    break;
            }

        }.bind(this);

        // 【テンプレート】
        // グリッド行にクラスを追加
        gantt.templates.grid_row_class = function(start, end, task) {
            var addClass = "";
            switch (task.ex_property.task_kbn) {
                case this.GANTT_TASK_KBN.MILESTONE_PARENT:    // マイルストーン親
                    addClass = "ex-gantt-row-milestone-parent";
                    break;
                case this.GANTT_TASK_KBN.MILESTONE_CONSTRUCTION_DATE:    // マイルストーン着工日
                    addClass = "ex-gantt-row-milestone-construction-date";
                    break;
                case this.GANTT_TASK_KBN.MILESTONE_RIDGEPOLE_RAISING_DATE:    // マイルストーン上棟日
                    addClass = "ex-gantt-row-milestone-ridgepole-raising-date";
                    break;
                case this.GANTT_TASK_KBN.CONSTRUCTION:    // 工事区分
                    addClass = "ex-gantt-row-construction";
                    break;
                case this.GANTT_TASK_KBN.PROCESS:         // 工程
                    addClass = "ex-gantt-row-process";
                    break;
                case this.GANTT_TASK_KBN.RECEIVED:         // 商品
                    addClass = "ex-gantt-row-received";
                    break;
                case this.GANTT_TASK_KBN.WORK:            // 作業
                    addClass = "ex-gantt-row-work";
                    break;
                default:
                    break;
            }
            return addClass;
        }.bind(this);

        // 【テンプレート】タスクのクラス
        gantt.templates.task_class = function(start, end, task) {
            // タスクにクラスを追加
            var addClass = "";
            switch (task.ex_property.task_kbn) {
                case this.GANTT_TASK_KBN.MILESTONE_PARENT:    // マイルストーン親
                    addClass = "ex-gantt-bar-milestone-parent";
                    break;
                case this.GANTT_TASK_KBN.MILESTONE_CONSTRUCTION_DATE:    // マイルストーン（着工日）
                    addClass = "ex-gantt-bar-milestone-construction-date";
                    break;
                case this.GANTT_TASK_KBN.MILESTONE_RIDGEPOLE_RAISING_DATE:    // マイルストーン（上棟日）
                    addClass = "ex-gantt-bar-milestone-ridgepole-raising-date";
                    break;
                case this.GANTT_TASK_KBN.CONSTRUCTION:    // 工事区分
                    addClass = "ex-gantt-bar-construction";
                    break;
                case this.GANTT_TASK_KBN.PROCESS:         // 工程
                    addClass = "ex-gantt-bar-process";
                    break;
                case this.GANTT_TASK_KBN.RECEIVED:         // 商品
                    addClass = "ex-gantt-bar-received";
                    break;
                case this.GANTT_TASK_KBN.WORK:            // 作業
                    addClass = "ex-gantt-bar-work";
                    break;
                default:
                    break;
            }
            if (gantt.getState().fullscreen) {
                addClass += " ex-gantt-link-activate";
            }
            return addClass;
        }.bind(this);

        //【テンプレート】タスクのHTMLテキスト
        gantt.templates.task_text=function(start, end, task){
            if(task.ex_property.task_kbn == this.GANTT_TASK_KBN.WORK){
                task.color = "transparent";
                if (this.rmUndefinedBlank(task.ex_property.icon_info) == '') {
                    var startIconInfo = task.ex_property.s_icon_info;
                    var endIconInfo = task.ex_property.e_icon_info;
                    var sHtmlClass, eHtmlClass = '';
                    if (startIconInfo.finished) {
                        sHtmlClass = 'finished';
                    }else if (task.end_date.getTime() > today.getTime()) {
                        sHtmlClass = 'unfinished';
                    }else{
                        sHtmlClass = 'exceed';
                    }
                    if (endIconInfo.finished) {
                        eHtmlClass = 'finished';
                    }else if (task.end_date.getTime() > today.getTime()) {
                        eHtmlClass = 'unfinished';
                    }else{
                        eHtmlClass = 'exceed';
                    }
                    return '\
                        <div class="ex-gantt-icon-bar">\
                            <div class="left-icon"><svg class="' + sHtmlClass + '" style="width:' + this.GANTT_INFO.SCALE_WIDTH + 'px;"><use xlink:href="#' + startIconInfo.icon + '"></use></svg></div>\
                            <div class="right-icon"><svg class="' + eHtmlClass + '" style="width:' + this.GANTT_INFO.SCALE_WIDTH + 'px;"><use xlink:href="#' + endIconInfo.icon + '"></use></svg></div>\
                        </div>';
                }else{
                    var iconInfo = task.ex_property.icon_info;
                    var htmlClass = '';
                    if (iconInfo.finished) {
                        htmlClass = 'finished';
                    }else if (task.end_date.getTime() > today.getTime()) {
                        htmlClass = 'unfinished';
                    }else{
                        htmlClass = 'exceed';
                    }
                    return '\
                        <div class="ex-gantt-icon-bar">\
                            <div class="icon"><svg class="' + htmlClass + '" style="width:' + this.GANTT_INFO.SCALE_WIDTH + 'px;"><use xlink:href="#' + iconInfo.icon + '"></use></svg></div>\
                        </div>';
                }
            }
            return task.text;
        }.bind(this);

        //【テンプレート】リンクのクラス
        gantt.templates.link_class = function(link){
            var sourceTask = gantt.getTask(link.source);
            if (sourceTask.ex_property.task_kbn == this.GANTT_TASK_KBN.MILESTONE_CONSTRUCTION_DATE
                || sourceTask.ex_property.task_kbn == this.GANTT_TASK_KBN.MILESTONE_RIDGEPOLE_RAISING_DATE
            ) {
                return 'ex-gantt-milestone-link';
            }
        }.bind(this);

        /**
         * 他
         */
        // 休日解除(休日判定はDB側のデータに依存する)
        gantt.setWorkTime({ day: 0, hours: true }); // 曜日指定：日曜休日を解除
        gantt.setWorkTime({ day: 6, hours: true }); // 曜日指定：土曜休日を解除

        // カレンダー(id:global)に休日を設定
        for (const key in this.ganttHolidayList) {
            const date = this.ganttHolidayList[key];
            var convertedDate = new Date(moment(date).format(FORMAT_DATE));
            gantt.setWorkTime({ date:convertedDate, hours:false })
        }

        // 雨(id:rain) & グローバル+雨(id:global_and_rain)カレンダーを生成して追加
        this.generateGrobalAndRainCalendar();

        // 休日の影響を受けないカレンダー
        gantt.addCalendar({ id: this.GANTT_CALENDAR_KBN.NO_CALENDAR });

        gantt.init("gantt_here");   // 初期化
        this.getIndexGantt();   // 初回データ読込
    },
    methods: {
        /**************** wijmo制御系 ****************/
        createGridCtrl(targetGridDivId, gridItemSource) {
            var gridCtrl = new wjGrid.FlexGrid(targetGridDivId, {
                autoGenerateColumns: false,
                headersVisibility: wjGrid.HeadersVisibility.Column,
                columns: [  // 列レイアウト
                    { binding: 'chk', header: '', width: 40 },
                    { binding: 'process_name', header: '工程名', width: 200, isReadOnly: true },
                    { binding: 'layer_name', header: '階層名', width: 200, isReadOnly: true },
                    { binding: 'product_name', header: '商品名', width: '*', isReadOnly: true },
                    { binding: 'received_task_id', header: '', visible: false },
                ],     
                itemsSource: gridItemSource,    // データ
            });
            gridCtrl.itemFormatter = function (panel, r, c, cell) {
                var col = panel.columns[c];
                if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                    cell.style.textAlign = 'center';
                    
                    // 全チェック用のチェックボックス生成
                    if (col.binding == 'chk') {
                        var checkedCount = 0;
                        for (var i = 0; i < gridCtrl.rows.length; i++) {
                            if (gridCtrl.getCellData(i, c) == true) checkedCount++;
                        }

                        // ヘッダ部にチェックボックス追加
                        var checkBox = '<input type="checkbox">';
                        cell.innerHTML = checkBox;
                        var checkBox = cell.firstChild;
                        checkBox.checked = checkedCount > 0;
                        checkBox.indeterminate = checkedCount > 0 && checkedCount < gridCtrl.rows.length;

                        // 明細行にチェック状態を反映
                        checkBox.addEventListener('click', function (e) {
                            gridCtrl.beginUpdate();
                            for (var i = 0; i < gridCtrl.collectionView.items.length; i++) {
                                gridCtrl.setCellData(i, c, checkBox.checked);
                            }
                            gridCtrl.endUpdate();
                        }.bind(this));
                    }
                }

                // セルごとの設定
                if (panel.cellType == wjGrid.CellType.Cell) {
                }
            }.bind(this);
            return gridCtrl;
        },
        /**************** 案件情報 ****************/
        // 添付ファイルダウンロード
        async downloadFile(dlKbn, id, fileName) {
            var existsUrl = '';
            var downloadUrl = '';
            switch (dlKbn) {
                case this.DOWNLOAD_KBN.QUOTE:
                    existsUrl = '/matter-detail/exists/quote/' + id + '/' + encodeURIComponent(fileName);
                    downloadUrl = '/matter-detail/download/quote/' + id + '/' + encodeURIComponent(fileName);
                    break;
                case this.DOWNLOAD_KBN.ORDER:
                    existsUrl = '/matter-detail/exists/order/' + this.mainData.matter_id + '/' + id + '/' + encodeURIComponent(fileName);
                    downloadUrl = '/matter-detail/download/order/' + this.mainData.matter_id + '/' + id + '/' + encodeURIComponent(fileName);
                    break;
                default:
                    break;
            }

            var result = await this.existsFile(existsUrl);
            if (result != undefined && result) {
                // unloadイベントの内容を保持
                var unloadEv = window.onbeforeunload;
                window.onbeforeunload = null;

                location.href = downloadUrl;
                
                // unloadイベントの内容を元に戻す
                window.onbeforeunload = unloadEv;   
            }
        },
        // 添付ファイルが存在するか
        existsFile(url){
            var promise = axios.get(url)
            .then( function (response) {
                if (response.data) {
                    // 成功
                    return response.data;
                } else {
                    // 失敗
                    alert(MSG_ERROR_NOT_EXISTS_FILE);
                }
            }.bind(this))
            .catch(function (error) {
                alert(MSG_ERROR); 
            }.bind(this))
            return promise;
        },
        // 案件住所入力
        inputMatterAddress(){
            var matterId = this.mainData.matter_id;
            var link = '/address-edit/' + matterId;
            window.open(link, '_blank')
        },
        // 案件情報取得
        reloadAddress(){
            var params = new URLSearchParams();

            // 案件ID
            params.append('matter_id', this.rmUndefinedZero(this.mainData.matter_id));
            axios.post('/matter-detail/matter-address/get', params)
            .then( function (response) {
                if (response.data) {
                    var address = response.data.matter_address;
                    this.mainData.zipcode = address.zipcode;
                    this.mainData.address1 = address.address1;
                    this.mainData.address2 = address.address2;
                }
            }.bind(this))
            .catch(function (error) {
            }.bind(this))
        },
        /***************** 週間カレンダー *****************/
        // 初期表示用データ取得
        getSchedulerData(){
            if (this.mainData.complete_flg == this.FLG_ON) {
                return;
            }

            var params = new URLSearchParams();
            // // 案件ID
            params.append('matter_id', this.rmUndefinedZero(this.mainData.matter_id));

            this.loadingScheduler = true;
            axios.post('/matter-detail/get/scheduler', params)
            .then(function(response){
                if (response.data) {
                    // success
                    var resData = response.data;
                    var schedulerData = JSON.parse(resData.scheduler_data);

                    scheduler.clearAll();
                    scheduler.parse(schedulerData);
                }else{
                    alert(MSG_ERROR);
                }
            }.bind(this))
            .catch(function(error) {
                if (error.response.data.message) {
                    alert(error.response.data.message)
                } else {
                    alert(MSG_ERROR)
                }
            }.bind(this))
            .finally(() => {
                this.loadingScheduler = false;
            });
        },
        /******************** 工程表 ********************/
        // 着工日入力ダイアログ
        showGanttConstructionDateDlg(show){
            this.dlgGanttConstructionDate.show = show;
            if (show) { // OPEN
                this.dlgGanttConstructionDate.error = '';
                // wijmoオブジェクトが生成されていない場合(初回OPEN時)があるのでDOM生成後
                this.$nextTick(function() {
                    this.dlgGanttConstructionDate.wjDate.value = this.mainData.construction_date;
                });
            }else{ // CLOSE
            }
        },
        // 上棟日入力ダイアログ
        showGanttRidgepoleRaisingDateDlg(show){
            this.dlgGanttRidgepoleRaisingDate.show = show;
            if (show) { // OPEN
                this.dlgGanttRidgepoleRaisingDate.error = '';
                // wijmoオブジェクトが生成されていない場合(初回OPEN時)があるのでDOM生成後
                this.$nextTick(function() {
                    this.dlgGanttRidgepoleRaisingDate.wjDate.value = this.mainData.ridgepole_raising_date;
                });
            }else{ // CLOSE
            }
        },
        // 受注確定タスクの所属設定を行う
        showGanttReceivedTaskMoveDlg(show){
            this.dlgGanttReceivedTaskMoveDlg.show = show;
            var constructionTaskId = this.dlgGanttReceivedTaskMoveDlg.taskId;
            var constructionTask = gantt.getTask(constructionTaskId);
            if (show) {
                this.initErrArrObj(this.dlgGanttReceivedTaskMoveDlg.errors);

                this.dlgGanttReceivedTaskMoveDlg.constructionName = constructionTask.text;
                // wijmoオブジェクトが生成されていない場合(初回OPEN時)があるのでDOM生成後
                this.$nextTick(function() {
                    var filterComboProcessList = []; // 絞込用コンボボックス（工程）
                    var filterComboLayerList = [];      // 絞込用コンボボックス（階層）
                    var processComboList = [];          // 移動先指定用コンボボックス（工程）
                    var gridItemsSource = [];           // 商品移動候補データ

                    // 工事区分階層直下の工程を取得
                    var processData = gantt.getTaskBy(rec =>
                                        rec.ex_property.task_kbn == this.GANTT_TASK_KBN.PROCESS
                                        && rec.parent == constructionTaskId
                                    );
                    for (const key in processData) {
                        const processTask = processData[key];
                        processComboList.push({id: processTask.id, text: processTask.text});
                    }

                    // 移動先指定用コンボボックス（工程）にデータ適用
                    this.dlgGanttReceivedTaskMoveDlg.wjMoveToProcessCombo.itemsSource = processComboList;
                    this.dlgGanttReceivedTaskMoveDlg.wjMoveToProcessCombo.selectedIndex = -1;
                    this.dlgGanttReceivedTaskMoveDlg.wjMoveToProcessCombo.maxItems = processComboList.length;

                    // 選択した工事区分内に存在する全ての受注確定商品タスクを取得
                    var receivedData = gantt.getTaskBy(rec =>
                                            rec.ex_property.task_kbn == this.GANTT_TASK_KBN.RECEIVED
                                            && this.getGanttAncestorsTaskId(rec.id)[this.GANTT_TASK_KBN.CONSTRUCTION] == constructionTaskId
                                        );
                    for (const key in receivedData) {
                        const receivedTask = receivedData[key];
                        var parentTask = gantt.getTask(receivedTask.parent);
                        var processName = null;
                        if (parentTask.ex_property.task_kbn == this.GANTT_TASK_KBN.PROCESS) {
                            // 工程タスク
                            processName = parentTask.text;
                            if(filterComboProcessList.indexOf(parentTask.text) == -1){
                                filterComboProcessList.push(parentTask.text);
                            }
                        }else{
                            // 工事区分タスク
                            processName = this.NO_PROCESS_SETTING;
                            if(filterComboProcessList.indexOf(processName) == -1){
                                filterComboProcessList.unshift(processName);    // 工程なしは先頭に追加
                            }
                        }
                        var layerName = this.pParentQuoteLayerNameData[receivedTask.ex_property.quote_detail_id]['layer_name'];

                        gridItemsSource.push(
                            {
                                chk: false,
                                process_name: processName,
                                layer_name: layerName,
                                product_name: receivedTask.text,
                                received_task_id: receivedTask.id,
                            }
                        );
                        if (filterComboLayerList.indexOf(layerName) == -1) {
                            filterComboLayerList.push(layerName);
                        }
                    }

                    // 配列⇒多次元連想配列
                    filterComboProcessList = filterComboProcessList.map(function(value){
                        return { text: value };
                    });
                    filterComboLayerList = filterComboLayerList.map(function(value){
                        return { text: value };
                    });

                    // 絞込用コンボボックス（工程）にデータ適用
                    this.dlgGanttReceivedTaskMoveDlg.wjFilterProcessCombo.itemsSource = filterComboProcessList;
                    this.dlgGanttReceivedTaskMoveDlg.wjFilterProcessCombo.selectedIndex = -1;
                    this.dlgGanttReceivedTaskMoveDlg.wjFilterProcessCombo.maxItems = filterComboProcessList.length;

                    // 絞込用コンボボックス（階層）にデータ適用
                    this.dlgGanttReceivedTaskMoveDlg.wjFilterLayerCombo.itemsSource = filterComboLayerList;
                    this.dlgGanttReceivedTaskMoveDlg.wjFilterLayerCombo.selectedIndex = -1;
                    this.dlgGanttReceivedTaskMoveDlg.wjFilterLayerCombo.maxItems = filterComboLayerList.length;

                    // グリッド生成
                    this.dlgGanttReceivedTaskMoveDlg.wjGrid = this.createGridCtrl('#productMoveGrid', gridItemsSource);
                });
            }else{
                this.dlgGanttReceivedTaskMoveDlg.wjGrid.dispose();
                this.dlgGanttReceivedTaskMoveDlg.wjGrid = null;
            }
        },
        // 商品一括移動グリッドのフィルター
        filterGanttReceivedTask(){
            if (this.dlgGanttReceivedTaskMoveDlg.wjGrid) {
                this.dlgGanttReceivedTaskMoveDlg.wjGrid.collectionView.filter = item => {
                    var showItem = true;
                    var filterText = '';

                    filterText = this.dlgGanttReceivedTaskMoveDlg.wjFilterProcessCombo.text;
                    if (item.process_name.indexOf(filterText) == -1) {
                        showItem = false;
                    }

                    filterText = this.dlgGanttReceivedTaskMoveDlg.wjFilterLayerCombo.text;
                    if (item.layer_name.indexOf(filterText) == -1) {
                        showItem = false;
                    }

                    return showItem;
                };
            }
        },
        // 受注確定タスクを指定された工程に移動する
        moveGanttReceivedTask(){
            var result = true;
            var targetGridData = this.dlgGanttReceivedTaskMoveDlg.wjGrid.collectionView.items;
            targetGridData = targetGridData.filter(function(rec){
                return (rec.chk);
            });
            var moveToProcessTaskId = this.dlgGanttReceivedTaskMoveDlg.wjMoveToProcessCombo.selectedValue;

            this.initErrArrObj(this.dlgGanttReceivedTaskMoveDlg.errors);

            if (targetGridData.length == 0) {
                result = false;
                this.dlgGanttReceivedTaskMoveDlg.errors.grid = MSG_ERROR_CHECKBOX_NO_SELECT;
            }

            if (this.rmUndefinedZero(moveToProcessTaskId) == 0) {
                result = false;
                this.dlgGanttReceivedTaskMoveDlg.errors.move_to_process = MSG_ERROR_NO_SELECT;
            }

            if (result) {
                this.showGanttReceivedTaskMoveDlg(false);
                this.loadingGantt = true;

                // 受注確定タスク及び受注確定タスク配下の作業タスクのデータを更新
                var moveToProcessTask = gantt.getTask(moveToProcessTaskId);
                for (const gridIdx in targetGridData) {
                    const gridRec = targetGridData[gridIdx];
                    var receivedTask = gantt.getTask(gridRec.received_task_id);
                    receivedTask.parent = moveToProcessTaskId;
                    receivedTask.ex_property.class_small_id = moveToProcessTask.ex_property.class_small_id;
                    gantt.updateTask(receivedTask.id);

                    // 作業タスクのデータを取得
                    var childTaskList = gantt.getTaskBy(rec => rec.parent == receivedTask.id);
                    for (const childTaskIdx in childTaskList) {
                        var workTask = childTaskList[childTaskIdx];
                        workTask.ex_property.class_small_id = moveToProcessTask.ex_property.class_small_id;
                        gantt.updateTask(workTask.id);
                    }
                }
                gantt.render();
                this.loadingGantt = false;
            }
        },
        // 工程タスクの情報をダイアログで表示
        showGanttTaskDlg(show){
            this.dlgGanttTaskBox.show = show;
            // 工程追加ダイアログ表示時
            if (show) { // OPEN
                var task = gantt.getTask(this.dlgGanttTaskBox.taskId);
                this.initErrArrObj(this.dlgGanttTaskBox.errors);    // エラークリア
                var constructionTask = gantt.getTask(task.parent);  // 工事区分タスクの取得

                this.dlgGanttTaskBox.isNew = false;
                if (task.$new) { this.dlgGanttTaskBox.isNew = true; }

                this.dlgGanttTaskBox.content.construction_name = constructionTask.text;
                this.dlgGanttTaskBox.content.task_name = task.text;
                this.dlgGanttTaskBox.content.duration = task.duration;
                this.dlgGanttTaskBox.content.date_calc_flg = task.ex_property.date_calc_flg;
                this.dlgGanttTaskBox.content.order_timing = task.ex_property.order_timing;
                this.dlgGanttTaskBox.content.rain_flg = task.ex_property.rain_flg;

                // 新規工程時の処理
                if (this.dlgGanttTaskBox.isNew) {
                    this.dlgGanttTaskBox.content.date_calc_flg = this.FLG_ON;
                }

                // wijmoオブジェクトが生成されていない場合(初回OPEN時)があるのでDOM生成後
                this.$nextTick(function() {
                    this.dlgGanttTaskBox.content.wj_start_date.value = (this.dlgGanttTaskBox.isNew) ? null:task.start_date;
                });
            }else{  // CLOSE
                if (this.dlgGanttTaskBox.isNew) {
                    gantt.deleteTask(this.dlgGanttTaskBox.taskId)
                }
            }
        },
        // タスクの保存
        saveGanttTask(taskId) {
            this.initErrArrObj(this.dlgGanttTaskBox.errors);

            var result = true;

            // 工程名称
            if (this.rmUndefinedBlank(this.dlgGanttTaskBox.content.task_name) === '') {
                // 空
                this.dlgGanttTaskBox.errors.task_name = MSG_ERROR_NO_INPUT;
                result = false;
            }
            // 開始日
            if (this.rmUndefinedBlank(this.dlgGanttTaskBox.content.wj_start_date.text) === '') {
                // 空
                this.dlgGanttTaskBox.errors.start_date = MSG_ERROR_NO_INPUT;
                result = false;
            }
            // 工期日数
            if (this.rmUndefinedBlank(this.dlgGanttTaskBox.content.duration) === '') {
                // 空
                this.dlgGanttTaskBox.errors.duration = MSG_ERROR_NO_INPUT;
                result = false;
            }else if(!(/^[1-9][0-9]*$/).test(this.dlgGanttTaskBox.content.duration)){
                // 半角数値以外（0もNG）
                this.dlgGanttTaskBox.errors.duration = MSG_ERROR_INPUT_GREATER_THAN_ZERO;
            }
            // 発注タイミング
            if (this.rmUndefinedBlank(this.dlgGanttTaskBox.content.order_timing) === '') {
                // 空
                this.dlgGanttTaskBox.errors.order_timing = MSG_ERROR_NO_INPUT;
                result = false;
            }else if(!(/^[0-9]/).test(this.dlgGanttTaskBox.content.duration)){
                // 半角数値以外
                this.dlgGanttTaskBox.errors.order_timing = MSG_ERROR_NOT_NUMBER;
                result = false;
            }

            if (result) {
                var task = gantt.getTask(taskId);
                task.text = this.dlgGanttTaskBox.content.task_name;
                task.ex_property.order_timing = this.dlgGanttTaskBox.content.order_timing;

                // 使用カレンダーを判別（必ず日付のセットよりも先にやる）
                task.ex_property.date_calc_flg = this.dlgGanttTaskBox.content.date_calc_flg;
                task.ex_property.rain_flg = this.dlgGanttTaskBox.content.rain_flg;
                task.calendar_id = this.judgeCalendarId(task.ex_property);

                // 今日を含めて未来の有効な日付を開始日付にセットする
                task.start_date = gantt.getClosestWorkTime({ date: new Date(this.dlgGanttTaskBox.content.wj_start_date.text), dir: "future", task:task });
                task.duration = this.dlgGanttTaskBox.content.duration;
                task.end_date = gantt.calculateEndDate({
                    start_date: task.start_date, duration: task.duration, task: task
                });

                if(task.$new){
                    task.$new = false;
                    gantt.addTask(task, task.parent);
                    
                }else{
                    gantt.updateTask(task.id);
                }
                if (this.isGanttAutoScheduling) {
                    this.calcGanttProcessAutoSchedule(task.id);
                }
                this.calcGanttStartDateCalcDays(task);  // 開始日計算日数の更新
                this.calcGanttConstructionPeriod();  // 全工事区分再計算（自動スケジューリングで更新されるデータが同一工事区分とは限らない）
                this.dlgGanttTaskBox.show = false;   
            }
        },
        // タスクの削除
        deleteGanttTask(taskId) {
            var task = gantt.getTask(taskId);

            var result = true;

            // 子が存在する場合は削除不可
            if (gantt.getChildren(task.id).length > 0) {
                alert(MSG_ERROR_EXISTS_RECEIVED_ORDER);
                result = false;
            }

            if (result) {
                gantt.deleteTask(taskId);
                this.dlgGanttTaskBox.show = false;   
            }
        },
        // 得意先標準登録
        saveCustomerStandard() {
            // エラーの初期化
            this.initErr(this.errors);

            var result = window.confirm(MSG_CONFIRM_SAVE_CUSTOMER_STANDARD);
            if (result) {
                this.loadingGantt = true;

                var params = new URLSearchParams();
                params.append('matter_id', this.mainData.matter_id);

                // マイルストーンのタスクを取得
                var milestoneTaskList = gantt.getTaskBy(task =>
                                    task.ex_property.task_kbn == this.GANTT_TASK_KBN.MILESTONE_CONSTRUCTION_DATE
                                    || task.ex_property.task_kbn == this.GANTT_TASK_KBN.MILESTONE_RIDGEPOLE_RAISING_DATE
                                );
                // var milestoneLinks = [];    // マイルストーンがソース元のリンクID ※リンク先が着工日や上棟日のタスク
                var milestoneConnectedGroup = [];   // マイルストーンと関連のある全てのタスクID（リンクが繋がっている全てのタスク）
                for (const idx in milestoneTaskList) {
                    const task = milestoneTaskList[idx];
                    // milestoneLinks = milestoneLinks.concat(task.$source);
                    milestoneConnectedGroup = milestoneConnectedGroup.concat(gantt.getConnectedGroup(task.id)['tasks']);   
                }

                // 保存対象のデータを取得する(工事区分, 工程)
                var taskList = gantt.getTaskBy(task =>
                                task.ex_property.task_kbn == this.GANTT_TASK_KBN.CONSTRUCTION
                                || (task.ex_property.task_kbn == this.GANTT_TASK_KBN.PROCESS && milestoneConnectedGroup.indexOf(task.id) != -1)
                            );
                var index = 1;  // task.$indexはtreeの開閉状態を考慮しないといけない為,不使用
                var tasks = [];
                for (const idx in taskList) {
                    const task = taskList[idx];
                    tasks.push({
                        id: task.id,                                                    // ID
                        type: task.ex_property.server_task_type,                        // 種別(サーバ側)
                        construction_id: task.ex_property.construction_id,              // 工事区分ID
                        class_small_id: task.ex_property.class_small_id,                // 工事区分ID
                        text: task.text,                                                // タスク名
                        duration: task.duration,                                        // 期間
                        parent: task.parent,                                            // 親ID
                        sortorder: index++,                                             // 並び順
                        date_calc_flg: task.ex_property.date_calc_flg,                  // 日付計算フラグ
                        start_date_calc_days: task.ex_property.start_date_calc_days,    // 開始日計算日数
                        order_timing: task.ex_property.order_timing,                    // 発注タイミング
                        rain_flg: task.ex_property.rain_flg,                            // 雨延期フラグ
                        base_date_type: task.ex_property.base_date_type,                // 基準日種別
                    });     
                }

                var links = [];
                var linkList = gantt.getLinks();
                // ソース元が工程 && マイルストーンと関連のあるリンクのみ登録対象とする
                for (const idx in linkList) {
                    var link = linkList[idx];
                    var sourceTask = gantt.getTask(link.source);
                    if (sourceTask.ex_property.task_kbn == this.GANTT_TASK_KBN.PROCESS && milestoneConnectedGroup.indexOf(sourceTask.id) != -1) {
                        links.push(link);
                    }
                }

                params.append('tasks', JSON.stringify(tasks));
                params.append('links', JSON.stringify(links));

                axios.post('/matter-detail/save/customer-standard', params)
                .then( function (response) {
                    if (response.data) {
                        if (response.data.status) {
                            if (response.data.msg) {
                                alert(response.data.msg);
                            }
                        }else{
                            if(response.data.msg){
                                alert(response.data.msg);
                            }else{
                                alert(MSG_ERROR);
                            }
                        }
                    }else{
                        alert(MSG_ERROR);
                    }
                }.bind(this))
                .catch(function (error) {
                    if (error.response.data.message) {
                        alert(error.response.data.message)
                    } else {
                        alert(MSG_ERROR)
                    }
                }.bind(this))
                .finally(() => {
                    this.loadingGantt = false;
                });
            }
        },
        // チャート呼出ダイアログ表示・非表示
        showCallChartDlg(show){
            this.dlgCallChart.show = show;
            if (show) { // OPEN
                this.dlgCallChart.kbnValue = this.dlgCallChart.KBN.CUSTOMER_STANDARD;
                this.dlgCallChart.error = '';
                // wijmoオブジェクトが生成されていない場合(初回OPEN時)があるのでDOM生成後
                this.$nextTick(function() {
                    this.dlgCallChart.wjMatterCombo.selectedIndex = -1;
                });
            }else{ // CLOSE
            }
        },
        // チャート呼出
        callChart(){
            var result = true;
            this.dlgCallChart.error = '';

            // 案件にチェックが付いてる時は、案件コンボの選択必須
            var callMatterId = this.rmUndefinedZero(this.dlgCallChart.wjMatterCombo.selectedValue);
            if (result && this.dlgCallChart.kbnValue == this.dlgCallChart.KBN.MATTER && callMatterId == 0
            ) {
                this.dlgCallChart.error = MSG_ERROR_NO_SELECT;
                result = false;
            }

            if (result) {
                this.loadingGantt = true;
                this.dlgCallChart.show = false;

                var params = new URLSearchParams();
                params.append('matter_id', this.mainData.matter_id);
                params.append('call_matter_id', callMatterId);

                axios.post('/matter-detail/get/chart', params)
                .then(function(response){
                    if (response.data) {
                        // success
                        var resData = response.data;

                        var id = resData.next_id;
                        var resTasks = JSON.parse(resData.tasks);
                        var resLinks = JSON.parse(resData.links);
                        var resWorks = JSON.parse(resData.works);

                        var ganttData = {
                            data: [],
                            links: resLinks,
                        };

                        if (resTasks.length > 0) {
                            var linkReceived = {};

                            // ガントチャート用のデータを作成
                            var mileStoneParentRecord = this.generateGanttMileStoneTask(this.GANTT_TASK_KBN.MILESTONE_PARENT, id++);
                            var mileStoneConstructionRecord = this.generateGanttMileStoneTask(this.GANTT_TASK_KBN.MILESTONE_CONSTRUCTION_DATE, id++);
                            mileStoneConstructionRecord.parent = mileStoneParentRecord.id;
                            var mileStoneRidgepoleRaisingRecord = this.generateGanttMileStoneTask(this.GANTT_TASK_KBN.MILESTONE_RIDGEPOLE_RAISING_DATE, id++);
                            mileStoneRidgepoleRaisingRecord.parent = mileStoneParentRecord.id;

                            for (const key in resTasks) {
                                const taskItem = resTasks[key];
                                var record = JSON.parse(this.ganttTaskProperty);
                                record.id = taskItem.id;
                                record.text = taskItem.text;
                                record.start_date = moment().startOf('day').format(FORMAT_DATE);    // とりあえず今日の日付をセット
                                record.duration = taskItem.duration,
                                record.parent = taskItem.parent,
                                record.ex_property.construction_id = taskItem.construction_id;
                                record.ex_property.class_small_id = taskItem.class_small_id;
                                record.ex_property.quote_detail_id = taskItem.quote_detail_id;
                                record.ex_property.server_task_type = taskItem.type;
                                switch (taskItem.type) {
                                    case this.pConst.SERVER_TASK_TYPE.construction:
                                        record.type = gantt.config.types.task;
                                        record.ex_property.task_kbn = this.GANTT_TASK_KBN.CONSTRUCTION;
                                        break;
                                    case this.pConst.SERVER_TASK_TYPE.process:
                                        record.type = gantt.config.types.task;
                                        record.ex_property.task_kbn = this.GANTT_TASK_KBN.PROCESS;
                                        record.ex_property.add_flg = taskItem.add_flg;
                                        record.ex_property.date_calc_flg = taskItem.date_calc_flg;
                                        record.ex_property.rain_flg = taskItem.rain_flg;
                                        record.ex_property.order_timing = taskItem.order_timing;
                                        record.ex_property.start_date_calc_days = taskItem.start_date_calc_days;
                                        record.ex_property.base_date_type = taskItem.base_date_type;
                                        if (taskItem.base_date_type == this.pConst.BASE_DATE_TYPE.construction_date) {
                                            record.start_date = this.mainData.construction_date;
                                            ganttData.links.push(
                                                    { source: mileStoneConstructionRecord.id, target: record.id, type: gantt.config.links.finish_to_start }
                                            );
                                        }else if(taskItem.base_date_type == this.pConst.BASE_DATE_TYPE.ridgepole_raising_date){
                                            record.start_date = this.mainData.ridgepole_raising_date;
                                            ganttData.links.push(
                                                    { source: mileStoneRidgepoleRaisingRecord.id, target: record.id, type: gantt.config.links.finish_to_start }
                                            );
                                        }
                                        record.calendar_id = this.judgeCalendarId(record.ex_property);
                                        break;
                                    case this.pConst.SERVER_TASK_TYPE.detail:
                                        record.type = gantt.config.types.project;
                                        record.start_date = null;
                                        record.duration = 0;
                                        record.render = 'split';
                                        record.ex_property.task_kbn = this.GANTT_TASK_KBN.RECEIVED;
                                        linkReceived[taskItem.quote_detail_id] = record;
                                        break;
                                    default:
                                        break;
                                }
                                ganttData['data'].push(record);
                            }

                            ganttData['data'].unshift(mileStoneParentRecord);
                            ganttData['data'].splice(1, 0, mileStoneConstructionRecord, mileStoneRidgepoleRaisingRecord);

                            for (const quoteDetailId in resWorks) {
                                const workList = resWorks[quoteDetailId];
                                for (var i=0; i<workList.length; i++) {
                                    const work = workList[i];
                                    var workRecord = JSON.parse(this.ganttTaskProperty);

                                    workRecord.id = id++;
                                    workRecord.text = work.text;
                                    workRecord.type = gantt.config.types.task;
                                    workRecord.start_date = moment(work.start_date).format(FORMAT_DATE);
                                    workRecord.end_date = moment(work.end_date).format(FORMAT_DATE);

                                    // 期間が曖昧な場合
                                    if (work.is_date_ambiguous) {
                                        workRecord.ex_property.is_date_ambiguous = work.is_date_ambiguous;
                                        workRecord.start_date = null;
                                        workRecord.end_date = null;
                                    }
                                    workRecord.parent = linkReceived[quoteDetailId].id;
                                    workRecord.ex_property.server_task_type = work.type;
                                    workRecord.ex_property.task_kbn = this.GANTT_TASK_KBN.WORK;
                                    workRecord.ex_property.construction_id = linkReceived[quoteDetailId].ex_property.construction_id;
                                    workRecord.ex_property.class_small_id = linkReceived[quoteDetailId].ex_property.class_small_id;
                                    workRecord.ex_property.quote_detail_id = linkReceived[quoteDetailId].ex_property.quote_detail_id;
                                    workRecord.ex_property.icon_info = work.icon_info;
                                    workRecord.ex_property.s_icon_info = work.s_icon_info;
                                    workRecord.ex_property.e_icon_info = work.e_icon_info;
                                    ganttData.data.push(workRecord);
                                }
                            }   
                            gantt.clearAll();
                            gantt.parse(ganttData);

                            this.loadingGantt = false;
                        }
                    }else{
                        alert(MSG_ERROR);
                    }
                }.bind(this))
                .catch(function(error) {
                    if (error.response.data.message) {
                        alert(error.response.data.message)
                    } else {
                        alert(MSG_ERROR)
                    }
                }.bind(this))
                .finally(() => {
                    this.loadingGantt = false;
                });
            }
        },
        // 雨延期ダイアログ表示・非表示
        showRainDlg(show){
            // エラーの初期化
            this.initErr(this.errors);

            this.dlgRain.show = show;
            if (show) { // OPEN
                // wijmoオブジェクトが生成されていない場合(初回OPEN時)があるのでDOM生成後
                this.$nextTick(function() {
                    this.dlgRain.wjRainDate.value = null;
                });
            }else{ // CLOSE
            }
        },
        // 雨延期リストに追加
        addRainDateList(){
            var dateText = this.dlgRain.wjRainDate.text;
            if (this.rmUndefinedBlank(dateText) != "") {
                var addItem = {
                    rain_date: dateText,
                }
                if (this.dlgRain.wjRainDateList.itemsSource.length > 0) {
                    var findIdx = this.dlgRain.wjRainDateList.itemsSource.findIndex(rec => {
                        return (rec.rain_date == dateText);
                    })
                    if (findIdx == -1) {
                        var itemsSource = JSON.parse(JSON.stringify(this.dlgRain.wjRainDateList.itemsSource));
                        itemsSource.push(addItem);
                        itemsSource.sort(function(a,b){
                            if(a.rain_date.replace('/', '') < b.rain_date.replace('/', '')) return -1;
                            if(a.rain_date.replace('/', '') > b.rain_date.replace('/', '')) return 1;
                            return 0;
                        });
                        this.dlgRain.wjRainDateList.itemsSource = itemsSource;
                    }
                }else{
                    this.dlgRain.wjRainDateList.itemsSource = [addItem];
                }
            }
        },
        // 雨延期リストから削除
        removeRainDateList(){
            this.dlgRain.wjRainDateList.itemsSource.splice(this.dlgRain.wjRainDateList.selectedIndex, 1);
            this.dlgRain.wjRainDateList.collectionView.refresh();
        },
        // 雨延期リストに適用
        applyRainDateList(){
            this.matterRainData = this.dlgRain.wjRainDateList.itemsSource;
            this.generateGrobalAndRainCalendar();
            var taskList = gantt.getTaskBy(task => task.calendar_id == this.GANTT_CALENDAR_KBN.GLOBAL_AND_RAIN);
            for (const idx in taskList) {
                var task = taskList[idx];
                var startDate = gantt.getClosestWorkTime({ date: task.start_date, dir: "future", task:task }); // 直近の稼働日(今日を含む未来)
                task.start_date = startDate;
                task.end_date = gantt.calculateEndDate({
                    start_date: task.start_date, duration: task.duration, task: task
                });
                gantt.updateTask(task.id);

                // オートスケジューリング状態でないなら開始日計算日数の更新を行う
                if (!this.isGanttAutoScheduling) {
                    this.calcGanttStartDateCalcDays(task);
                }
            }
            // オートスケジューリング状態なら全体を再スケジューリング
            if (this.isGanttAutoScheduling) {
                this.calcGanttProcessAutoSchedule();
            }
            this.calcGanttConstructionPeriod();  // 全工事区分再計算
            gantt.render(); // レンダリングしなければセルの色が変わらない
            this.dlgRain.show = false;
        },
        // 雨 & 雨+グローバルカレンダーを再生成　※gantt.mergeCalendars()は正しく動いてくれないので使用しない
        generateGrobalAndRainCalendar(){
            gantt.deleteCalendar(this.GANTT_CALENDAR_KBN.GLOBAL_AND_RAIN);
            gantt.deleteCalendar(this.GANTT_CALENDAR_KBN.RAIN);

            var globalAndRain = gantt.createCalendar(gantt.getCalendar(this.GANTT_CALENDAR_KBN.GLOBAL));  // グローバルカレンダーコピー
            globalAndRain.id = this.GANTT_CALENDAR_KBN.GLOBAL_AND_RAIN;   // ID変更
            gantt.addCalendar(globalAndRain);   // (グローバル + 雨)カレンダー
            gantt.addCalendar({ id: this.GANTT_CALENDAR_KBN.RAIN });   // 雨カレンダー
            for (const key in this.matterRainData) {
                const rainDate = this.matterRainData[key].rain_date;
                var convertedDate = new Date(moment(rainDate).format(FORMAT_DATE));
                gantt.getCalendar(this.GANTT_CALENDAR_KBN.GLOBAL_AND_RAIN).setWorkTime({ date:convertedDate, hours:false })
                gantt.getCalendar(this.GANTT_CALENDAR_KBN.RAIN).setWorkTime({ date:convertedDate, hours:false })
            }
        },
        // 画面表示切替
        switchDisplaySize(){
            var elem = [];
            
            // 全画面表示にしても表示されている要素を取得

            // サイドメニューが消えないので非表示にする
            var sideMenuElem = document.getElementsByClassName('sidemenu-bar');
            for (const item of sideMenuElem) {
                elem.push(item);
            }

            // 一部input-groupの要素が表示されてしまう為、非表示にする
            var matterDetailContentTopElem = document.getElementsByClassName('matter-detail-content-top');
            for (const item of matterDetailContentTopElem) {
                elem.push(item);
            }

            // 一部input-groupの要素が表示されてしまう為、非表示にする
            var psGanttMenuElem = document.getElementsByClassName('ps-gantt-menu-bar-1');
            for (const item of psGanttMenuElem) {
                elem.push(item);
            }

            for (const key in elem) {
                if (elem[key].style.visibility == 'hidden') {
                    elem[key].style.visibility = 'visible';
                }else{
                    elem[key].style.visibility = 'hidden';
                }
            }
        },
        // 初期表示用データ取得
        getIndexGantt(){
            var params = new URLSearchParams();
            params.append('matter_id', this.mainData.matter_id);

            this.loadingGantt = true;
            axios.post('/matter-detail/get/index-gantt', params)
            .then(function(response){
                if (response.data) {
                    // success
                    var resData = response.data;

                    var id = resData.next_id;
                    var resTasks = JSON.parse(resData.tasks);
                    var resLinks = JSON.parse(resData.links);
                    var resWorks = JSON.parse(resData.works);

                    var ganttData = {
                        data: [],
                        links: resLinks,
                    };

                    if (resTasks.length > 0) {
                        var linkReceived = {};
                        var linkWork = {}; 
                        var milestoneKbn = this.GANTT_TASK_KBN.MILESTONE_CONSTRUCTION_DATE;

                        var mileStoneParentRecord = this.generateGanttMileStoneTask(this.GANTT_TASK_KBN.MILESTONE_PARENT, id++);
                        ganttData['data'].push(mileStoneParentRecord);

                        for (const key in resTasks) {
                            const taskItem = resTasks[key];
                            var record = JSON.parse(this.ganttTaskProperty);
                            record.id = taskItem.id;
                            record.text = taskItem.text;
                            record.start_date = moment(taskItem.start_date).format(FORMAT_DATE);
                            record.duration = taskItem.duration,
                            record.parent = taskItem.parent,
                            record.ex_property.construction_id = taskItem.construction_id;
                            record.ex_property.class_small_id = taskItem.class_small_id;
                            record.ex_property.quote_detail_id = taskItem.quote_detail_id;
                            record.ex_property.server_task_type = taskItem.type;
                            switch (taskItem.type) {
                                case this.pConst.SERVER_TASK_TYPE.milestone:
                                    record.type = gantt.config.types.milestone;
                                    record.parent = mileStoneParentRecord.id;
                                    record.ex_property.task_kbn = milestoneKbn;
                                    milestoneKbn = this.GANTT_TASK_KBN.MILESTONE_RIDGEPOLE_RAISING_DATE;
                                    break;
                                case this.pConst.SERVER_TASK_TYPE.construction:
                                    record.type = gantt.config.types.task;
                                    record.ex_property.task_kbn = this.GANTT_TASK_KBN.CONSTRUCTION;
                                    break;
                                case this.pConst.SERVER_TASK_TYPE.process:
                                    record.type = gantt.config.types.task;
                                    record.ex_property.task_kbn = this.GANTT_TASK_KBN.PROCESS;
                                    record.ex_property.add_flg = taskItem.add_flg;
                                    record.ex_property.date_calc_flg = taskItem.date_calc_flg;
                                    record.ex_property.rain_flg = taskItem.rain_flg;
                                    record.ex_property.order_timing = taskItem.order_timing;
                                    record.ex_property.start_date_calc_days = taskItem.start_date_calc_days;
                                    record.ex_property.base_date_type = taskItem.base_date_type;
                                    record.calendar_id = this.judgeCalendarId(record.ex_property);
                                    break;
                                case this.pConst.SERVER_TASK_TYPE.detail:
                                    record.type = gantt.config.types.project;
                                    record.start_date = null;
                                    record.duration = 0;
                                    record.render = 'split';
                                    record.ex_property.task_kbn = this.GANTT_TASK_KBN.RECEIVED;
                                    linkReceived[taskItem.quote_detail_id] = record;
                                    break;
                                default:
                                    record.type = gantt.config.types.task;
                                    record.ex_property.task_kbn = this.GANTT_TASK_KBN.WORK;
                                    if (!linkWork[taskItem.quote_detail_id]) {
                                        linkWork[taskItem.quote_detail_id] = {};
                                    }
                                    linkWork[taskItem.quote_detail_id][taskItem.type] = record;
                                    break;
                            }
                            ganttData['data'].push(record);
                        }

                        for (const quoteDetailId in resWorks) {
                            const workList = resWorks[quoteDetailId];
                            for (var i=0; i<workList.length; i++) {
                                const work = workList[i];
                                var workRecord = JSON.parse(this.ganttTaskProperty);
                                if (linkWork[quoteDetailId] && linkWork[quoteDetailId][work.type]) {
                                    linkWork[quoteDetailId][work.type].ex_property.is_date_ambiguous = false;
                                    linkWork[quoteDetailId][work.type].ex_property.icon_info = work.icon_info;
                                    linkWork[quoteDetailId][work.type].ex_property.s_icon_info = work.s_icon_info;
                                    linkWork[quoteDetailId][work.type].ex_property.e_icon_info = work.e_icon_info;
                                }else{
                                    workRecord.id = id++;
                                    workRecord.text = work.text;
                                    workRecord.type = gantt.config.types.task;
                                    workRecord.start_date = moment(work.start_date).format(FORMAT_DATE);
                                    workRecord.end_date = moment(work.end_date).format(FORMAT_DATE);

                                    // 期間が曖昧な場合
                                    if (work.is_date_ambiguous) {
                                        workRecord.ex_property.is_date_ambiguous = work.is_date_ambiguous;
                                        workRecord.start_date = null;
                                        workRecord.end_date = null;
                                    }

                                    workRecord.parent = linkReceived[quoteDetailId].id;
                                    workRecord.ex_property.server_task_type = work.type;
                                    workRecord.ex_property.task_kbn = this.GANTT_TASK_KBN.WORK;
                                    workRecord.ex_property.construction_id = linkReceived[quoteDetailId].ex_property.construction_id;
                                    workRecord.ex_property.class_small_id = linkReceived[quoteDetailId].ex_property.class_small_id;
                                    workRecord.ex_property.quote_detail_id = linkReceived[quoteDetailId].ex_property.quote_detail_id;
                                    workRecord.ex_property.icon_info = work.icon_info;
                                    workRecord.ex_property.s_icon_info = work.s_icon_info;
                                    workRecord.ex_property.e_icon_info = work.e_icon_info;
                                    ganttData.data.push(workRecord);
                                }
                            }
                        }   
                        gantt.clearAll();
                        gantt.parse(ganttData);

                        this.moveGanttDate(moment().startOf('day').subtract(1, 'd').toDate());
                    }
                }else{
                    alert(MSG_ERROR);
                }
            }.bind(this))
            .catch(function(error) {
                if (error.response.data.message) {
                    alert(error.response.data.message)
                } else {
                    alert(MSG_ERROR)
                }
            }.bind(this))
            .finally(() => {
                this.loadingGantt = false;
            });
        },
        // 着工日,上棟日を元にデータを初期化する
        initGantt(){
            this.loadingGantt = true;

            var params = new URLSearchParams();
            params.append('matter_id', this.mainData.matter_id);
            params.append('construction_date', this.mainData.construction_date);
            params.append('quote_version_id_list', JSON.stringify(this.mainData.quote_version_id_list));
            params.append('order_id_list', JSON.stringify(this.mainData.order_id_list));

            axios.post('/matter-detail/init/gantt', params)
            .then( function (response) {
                if (response.data) {
                    // success
                    var resData = response.data;

                    this.ganttHolidayList = JSON.parse(resData.gantt_holiday_list);
                    this.companyHolidayList = JSON.parse(resData.company_holiday_list);
                    var resConstructionData = JSON.parse(resData.construction_data);
                    var resClassSmallData = JSON.parse(resData.class_small_data);
                    var resBaseClassSmallData = JSON.parse(resData.base_class_small_data);
                    var resClassSmallPair = JSON.parse(resData.class_small_pair);
                    var resConstructionReceivedData = JSON.parse(resData.construction_received_data);
                    var resClassSmallReceivedData = JSON.parse(resData.class_small_received_data);
                    var resWorkData = JSON.parse(resData.work_data);

                    var ganttData = {
                        data: [],
                        links: [],
                    };

                    var taskId = 1;
                    var constructionIdList = {};
                    var processTaskList = [];
                    const addTaskFunc = (prevTask, classSmall) => {
                        var processRecord = JSON.parse(this.ganttTaskProperty);
                        var processTaskId = taskId++;

                        processRecord.calendar_id = this.judgeCalendarId(classSmall);

                        var startDate = prevTask.start_date;
                        if (classSmall.base_date_type == this.pConst.BASE_DATE_TYPE.construction_date) {
                            // 基準日種別が着工日
                            processRecord.ex_property.base_date_type = this.pConst.BASE_DATE_TYPE.construction_date;
                            startDate = this.mainData.construction_date;
                            ganttData.links.push(
                                { source: mileStoneConstructionRecord.id, target: processTaskId, type: gantt.config.links.finish_to_start }
                            );
                        }else if(classSmall.base_date_type == this.pConst.BASE_DATE_TYPE.ridgepole_raising_date){
                            // 基準日種別が上棟日
                            processRecord.ex_property.base_date_type = this.pConst.BASE_DATE_TYPE.ridgepole_raising_date;
                            startDate = this.mainData.ridgepole_raising_date;
                            ganttData.links.push(
                                { source: mileStoneRidgepoleRaisingRecord.id, target: processTaskId, type: gantt.config.links.finish_to_start }
                            );
                        }

                        processRecord.id = processTaskId;
                        processRecord.text = classSmall.class_small_name;
                        processRecord.start_date = moment(startDate).format(FORMAT_DATE);
                        processRecord.duration = classSmall.construction_period_days;
                        processRecord.ex_property.server_task_type = this.pConst.SERVER_TASK_TYPE.process;
                        processRecord.ex_property.task_kbn = this.GANTT_TASK_KBN.PROCESS;
                        processRecord.ex_property.construction_id = classSmall.construction_id;
                        processRecord.ex_property.class_small_id = classSmall.id;
                        processRecord.ex_property.order_timing = classSmall.order_timing;
                        processRecord.ex_property.rain_flg = classSmall.rain_flg;
                        processRecord.ex_property.date_calc_flg = classSmall.date_calc_flg;
                        processRecord.ex_property.start_date_calc_days = classSmall.start_date_calc_days;

                        processTaskList.push(processRecord);
                        ganttData.data.push(processRecord);
                        if (resClassSmallReceivedData.hasOwnProperty(classSmall.id)) {
                            for (const quoteDetailId in resClassSmallReceivedData[classSmall.id]) {
                                const quoteDetail = resClassSmallReceivedData[classSmall.id][quoteDetailId];
                                var receivedRecord = JSON.parse(this.ganttTaskProperty);
                                var receivedTaskId = taskId++;
                                receivedRecord.id = receivedTaskId;
                                receivedRecord.text = quoteDetail.product_name;
                                receivedRecord.type = gantt.config.types.project;
                                receivedRecord.start_date = moment(startDate).format(FORMAT_DATE),
                                receivedRecord.duration = 1,
                                receivedRecord.parent = processTaskId;
                                receivedRecord.render = 'split';
                                receivedRecord.ex_property.server_task_type = this.pConst.SERVER_TASK_TYPE.detail;
                                receivedRecord.ex_property.task_kbn = this.GANTT_TASK_KBN.RECEIVED;
                                receivedRecord.ex_property.construction_id = classSmall.construction_id;
                                receivedRecord.ex_property.class_small_id = classSmall.id;
                                receivedRecord.ex_property.quote_detail_id = quoteDetail.id;
                                ganttData.data.push(receivedRecord);

                                // アイコン部分
                                if (resWorkData[quoteDetailId]) {
                                    for (var i=0; i < resWorkData[quoteDetailId].length; i++) {
                                        const work = resWorkData[quoteDetailId][i];
                                        var workRecord = JSON.parse(this.ganttTaskProperty);
                                        var workTaskId = taskId++;
                                        workRecord.id = workTaskId;
                                        workRecord.text = work.text;
                                        workRecord.type = gantt.config.types.task;
                                        workRecord.start_date = moment(work.start_date).format(FORMAT_DATE);
                                        workRecord.end_date = moment(work.end_date).format(FORMAT_DATE); 
                                        
                                        // 期間が曖昧な場合
                                        if (work.is_date_ambiguous) {
                                            workRecord.ex_property.is_date_ambiguous = work.is_date_ambiguous;
                                            workRecord.start_date = null;
                                            workRecord.end_date = null;
                                        }

                                        workRecord.parent = receivedTaskId;
                                        workRecord.ex_property.server_task_type = work.type;
                                        workRecord.ex_property.task_kbn = this.GANTT_TASK_KBN.WORK;
                                        workRecord.ex_property.construction_id = classSmall.construction_id;
                                        workRecord.ex_property.class_small_id = classSmall.id;
                                        workRecord.ex_property.quote_detail_id = quoteDetail.id;
                                        workRecord.ex_property.icon_info = work.icon_info;
                                        workRecord.ex_property.s_icon_info = work.s_icon_info;
                                        workRecord.ex_property.e_icon_info = work.e_icon_info;
                                        ganttData.data.push(workRecord);
                                    }
                                }
                            }   
                        }
                        if (resClassSmallPair[classSmall.id]) {
                            var childClassSmallList = resClassSmallPair[classSmall.id];
                            for (var i=0; i<childClassSmallList.length; i++) {
                                var childClassSmallId = childClassSmallList[i];
                                ganttData.links.push(
                                    { source: processTaskId, target: taskId, type: gantt.config.links.finish_to_start }
                                );
                                addTaskFunc(processRecord, resClassSmallData[childClassSmallId]);
                            }
                        }
                    };

                    // ガントチャート用のデータを作成
                    var mileStoneParentRecord = this.generateGanttMileStoneTask(this.GANTT_TASK_KBN.MILESTONE_PARENT, taskId++);
                    var mileStoneConstructionRecord = this.generateGanttMileStoneTask(this.GANTT_TASK_KBN.MILESTONE_CONSTRUCTION_DATE, taskId++);
                    mileStoneConstructionRecord.parent = mileStoneParentRecord.id;
                    var mileStoneRidgepoleRaisingRecord = this.generateGanttMileStoneTask(this.GANTT_TASK_KBN.MILESTONE_RIDGEPOLE_RAISING_DATE, taskId++);
                    mileStoneRidgepoleRaisingRecord.parent = mileStoneParentRecord.id;

                    ganttData['data'].push(mileStoneParentRecord);
                    ganttData['data'].push(mileStoneConstructionRecord);
                    ganttData['data'].push(mileStoneRidgepoleRaisingRecord);

                    for (const constructionIdx in resConstructionData) {
                        const constructionId = resConstructionData[constructionIdx];
                        const construction = this.pConstructionData[constructionId];
                        var constructionRecord = JSON.parse(this.ganttTaskProperty);
                        var constructionTaskId = taskId++;
                        constructionIdList[constructionId] = constructionTaskId;
                        constructionRecord.id = constructionTaskId;
                        constructionRecord.text = construction.construction_name;
                        constructionRecord.ex_property.server_task_type = this.pConst.SERVER_TASK_TYPE.construction;
                        constructionRecord.ex_property.task_kbn = this.GANTT_TASK_KBN.CONSTRUCTION;
                        constructionRecord.ex_property.construction_id = construction.id;
                        ganttData.data.push(constructionRecord);

                        // 工程が決まっていない受注確定データ ※工事区分に紐づいている受注確定データ
                        if (resConstructionReceivedData[construction.id]) {
                            for (const quoteDetailId in resConstructionReceivedData[construction.id]) {
                                const quoteDetail = resConstructionReceivedData[construction.id][quoteDetailId];
                                var receivedRecord = JSON.parse(this.ganttTaskProperty);
                                var receivedTaskId = taskId++;
                                receivedRecord.id = receivedTaskId;
                                receivedRecord.text = quoteDetail.product_name;
                                receivedRecord.type = gantt.config.types.project;
                                receivedRecord.start_date = moment(new Date()).format(FORMAT_DATE),
                                receivedRecord.duration = 1,
                                receivedRecord.parent = constructionTaskId;
                                receivedRecord.render = 'split';
                                receivedRecord.ex_property.server_task_type = this.pConst.SERVER_TASK_TYPE.detail;
                                receivedRecord.ex_property.task_kbn = this.GANTT_TASK_KBN.RECEIVED;
                                receivedRecord.ex_property.construction_id = construction.id;
                                receivedRecord.ex_property.quote_detail_id = quoteDetail.id;
                                ganttData.data.push(receivedRecord);

                                // アイコン部分
                                if (resWorkData[quoteDetailId]) {
                                    for (const idx in resWorkData[quoteDetailId]) {
                                        const work = resWorkData[quoteDetailId][idx];
                                        var workRecord = JSON.parse(this.ganttTaskProperty);
                                        var workTaskId = taskId++;
                                        workRecord.id = workTaskId;
                                        workRecord.text = work.text;
                                        workRecord.type = gantt.config.types.task;
                                        workRecord.start_date = moment(work.start_date).format(FORMAT_DATE);
                                        workRecord.end_date = moment(work.end_date).format(FORMAT_DATE);   

                                        if (work.is_date_ambiguous) {
                                            workRecord.ex_property.is_date_ambiguous = work.is_date_ambiguous;
                                            workRecord.start_date = null;
                                            workRecord.end_date = null;
                                        }

                                        workRecord.parent = receivedTaskId;
                                        workRecord.ex_property.server_task_type = work.type;
                                        workRecord.ex_property.task_kbn = this.GANTT_TASK_KBN.WORK;
                                        workRecord.ex_property.construction_id = construction.id;
                                        workRecord.ex_property.quote_detail_id = quoteDetail.id;
                                        workRecord.ex_property.icon_info = work.icon_info;
                                        workRecord.ex_property.s_icon_info = work.s_icon_info;
                                        workRecord.ex_property.e_icon_info = work.e_icon_info;
                                        ganttData.data.push(workRecord);
                                    }
                                }
                            }   
                        }

                        // 基準日種別が着工日,上棟日の小分類に対して処理を行う
                        for (const classSmallId in resBaseClassSmallData[constructionId]) {
                            const classSmall = resClassSmallData[classSmallId];
                            // 基準日種別が着工日でも上棟日でもない
                            if (this.rmUndefinedZero(classSmall.base_date_type) == 0) {
                                continue;
                            }
                            addTaskFunc(constructionRecord, classSmall);
                        }
                    }
                    // 工程タスクのparentをセットする
                    for (const idx in processTaskList) {
                        var linkProcessTask = processTaskList[idx];
                        linkProcessTask.parent = constructionIdList[linkProcessTask.ex_property.construction_id];
                    }
                    gantt.clearAll();
                    gantt.parse(ganttData);

                    this.loadingGantt = false;
                }else{
                    alert(MSG_ERROR);
                }
            }.bind(this))
            .catch(function (error) {
                if (error.response.data.message) {
                    alert(error.response.data.message);
                } else {
                    alert(MSG_ERROR);
                }
            }.bind(this))
            .finally(() => {
                this.loadingGantt = false;
            });
        },
        // 保存
        save(){
            this.loading = true;

            // エラーの初期化
            this.initErr(this.errors);
            var hasErr = false;
            if (this.rmUndefinedBlank(this.mainData.construction_date) === '') {
                this.errors.construction_date = MSG_ERROR_REQUIRED;
                hasErr = true;
            }
            if (this.rmUndefinedBlank(this.mainData.ridgepole_raising_date) === '') {
                this.errors.ridgepole_raising_date = MSG_ERROR_REQUIRED;
                hasErr = true;
            }
            if (hasErr) {
                this.loading = false;
                return false;
            }

            var params = new URLSearchParams();
            params.append('matter_id', this.mainData.matter_id);
            params.append('construction_date', this.rmUndefinedBlank(this.mainData.construction_date));
            params.append('ridgepole_raising_date', this.rmUndefinedBlank(this.mainData.ridgepole_raising_date));
            params.append('matter_rain_data', this.matterRainData);

            var index = 1;  // task.$indexはtreeの開閉状態を考慮しないといけない為,不使用
            var tasks = [];
            gantt.eachTask(function(task){
                if (this.rmUndefinedZero(task.ex_property.server_task_type) !== 0) {
                    tasks.push({
                        id: task.id,                                                    // ID
                        type: task.ex_property.server_task_type,                        // 種別(サーバ側)
                        add_flg: task.ex_property.add_flg,                              // 追加工程フラグ
                        construction_id: task.ex_property.construction_id,              // 工事区分ID
                        class_small_id: task.ex_property.class_small_id,                // 小分類ID
                        quote_detail_id: task.ex_property.quote_detail_id,              // 見積明細ID
                        text: task.text,                                                // タスク名
                        duration: task.duration,                                        // 期間
                        base_date_type: task.ex_property.base_date_type,                // 基準日種別
                        parent: task.parent,                                            // 親ID
                        start_date: moment(task.start_date).format(FORMAT_DATE),        // 開始日
                        end_date: moment(task.end_date).format(FORMAT_DATE),            // 終了日
                        sortorder: index++,                                             // 並び順
                        date_calc_flg: task.ex_property.date_calc_flg,                  // 日付計算フラグ
                        start_date_calc_days: task.ex_property.start_date_calc_days,    // 開始日計算日数
                        order_timing: task.ex_property.order_timing,                    // 発注タイミング
                        rain_flg: task.ex_property.rain_flg,                            // 雨延期フラグ
                    });   
                }
            }.bind(this));
            params.append('tasks', JSON.stringify(tasks));
            params.append('links', JSON.stringify(gantt.getLinks()));

            axios.post('/matter-detail/save', params)
            .then( function (response) {
                if (response.data) {
                    if (response.data.status) {
                        // nop
                        this.getSchedulerData();
                        if (response.data.msg) {
                            alert(response.data.msg);
                        }
                    }else{
                        if(response.data.msg){
                            alert(response.data.msg);
                        }else{
                            alert(MSG_ERROR);
                        }
                    }
                }else{
                    alert(MSG_ERROR);
                }
            }.bind(this))
            .catch(function (error) {
                if (error.response.data.message) {
                    alert(error.response.data.message)
                } else {
                    alert(MSG_ERROR)
                }
            }.bind(this))
            .finally(() => {
                this.loading = false;
            });
        },
        // 案件完了/完了解除
        completeMatter(){
            // エラーの初期化
            this.initErr(this.errors);

            var result = true;
            // ガントチャート上の未処理又は超過の売上アイコンを取得
            var taskData = gantt.getTaskBy(task =>
                            task.ex_property.task_kbn == this.GANTT_TASK_KBN.WORK && task.ex_property.icon_info
                            && task.ex_property.icon_info.icon == 'saleIcon' && !task.ex_property.icon_info.finished
                        );
            if (result && taskData.length > 0) {
                result = false;
                alert(this.pMessage.ERROR.complete_sales);
            }

            if (result && confirm(MSG_CONFIRM_MATTER_COMPLETE)) {
                this.loading = true;

                var params = new URLSearchParams();
                params.append('matter_id', this.mainData.matter_id);

                axios.post('/matter-detail/complete', params)
                .then( function (response) {
                    if (response.data) {
                        if (response.data.status) {
                            // location.reload();
                        }else{
                            if(response.data.msg){
                                alert(response.data.msg);
                            }else{
                                alert(MSG_ERROR);
                            }
                        }
                    }else{
                        alert(MSG_ERROR);
                    }
                }.bind(this))
                .catch(function (error) {
                    if (error.response.data.message) {
                        alert(error.response.data.message)
                    } else {
                        alert(MSG_ERROR)
                    }
                }.bind(this))
                .finally(() => {
                    this.loading = false;
                    window.onbeforeunload = null;
                    location.reload();
                });
            }
        },
        // 案件完了解除
        cancelCompleteMatter(){
            if (confirm(MSG_CONFIRM_MATTER_CANCEL_COMPLETE)) {
                this.loading = true;

                var params = new URLSearchParams();
                params.append('matter_id', this.mainData.matter_id);

                axios.post('/matter-detail/cancel-complete', params)
                .then( function (response) {
                    if (response.data) {
                        if (response.data.status) {
                            // location.reload();
                        }else{
                            // if(response.data.msg){
                            //     alert(response.data.msg);
                            // }else{
                            //     alert(MSG_ERROR);
                            // }
                        }
                    }else{
                        alert(MSG_ERROR);
                    }
                }.bind(this))
                .catch(function (error) {
                    if (error.response.data.message) {
                        alert(error.response.data.message)
                    } else {
                        alert(MSG_ERROR)
                    }
                }.bind(this))
                .finally(() => {
                    location.reload();
                    this.loading = false;
                });
            }
        },
        // 案件削除
        deleteMatter(){
            // エラーの初期化
            this.initErr(this.errors);

            if (confirm(MSG_CONFIRM_DELETE)) {
                this.loading = true;

                var params = new URLSearchParams();
                params.append('matter_id', this.mainData.matter_id);

                axios.post('/matter-detail/delete', params)
                .then( function (response) {
                    if (response.data) {
                        if (response.data.status) {
                            location.href = '/matter-list';;
                        }else{
                            if(response.data.msg){
                                alert(response.data.msg);
                            }else{
                                alert(MSG_ERROR);
                            }
                        }
                    }else{
                        alert(MSG_ERROR);
                    }
                }.bind(this))
                .catch(function (error) {
                    if (error.response.data.message) {
                        alert(error.response.data.message)
                    } else {
                        alert(MSG_ERROR)
                    }
                }.bind(this))
                .finally(() => {
                    this.loading = false;
                });
            }
        },
        // ガントチャートを再計算
        setGanttConstructionDate(){
            // errorリセット
            this.dlgGanttConstructionDate.error = '';
            this.initErr(this.errors);
            
            if (this.rmUndefinedBlank(this.dlgGanttConstructionDate.wjDate.text) === '') {
                this.dlgGanttConstructionDate.error = MSG_ERROR_REQUIRED;
                return false;
            }

            // 上棟日が未設定の場合は着工日の21日後
            if (this.rmUndefinedBlank(this.mainData.ridgepole_raising_date) == '') {
                this.mainData.ridgepole_raising_date = moment(this.dlgGanttConstructionDate.wjDate.text).add(21, 'd').format(FORMAT_DATE);
            }

            // 上棟日 > 着工日でない場合はエラー
            var constructionDate = new Date(this.dlgGanttConstructionDate.wjDate.text);
            var ridgepoleRaisingDate = new Date(this.mainData.ridgepole_raising_date);
            if (ridgepoleRaisingDate.getTime() > constructionDate.getTime()) {
                this.mainData.construction_date = moment(constructionDate).format(FORMAT_DATE)
                if (gantt.getTaskByTime().length > 0) {
                    this.calcGantt(true);   
                }
                this.dlgGanttConstructionDate.show = false;
            }else{
                this.dlgGanttConstructionDate.error = MSG_ERROR_MATTER_GANTT_STANDARD_DATE;
            }
        },
        // ガントチャートを再計算
        setGanttRidgepoleRaisingDate(){
            // errorリセット
            this.dlgGanttRidgepoleRaisingDate.error = '';
            this.initErr(this.errors);

            if (this.rmUndefinedBlank(this.dlgGanttRidgepoleRaisingDate.wjDate.text) === '') {
                this.dlgGanttRidgepoleRaisingDate.error = MSG_ERROR_REQUIRED;
                return false;
            }

            // 着工日が未設定の場合は上棟日の21日前
            if (this.rmUndefinedBlank(this.mainData.construction_date) == '') {
                this.mainData.construction_date = moment(this.dlgGanttRidgepoleRaisingDate.wjDate.text).subtract(21, 'd').format(FORMAT_DATE);
            }

            // 上棟日 > 着工日でない場合はエラー
            var constructionDate = new Date(this.mainData.construction_date);
            var ridgepoleRaisingDate = new Date(this.dlgGanttRidgepoleRaisingDate.wjDate.text);
            if (ridgepoleRaisingDate.getTime() > constructionDate.getTime()) {
                this.mainData.ridgepole_raising_date = moment(ridgepoleRaisingDate).format(FORMAT_DATE);
                if (gantt.getTaskByTime().length > 0) {
                    this.calcGantt(false);
                }
                this.dlgGanttRidgepoleRaisingDate.show = false;
            }else{
                this.dlgGanttRidgepoleRaisingDate.error = MSG_ERROR_MATTER_GANTT_STANDARD_DATE;
            }
        },
        // 開始基準となる工程を基に再計算する
        calcGantt(isConstructionDate){
            this.loadingGantt = true;
            var milestoneTask = null;
            if (isConstructionDate) {
                milestoneTask = gantt.getTaskBy(rec => rec.ex_property.task_kbn == this.GANTT_TASK_KBN.MILESTONE_CONSTRUCTION_DATE)[0];
                milestoneTask.start_date = new Date(this.mainData.construction_date);
            }else{
                milestoneTask = gantt.getTaskBy(rec => rec.ex_property.task_kbn == this.GANTT_TASK_KBN.MILESTONE_RIDGEPOLE_RAISING_DATE)[0];
                milestoneTask.start_date = new Date(this.mainData.ridgepole_raising_date);
            }
            milestoneTask.end_date = gantt.calculateEndDate(milestoneTask);
            gantt.updateTask(milestoneTask.id);

            this.calcGanttProcessAutoSchedule(milestoneTask.id);    // マイルストーン単位で自動スケジュール
            this.calcGanttConstructionPeriod();     // 全工事区分タスクを再計算

            // [工事区分-受注確定]配下の作業タスクに日付曖昧フラグを立てる
            // 作業タスク && サーバタスクタイプが存在する（DB保存対象） && 祖先に工程タスクIDが存在しない
            var workTaskList = gantt.getTaskBy(rec => 
                                    rec.ex_property.task_kbn == this.GANTT_TASK_KBN.WORK
                                    && this.rmUndefinedZero(rec.ex_property.server_task_type) != 0
                                    && this.rmUndefinedZero(this.getGanttAncestorsTaskId(rec.id)[this.GANTT_TASK_KBN.PROCESS]) == 0
                                )
            for (const idx in workTaskList) {
                var workTask = workTaskList[idx];
                workTask.ex_property.is_date_ambiguous = true;
                gantt.updateTask(workTask.id);
            }

            // [工事区分-工程-受注確定]配下の作業タスクに日付曖昧フラグを立てる
            // 作業タスク && サーバタスクタイプが存在する（DB保存対象）&& 祖先の工程タスクIDがマイルストーングループの中に存在する
            var milestoneConnectedGroup = gantt.getConnectedGroup(milestoneTask.id)['tasks'];   // 着工日と関連のあるタスク（先頭から末端まで）
            var workTaskList = gantt.getTaskBy(rec =>
                                        rec.ex_property.task_kbn == this.GANTT_TASK_KBN.WORK
                                        && this.rmUndefinedZero(rec.ex_property.server_task_type) != 0
                                        && milestoneConnectedGroup.indexOf(this.getGanttAncestorsTaskId(rec.id)[this.GANTT_TASK_KBN.PROCESS]) != -1
                                    );
            for (const idx in workTaskList) {
                var workTask = workTaskList[idx];
                workTask.ex_property.is_date_ambiguous = true;
                gantt.updateTask(workTask.id);
            }

            // 期間指定がされていない作業タスクの期間(開始日付～終了日付)を設定する
            this.calcGanttWorkPeriod();

            gantt.render(); // レンダリングしなければ範囲外にいったタスクが見えなくなる
            this.loadingGantt = false;
        },
        /**
         * 工程タスクを基に工事区分タスクの期間(開始日付～終了日付)を設定する
         */
        calcGanttConstructionPeriod(constructionTaskId=null){
            var constructionTaskList = gantt.getTaskBy(task => task.ex_property.task_kbn == this.GANTT_TASK_KBN.CONSTRUCTION);
            if (constructionTaskId) {
                constructionTaskList = gantt.getTaskBy(task => task.id == constructionTaskId);
            }
            for (var constructionTask of constructionTaskList) {
                var startDate = null;
                var endDate = null;
                var childTaskList = gantt.getTaskBy(function(task){
                    return task.parent == constructionTask.id && task.ex_property.task_kbn == this.GANTT_TASK_KBN.PROCESS;
                }.bind(this));
                for (const childTask of childTaskList) {
                    if (startDate) {
                        // 工事区分タスクの開始日付が工程タスクの開始日付より大きい場合、工程の開始日付をセットする
                        if (startDate.getTime() > childTask.start_date.getTime()) {
                            startDate = childTask.start_date;
                        } 
                    }else{
                        // 工事区分タスクの開始日付が設定されていない場合、工程タスクの開始日付をセットする
                        startDate = childTask.start_date;
                    }
                    if (endDate) {
                        // 工事区分タスクの終了日付が工程タスクの終了日付より小さい場合、工程タスクの終了日付をセットする
                        if (endDate.getTime() < childTask.end_date.getTime()) {
                            endDate = childTask.end_date;
                        }
                    }else{
                        // 工事区分タスクの終了日付が設定されていない場合、工程タスクの終了日付をセットする
                        endDate = childTask.end_date;
                    }
                }
                // 子の工程タスクが存在しなかった場合、着工日 or 上棟日をセット
                if (this.rmUndefinedBlank(startDate) == '') {
                    startDate = new Date(this.mainData.construction_date);
                    endDate = moment(startDate).add(1, 'd').toDate();
                }
                if(constructionTask.$no_start) { constructionTask.$no_start = false; }
                if(constructionTask.$no_end) { constructionTask.$no_end = false; }
                constructionTask.start_date = startDate;
                constructionTask.end_date = endDate;
                constructionTask.duration = gantt.calculateDuration(constructionTask.start_date, constructionTask.end_date);
                gantt.updateTask(constructionTask.id);
            }
        },
        /**
         * オートスケジューリング機能
         */
        calcGanttProcessAutoSchedule(taskId=null){
            var startPointTaskList = [];
            if (taskId) {
                startPointTaskList.push(gantt.getTask(taskId));
            }else{
                // 着工日基準のタスク、上棟日基準のタスク、ターゲットにはされてないがソース元にはなっているタスク（=着工日,上棟日から切り離されている & 後続の工程が存在する）
                startPointTaskList = gantt.getTaskBy(task =>
                                            task.ex_property.task_kbn == this.GANTT_TASK_KBN.MILESTONE_CONSTRUCTION_DATE
                                            || task.ex_property.task_kbn == this.GANTT_TASK_KBN.MILESTONE_RIDGEPOLE_RAISING_DATE
                                            || (task.$target.length == 0 && task.$source.length > 0)
                                        );
            }
            const funcAutoSchedule = function(currentTask, targetLinkList){
                for (const idx in targetLinkList) {
                    var link = gantt.getLink(targetLinkList[idx]);
                    var nextTask = gantt.getTask(link.target);

                    // 例）↓ガントチャートの1月1日に表示されている工期1日のタスクデータ
                    //   start_date: 2021/01/01, end_date: 2021/01/02, duration: 1
                    //   ※1月2日が労働日・非労働日なのかは関係無

                    // 例）↓ガントチャートの1月1日に表示されているマイルストーンデータ
                    //   start_date: 2021/01/01, end_date: 2021/01/01, duration: 0

                    // 計算の基点となる日付
                    var pointDate = currentTask.end_date;
                    if(currentTask.type == gantt.config.types.task){
                        var pointDate = moment(pointDate).subtract(1, 'd').toDate();    // 自タスクの最終日
                    }

                    var startDate = null;
                    var startDateCalcDays = nextTask.ex_property.start_date_calc_days;
                    if (Math.sign(nextTask.ex_property.start_date_calc_days) == 0
                        || Math.sign(nextTask.ex_property.start_date_calc_days) == 1
                    ) {
                        // 直近の有効日付（未来）
                        startDate = gantt.getClosestWorkTime({
                            date: pointDate, dir: "future", task: nextTask
                        });
                        // 基点となる日付と開始日付が異なり、開始日計算日数が0より大きい場合1引く
                        if (pointDate.getTime() != startDate.getTime() && startDateCalcDays > 0) {
                            startDateCalcDays--;
                        }
                        // 開始日付を探索
                        while (startDateCalcDays > 0) {
                            startDate = moment(startDate).add(1, 'd').toDate();
                            if (gantt.isWorkTime({date: startDate, task: nextTask})) {
                                startDateCalcDays--;
                            }
                        }
                    }else{
                        // 開始日計算日数が『負数』
                        startDate = gantt.getClosestWorkTime({
                            date: pointDate, dir: "past", task: nextTask
                        });
                        // 上記の処理で終了日付として有効な値も抽出してしまう為、労働日か確認を行う
                        if (!gantt.isWorkTime({date: startDate, task: nextTask})) {
                            startDate = moment(startDate).subtract(1).startOf('day').toDate();  // 1日引く（1日前は必ず労働日）
                        }
                        startDateCalcDays = startDateCalcDays * -1; // 正数変換
                        // 基点となる日付と開始日付が異なり、開始日計算日数が0より大きい場合1引く
                        if (pointDate.getTime() != startDate.getTime() && startDateCalcDays > 0) {
                            startDateCalcDays--;
                        }
                        // 開始日付を探索
                        while (startDateCalcDays > 0) {
                            startDate = moment(startDate).subtract(1, 'd').toDate();
                            if (gantt.isWorkTime({date: startDate, task: nextTask})) {
                                startDateCalcDays--;
                            }
                        }
                    }

                    nextTask.start_date = startDate;
                    nextTask.end_date = gantt.calculateEndDate(nextTask);
                    gantt.updateTask(nextTask.id);

                    funcAutoSchedule(nextTask, nextTask.$source);
                }
            }
            for (const idx in startPointTaskList) {
                var startPointTask = startPointTaskList[idx];
                funcAutoSchedule(startPointTask, startPointTask.$source);
            }
        },
        /**
         * 引数の工程と次の工程（リンク先）の開始日計算日数を計算する
         * ※オートスケジューリングモードの場合は引数の工程のみ
         */
        calcGanttStartDateCalcDays(task){
            if (task.ex_property.task_kbn == this.GANTT_TASK_KBN.PROCESS) {
                // 自タスクがターゲットとなっているリンク
                if (task.$target.length > 0) {
                    // 前工程の終了日付と自工程の開始日付の差分をセット
                    for (const idx in task.$target) {
                        const link = gantt.getLink(task.$target[idx]);
                        // prevTaskList.push(gantt.getTask(link.source));
                        var prevTask = gantt.getTask(link.source);

                        var durationStartDate = prevTask.end_date;
                        if(prevTask.type == gantt.config.types.task){
                            durationStartDate = moment(durationStartDate).subtract(1, 'd').toDate();    // タスクの最終日
                        }
                        var durationEndDate = task.start_date;
                        var duration = gantt.calculateDuration({
                            start_date: durationStartDate,
                            end_date: durationEndDate,
                            task
                        });
                        task.ex_property.start_date_calc_days = duration;
                        gantt.updateTask(task.id);
                    }
                }else{
                    task.ex_property.start_date_calc_days = 0;
                    gantt.updateTask(task.id);
                }

                // オートスケジューリングモードじゃない場合、次工程も調整
                if (!this.isGanttAutoScheduling) {
                    // 自タスクがソースとなっているリンク
                    if (task.$source.length > 0) {
                        for (const idx in task.$source) {
                            const link = gantt.getLink(task.$source[idx]);
                            // nextTaskList.push(gantt.getTask(link.target));
                            var nextTask = gantt.getTask(link.target);
                            var duration = gantt.calculateDuration({
                                start_date: moment(task.end_date).startOf('day').toDate(),
                                end_date: moment(nextTask.start_date).startOf('day').toDate(),
                                nextTask
                            });
                            nextTask.ex_property.start_date_calc_days = duration+1; // 1ずれているので加算する
                            gantt.updateTask(nextTask.id);
                        }    
                    }   
                }
            }
        },
        /**
         * 期間指定がされていない作業タスクの期間(開始日付～終了日付)を設定する
         *   ※再計算したい時は呼び出し元で再計算したい作業タスクの日付曖昧フラグを立てる
         */
        calcGanttWorkPeriod(){
            // is_date_
            var taskList = gantt.getTaskBy(rec => rec.ex_property.is_date_ambiguous);
            for (const idx in taskList) {
                var workTask = taskList[idx];
                workTask.$no_start = false;
                workTask.$no_end = false;
                var receivedTask = gantt.getTask(workTask.parent);    // 受注確定タスク
                if (workTask.ex_property.class_small_id) {
                    // 工事区分-工程-受注確定-作業(アイコン)の情報を書き換える
                    // 発注タイミング：作業の期間を工程の発注タイミングから計算する
                    // 希望納品予定日：工程の開始日付
                    var processTask = gantt.getTask(receivedTask.parent);   // 工程タスク
                    if (workTask.ex_property.server_task_type == this.pConst.SERVER_TASK_TYPE.order_timing) {
                        // 発注タイミング
                        workTask.start_date = moment(processTask.start_date).add(-processTask.ex_property.order_timing, 'd').toDate();
                        workTask.end_date = moment(workTask.start_date).add(1, 'd').toDate();
                    }else{
                        // 希望納品予定日
                        workTask.start_date = moment(processTask.start_date).toDate();
                        workTask.end_date = moment(workTask.start_date).add(1, 'd').toDate();
                    }
                }else{
                    // 工事区分-受注確定-作業(アイコン)の情報を書き換える
                    // 発注タイミング：工事区分配下で最も開始日付が速い工程の発注タイミングから計算する。無い場合は工事区分の開始日付
                    // 希望納品予定日：所属工程が無い場合、工事区分の開始日付に合わせる
                    var constructionTask = gantt.getTask(receivedTask.parent);    // 工事区分タスク
                    var childrenTaskId = gantt.getChildren(constructionTask.id);  // 配下のタスクを取得（工程 or 受注確定）
                    var minStartDateTask = null;

                    if (workTask.ex_property.server_task_type == this.pConst.SERVER_TASK_TYPE.order_timing) {
                        if (childrenTaskId.length > 0) {
                            for (const key in childrenTaskId) {
                                const childTaskId = childrenTaskId[key];
                                var childTask = gantt.getTask(childTaskId);
                                if (childTask.ex_property.task_kbn != this.GANTT_TASK_KBN.PROCESS) {
                                    continue;
                                }
                                if (!minStartDateTask || minStartDateTask.start_date.getTime() > childTask.start_date.getTime()) {
                                    minStartDateTask = childTask;
                                }
                            }
                            if (minStartDateTask) {
                                workTask.start_date = moment(minStartDateTask.start_date).add(-minStartDateTask.ex_property.order_timing, 'd').toDate();
                                workTask.end_date = moment(workTask.start_date).add(1, 'd').toDate();
                            }                            
                        }
                    }

                    if (!minStartDateTask) {
                        workTask.start_date = constructionTask.start_date;
                        workTask.end_date = moment(workTask.start_date).add(1, 'd').toDate();    
                    }
                }
                workTask.duration = 1;
                gantt.updateTask(workTask.id);
            }
        },
        // 基準日種別を設定
        updateBaseDateTypeByTask(taskId){
            var task = gantt.getTask(taskId);
            task.ex_property.base_date_type = 0;
            
            if (task.$target.length == 1) {
                const link = gantt.getLink(task.$target[0]);
                var sourceTask = gantt.getTask(link.source);
                if (sourceTask.ex_property.task_kbn == this.GANTT_TASK_KBN.MILESTONE_CONSTRUCTION_DATE) {
                    task.ex_property.base_date_type = this.pConst.BASE_DATE_TYPE.construction_date;
                }else if (sourceTask.ex_property.task_kbn == this.GANTT_TASK_KBN.MILESTONE_RIDGEPOLE_RAISING_DATE) {
                    task.ex_property.base_date_type = this.pConst.BASE_DATE_TYPE.ridgepole_raising_date;
                }
            }
            gantt.updateTask(task.id);
        },
        /**
         * カレンダーの種別を判定する
         */
        judgeCalendarId(classSmall){
            var result = null;
            if (classSmall.date_calc_flg == this.FLG_ON && classSmall.rain_flg == this.FLG_ON) {
                // global_and_rain
                result = this.GANTT_CALENDAR_KBN.GLOBAL_AND_RAIN;
            }else if(classSmall.date_calc_flg == this.FLG_ON){
                // global
                result = this.GANTT_CALENDAR_KBN.GLOBAL;
            }else if(classSmall.rain_flg == this.FLG_ON){
                // rain
                result = this.GANTT_CALENDAR_KBN.RAIN;
            }else{
                // no_calendar
                result = this.GANTT_CALENDAR_KBN.NO_CALENDAR;
            }
            return result;
        },
        /**
         * マイルストーンタスクを作成する
         */
        generateGanttMileStoneTask(kbn, id){
            var record = JSON.parse(this.ganttTaskProperty);
            switch(kbn){
                case this.GANTT_TASK_KBN.MILESTONE_PARENT:
                    record.id = id;
                    record.text = 'マイルストーン';
                    record.type = gantt.config.types.project;
                    record.render = 'split';
                    record.ex_property.task_kbn = this.GANTT_TASK_KBN.MILESTONE_PARENT;
                    break;
                case this.GANTT_TASK_KBN.MILESTONE_CONSTRUCTION_DATE:
                        record.id = id;
                        record.text = '着工日';
                        record.type = gantt.config.types.milestone;
                        record.start_date = this.mainData.construction_date;
                        record.duration = 1;
                        record.ex_property.server_task_type = this.pConst.SERVER_TASK_TYPE.milestone;
                        record.ex_property.task_kbn = this.GANTT_TASK_KBN.MILESTONE_CONSTRUCTION_DATE;
                    break;
                case this.GANTT_TASK_KBN.MILESTONE_RIDGEPOLE_RAISING_DATE:
                        record.id = id;
                        record.text = '上棟日';
                        record.type = gantt.config.types.milestone;
                        record.start_date = this.mainData.ridgepole_raising_date;;
                        record.duration = 1;
                        record.ex_property.server_task_type = this.pConst.SERVER_TASK_TYPE.milestone;
                        record.ex_property.task_kbn = this.GANTT_TASK_KBN.MILESTONE_RIDGEPOLE_RAISING_DATE;
                    break;
                default:
                    break;
            }
            return record;
        },
        /**
         * 工事区分直下をソートする
         */
        sortGanttTask(taskId=null){
            const sortFunc = function(a, b){
                if (a.ex_property.task_kbn != b.ex_property.task_kbn) {
                    // aとbで区分が異なる（工程 or 受注確定）
                    // 受注確定が必ず上
                    return a.ex_property.task_kbn == this.GANTT_TASK_KBN.PROCESS ? 1 : -1;
                }else if(a.ex_property.task_kbn == this.GANTT_TASK_KBN.PROCESS && b.ex_property.task_kbn == this.GANTT_TASK_KBN.PROCESS){
                    // aとbで区分が同じ（工程）
                    // 開始日付が速い方が上
                    return a.start_date.getTime() > b.start_date.getTime() ? 1: (a.start_date.getTime() < b.start_date.getTime() ? -1 : 0);
                }
            }.bind(this);

            var taskList = [];

            if (taskId) {
                var task = gantt.getTask(taskId);
                if(task.ex_property.task_kbn == this.GANTT_TASK_KBN.CONSTRUCTION){
                    taskList.push();
                }
            }else{
                taskList = gantt.getTaskBy(task => task.ex_property.task_kbn == this.GANTT_TASK_KBN.CONSTRUCTION);
            }

            for (const idx in taskList) {
                const taskId = taskList[idx].id;
                gantt.sort(sortFunc, false, taskId);
            }
        },
        // 特定の日付の位置に移動する
        moveGanttDate(date){
            var position = gantt.posFromDate(date);
            gantt.scrollTo(position);
        },
        // 引数タスクIDの親を取得する（祖先）
        getGanttAncestorsTaskId(taskId){
            var taskAncestors = {}
            // タスクの親となる可能性があるのは、工事区分・工程・受注確定タスクのみ
            taskAncestors[this.GANTT_TASK_KBN.CONSTRUCTION] = null;
            taskAncestors[this.GANTT_TASK_KBN.PROCESS] = null;
            taskAncestors[this.GANTT_TASK_KBN.RECEIVED] = null;

            const setTaskAncestors = function(parentTaskId){
                var parentTask = gantt.getTask(parentTaskId);
                switch (parentTask.ex_property.task_kbn) {
                    case this.GANTT_TASK_KBN.CONSTRUCTION:
                        taskAncestors[this.GANTT_TASK_KBN.CONSTRUCTION] = parentTask.id;
                        break;
                    case this.GANTT_TASK_KBN.PROCESS:
                        taskAncestors[this.GANTT_TASK_KBN.PROCESS] = parentTask.id;
                        setTaskAncestors(parentTask.parent);
                        break;
                    case this.GANTT_TASK_KBN.RECEIVED:
                        taskAncestors[this.GANTT_TASK_KBN.RECEIVED] = parentTask.id;
                        setTaskAncestors(parentTask.parent);
                        break;
                    default:
                        break;
                }
            }.bind(this);
            var task = gantt.getTask(taskId);
            setTaskAncestors(task.parent);
            return taskAncestors;
        },
        /**************** 排他制御メソッド ****************/
        // 編集モード
        edit() {
            this.loading = true
            var params = new URLSearchParams();
            params.append('screen', this.pScreenName);
            params.append('keys', this.rmUndefinedBlank(this.mainData.matter_id));

            axios.post('/common/lock', params)
            .then( function (response) {
                // this.loading = false;
                if (response.data.status) {
                    if (response.data.isLocked) {
                        alert(MSG_EDITING);
                        location.reload();
                    } else {
                        // グリッドのReadOnly解除のためにリロード
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
        // 強制ロック解除
        unlock() {
            if (!confirm(MSG_CONFIRM_UNLOCK)) {
                return;
            }
            
            this.loading = true;
            
            var params = new URLSearchParams();
            params.append('screen', this.pScreenName);
            params.append('keys', this.rmUndefinedBlank(this.mainData.matter_id));

            axios.post('/common/gain-lock', params)
            .then( function (response) {
                // this.loading = false
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
                    alert(error.response.data.message);
                } else {
                    alert(MSG_ERROR);
                }
            }.bind(this))
        },
        // ロック解除
        releaseLock(url=null) {
            if (!confirm(MSG_CONFIRM_LOCK_RELEASE)) {
                return;
            }

            this.loading = true;

            // ロック解放
            var params = new URLSearchParams();
            params.append('screen', this.pScreenName);
            params.append('keys', this.rmUndefinedBlank(this.mainData.matter_id));

            axios.post('/common/release-lock', params)
            .then( function (response) {
                // this.loading = false
                if (response.data && this.rmUndefinedBlank(url) !== '') {
                    window.onbeforeunload = null;
                    location.href = url;
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
        },
        /**************** 他 ****************/
        // 戻る
        back() {
            // 遷移元URL取得
            var query = window.location.search;
            var rtnUrl = this.getLocationUrl(query);
            if (rtnUrl == '') {
                rtnUrl = '/matter-list';
            }
            var listUrl = rtnUrl + query;

            if (this.isReadOnly) {
                location.href = listUrl;
            }else{
                //  操作している人がロックしている場合
                this.releaseLock(listUrl);  // ロック解除
            }
        },
        /***************** 共通 *****************/
    }
};
</script>