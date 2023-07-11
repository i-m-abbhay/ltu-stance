<template>
    <div>
        <loading-component :loading="loading" />
        <div class="form-horizontal save-form">
            <form id="saveForm" name="saveForm" class="form-horizontal">
                <!-- ボタン -->
                <div class="col-md-12 lock-info">
                    <div class="row">
                        <label v-show="maindata.complete_flg == FLG_ON" class="attention-color">案件完了済&emsp;</label>
                        <label class="form-control-static" v-show="(rmUndefinedBlank(lockdata.id) != '')">ロック日時：{{ lockdata.lock_dt|datetime_format }}&emsp;</label>
                        <label class="form-control-static" v-show="(rmUndefinedBlank(lockdata.id) != '')">ロック者：{{ lockdata.lock_user_name }}&emsp;</label>
                        <button type="button" class="btn btn-danger pull-right btn-unlock" v-on:click="unlock" v-show="isLocked" v-bind:disabled="maindata.complete_flg == FLG_ON">ロック解除</button>
                        <button type="button" class="btn btn-primary pull-right btn-edit" v-on:click="edit" v-show="isShowEditBtn" v-bind:disabled="maindata.complete_flg == FLG_ON">編集</button>
                        <div class="pull-right">
                            <p class="btn btn-default btn-editing" v-on:click="lockRelease" v-show="(!isLocked && !isShowEditBtn)">編集中</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <!-- 見積ヘッダー -->
                    <div class="quote-header">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="control-label">得意先名</label>
                                <input type="text" class="form-control" v-model="main.customer_name" v-bind:readonly="true">
                            </div>
                            <div class="col-md-3">
                                <label class="control-label">案件名</label>
                                <input type="text" class="form-control" v-model="main.matter_name" v-bind:readonly="true">
                            </div>
                            <div class="col-md-2">
                                <label class="control-label">案件番号</label>
                                <input type="text" class="form-control" v-model="main.matter_no" v-bind:readonly="true">
                            </div>
                            <div class="col-md-2">
                                <label class="control-label">部門名</label>
                                <input type="text" class="form-control" v-model="main.department_name" v-bind:readonly="true">
                            </div>
                            <div class="col-md-2">
                                <label class="control-label">担当者名</label>
                                <input type="text" class="form-control" v-model="main.staff_name" v-bind:readonly="true">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-2">
                                <label class="control-label">見積番号</label>
                                <input type="text" class="form-control" v-model="main.quote_no" v-bind:readonly="true">
                            </div>
                            <div class="col-md-3">
                                <label class="control-label">得意先特別単価の使用</label>
                                <el-radio-group class="col-md-12" v-model="main.special_flg" v-bind:disabled="isReadOnly">
                                    <el-radio class="" :label="FLG_ON">あり</el-radio>
                                    <el-radio class="" :label="FLG_OFF">なし</el-radio>
                                </el-radio-group>
                            </div>
                            <div class="col-md-3">
                                <label class="control-label">相手先担当者名</label>
                                <wj-auto-complete class="form-control"
                                    search-member-path="name,kana" 
                                    display-member-path="name" 
                                    :items-source="personList" 
                                    selected-value-path="id"
                                    :selected-value="main.person_id"
                                    :isDisabled="isReadOnly"
                                    :isRequired=false
                                    :lost-focus="selectPerson"
                                    :initialized="initPersonCombo"
                                    :max-items="personList.length"
                                >
                                </wj-auto-complete>
                            </div>
                            <div class="col-md-4">
                                <label class="control-label">工事期間</label>
                                <input type="text" class="form-control" v-model="main.construction_period" v-bind:readonly="isReadOnly">
                                <p class="text-danger">{{ errors.quote.construction_period }}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-7">
                                <label class="control-label">工事概要</label>
                                <textarea class="form-control" v-model="main.construction_outline" v-bind:readonly="isReadOnly"></textarea>
                            </div>
                            <div class="col-md-3">
                                <label class="control-label">個人客宛名</label>
                                <input type="text" class="form-control" v-model="main.quote_report_to" v-bind:readonly="isReadOnly">
                                <p class="text-danger">{{ errors.quote.quote_report_to }}</p>
                            </div>
                            <div class="col-md-2">
                                <div class="pull-right">
                                <label class="control-label col-md-12">&nbsp;</label>
                                <button type="button" class="btn btn-primary btn-new" @click="showDlgNewVersion=true;" v-bind:disabled="isReadOnly || maindata.no_matter_flg || isCopy">版新規作成</button>
                                <button type="button" class="btn btn-danger btn-delete" v-on:click="del" v-bind:disabled="isReadOnly || maindata.no_matter_flg">見積削除</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- タブ -->
                    <div class="quote-version">
                        <el-tabs type="border-card" @tab-click="tabClick">
                            <el-tab-pane v-for="(verData) in main.version_list" :key="verData.quote_version"
                                :label="`第${verData.quote_version}版 ${verData.caption_tab}`" >
                                <div class="row">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-danger btn-apply" @click="apply" v-show="verData.status != STATUS.APPLYING && verData.status != STATUS.APPROVED" v-bind:disabled="isReadOnly || maindata.no_matter_flg">承認申請</button>
                                        <button type="button" class="btn btn-danger btn-apply-cancel" @click="applyCancel" v-show="verData.approval_status == APPROVAL_STATUS.NOT_APPROVED" v-bind:disabled="isReadOnly">承認申請取消</button>
                                        <button type="button" class="btn btn-primary btn-save" @click="save" v-bind:disabled="isReadOnly || verData.status == STATUS.APPLYING || (verData.quote_version != ZERO && verData.status == STATUS.APPROVED)">保存</button>
                                        <!-- <button type="button" class="btn btn-primary btn-save" @click="saveZero" v-show="verData.quote_version == ZERO" v-bind:disabled="isReadOnly || maindata.no_matter_flg">0版保存</button> -->
                                        <button type="button" class="btn btn-primary btn-new" @click="showDlgCopyVersion=true" v-bind:disabled="isReadOnly || maindata.no_matter_flg || isCopy">複製して新規作成</button>
                                        <button type="button" class="btn btn-warning btn-print" @click="print(verData.quote_version_id)" v-bind:disabled="rmUndefinedZero(verData.quote_version_id) == 0">印刷</button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="col-md-12">
                                            <div class="col-md-4">
                                                <label class="control-label">版名</label>
                                                <input type="text" class="form-control" v-model="verData.caption" v-bind:readonly="isReadOnly || verData.quote_version == ZERO || verData.status == STATUS.APPLYING || verData.status == STATUS.APPROVED" >
                                                <p class="text-danger">{{ errors.quoteVerTab[verData.quote_version].quote_version_caption }}</p>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="control-label">見積提出日</label>
                                                <wj-input-date
                                                    :value="verData.quote_create_date"
                                                    :isRequired=false
                                                    :isDisabled="isReadOnly"
                                                    :lost-focus="inputPrintDate"
                                                ></wj-input-date>
                                                <p class="text-danger">{{ errors.quoteVerTab[verData.quote_version].quote_create_date }}</p>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="control-label">税率 %</label>
                                                <input type="text" class="form-control text-right" v-model="verData.tax_rate" v-bind:readonly="taxRateLockFlg == FLG_ON || verData.status == STATUS.APPLYING || verData.status == STATUS.APPROVED" >
                                                <p class="text-danger">{{ errors.quoteVerTab[verData.quote_version].tax_rate }}</p>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="col-md-4">
                                                <label class="control-label">見積提出期限</label>
                                                <wj-input-date
                                                    :value="verData.quote_limit_date"
                                                    :isRequired=false
                                                    :isDisabled="isReadOnly || verData.status == STATUS.APPLYING || verData.status == STATUS.APPROVED"
                                                    :lost-focus="inputQuoteLimitDate"
                                                ></wj-input-date>
                                                <p class="text-danger">{{ errors.quoteVerTab[verData.quote_version].quote_limit_date }}</p>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="control-label">見積有効期限</label>
                                                <input type="text" class="form-control" v-model="verData.quote_enabled_limit_date" v-bind:readonly="isReadOnly || verData.status == STATUS.APPLYING || verData.status == STATUS.APPROVED" >
                                                <p class="text-danger">{{ errors.quoteVerTab[verData.quote_version].quote_enabled_limit_date }}</p>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="control-label">支払条件</label>
                                                <wj-auto-complete class="form-control"
                                                    search-member-path="value_code" 
                                                    display-member-path="value_text_1" 
                                                    :items-source="payconList" 
                                                    selected-value-path="value_code"
                                                    :selected-value="verData.payment_condition"
                                                    :isDisabled="isReadOnly || verData.status == STATUS.APPLYING || verData.status == STATUS.APPROVED"
                                                    :isRequired=false
                                                    :lost-focus="selectPaycon"
                                                    :max-items="payconList.length"
                                                >
                                                </wj-auto-complete>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- 添付ファイル -->
                                    <div class="col-md-6">
                                        <div class="col-md-12 quote-ver-row">
                                            <label class="control-label">添付資料</label>
                                            <label class="file-up" for="file-up">
                                                <svg class="file-up-icon"><use width="25" height="25" xlink:href="#clipIcon"/></svg>
                                            </label>
                                            <input style="display:none;" type="file" multiple="multiple" @change="onDrop" id="file-up" v-bind:disabled="isReadOnly">
                                            <div class="file-operation-area well-sm" @dragleave.prevent @dragover.prevent @drop.prevent="onDrop">
                                                <div v-for="(item, iCnt) in uploadFileList[verData.quote_version]" :key="iCnt">
                                                    <div class="form-group">
                                                        <div class="col-md-12">
                                                            <label class="file-row col-md-10 col-sm-8 col-xs-8" for="`${verData.quote_version}-file-${iCnt}`">{{ item.file_name }}</label>
                                                            <el-button type="danger" icon="el-icon-delete" circle size="mini" v-show="item.file != ''" @click="deleteFile(iCnt)" v-bind:disabled="isReadOnly || verData.status == STATUS.APPLYING || verData.status == STATUS.APPROVED"></el-button>
                                                            <el-button type="success" icon="el-icon-download" circle size="mini" v-show="!item.is_tmp && !item.copy_flg" @click="downloadFile(item.file_name)"></el-button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-danger">{{ errors.quoteVerTab[verData.quote_version].upload_file }}</p>
                                    </div>
                                </div>
                                <div class="col-md-12 quote-ver-row">
                                    <div class="col-md-1">
                                        <label class="control-label">総計</label>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="control-label">仕入計</label>
                                    </div>
                                    <div class="col-md-2">
                                        <label readonly="readonly" class="form-control text-right">{{verData.cost_total|comma_format}}</label>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="control-label">売上計</label>
                                    </div>
                                    <div class="col-md-2">
                                        <label readonly="readonly" class="form-control text-right">{{verData.sales_total|comma_format}}</label>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="control-label">粗利計</label>
                                    </div>
                                    <div class="col-md-2">
                                        <label readonly="readonly" class="form-control text-right">{{verData.profit_total|comma_format}}</label>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="control-label">粗利率</label>
                                    </div>
                                    <div class="col-md-1">
                                        <label readonly="readonly" class="form-control text-right">{{version_profit_rate}}</label>
                                    </div>
                                </div>
                                <div class="col-md-12 quote-ver-row">
                                    <div class="col-md-1">
                                        <label class="control-label">階層別計</label>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="control-label">仕入計</label>
                                    </div>
                                    <div class="col-md-2">
                                        <label readonly="readonly" class="form-control text-right">{{layer_cost_total|comma_format}}</label>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="control-label">売上計</label>
                                    </div>
                                    <div class="col-md-2">
                                        <label readonly="readonly" class="form-control text-right">{{layer_sales_total|comma_format}}</label>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="control-label">粗利計</label>
                                    </div>
                                    <div class="col-md-2">
                                        <label readonly="readonly" class="form-control text-right">{{layer_profit_total|comma_format}}</label>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="control-label">粗利率</label>
                                    </div>
                                    <div class="col-md-1">
                                        <label readonly="readonly" class="form-control text-right">{{layer_profit_rate}}</label>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 quote-ver-row">
                                        <button type="button" class="btn btn-primary btn-complete" @click="showDlgReleaseEstimation=false;showDlgCompEstimationPrepare()" v-show="verData.quote_version != ZERO" v-bind:disabled="isReadOnly || maindata.no_matter_flg || verData.status == STATUS.APPLYING || verData.status == STATUS.APPROVED">積算完了</button>
                                        <button type="button" class="btn btn-primary btn-release" @click="showDlgReleaseEstimation=true;showDlgCompEstimationPrepare()" v-show="verData.quote_version != ZERO" v-bind:disabled="isReadOnly || maindata.no_matter_flg || verData.status == STATUS.APPLYING || verData.status == STATUS.APPROVED || verData.complete_flg_list.length == 0">積算完了解除</button>
                                        <button type="button" class="btn btn-primary btn-print" @click="printTarget(verData.quote_version_id)" v-bind:disabled="isReadOnly || rmUndefinedZero(verData.quote_version_id) == 0">指定印刷</button>
                                        <button type="button" class="btn btn-primary btn-save" @click="receivedOrder" v-show="verData.quote_version != ZERO" v-bind:disabled="isReadOnly || maindata.no_matter_flg">受注登録</button>
                                        <button type="button" class="btn btn-primary btn-delete" @click="cancelReceivedOrder" v-show="verData.quote_version == ZERO" v-bind:disabled="isReadOnly">受注取消</button>
                                        <button type="button" class="btn btn-primary btn-new" @click="showDlgAddTreeLayerPrepare" v-show="verData.quote_version != ZERO" v-bind:disabled="isReadOnly || verData.status == STATUS.APPLYING || verData.status == STATUS.APPROVED">工事種別追加</button>
                                        <button type="button" class="btn btn-primary btn-new" @click="toLayer" v-show="verData.quote_version != ZERO" v-bind:disabled="isReadOnly || verData.status == STATUS.APPLYING || verData.status == STATUS.APPROVED">階層作成</button>
                                        <button type="button" class="btn btn-primary btn-new" @click="toSet" v-show="verData.quote_version != ZERO" v-bind:disabled="isReadOnly || verData.status == STATUS.APPLYING || verData.status == STATUS.APPROVED">一式作成</button>
                                        <button type="button" class="btn btn-warning btn-operation" @click="addRow(false)" v-show="verData.quote_version != ZERO" v-bind:disabled="isReadOnly || gridReadOnlyFlg || verData.status == STATUS.APPLYING || verData.status == STATUS.APPROVED">空の行を上に挿入</button>
                                        <button type="button" class="btn btn-warning btn-operation" @click="addRow(true)" v-show="verData.quote_version != ZERO" v-bind:disabled="isReadOnly || gridReadOnlyFlg || verData.status == STATUS.APPLYING || verData.status == STATUS.APPROVED">空の行を下に挿入</button>
                                        <button type="button" class="btn btn-warning btn-delete" @click="deleteRows" v-show="verData.quote_version != ZERO" v-bind:disabled="isReadOnly || verData.status == STATUS.APPLYING || verData.status == STATUS.APPROVED">行を削除</button>
                                        <button type="button" class="btn btn-warning btn-operation" @click="showRoundFraction" v-show="verData.quote_version != ZERO" v-bind:disabled="isReadOnly || verData.status == STATUS.APPLYING || verData.status == STATUS.APPROVED">端数丸め</button>
                                        <button type="button" class="btn btn-warning btn-operation" @click="showGrossProfitSetting" v-show="verData.quote_version != ZERO" v-bind:disabled="isReadOnly || verData.status == STATUS.APPLYING || verData.status == STATUS.APPROVED">粗利一括設定</button>
                                        <button type="button" class="btn btn-warning btn-operation" @click="showQuoteImport" v-show="verData.quote_version != ZERO" v-bind:disabled="isReadOnly || verData.status == STATUS.APPLYING || verData.status == STATUS.APPROVED">見積取込</button>
                                        <button type="button" class="btn btn-warning btn-operation" @click="downloadCSV">Excelダウンロード</button>
                                    </div>
                                </div>

                                <!-- 階層 -->
                                <div class="col-md-2 layer-div">
                                    <div v-bind:id="'quoteLayerTree-' + verData.quote_version"></div>
                                </div>
                                
                                <!-- グリッド -->
                                <div class="col-md-10 quote-grid-div">
                                    <div v-bind:id="'quoteDetailGrid-' + verData.quote_version"></div>
                                </div>

                                <!-- コメント入力欄 -->
                                <div class="col-md-12 quote-ver-row">&nbsp;</div>
                                <div class="col-md-12 col-sm-12">
                                    <div class="col-md-4 col-sm-12">
                                        <label class="col-md-12 col-sm-4">営業支援コメント</label>
                                        <textarea class="col-md-12 col-sm-8" v-model="verData.sales_support_comment" v-bind:readonly="isReadOnly || verData.status == STATUS.APPLYING || verData.status == STATUS.APPROVED"></textarea>
                                    </div>
                                    <div class="col-md-4 col-sm-12">
                                        <label class="col-md-12 col-sm-4">稟議用コメント　</label>
                                        <textarea class="col-md-12 col-sm-8" v-model="verData.approval_comment" v-bind:readonly="isReadOnly || verData.status == STATUS.APPLYING || verData.status == STATUS.APPROVED"></textarea>
                                    </div>
                                    <div class="col-md-4 col-sm-12">
                                        <label class="col-md-12 col-sm-4">顧客向けコメント</label>
                                        <textarea class="col-md-12 col-sm-8" v-model="verData.customer_comment" v-bind:readonly="isReadOnly || verData.status == STATUS.APPLYING || verData.status == STATUS.APPROVED"></textarea>
                                    </div>
                                </div>
                            </el-tab-pane>
                        </el-tabs>
                    </div>

                </div>
                <!-- ボタン -->
                <div class="col-md-12">
                    <button type="button" class="btn btn-warning btn-back pull-right" v-on:click="back">戻る</button>
                </div>
            </form>
        </div>

        <!-- 新規見積初回ダイアログ -->
        <el-dialog
            :title="showDlgMatterAdd == false && showDlgMatterSelect == true ? '案件選択／新規登録':'新規作成'"
            :visible.sync="showDlgFirst" :showClose=false :closeOnClickModal=false :closeOnPressEscape=false>
            <div v-if="showDlgMatterAdd == true">
                <p>案件情報を入力してください。</p>
                <label class="control-label">得意先</label>
                <wj-auto-complete class="form-control"
                    search-member-path="customer_name, customer_short_name, customer_kana" 
                    display-member-path="customer_name" 
                    :initialized="initCustomerCombo"
                    :selectedIndexChanged="changeCustomerCombo"
                    :items-source="customerList" 
                    selected-value-path="id"
                    :isRequired=false
                    :max-items="customerList.length"
                >
                </wj-auto-complete>
                <p class="text-danger">{{ errors.dialog.customer_id }}</p>

                <label class="control-label">施主名</label>
                <wj-auto-complete class="form-control"
                    search-member-path="owner_name" 
                    display-member-path="owner_name" 
                    :initialized="initOwnerCombo"
                    :items-source="ownerList" 
                    selected-value-path="owner_name"
                    :selected-index=-1
                    :isRequired=false
                    :max-items="ownerList.length"
                >
                </wj-auto-complete>
                <p class="text-danger">{{ errors.dialog.owner_name }}</p>

                <label class="control-label">建築種別</label>
                <el-radio-group v-model="architectureType">
                    <el-radio v-for="arch in architectureList" :key="arch.value_code" :label="arch.value_code">{{ arch.value_text_1 }}</el-radio>
                </el-radio-group>
                <p class="text-danger">{{ errors.dialog.architecture_type }}</p>

                <div class="dlg-footer">
                    <el-button @click="confirmMatter" class="btn-create">案件作成</el-button>
                    <el-button @click="back" class="btn-cancel">キャンセル</el-button>
                </div>
            </div>

            <div v-if="showDlgMatterAdd == false && showDlgMatterSelect == true">
                <div class="dlg-body">
                    <el-radio-group v-model="newMatterSelected">
                        <el-radio class="form-group col-md-10" :label="FLG_ON">
                            <label @click="newMatterSelected=FLG_ON">新規</label>
                            <div class="col-md-12">
                                <label class="control-label in-el-radio-group" @click="newMatterSelected=FLG_ON">{{ main.matter_name }}</label>
                            </div>
                        </el-radio>
                        <el-radio class="form-group col-md-10" :label="FLG_OFF">
                            <label @click="newMatterSelected=FLG_OFF">選択</label>
                            <div class="col-md-12">
                                <wj-auto-complete class="form-control"
                                    search-member-path="matter_name" 
                                    display-member-path="matter_no_name" 
                                    :initialized="initSameMatterCombo"
                                    :items-source="sameMatterList" 
                                    selected-value-path="matter_no"
                                    :selected-value="main.matter_no"
                                    :isReadonly="true"
                                    :max-items="sameMatterList.length"
                                >
                                </wj-auto-complete>
                            </div>
                        </el-radio>
                    </el-radio-group>
                    <p class="text-danger">{{ errors.dialog.matter_no }}</p>
                </div>
                <div class="dlg-footer">
                    <el-button @click="confirmQuote" class="btn-create">決定</el-button>
                    <el-button @click="back" class="btn-cancel">キャンセル</el-button>
                </div>
            </div>

            <div v-if="showDlgMatterAdd == false && showDlgMatterSelect == false">
                <div class="row">
                    <div class="col-md-12"><label class="control-label">以前作成した見積を採用しますか？</label></div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <el-radio v-model="selectQuoteToCopy" :label="FLG_ON"><label @click="selectQuoteToCopy=FLG_ON">採用する</label>
                    </el-radio></div>
                    <div class="col-md-12">
                        <div>案件名</div>
                        <wj-auto-complete class="form-control" id="acMatterName"
                            search-member-path="matter_name"
                            display-member-path="matter_name"
                            selected-value-path="matter_no"
                            :isDisabled="selectQuoteToCopy==FLG_OFF"
                            :initialized="initMatterNameCombo"
                            :textChanged="textChangedMatterNameCombo"
                            :selectedIndexChanged="changeIdxMatterNameCombo"
                            :selected-index="-1"
                            :min-length=1
                            :delay=10
                            :max-items="matterList.length"
                            :items-source="matterList">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-12">
                        <div>見積番号</div>
                        <wj-auto-complete class="form-control" id="acQuote"
                            search-member-path="quote_no"
                            display-member-path="quote_no"
                            selected-value-path="quote_no"
                            :isDisabled="selectQuoteToCopy==FLG_OFF"
                            :initialized="initQuoteCombo"
                            :textChanged="textChangedQuoteCombo"
                            :selectedIndexChanged="changeIdxQuoteCombo"
                            :selected-index="-1"
                            :min-length=1
                            :delay=10
                            :max-items="quoteList.length"
                            :items-source="quoteList">
                        </wj-auto-complete>
                    </div>
                    <p class="col-md-12 text-danger">{{ errors.dialog.select_quote_to_copy }}</p>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <el-radio v-model="selectQuoteToCopy" :label="FLG_OFF"><label @click="selectQuoteToCopy=FLG_OFF">採用しない</label></el-radio>
                    </div>
                    <p class="col-md-12">見積作成項目を選択してください&nbsp;<el-checkbox v-model="isQuoteRequestAll" :indeterminate="isQuoteRequestIndeterminate"  @change="changeAllQreq()" v-bind:disabled="selectQuoteToCopy==FLG_ON"></el-checkbox></p>
                    <div class="col-md-12">
                        <el-checkbox-group v-model="selectQuoteRequest">
                            <el-checkbox v-for="row in qreqList" :label="row.construction_id" :key="row.construction_id" v-bind:disabled="selectQuoteToCopy==FLG_ON">{{ row.construction_name }}</el-checkbox>
                        </el-checkbox-group>
                    </div>
                    <p class="col-md-12 text-danger">{{ errors.dialog.select_quote_request }}</p>
                </div>
                <br>
                <div class="row">
                    <p class="col-md-12"><label class="control-label">第{{ main.version_list.length }}版</label></p>
                    <div class="col-md-12"><label class="control-label">版名</label></div>
                    <div class="col-md-12"><input type="text" v-model="newVersionName" class="form-control"></div>
                    <p class="col-md-12 text-danger">{{ errors.dialog.new_version_name }}</p>
                </div>

                <div class="dlg-footer">
                    <el-button @click="createFirstVersion" class="btn-create" v-bind:disabled="isCopy || isProcessing()">作成</el-button>
                </div>
            </div>
        </el-dialog>
        <!-- 版コピーダイアログ -->
        <el-dialog title="版をコピー" :visible.sync="showDlgCopyVersion" >
            <p><label class="control-label">第{{ main.version_list.length }}版</label></p>
            <label class="control-label">版名</label>
            <input type="text" v-model="newVersionName" class="form-control">
            <p class="text-danger">{{ errors.dialog.new_version_name }}</p>

            <span slot="footer" class="dialog-footer">
                <el-button @click="createCopyVersion" class="btn-create" v-bind:disabled="isCopy || isProcessing()">作成</el-button>
            </span>
        </el-dialog>
        <el-dialog title="版を新規作成" :visible.sync="showDlgNewVersion" >
            <div class="row">
                <div class="col-md-12"><label class="control-label">以前作成した見積を採用しますか？</label></div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <el-radio v-model="selectQuoteToCopy" :label="FLG_ON"><label @click="selectQuoteToCopy=FLG_ON">採用する</label>
                </el-radio></div>
                <div class="col-md-12">
                    <div>案件名</div>
                    <wj-auto-complete class="form-control" id="acMatterName"
                        search-member-path="matter_name"
                        display-member-path="matter_name"
                        selected-value-path="matter_no"
                        :isDisabled="selectQuoteToCopy==FLG_OFF"
                        :initialized="initMatterNameCombo"
                        :textChanged="textChangedMatterNameCombo"
                        :selectedIndexChanged="changeIdxMatterNameCombo"
                        :selected-index="-1"
                        :min-length=1
                        :delay=10
                        :max-items="matterList.length"
                        :items-source="matterList">
                    </wj-auto-complete>
                </div>
                <div class="col-md-12">
                    <div>見積番号</div>
                    <wj-auto-complete class="form-control" id="acQuote"
                        search-member-path="quote_no"
                        display-member-path="quote_no"
                        selected-value-path="quote_no"
                        :isDisabled="selectQuoteToCopy==FLG_OFF"
                        :initialized="initQuoteCombo"
                        :textChanged="textChangedQuoteCombo"
                        :selectedIndexChanged="changeIdxQuoteCombo"
                        :selected-index="-1"
                        :min-length=1
                        :delay=10
                        :max-items="quoteList.length"
                        :items-source="quoteList">
                    </wj-auto-complete>
                </div>
                <p class="col-md-12 text-danger">{{ errors.dialog.select_quote_to_copy }}</p>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <el-radio v-model="selectQuoteToCopy" :label="FLG_OFF"><label @click="selectQuoteToCopy=FLG_OFF">採用しない</label></el-radio>
                </div>
                <p class="col-md-12">見積作成項目を選択してください&nbsp;<el-checkbox v-model="isQuoteRequestAll" :indeterminate="isQuoteRequestIndeterminate"  @change="changeAllQreq()" v-bind:disabled="selectQuoteToCopy==FLG_ON"></el-checkbox></p>
                <div class="col-md-12">
                    <el-checkbox-group v-model="selectQuoteRequest">
                        <el-checkbox v-for="row in qreqList" :label="row.construction_id" :key="row.construction_id" v-bind:disabled="selectQuoteToCopy==FLG_ON">{{ row.construction_name }}</el-checkbox>
                    </el-checkbox-group>
                </div>
                <p class="col-md-12 text-danger">{{ errors.dialog.select_quote_request }}</p>
            </div>
            <br>
            <div class="row">
                <p class="col-md-12"><label class="control-label">第{{ main.version_list.length }}版</label></p>
                <div class="col-md-12"><label class="control-label">版名</label></div>
                <div class="col-md-12"><input type="text" v-model="newVersionName" class="form-control"></div>
                <p class="col-md-12 text-danger">{{ errors.dialog.new_version_name }}</p>
            </div>
            <span slot="footer" class="dialog-footer">
                <el-button @click="createNewVersion" class="btn-create" v-bind:disabled="isProcessing()">作成</el-button>
                <el-button @click="showDlgNewVersion=false" class="btn-cancel">キャンセル</el-button>
            </span>
        </el-dialog>
        <!-- 積算完了ダイアログ -->
        <el-dialog :title="(showDlgReleaseEstimation)? '積算完了解除': '積算完了'" :visible.sync="showDlgCompEstimation">
            <el-checkbox-group v-model="selectEstimationList">
                <el-checkbox v-for="row in choiceEstimationList" :label="row.construction_id" :key="row.construction_id">{{ row.construction_name }}</el-checkbox>
            </el-checkbox-group>
            <p class="text-danger">{{ errors.dialog.select_estimation }}</p>
            <span slot="footer" class="dialog-footer">
                <el-button v-if="showDlgReleaseEstimation" @click="compEstimation" class="btn-release">積算完了解除</el-button>
                <el-button v-else @click="compEstimation" class="btn-complete">積算完了</el-button>
                <el-button @click="showDlgCompEstimation = false" class="btn-cancel">キャンセル</el-button>
            </span>
        </el-dialog>
        <!-- 工事種別追加ダイアログ -->
        <el-dialog title="工事種別追加" :visible.sync="showDlgAddTreeLayer">
            <el-checkbox-group v-model="addTreeLayerList">
                <el-checkbox v-for="row in qreqList" :label="row.construction_id" :key="row.construction_id" :disabled="existConstructionIds.indexOf(row.construction_id) >= 0" >{{ row.construction_name }}</el-checkbox>
            </el-checkbox-group>
            <span slot="footer" class="dialog-footer">
                <el-button @click="addTreeLayer" class="btn-create" v-bind:disabled="isProcessing()">追加</el-button>
                <el-button @click="showDlgAddTreeLayer = false" class="btn-cancel">キャンセル</el-button>
            </span>
        </el-dialog>
        <!-- 端数丸めダイアログ -->
        <el-dialog title="端数丸め" :visible.sync="showDlgRoundFraction" :closeOnClickModal=false>
            <label class="col-md-12 col-sm-12 col-xs-12 control-label">単位</label>
            <div class="col-md-12">
                <el-radio-group class="col-md-12" v-model="roundUnit">
                    <el-radio class="" :label="ROUND_UNIT.JU">10円未満</el-radio>
                    <el-radio class="" :label="ROUND_UNIT.HYAKU">100円未満</el-radio>
                    <el-radio class="" :label="ROUND_UNIT.SEN">1,000円未満</el-radio>
                    <el-radio class="" :label="ROUND_UNIT.MAN">10,000円未満</el-radio>
                </el-radio-group>
                <p class="text-danger">{{ errors.dialog.round_unit }}</p>
            </div>
            <label class="col-md-12 col-sm-12 col-xs-12 control-label">丸め方法</label>
            <div class="col-md-12">
                <el-radio-group class="col-md-12" v-model="roundType">
                    <el-radio class="" :label="ROUND_TYPE.FLOOR">切り捨て</el-radio>
                    <el-radio class="" :label="ROUND_TYPE.CEIL">切り上げ</el-radio>
                    <el-radio class="" :label="ROUND_TYPE.ROUND">四捨五入</el-radio>
                </el-radio-group>
                <p class="text-danger">{{ errors.dialog.round_type }}</p>
            </div>
            <span slot="footer" class="dialog-footer">
                <el-button @click="roundFraction" class="btn-change">実行</el-button>
                <el-button @click="showDlgRoundFraction = false" class="btn-cancel">キャンセル</el-button>
            </span>
        </el-dialog>
        <!-- 粗利一括設定ダイアログ -->
        <el-dialog title="粗利一括設定" :visible.sync="showDlgGrossProfitSetting" :closeOnClickModal=false>
            <label class="col-md-12 col-sm-12 col-xs-12 control-label">粗利率</label>
            <div class="col-md-5 col-sm-5 col-xs-5">
                <input type="text" v-model="grossProfitRate" class="form-control" @keyup="grossProfitRate=zenkakuToHankaku(grossProfitRate)" @change="grossProfitRate=zenkakuToHankaku(grossProfitRate)">
                <p class="text-danger" style="font-size: 12px;">{{ errors.dialog.change_gross_profit_rate }}</p>
            </div>
            <p class="col-md-1 col-sm-1 col-xs-1" style="padding: 8 0;">％</p>
            <span slot="footer" class="dialog-footer">
                <el-button @click="changeGrossProfit" class="btn-change">変更</el-button>
                <el-button @click="showDlgGrossProfitSetting = false" class="btn-cancel">キャンセル</el-button>
            </span>
        </el-dialog>
        <!-- 見積取込ダイアログ -->
        <el-dialog title="見積取込" :visible.sync="showDlgQuoteImport" :closeOnClickModal=false>
            <div class="col-md-12 col-sm-12 col-xs-12 control-label">ファイル種別を選択してください</div>
            <div class="col-md-12">
                <el-radio-group class="col-md-12" v-model="quoteImportKbn">
                    <div><el-radio class="" :label="QUOTE_IMPORT.QUOTE_DETAIL_EXCEL.KBN">見積明細取込用Excelフォーマット</el-radio></div>
                    <div><el-radio class="" :label="QUOTE_IMPORT.BEE_CONNECT_CSV.KBN">Bee-Connect共通フォーマット（CSV）</el-radio></div>
                    <div><el-radio class="" :label="QUOTE_IMPORT.WOOD_CAD_CSV.KBN">木材CAD（CSV）</el-radio></div>
                </el-radio-group>
                <p class="text-danger">{{ errors.dialog.quote_import }}</p>
                <div v-for="(item, iCnt) in errors.dialog.quote_import_arr" :key="iCnt">
                    <div class="text-danger">{{ item }}</div>
                </div>
            </div>
            <div class="col-md-12" @dragleave.prevent @dragover.prevent>
                <label id="quoteImportFileLabel" class="file_label col-md-6 col-sm-8">
                    <input type="file" id="importFile" class="file-upload-btn" :accept="quoteImportAcceptType" v-bind:disabled="isReadOnly" />
                    <span for="file">{{ quoteImportFile.label }}</span>
                </label>
            </div>
            <span slot="footer" class="dialog-footer">
                <el-button @click="quoteImport" class="btn-import" v-bind:disabled="isProcessing()">取込</el-button>
                <el-button @click="showDlgQuoteImport=false" class="btn-cancel">キャンセル</el-button>
            </span>
        </el-dialog>
    </div>
</template>

<style>
.lock-info {
    text-align: right;
}
.layer-div {
    border: 1px solid #bbb;
    padding-right: 0 !important;
    padding-left: 0 !important;
}
.quote-grid-div {
    padding-right: 0 !important;
    padding-left: 0 !important;
    height: 500px;
}
.quote-gridcell-rowspan2 {
    height: 60px !important;
}
.quote-header {
    margin-bottom: 10px;
}
.quote-version {
    margin-bottom: 10px;
}
.quote-ver-row {
    margin-top: 3px;
    margin-bottom: 3px;
}
.multi-grid-btn{
    border: none; 
    border-radius: 0px;
    width: 99.8%;
    height: 100%;
    padding: 0;
}
.file-up-icon {
    height: 25px !important;
    width: 25px !important;
    cursor: pointer;
}
.file-up{
    margin-bottom: 0px;
    vertical-align: bottom;
}
.file-operation-area{
    padding: 5px;
    border: 1px solid #ccd0d2;
    height: 140px;
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
.dlg-footer {
    text-align: right;
}
.wj-cells .wj-cell.wj-state-selected {
    background: #0085c7 !important;
}
.wj-cells .wj-cell.wj-state-multi-selected {
    background: #80adbf !important;
}
</style>

<script>
// グリッド行初期値
// true/falseのデータ行があると、その列はチェックボックスになる
// 数値のデータ行があると、その行は数値しか入力できなくなる
var QUOTE_INIT_ROW = {
    // ↓グリッド表示項目↓
    btn_up: '',                       // 行移動（上）
    btn_down: '',                     // 行移動（下）
    chk: false,                       // 行チェック
    product_code: '',                 // 品番
    product_name: '',                 // 商品名
    model: '',                        // 型式・規格
    maker_name: '',                   // メーカー名
    supplier_name: '',                // 仕入先名
    quote_quantity: 1.00,             // 見積数
    unit: '',                         // 単位
    stock_quantity: 100,              // 管理数
    stock_unit: '',                   // 管理数単位
    regular_price: 0,                 // 定価
    cost_kbn: 0,                      // 仕入区分
    sales_kbn: 2,                     // 販売区分
    cost_unit_price: 0,               // 仕入単価
    sales_unit_price: 0,              // 販売単価
    cost_makeup_rate: 0.00,           // 仕入掛率
    sales_makeup_rate: 0.00,          // 販売掛率
    cost_total: 0,                    // 仕入総額
    sales_total: 0,                   // 販売総額
    gross_profit_rate: 0.00,          // 粗利率
    profit_total: 0,                  // 粗利総額
    memo: '',                         // 備考
    row_print_flg: true,              // 明細印字フラグ
    price_print_flg: true,          　// 売価印字フラグ
    sales_use_flg: false,             // 階層販売金額利用
    // ↓グリッド非表示項目↓
    quote_detail_id: 0,               // 見積明細ID
    construction_id: 0,               // 工事区分ID
    layer_flg: 0,                     // 階層フラグ
    parent_quote_detail_id: 0,        // 親見積明細ID
    seq_no: 0,                        // 連番
    depth: 0,                         // 深さ
    filter_tree_path: '',             // 階層パス
    set_flg: 0,                       // 一式フラグ
    product_id: 0,                    // 商品ID
    maker_id: 0,                      // メーカーID
    supplier_id: 0,                   // 仕入先ID
    min_quantity: 0.01,               // 最小単位数（0.01は無形品の初期値）
    order_lot_quantity: 0.01,         // 発注ロット（0.01は無形品の初期値）
    quantity_per_case: 0.00,          // 入り数     ※商品マスタ仮登録用
    set_kbn: '',                      // セット区分 ※商品マスタ仮登録用
    class_big_id: 0,                  // 大分類ID   ※商品マスタ仮登録用
    class_middle_id: 0,               // 中分類ID   ※商品マスタ仮登録用
    class_small_id: 0,                // 小分類ID   ※商品マスタ仮登録用
    tree_species: 0,                  // 樹種       ※商品マスタ仮登録用
    grade: 0,                         // 等級       ※商品マスタ仮登録用
    length: 0,                        // 長さ       ※商品マスタ仮登録用
    thickness: 0,                     // 厚み       ※商品マスタ仮登録用
    width: 0,                         // 幅         ※商品マスタ仮登録用
    received_order_flg: 0,            // 受注確定フラグ
    complete_flg: 0,                  // 積算完了フラグ  
    copy_quote_detail_id: 0,          // コピー元見積明細ID
    copy_received_order_flg: 0,       // コピー元受注確定フラグ
    copy_complete_flg: 0,             // コピー元積算完了フラグ
    add_flg: 0,                       // 追加部材フラグ
    over_quote_detail_id: 0,          // 数量超過元明細ID

    intangible_flg: 1,                // 無形品フラグ ※フロント制御用
    order_id_list: '',                // 
    product_maker_id: 0,
};

// 階層特定用一時変数
var currentFilterTreePath = 0;

// 見積依頼項目
var requestedItems = [];
var qreqIdByRequestedItems = {};

import * as wjNav from '@grapecity/wijmo.nav';
import * as wjCore from '@grapecity/wijmo';
import * as wjcInput from '@grapecity/wijmo.input';
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjMultiRow from '@grapecity/wijmo.grid.multirow';
import * as wjcXlsx from '@grapecity/wijmo.xlsx';
import { CustomGridEditor } from '../CustomGridEditor.js';

export default {
    data: () => ({
        loading: false,
        isReadOnly: true,
        isShowEditBtn: false,
        isLocked: false,

        LEN_LIMIT_VERSION_NAME: 30,
        // 0版のみ特別扱い
        ZERO: 0,
        // 単価区分：標準
        PRICE_KBN_NORMAL : 0,
        // 見積版ステータス
        STATUS: {
            EDITING:  0,
            APPLYING: 1,
            APPROVED: 2,
            SENDBACK: 3
        },
        // 見積版ステータス
        APPROVAL_STATUS: {
            NOT_APPROVED: 0,
            APPROVING: 1,
            APPROVED: 2,
            SENDBACK: 3
        },
        // 丸め
        ROUND_UNIT: {
            JU: 10,
            HYAKU: 100,
            SEN: 1000,
            MAN: 10000
        },
        ROUND_TYPE: {
            FLOOR: 0,
            CEIL: 1,
            ROUND: 2,
        },

        // 見積取込
        QUOTE_IMPORT: {
            QUOTE_DETAIL_EXCEL: {
                KBN: 0,
                ACCEPT: '.xls,.xlsx,.xlsm',
                START_ROW_NUMBER: 3,
                COLS_OF_LOWER_END: ['品番', '商品名'],
                REQUIRE_COL: [
                    '品番', '商品名', '型式・規格', 'メーカー名', '仕入先名', '見積数', '単位', '最小単位数','管理数単位',
                    '定価', '仕入区分', '仕入単価', '仕入掛率', '販売区分', '販売単価', '販売掛率', '備考'
                ],
                NUMERIC_COL: ['見積数', '最小単位数', '定価', '仕入単価', '仕入掛率', '販売単価', '販売掛率'],
            },
            BEE_CONNECT_CSV: {
                KBN: 1,
                ACCEPT: '.csv',
            },
            WOOD_CAD_CSV: {
                KBN: 2,
                ACCEPT: '.csv',
                NUMBER_OF_COLUMNS: 12,
                IMPORT_COL: {
                    kbnA: 1, kbnB: 2, woodCadCode: 3 , gradeCadCode: 4,
                    thickness: 5, width: 6, length: 7, quantity: 8,
                    cubicMater: 9, unit: 10, regularPrice: 11, volumePerOne: 12,
                },
                NUMERIC_COL: [3, 4, 5, 6, 7, 8, 11, 12],    // 3:樹種番号, 4:等級番号, 5:厚み(mm), 6:幅(mm), 7:長さ(mm), 8:材木数, 11:単価, 12:1本あたりの体積(㎥)
            },
        },

        // GETパラメータ
        getQuery: '',

        main: {},
        lock: {},

        personCombo: null,

        // 階層合計額
        costLayerTotal: 0,
        salesLayerTotal: 0,
        profitLayerTotal: 0,
        // 合計額
        costTotal: 0,
        salesTotal: 0,
        rofitTotal: 0,

        // 新規作成
        showDlgMatterAdd: false,
        matterCustomerCombo: null,
        ownerNameCombo: null,
        architectureType: '',

        showDlgMatterSelect: false,
        sameMatterList: [],
        sameMatterCombo: null,
        newMatterSelected: 1,

        showDlgFirst: false,
        newVersionName: '',
        selectQuoteRequest: [],
        orgSelectQuoteRequest: [],
        isQuoteRequestAll: false,
        isQuoteRequestIndeterminate: false,
        matterNameCombo:null,
        quoteCombo:null,
        selectQuoteToCopy:0,    // 見積コピー対象選択ラジオ

        // 積算完了
        showDlgCompEstimation: false,
        choiceEstimationList: [],
        selectEstimationList: [],

        // 積算完了解除
        showDlgReleaseEstimation: false,
        deselectEstimationList: [],

        // 粗利一括設定
        showDlgGrossProfitSetting: false,
        grossProfitRate: 0.00,

        // 端数丸め
        showDlgRoundFraction: false,
        roundUnit: 10,
        roundType: 1,

        // 見積取込
        showDlgQuoteImport: false,
        quoteImportKbn: 0,
        quoteImportAcceptType: null,
        quoteImportData: null,
        quoteImportFile: {
            label: LBL_FILE,
            content: null,
        },

        // ファイルアップロード
        uploadFileList: [],
        // 削除ファイル
        deleteFileList: [],

        // 版コピー
        showDlgCopyVersion: false,
        // 版新規作成
        showDlgNewVersion: false,
        // 開いている版
        focusVersion: 0,
        isCopy: false,
        // quoteLimitDate: null,

        // 階層データ
        wjTreeViewControl: [],
        treeDataList: [],
        treedata: [],
        // 階層追加ダイアログ
        showDlgAddTreeLayer: false,
        addTreeLayerList: [],
        existConstructionIds: [],

        // 階層チェックボックス
        layerChk: {
            isCheck: false,
            conId: 0,
            bigId: 0,
            middleId: 0,
            smallId: 0,
        },
        
        DRAG_FILTER_TREE_PATH: '', 

        // 階層金額合計
        version_profit_rate: 0,
        layer_cost_total: 0,
        layer_sales_total: 0,
        layer_profit_total: 0,
        layer_profit_rate: 0,

        // グリッド用現在選択中の階層
        curGridLayer: { conId: 0, bigId: 0, middleId: 0, smallId: 0 },
        // グリッドReadOnly制御
        gridReadOnlyFlg: false, //true,

        // グリッドデータ
        wjMultiRowControl: [],
        multirowGrid: null,
        gridDataList: [],
        gridLayout: [],
        // 非表示カラム
        INVISIBLE_COLS: [
            'quote_detail_id',
            'construction_id',
            'layer_flg',
            'parent_quote_detail_id',
            'seq_no',
            'depth',
            'filter_tree_path',
            'set_flg',
            'product_id',
            'maker_id',
            'supplier_id',
            'min_quantity',
            'order_lot_quantity',
            'quantity_per_case',
            'set_kbn',
            'class_big_id',
            'class_middle_id',
            'class_small_id',
            'tree_species',
            'grade',
            'length',
            'thickness',
            'width',
            'received_order_flg',
            'complete_flg',
            'copy_quote_detail_id',
            'copy_received_order_flg',
            'copy_complete_flg',
            'add_flg',
            'over_quote_detail_id',
            'intangible_flg',
            'order_id_list',
            'product_maker_id',
        ],
        // 文字入力カラム
        STR_COLS: [
            'product_code',
            'product_name',
            'model',
            'maker_name',
            'supplier_name',
            'memo',
        ],
        // コピペ時に貼り付け対象としない列
        NON_PASTING_COLS: [
            'quote_detail_id',
            'construction_id',
            'layer_flg',
            'parent_quote_detail_id',
            'seq_no',
            'depth',
            'tree_path',
            'filter_tree_path',
            'set_flg',
            'received_order_flg',
            'complete_flg',
            'copy_quote_detail_id',
            'copy_received_order_flg',
            'copy_complete_flg',
            'add_flg',
            'over_quote_detail_id',
            'order_id_list',
        ],

        NORMAL_PRODUCT_PRICE_KBN: 0,

        // グリッド内オートコンプリート
        acProductCodeList: [],
        acProductNameList: [],
        acMakerList: [],
        acSupplierList: [],

        // エラー管理用
        errors: {
            // エラー（見積）
            quote: {
                construction_period: '',        //（見積）工事期間
                quote_report_to: '',        //（見積）工事期間
            },
            // エラー（見積版ごと）
            quoteVerTab: [],
            quoteVerTabTemplate: {
                quote_version_caption: '',      //（見積版）版名
                quote_create_date: '',          //（見積版）見積提出日
                tax_rate: '',                   //（見積版）税率
                quote_limit_date: '',           //（見積版）見積提出期限
                quote_enabled_limit_date: '',   //（見積版）見積有効期限
                upload_file: '',                //（見積版）添付ファイル
            },
            // エラー（ダイアログ） ※initErrした際に見積や版で表示されているエラーが消えないよう他とは分ける
            dialog: {
                customer_id: '',                //（案件作成Dialog）得意先
                owner_name: '',                 //（案件作成Dialog）施主名
                architecture_type: '',          //（案件作成Dialog）建築種別
                matter_no: '',                  //（案件作成Dialog）案件
                select_quote_to_copy: '',       //（版作成Dialog）案件 or 見積選択
                new_version_name: '',           //（版作成Dialog）見積版名
                select_quote_request: '',       //（版作成Dialog）見積作成項目選択
                select_estimation: '',          //（積算完了Dialog）積算完了選択
                round_unit: '',                 //（端数丸めDialog）単位
                round_type: '',                 //（端数丸めDialog）丸め方法
                change_gross_profit_rate: '',   //（粗利一括設定Dialog）粗利率
                quote_import: '',               //（見積取込Dialog）取込ファイル
                quote_import_arr: [],           //（見積取込Dialog）取込ファイル(繰り返し)
            }
        },
        // 変更検知用
        keepMain: null,
        keepGridDataList: null,

        // 一式作成のエラーメッセージ
        MSG_LIST_TREE_GRID_CHK_KBN_TO_SET: {
            0: '',
            1: MSG_ERROR_CREATE_SET_PRODUCT,
            2: MSG_ERROR_CREATE_SET_PRODUCT_CONSTRUCTION_LAYER,
            3: MSG_ERROR_CREATE_SET_PRODUCT_EXISTS_LAYER,
            4: MSG_ERROR_CREATE_SET_PRODUCT_EXISTS_SET_PRODUCT,
            5: MSG_ERROR_CREATE_SET_PRODUCT_INSIDE_SET_PRODUCT,
            6: MSG_ERROR_CREATE_SET_PRODUCT_INSIDE_ADD_CONSTRUCTION_LAYER,
        },

        INIT_ROW_MIN_QUANTITY: 1.00,
        INIT_ROW_ORDER_LOT_QUANTITY: 1.00,

    }),
    props: {
        isOwnLock: Number,
        lockdata: {},
        maindata: {},
        quoteLayerList: Array,
        quoteDetailList: Array,
        customerList: Array,
        ownerList: Array,
        customerOwnerList: {},
        architectureList: Array,
        personList: Array,
        requestedList: Array,              // 見積と紐づく見積依頼データの見積依頼項目
        qreqList: Array,             // 工事区分マスタの内、見積依頼項目のデータ
        // qreqConList: Array,          // 大分類・工事区分紐付けデータ
        payconList: Array,
        priceList: Array,
        allocList: Array,
        matterList: Array,
        quoteList: Array,
        makerList: Array,
        supplierList: Array,
        supplierMakerList: {},
        supplierFileList: {},
        woodList: {},
        gradeList: {},
        initTree: Array,
        initConstructionBranch: {},
        taxRateLockFlg: Number,
        addLayerId: Number,
        quoteVersionDefault: {},
    },
    watch: {
        // 見積取込ダイアログ
        showDlgQuoteImport: function (newItem, oldItem) {
            if (newItem) {
                this.initErrArrObj(this.errors.dialog);
                this.quoteImportKbn = this.QUOTE_IMPORT.QUOTE_DETAIL_EXCEL.KBN,
                this.quoteImportAcceptType = this.QUOTE_IMPORT.QUOTE_DETAIL_EXCEL.ACCEPT;
                this.quoteImportData = null;
                this.quoteImportFile.label = LBL_FILE;
                this.quoteImportFile.content = null;

                this.$nextTick(function() {
                    document.getElementById('importFile').value = '';
                    document.getElementById('quoteImportFileLabel').addEventListener('drop', () => {
                        event.preventDefault();
                        let fileList = event.target.files ? event.target.files:event.dataTransfer.files;
                        this.quoteImportFile.content = fileList[0];
                        this.quoteImportFile.label = fileList[0].name;
                    });
                    document.getElementById('importFile').addEventListener('change', () => {
                        let fileList = event.target.files ? event.target.files:event.dataTransfer.files;
                        this.quoteImportFile.content = fileList[0];
                        this.quoteImportFile.label = fileList[0].name;
                    });
                }.bind(this));         
            }
        },
        // 版新規作成ダイアログ
        showDlgNewVersion: function (newItem, oldItem) {
            if (newItem) {
                this.initErrArrObj(this.errors.dialog);
                if (this.matterNameCombo) {
                    this.matterNameCombo.selectedIndex = -1;   
                }
                if (this.quoteCombo) {
                    this.quoteCombo.selectedIndex = -1;
                }
                this.selectQuoteToCopy = this.FLG_OFF;
                this.selectQuoteRequest = this.orgSelectQuoteRequest;
                this.isQuoteRequestAll = false;
                if (this.qreqList.length == this.selectQuoteRequest.length) {
                    this.isQuoteRequestAll = true;
                }
                this.newVersionName = '';
            }
        },
        // 版コピーダイアログ
        showDlgCopyVersion: function (newItem, oldItem) {
            if (newItem) {
                this.initErrArrObj(this.errors.dialog);
                this.newVersionName = '';
            }
        },
        // 見積項目（見積版作成ダイアログで使用）
        selectQuoteRequest: function (newItem, oldItem) {
            this.isQuoteRequestAll = (newItem.length > 0) ? true:false;
            this.isQuoteRequestIndeterminate = (newItem.length > 0 && newItem.length < this.qreqList.length) ? true:false; 
        },
        showDlgGrossProfitSetting: function(newItem, oldItem) {
            if (newItem) {
                this.grossProfitRate = "";
            }
        },
        quoteImportKbn: function (newItem, oldItem) {
            switch (newItem) {
                case this.QUOTE_IMPORT.QUOTE_DETAIL_EXCEL.KBN:
                    this.quoteImportAcceptType = this.QUOTE_IMPORT.QUOTE_DETAIL_EXCEL.ACCEPT;
                    break;
                case this.QUOTE_IMPORT.BEE_CONNECT_CSV.KBN:
                    this.quoteImportAcceptType = this.QUOTE_IMPORT.BEE_CONNECT_CSV.ACCEPT;
                    break
                case this.QUOTE_IMPORT.WOOD_CAD_CSV.KBN:
                    this.quoteImportAcceptType = this.QUOTE_IMPORT.WOOD_CAD_CSV.ACCEPT;
                    break;
            }
        }
    },
    created() {
        // propsで受け取った値をローカル変数に入れる
        this.main = this.maindata;
        this.lock = this.lockdata;

        // 見積依頼項目との紐付け用データ
        // 見積依頼IDの降順にソート
        this.requestedList.sort((a, b) => {
            if (a.id > b.id) return -1;
            if (a.id < b.id) return 1;
            return 0;
        });
        if (this.requestedList.length > 0) {
            for (var i=0; i < this.requestedList.length; i++) {
                const qreqItem = this.requestedList[i];
                this.selectQuoteRequest = this.selectQuoteRequest.concat(qreqItem.quote_request_kbn_arr);
                requestedItems = requestedItems.concat(qreqItem.quote_request_kbn_arr);
                for (const j in qreqItem.quote_request_kbn_arr) {
                    var constructionId = qreqItem.quote_request_kbn_arr[j];
                    // 同じ工事区分が複数存在した場合は、見積依頼IDが大きいものを使う為、上書きしない
                    if (!qreqIdByRequestedItems[constructionId]) {
                        qreqIdByRequestedItems[constructionId] = qreqItem.id;
                    }
                }
            }
            // 重複削除
            this.selectQuoteRequest = Array.from(new Set(this.selectQuoteRequest));
            this.orgSelectQuoteRequest = this.selectQuoteRequest;
            requestedItems = Array.from(new Set(requestedItems));
        }

        // propsで受け取った階層データから表示用にデータ整形
        this.setTreeData();

        // グリッドレイアウトセット
        this.gridLayout = this.getGridLayout(false);
        this.gridLayoutZero = this.getGridLayout(true);
        // 初期TreeViewデータセット 配列ディープコピー
        var jsonDefaultTree = JSON.stringify(this.initTree);
        this.treedata = JSON.parse(jsonDefaultTree);

        // 編集可
        this.isShowEditBtn = true;
        if (this.main.matter_id == null) {
            this.isShowEditBtn = false;
            this.isReadOnly = false;
        }

        // ロック中判定
        if (this.rmUndefinedBlank(this.lock.id) != '' && this.isOwnLock != this.FLG_ON) {
            this.isLocked = true;
            this.isShowEditBtn = false;
            this.isReadOnly = true;
        }

        // タブ内のエラー初期化
        for (var i = 0; i < this.maindata.version_list.length; i++) {
            var tmpVersion = this.maindata.version_list[i].quote_version;
            this.errors.quoteVerTab.push(JSON.parse(JSON.stringify(this.errors.quoteVerTabTemplate)));
        }
    },
    mounted() {
        
        if (this.isLocked) {
            // ロック中
            this.isShowEditBtn = false;
            this.isReadOnly = true;
        } else {
            // 編集モードで開くか判定
            var query = window.location.search;
            if (this.isOwnLock == this.FLG_ON || this.isEditMode(query, this.isReadOnly, this.isEditable)) {
                this.isReadOnly = false;
                this.isShowEditBtn = false;
            }
        }

        // 照会モードの場合
        if (this.isReadOnly) {
            window.onbeforeunload = null;
        }
        
        // 案件がない場合
        if (this.maindata.no_matter_flg != undefined) {
            window.onbeforeunload = null;

            this.showDlgMatterAdd = true;
            this.showDlgFirst = true;
        } 
        // 見積がない場合
        if (this.maindata.no_quote_flg != undefined) {
            this.showDlgFirst = true;
        }

        // グリッドデータ用配列を空で作成
        for (var i = 0; i < this.maindata.version_list.length; i++) {
            this.gridDataList.push([]);
            this.acProductCodeList.push([]);
            this.acProductNameList.push([]);
            this.acMakerList.push([]);
            this.acSupplierList.push([]);
        }

        if (this.quoteDetailList.length != 0) {
            // 明細セット
            for (var i = 0; i < this.quoteDetailList.length; i++) {
                var detailArr = [];
                var cnt = 0;
                var ver = -1;
                for (var j = 0; j < this.quoteDetailList[i].length; j++) {
                    var rec = this.quoteDetailList[i][j];
                    ver = rec['quote_version'];
                    
                    // 選択チェックボックス
                    rec['chk'] = false;
                    
                    // decimal型は文字列になっているので数値キャスト
                    if (rec['quote_quantity'] != undefined) {
                        rec['quote_quantity'] = parseFloat(rec['quote_quantity']);
                    }
                    if (rec['min_quantity'] != undefined) {
                        rec['min_quantity'] = parseFloat(rec['min_quantity']);
                    }
                    if (rec['order_lot_quantity'] != undefined) {
                        rec['order_lot_quantity'] = parseFloat(rec['order_lot_quantity']);
                    }
                    if (rec['quantity_per_case'] !== undefined) {
                        rec['quantity_per_case'] = parseFloat(this.rmUndefinedZero(rec['quantity_per_case']));
                    }

                    if (rec['class_big_id'] !== undefined) {
                        rec['class_big_id'] = parseInt(this.rmUndefinedZero(rec['class_big_id']));
                    }
                    if (rec['class_middle_id'] !== undefined) {
                        rec['class_middle_id'] = parseInt(this.rmUndefinedZero(rec['class_middle_id']));
                    }
                    if (rec['class_small_id'] !== undefined) {
                        rec['class_small_id'] = parseInt(this.rmUndefinedZero(rec['class_small_id']));
                    }
                    if (rec['tree_species'] !== undefined) {
                        rec['tree_species'] = parseInt(this.rmUndefinedZero(rec['tree_species']));
                    }
                    if (rec['grade'] !== undefined) {
                        rec['grade'] = parseInt(this.rmUndefinedZero(rec['grade']));
                    }
                    if (rec['length'] !== undefined) {
                        rec['length'] = parseInt(this.rmUndefinedZero(rec['length']));
                    }
                    if (rec['thickness'] !== undefined) {
                        rec['thickness'] = parseInt(this.rmUndefinedZero(rec['thickness']));
                    }
                    if (rec['width'] !== undefined) {
                        rec['width'] = parseInt(this.rmUndefinedZero(rec['width']));
                    }

                    if (rec['regular_price'] != undefined) {
                        rec['regular_price'] = parseInt(rec['regular_price']);
                    }
                    if (rec['cost_unit_price'] != undefined) {
                        rec['cost_unit_price'] = parseInt(rec['cost_unit_price']);
                    }
                    if (rec['sales_unit_price'] != undefined) {
                        rec['sales_unit_price'] = parseInt(rec['sales_unit_price']);
                    }
                    if (rec['cost_total'] != undefined) {
                        rec['cost_total'] = parseInt(rec['cost_total']);
                    }
                    if (rec['sales_total'] != undefined) {
                        rec['sales_total'] = parseInt(rec['sales_total']);
                    }
                    if (rec['profit_total'] != undefined) {
                        rec['profit_total'] = parseFloat(rec['profit_total']);
                    }
                    if (rec['cost_makeup_rate'] != undefined) {
                        rec['cost_makeup_rate'] = parseFloat(rec['cost_makeup_rate']);
                    }
                    if (rec['sales_makeup_rate'] != undefined) {
                        rec['sales_makeup_rate'] = parseFloat(rec['sales_makeup_rate']);
                    }
                    if (rec['gross_profit_rate'] != undefined) {
                        rec['gross_profit_rate'] = parseFloat(rec['gross_profit_rate']);
                    }
                    if (rec['row_print_flg'] == undefined) {
                        rec['row_print_flg'] = false;
                    } else {
                        if (rec['row_print_flg'] == this.FLG_OFF) {
                            rec['row_print_flg'] = false;
                        } else {
                            rec['row_print_flg'] = true;
                        }
                    }
                    if (rec['price_print_flg'] == undefined) {
                        rec['price_print_flg'] = false;
                    } else {
                        if (rec['price_print_flg'] == this.FLG_OFF) {
                            rec['price_print_flg'] = false;
                        } else {
                            rec['price_print_flg'] = true;
                        }
                    }
                    if (rec['sales_use_flg'] == undefined) {
                        rec['sales_use_flg'] = false;
                    } else {
                        if (rec['sales_use_flg'] == this.FLG_OFF) {
                            rec['sales_use_flg'] = false;
                        } else {
                            rec['sales_use_flg'] = true;
                        }
                    }
                    // 【フロント制御用】無形品フラグ
                    if (rec['intangible_flg'] == undefined || rec['intangible_flg'] == null) {
                        if (rec['product_code'] == '') {
                            rec['intangible_flg'] = this.FLG_ON;
                        }else{
                            rec['intangible_flg'] = this.FLG_OFF;
                        }
                    }
                    this.gridDataList[rec['quote_version']][cnt] = rec;
                    cnt++;
                }
            }
        }
        // タブごとにツリー・グリッドを作成
        for (var i = 0; i < this.maindata.version_list.length; i++) {
            var tmpVersion = this.maindata.version_list[i].quote_version;
            this.focusVersion = tmpVersion;

            // グリッド作成
            var gridItemSource = new wjCore.CollectionView(this.gridDataList[tmpVersion], {
                newItemCreator: function () {
                    return Vue.util.extend({}, QUOTE_INIT_ROW);
                }
            });

            var gridCtrl = null;
            if (this.focusVersion === this.ZERO) {
                gridCtrl = this.createGridCtrlZero(gridItemSource);
            }else{
                gridCtrl = this.createGridCtrl(gridItemSource);
            }
            this.wjMultiRowControl.unshift(gridCtrl);

            //  階層作成
            var targetTreeDivId = '#quoteLayerTree-' + tmpVersion;
            var treeItemsSource = this.treeDataList[tmpVersion];
            var treeCtrl = this.createTreeCtrl(targetTreeDivId, treeItemsSource);

            if (tmpVersion == this.ZERO) {
                // 0版はツリーのD&D禁止
                treeCtrl.allowDragging = false;
            }

            this.wjTreeViewControl.unshift(treeCtrl);
        }

        // アップロードファイルセット
        var index = 0;
        for (var i = this.maindata.version_list.length - 1; i >= 0; i--) {
            var fileList = this.maindata.version_list[i].file_list;
            this.uploadFileList.push([]);
            this.deleteFileList.push([]);
            if (this.rmUndefinedBlank(fileList) != '') {
                for (var j = 0; j < fileList.length; j++) {
                    if (i >= 0) {                    
                        var fileName = fileList[j];

                        this.uploadFileList[index].push({
                            // file: '', 
                            file_name: fileName,
                            is_tmp: false,
                        })
                    }
                }
            }
            index++;
        }

        // GETパラメータ
        this.getQuery = window.location.search;

        // 先頭のタブが開かれるので、現在の版は配列の先頭の版番号
        if (this.maindata.version_list.length != 0) {
            this.focusVersion = this.maindata.version_list[0].quote_version;

            // 初期表示タブの指定確認
            var qVerNo = -1;
            var query = this.getQuery;
            query = query.substring(1) // ?除去
            var tmpArr = query.split('&');
            for (var i = 0; i < tmpArr.length; i++) {
                if (tmpArr[i].indexOf(QUOTE_VERSION_TAB_PARAM) >= 0) {
                    var items = tmpArr[i].split('=');
                    qVerNo = parseInt(items[1]);
                    break;
                }
            }
            // タブクリック
            if (qVerNo != -1) {
                var tabNo = this.maindata.version_list.length - qVerNo - 1;
                if (tabNo >= 0) {
                    this.focusVersion = qVerNo;
                    this.$nextTick(function() {
                        // タブを開く
                        document.getElementById('tab-'+tabNo).click();
                    });
                }
            }
            
            this.selectTree(this.wjTreeViewControl[this.focusVersion], 'top_flg', this.FLG_ON);

            this.calcLayerTotalPrice();
            // 総計粗利率(割り算の分母が0はNGなのでチェックする)
            if (this.rmUndefinedZero(this.maindata.version_list[0].sales_total) != 0 ) {
                this.version_profit_rate =
                    // 3334 ÷ 100 = 33.34 
                    this.roundDecimalRate(
                        // 0.333333… × 100 = 33.3333…
                        this.bigNumberTimes(
                            // 5000 ÷ 15000 = 0.333333…
                            this.bigNumberDiv(this.maindata.version_list[0].profit_total, this.maindata.version_list[0].sales_total)
                        , 100)
                    )
            }
        }

        // ページを開いた時の値を保存
        this.$nextTick(function() {
            this.keepMain = JSON.parse(JSON.stringify(this.main));
            this.keepGridDataList = JSON.parse(JSON.stringify(this.gridDataList));
        }.bind(this));

    },
    methods: {
        // ******************** 案件 ********************
        // 得意先オートコンプリート初期化
        initCustomerCombo: function(sender){
            this.matterCustomerCombo = sender;
            this.$nextTick(function() {
                // changeイベント発火
                this.matterCustomerCombo.onSelectedIndexChanged();
            });
        },
        // 施主名オートコンプリート初期化
        initOwnerCombo: function(sender){
            this.ownerNameCombo = sender;
        },
        // 案件オートコンプリート
        initSameMatterCombo: function(sender) {
          this.sameMatterCombo = sender;
        },
        // 案件名オートコンプリート初期化
        initMatterNameCombo: function(sender){
            this.matterNameCombo = sender;
        },
        // 見積番号オートコンプリート初期化
        initQuoteCombo: function(sender){
            this.quoteCombo = sender;
        },
        initPersonCombo: function(sender){
            this.personCombo = sender;
        },
        // 得意先オートコンプリート変更イベント
        changeCustomerCombo: function(sender){
            // 施主名を保存
            var tmpText = this.ownerNameCombo.text;

            // 得意先を施主名を絞込む
            var tmpOwnerList = this.ownerList;
            if (sender.selectedValue) {
                tmpOwnerList = []
                if (this.customerOwnerList[sender.selectedValue]) {
                    tmpOwnerList = this.customerOwnerList[sender.selectedValue];   
                }
            }
            this.ownerNameCombo.itemsSource = tmpOwnerList;
            this.ownerNameCombo.selectedIndex = -1;

            // 得意先変更前に施主名が既に入力済みだった場合は内容を復帰
            if (!tmpText == '') {
                this.ownerNameCombo.text = tmpText;
            }
        },
        // 案件名のcollectionViewを絞る
        textChangedQuoteCombo: function(sender){
            // 見積を変更したら案件名のコンボを変更する
            var matterNoList = sender.collectionView.sourceCollection.filter(rec => {
                return (rec.quote_no.indexOf(sender.text) !== -1)
            }).map(rec => {
                return rec.matter_no;
            });
            this.matterNameCombo.collectionView.filter = function(rec){
                return (matterNoList.indexOf(rec.matter_no) !== -1);
            }
        },
        // 見積番号を変更時（見積新規作成、版新規作成）
        changeIdxQuoteCombo: function(sender){
            // 見積を変更したら案件名のコンボを変更する
            if (sender.selectedValue) {
                this.matterNameCombo.selectedValue = sender.selectedItem.matter_no;   
            }
        },
        // 見積番号のcollectionViewを絞る
        textChangedMatterNameCombo: function(sender){
            // 見積を変更したら案件名のコンボを変更する
            var matterNoList = sender.collectionView.sourceCollection.filter(rec => {
                return (rec.matter_name.indexOf(sender.text) !== -1)
            }).map(rec => {
                return rec.matter_no;
            });

            this.quoteCombo.collectionView.filter = function(rec){
                return (matterNoList.indexOf(rec.matter_no) !== -1);
            }
        },
        // 案件名を変更した時（見積新規作成、版新規作成）
        changeIdxMatterNameCombo: function(sender){
            // 案件名を変更したら見積番号のコンボを変更する
            if (sender.selectedValue) {
                var findItem = this.quoteCombo.itemsSource.find(rec => {
                    return rec.matter_no == sender.selectedValue;
                })
                if (findItem) {
                    this.quoteCombo.selectedValue = findItem.quote_no;
                }
            }
        },
        // 案件確認
        confirmMatter() {
            // エラーの初期化
            this.initErrArrObj(this.errors.dialog);

            this.loading = true
            this.$set(this.main, 'customer_id', this.matterCustomerCombo.selectedValue);
            this.$set(this.main, 'owner_name', this.ownerNameCombo.text);
            this.$set(this.main, 'architecture_type', this.architectureType);

            // 入力値の取得
            var params = new URLSearchParams();
            params.append('customer_id', this.rmUndefinedBlank(this.matterCustomerCombo.selectedValue));
            params.append('owner_name', this.rmUndefinedBlank(this.ownerNameCombo.text));
            params.append('architecture_type', this.rmUndefinedBlank(this.architectureType));
            
            axios.post('/quote-edit/confirm-matter', params)
            .then( function (response) {
                this.loading = false;
                if (response.data) {
                    // 成功
                    this.$set(this.main, 'customer_id', this.matterCustomerCombo.selectedValue);
                    this.$set(this.main, 'customer_name', this.matterCustomerCombo.text);
                    this.$set(this.main, 'owner_name', this.ownerNameCombo.text);
                    this.$set(this.main, 'architecture_type', this.architectureType);
                    this.$set(this.main, 'matter_name', response.data.matter_name);
                    this.$set(this.main, 'department_name', response.data.department_name);
                    this.$set(this.main, 'staff_name', response.data.staff_name);

                    // パーソンを絞り込む
                    this.personCombo.itemsSource = this.personCombo.itemsSource.filter(rec => {
                        return (rec.company_id == this.main.customer_id);
                    });
                    this.personCombo.selectedIndex = -1;

                    // 案件作成終わり
                    this.showDlgMatterAdd = false;
                    // 似ている案件の存在が疑われる場合、案件選択に移る
                    this.sameMatterList = response.data.matter_list;
                    if (response.data.matter_list.length > 0) {
                        this.showDlgMatterSelect = true;
                    }
                } else {
                    // 失敗
                    alert(MSG_ERROR);
                }
            }.bind(this))
            .catch(function (error) {
                if (error.response.data.errors) {
                    // エラーメッセージ表示
                    this.showErrMsg(error.response.data.errors, this.errors.dialog);
                } else {
                    this.loading = false;
                    if (error.response.data.message) {
                        alert(error.response.data.message);
                    } else {
                        alert(MSG_ERROR);
                    }
                }
                this.loading = false;
            }.bind(this))
        },
        // 見積確認
        confirmQuote(){
            // エラーの初期化
            this.initErrArrObj(this.errors.dialog);

            if (this.newMatterSelected == this.FLG_ON) {
                // 新規
                this.showDlgMatterSelect = false;           
            }else{
                // 選択
                //コンボボックスを選択していない場合エラー
                if (this.rmUndefinedBlank(this.sameMatterCombo.selectedValue) == "") {
                    // this.errors.matter_no = MSG_ERROR_NO_SELECT
                    this.errors.dialog.matter_no = MSG_ERROR_NO_SELECT;
                    return false;
                }
                this.loading = true;
                var params = new URLSearchParams();
                params.append('matter_no', this.rmUndefinedBlank(this.sameMatterCombo.selectedValue));
                axios.post('/quote-edit/confirm-quote', params)
                .then( function (response) {
                    if (response.data) {
                        if (response.data.status) {
                            // 成功
                            window.location.href = '/quote-edit/' + this.sameMatterCombo.selectedItem.id;
                        }else{
                            this.errors.dialog.matter_no = MSF_ERROR_EXIST_QUOTE;
                        }
                        this.loading = false;
                    } else {
                        // 失敗
                        this.loading = false;
                        alert(MSG_ERROR);
                    }
                }.bind(this))
                .catch(function (error) {
                    if (error.response.data.errors) {
                        // エラーメッセージ表示
                        this.showErrMsg(error.response.data.errors, this.errors.dialog);
                    } else {
                        this.loading = false;
                        if (error.response.data.message) {
                            alert(error.response.data.message);
                        } else {
                            alert(MSG_ERROR);
                        }
                    }
                    this.loading = false;
                }.bind(this))
            }
        },

        // ******************** 版 ********************
        // 新規作成（第1版作成）
        createFirstVersion() {
            // catch無し
            try {
                this.startProcessing();
                var okFlg = true;
                // エラーの初期化
                this.initErrArrObj(this.errors.dialog);

                if (this.newVersionName == '') {
                    // 未入力の場合メッセージ表示
                    this.errors.dialog.new_version_name = MSG_ERROR_NO_INPUT;
                    okFlg = false;
                } else if (this.newVersionName.length > this.LEN_LIMIT_VERSION_NAME) {
                    // 文字数チェック
                    this.errors.dialog.new_version_name = '' + this.LEN_LIMIT_VERSION_NAME + MSG_ERROR_LIMIT_OVER;
                    okFlg = false;
                }
                if(this.selectQuoteToCopy === this.FLG_ON){
                    if (this.rmUndefinedBlank(this.matterNameCombo.selectedValue) == "" || this.rmUndefinedBlank(this.quoteCombo.selectedValue) == "") {
                        // 未選択の場合メッセージ表示
                        this.errors.dialog.select_quote_to_copy = MSG_ERROR_NO_SELECT;
                        okFlg = false;                        
                    }
                }else{
                    if (this.selectQuoteRequest.length == 0) {
                        // 未選択の場合メッセージ表示
                        this.errors.dialog.select_quote_request = MSG_ERROR_NO_SELECT;
                        okFlg = false;
                    }
                }
                if(okFlg) {
                    // エラーの初期化
                    this.initErrArrObj(this.errors.dialog);

                    if(this.selectQuoteToCopy == this.FLG_ON){
                        var quoteNo = this.rmUndefinedBlank(this.quoteCombo.selectedValue);
                        this.setQuoteVersion(quoteNo, this.ZERO)
                        .then( function (result) {
                            if (result) {
                                this.isCopy = true;
                                this.showDlgFirst = false;
                                this.focusVersion = (this.main.version_list.length - 1);
                                // 追加したタブを開く
                                this.$nextTick(function() {
                                    // コピー先のタブを開く
                                    document.getElementById('tab-0').click();
                                });   
                            }
                        }.bind(this))
                        .catch( function (result) {
                            // nop
                        }.bind(this));
                    }else{
                        // 版作成
                        this.createVersion();
                        this.isCopy = true;
                        this.showDlgFirst = false;
                        this.focusVersion = (this.main.version_list.length - 1);
                        // 追加したタブを開く
                        this.$nextTick(function() {
                            // コピー先のタブを開く
                            document.getElementById('tab-0').click();
                        });
                    }
                }
            } finally {
                // すぐに解除すると連打を防げない為、500ms秒遅らせる
                setTimeout(() => {
                    this.endProcessing();
                }, 500);
            }
        }, 
        // タブクリック
        tabClick(tab, event) {
            // 現在開いている版を取得
            this.focusVersion = (this.main.version_list.length - 1) - parseInt(tab.index);

            // GETパラメータの版番号を差し替え
            var query = this.getQuery;
            var newQuery = '';
            if (query.indexOf(QUOTE_VERSION_TAB_PARAM) >= 0) {
                // パラメータに存在する時
                var tmpArr = query.split('&');
                for (var i = 0; i < tmpArr.length; i++) {
                    if (i != 0) {
                        newQuery += '&';
                    }
                    if (tmpArr[i].indexOf(QUOTE_VERSION_TAB_PARAM) >= 0) {
                        // 先頭だった場合は'?'が付いているため、分割したほうを使用
                        var tmpTab = tmpArr[i].split('=');
                        newQuery += tmpTab[0] + '=' + this.focusVersion;
                    } else {
                        newQuery += tmpArr[i];
                    }
                }
            } else {
                // パラメータに存在しない時
                newQuery = query + '&' + QUOTE_VERSION_TAB_PARAM + this.focusVersion;
            }
            this.getQuery = newQuery;
            
            this.selectTree(this.wjTreeViewControl[this.focusVersion], 'top_flg', this.FLG_ON);

            // 階層金額計算
            this.calcGridCostSalesTotal();
            this.calcLayerTotalPrice();
        },
        // 階層金額表示更新
        calcLayerTotalPrice() {
            var currentTreeViewCtrl = this.wjTreeViewControl[this.focusVersion];

            if (currentTreeViewCtrl.selectedItem.top_flg === this.FLG_ON) {
                // 版合計を取得
                var versionListNo = this.main.version_list.length - this.focusVersion - 1;
                this.costLayerTotal = this.main.version_list[versionListNo].cost_total;
                this.salesLayerTotal = this.main.version_list[versionListNo].sales_total;
                this.profitLayerTotal = this.main.version_list[versionListNo].profit_total;
            } else {
                // 親階層行を取得
                var filterTreePath = currentTreeViewCtrl.selectedItem.filter_tree_path;
                var gridRow = Vue.util.extend({}, this.wjMultiRowControl[this.focusVersion].collectionView.sourceCollection.find((rec) => {
                    return (rec.filter_tree_path === filterTreePath);
                }));

                this.costLayerTotal = gridRow.cost_total;
                this.salesLayerTotal = gridRow.sales_total;
                this.profitLayerTotal = gridRow.profit_total;
            }

            // 階層合計
            this.layer_cost_total = this.costLayerTotal;
            this.layer_sales_total = this.salesLayerTotal;
            this.layer_profit_total = this.profitLayerTotal;
            var profitRate = 0;
            if (this.rmUndefinedZero(this.salesLayerTotal) != 0) {
                profitRate =
                    // 33.34 
                    this.roundDecimalRate(
                        // 0.333333… × 100 = 33.3333…
                        this.bigNumberTimes(
                            // (15000-10000)÷15000 = 0.333333…
                            this.bigNumberDiv((this.salesLayerTotal-this.costLayerTotal), this.salesLayerTotal)
                        , 100)
                    );
            }

            this.layer_profit_rate = profitRate;
        },
        // ******************** 階層 ********************
        // 階層データを整形
        setTreeData() {
            if (this.quoteLayerList.length != 0) {
                for (var i = 0; i < this.quoteLayerList.length; i++) {
                    
                    var layerEle = this.quoteLayerList[i][0].items;
                    if (layerEle.length > 0) {
                        for (var j = 0; j < layerEle.length; j++) {
                            // 依頼項目に存在するかチェック
                            for (var x = 0; x < requestedItems.length; x++) {
                                if (requestedItems[x] == layerEle[j].construction_id) {
                                    layerEle[j].link_flg = true;
                                    layerEle[j].tab_id = layerEle[j].construction_id;
                                    break;
                                }
                            }
                        }
                    }

                    this.treeDataList.unshift(this.quoteLayerList[i]);
                }
            } else {
                // 配列ディープコピー
                var jsonDefaultTree = JSON.stringify(this.initTree);
                var tree = JSON.parse(jsonDefaultTree);
                
                this.treeDataList.push(tree);
            }

        },
        // ツリーコントロール作成
        createTreeCtrl(targetTreeDivId, treeItemsSource) {
            var treeCtrl = new wjNav.TreeView(targetTreeDivId, {
                itemsSource: treeItemsSource,
                displayMemberPath: "header",
                childItemsPath: "items",
                showCheckboxes: !this.isReadOnly,
                allowDragging: !this.isReadOnly,
                autoCollapse: false,
                // 見積依頼詳細へのリンク作成
                formatItem: function (s, e) {
                    if (e.dataItem.tab_id != undefined && e.dataItem.link_flg != undefined && e.dataItem.link_flg == true) {
                        var linkUrl = "/quote-request-edit/" + qreqIdByRequestedItems[e.dataItem.tab_id] + "?tabid=" + e.dataItem.tab_id;
                        e.element.innerHTML += '&nbsp;<a href="' + linkUrl + '" target="_blank"><span style="color:#409EFF;" class="glyphicon glyphicon-share" aria-hidden="true"></span></a>';
                    }
                },
                // ドラッグ開始 
                dragStart: (s, e) =>{
                    var versionListNo = this.main.version_list.length - this.focusVersion - 1;  // main.version_listは版番号と逆順の配列になっている
                    if (this.hasReceivedOrderInTree(e.node.dataItem.filter_tree_path) ||
                        this.main.version_list[versionListNo].status == this.STATUS.APPLYING || this.main.version_list[versionListNo].status == this.STATUS.APPROVED
                    ) {
                        // 子に受注確定行が存在 or 版が申請中 or 版が承認済
                        e.cancel = true;
                    }else{
                        this.DRAG_FILTER_TREE_PATH = '';
                    }
                },
                // ドロップ
                drop: (s, e) => {
                    // ドラッグした階層
                    var target = e.dragSource;
                    // ドロップ位置の親階層　　　position 0:before(何も無いところに置いた) 2:into(階層に置いた)
                    var parent = e.position === wjNav.DropPosition.Into ? e.dropTarget :  e.dropTarget.parentNode;

                    var isCancel = false;
                    switch(target.level){
                        case 1: // 工事区分
                            if(parent === null || parent.level !== 0){
                                // 工事区分が別階層に移動しようとした
                                alert(MSG_ERROR_MOVE_TO_OTHER_LAYER);
                                isCancel = true;
                            }
                            break;
                        default :
                            if(parent === null || parent.dataItem['top_flg'] === this.FLG_ON){
                                // 通常の階層が トップ または 工事区分 に移動しようとした
                                alert(MSG_ERROR_MOVE_TO_CONSTRUCTION_LAYER);
                                isCancel = true;
                            }else{
                                var parentIsAddLayer = this.treeGridDetailRecordConstructionIsAddLayer(this.wjMultiRowControl[this.focusVersion], parent.dataItem);
                                var targetIsAddLayer = this.treeGridDetailRecordConstructionIsAddLayer(this.wjMultiRowControl[this.focusVersion], target.dataItem);

                                var parentIsSetProduct = this.treeGridDetailRecordParentIsSetFlg(this.wjMultiRowControl[this.focusVersion], parent.dataItem);
                                var targetIsSetProduct = this.treeGridDetailRecordParentIsSetFlg(this.wjMultiRowControl[this.focusVersion], target.dataItem);
                                if(parentIsAddLayer && !targetIsAddLayer){
                                    // 通常の階層が 追加部材 に移動しようとした
                                    alert(MSG_ERROR_MOVE_TO_ADD_CONSTRUCTION_LAYER);
                                    isCancel = true;
                                }else if(!parentIsAddLayer && targetIsAddLayer){
                                    // 追加部材配下から別の階層に移動しようとした
                                    alert(MSG_ERROR_MOVE_FROM_ADD_CONSTRUCTION_LAYER);
                                    isCancel = true;
                                }else if(parent.dataItem['set_flg'] === this.FLG_ON || parentIsSetProduct){
                                    // 一式に移動しようとした
                                    alert(MSG_ERROR_MOVE_TO_SET_PRODUCT);
                                    isCancel = true;
                                }
                            }
                            break;
                    }

                    if(isCancel){
                        e.cancel = true;
                    }else{
                        this.DRAG_FILTER_TREE_PATH = target.dataItem['filter_tree_path'];
                    }
                },
                // ドラッグ ドロップ 完了
                dragEnd: (selfWjTreeViewControl, e)=>{
                    if(this.rmUndefinedBlank(this.DRAG_FILTER_TREE_PATH) !== ''){
                        var gridData = this.getUpdateFilterTreePathData(this.wjMultiRowControl[this.focusVersion], selfWjTreeViewControl);
                        this.gridDataList[this.focusVersion] = gridData;

                        var gridItemSource = new wjCore.CollectionView(gridData, {
                            newItemCreator: function () {
                                return Vue.util.extend({}, QUOTE_INIT_ROW);
                            }
                        });

                        this.wjMultiRowControl[this.focusVersion].dispose();
                        this.wjMultiRowControl[this.focusVersion] = this.createGridCtrl(gridItemSource);
                        
                        this.wjTreeViewControl[this.focusVersion].loadTree();
                        // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
                        this.checkedAllTreeGrid(false);
                        this.selectTree(selfWjTreeViewControl, 'top_flg', this.FLG_ON);
                        this.calcGridCostSalesTotal();
                    }
                }
            });
            // TreeView選択イベントに処理を紐付け
            treeCtrl.selectedItemChanged.addHandler(function(sender) {
                if (sender.selectedItem === null) return;
                var kbnIdList = this.getTreeKbnId(sender);

                // 階層フィルター
                this.filterGrid();
            }.bind(this));
            // チェック状態変更イベント
            treeCtrl.isCheckedChanging.addHandler(function (s, e) {
                // 親階層へのチェック除去↓
                var checked = !e.node.isChecked;
                e.node.element.childNodes[0].checked = checked;
                if(checked){
                   e.cancel = true; 
                }

                var dataItem = e.node.dataItem;

                for (var i = 0; i < this.wjMultiRowControl[this.focusVersion].collectionView.sourceCollection.length; i++) {
                    var record = this.wjMultiRowControl[this.focusVersion].collectionView.sourceCollection[i];
                    if(dataItem.top_flg === this.FLG_ON){
                        record.chk = checked;
                    }else{
                        if(record.filter_tree_path.indexOf(dataItem.filter_tree_path) === 0){
                            record.chk = checked;
                        }
                    }
                }
                if(!checked){
                    // 上階層のグリッドのチェックを外す
                    this.checkedUpTreeGrid(dataItem.filter_tree_path, checked);
                }

                // 階層の以下チェック
                this.checkedDownTree(dataItem['items'], checked);
                // グリッド再描画
                this.wjMultiRowControl[this.focusVersion].collectionView.refresh();
            }.bind(this));

            return treeCtrl;
        },
        // 工事種別を全チェック
        changeAllQreq(){
            var result = [];
            if (this.isQuoteRequestAll && !this.isQuoteRequestIndeterminate) {
                for (const key in this.qreqList) {
                    const item = this.qreqList[key];
                    if (this.selectQuoteRequest.indexOf(item.construction_id) == -1) {
                        result.push(item.construction_id);
                    }
                }
            }
            this.selectQuoteRequest = result;
        },
        // 工事種別追加ダイアログを開く
        showDlgAddTreeLayerPrepare() {
            // TreeView上に存在する工事区分はチェック済み＆disabled
            this.addTreeLayerList.length = 0;
            var control = this.wjTreeViewControl[this.focusVersion];
            var node = control.nodes[0]; //トップノード
            this.existConstructionIds.length = 0;
            if (node && node.nodes) {
                for (var i = 0; i < node.nodes.length; i++) {
                    var tmp = node.nodes[i].dataItem;
                    this.existConstructionIds.push(tmp.construction_id);
                }
            }
            // ディープコピー
            this.addTreeLayerList = JSON.parse(JSON.stringify(this.existConstructionIds));

            this.showDlgAddTreeLayer = true;
        },
        // 工事種別追加
        addTreeLayer() {
            // catch無し
            try {
                this.startProcessing();
                // TreeViewに追加
                var control = this.wjTreeViewControl[this.focusVersion];
                var node = control.nodes[0]; //トップノード
                var index = 0;
                if (node) {
                    index = node.nodes ? node.nodes.length : 0;
                }
                var seqNo = 1; 

                var grid = this.gridDataList[this.focusVersion];
                var qreqList = this.qreqList;
                var selectArr = this.addTreeLayerList;
                for (var i = 0; i < selectArr.length; i++) {
                    // 元々あった階層は飛ばす
                    if (this.existConstructionIds.indexOf(selectArr[i]) >= 0) continue;

                    // 選択した工事区分IDから工事区分名取得
                    for (var j in qreqList) {
                        var qreqKbn = qreqList[j];
                        if (qreqKbn.construction_id == selectArr[i]) {
                            // 工事区分の階層作成
                            index = node.nodes ? node.nodes.length : 0;
                            // 工事種別階層のseq_no+1
                            if (node.dataItem.items.length > 0) {
                                seqNo = Math.max.apply(null,node.dataItem.items.map(function(o){return o.seq_no;})) + 1;
                            }
                            // 依頼項目に存在するかチェック
                            var linkFlg = false;
                            for (var x = 0; x < requestedItems.length; x++) {
                                if (requestedItems[x] == qreqKbn.construction_id) {
                                    linkFlg = true;
                                    break;
                                }
                            }
                            var newItem = {
                                id: 0,
                                construction_id: qreqKbn.construction_id,
                                layer_flg: this.FLG_ON,
                                parent_quote_detail_id: 0,
                                seq_no: seqNo,
                                depth: this.QUOTE_CONSTRUCTION_DEPTH,
                                tree_path: '',
                                sales_use_flg: false,
                                product_name: qreqKbn.construction_name,
                                add_flg: this.FLG_OFF,
                                top_flg: this.FLG_OFF,
                                header: qreqKbn.construction_name,
                                filter_tree_path: '' + seqNo,
                                to_layer_flg: this.FLG_OFF,
                                link_flg: linkFlg,
                                tab_id: qreqKbn.construction_id,
                                items: [],
                            };
                            control.selectedNode = node.addChildNode(index, newItem);

                            // grid row
                            var rowData = Vue.util.extend({}, QUOTE_INIT_ROW);
                            rowData.construction_id = qreqKbn.construction_id;
                            rowData.product_name = qreqKbn.construction_name;
                            rowData.layer_flg = this.FLG_ON;
                            rowData.filter_tree_path = newItem.filter_tree_path;
                            rowData.seq_no = seqNo;
                            rowData.min_quantity    = this.INIT_ROW_MIN_QUANTITY;
                            rowData.order_lot_quantity  = this.INIT_ROW_ORDER_LOT_QUANTITY;

                            // 管理数等を計算させる為
                            this.calcTreeGridRowData(rowData, 'quote_quantity');
                            
                            grid.push(rowData);
                        }
                    }
                }
                
                // ツリー再読み込み
                this.wjTreeViewControl[this.focusVersion].loadTree();
                this.checkedAllTreeGrid(false);
                
                // グリッド再描画
                this.wjMultiRowControl[this.focusVersion].collectionView.refresh();
                this.filterGrid();

                // ダイアログを閉じる
                this.showDlgAddTreeLayer = false;
            } finally {
                // すぐに解除すると連打を防げない為、500ms秒遅らせる
                setTimeout(() => {
                    this.endProcessing();
                }, 500);   
            }
        },
        // 階層作成ボタン
        toLayer() {
            var rowList = [];

            var gridData = this.wjMultiRowControl[this.focusVersion].itemsSource.items;
            for (var i = 0; i < gridData.length; i++) {
                if (gridData[i].chk) {
                    rowList.push(gridData[i]);
                }
            }
            // 追加対象なし
            if (rowList.length === 0) {
                alert(MSG_ERROR_NO_SELECT);
                return;
            }
            // チェック処理
            for (var i = 0; i < rowList.length; i++) {
                if(!this.toLayerIsValid(rowList[i])){
                    return;
                }
            }

            // 階層へ
            this.treeGridDetailRecordToLayer(this.wjMultiRowControl[this.focusVersion].itemsSource.items, this.wjTreeViewControl[this.focusVersion]);
            for (var i = 0; i < rowList.length; i++) {
                this.toLayerSetInitProp(rowList[i], true);
                // 再計算
                this.calcTreeGridRowData(rowList[i], 'quote_quantity');
            }

            // ツリー再読み込み
            this.wjTreeViewControl[this.focusVersion].loadTree();
            // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
            this.checkedAllTreeGrid(false);
            // グリッド再描画
            this.calcGridCostSalesTotal();   
        },
        // 一式作成ボタン
        toSet(){
            var rowList = [];

            var gridData = this.wjMultiRowControl[this.focusVersion].itemsSource.items;
            for (var i = 0; i < gridData.length; i++) {
                if (gridData[i].chk) {
                    rowList.push(gridData[i]);
                }
            }
            if (rowList.length === 0) {
                alert(MSG_ERROR_NO_SELECT);
                return;
            }
            for (var i = 0; i < rowList.length; i++) {
                if(!this.toSetIsValid(rowList[i])){
                    return;
                }
            }
            // 一式化する
            this.treeGridDetailRecordToSet(this.wjMultiRowControl[this.focusVersion].itemsSource.items, this.wjTreeViewControl[this.focusVersion]);
            for (var i = 0; i < rowList.length; i++) {
                this.toLayerSetInitProp(rowList[i], false);
                // 再計算
                this.calcTreeGridRowData(rowList[i], 'quote_quantity');
            }
            // ツリー再読み込み
            this.wjTreeViewControl[this.focusVersion].loadTree();
            // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
            this.checkedAllTreeGrid(false);
            // グリッド再描画
            this.calcGridCostSalesTotal();
        },
        // 一式化できるかチェック
        toSetIsValid(row){
            var result = true;
            var versionListNo = this.main.version_list.length - this.focusVersion - 1;  // main.version_listは版番号と逆順の配列になっている

            var chkResult = this.treeGridDetailRecordChkToSet(this.wjMultiRowControl[this.focusVersion], row);
            if(chkResult !== this.TREE_GRID_CHK_KBN_LIST.VALID){
                alert(this.MSG_LIST_TREE_GRID_CHK_KBN_TO_SET[chkResult]);
                result = false;
            }
            // 申請中 Or 承認中 Or 0版がｱｸﾃｨﾌﾞ Or 受注確定行 Or 階層行
            if (result && (
                this.focusVersion == this.ZERO ||
                this.main.version_list[versionListNo].status == this.STATUS.APPLYING ||
                this.main.version_list[versionListNo].status == this.STATUS.APPROVED ||
                row.received_order_flg === this.FLG_ON
            )) {
                alert(MSG_ERROR_CREATE_SET_PRODUCT);
                result = false;
            }
            return result;
        },
        // 階層選択時にグリッドのフィルター機能で表示をしぼる
        filterGrid() {
            var currentTreeViewCtrl = this.wjTreeViewControl[this.focusVersion];
            var currentGridCtrl = this.wjMultiRowControl[this.focusVersion];
            // currentGridCtrl.isReadOnly = this.gridReadOnlyFlg;
            currentGridCtrl.collectionView.filter = function(item) {
                var isConfirm = true;

                isConfirm = this.isTreeGridVisibleTarget(
                    item,
                    currentTreeViewCtrl.selectedItem.top_flg, 
                    currentTreeViewCtrl.selectedItem.depth, 
                    currentTreeViewCtrl.selectedItem.filter_tree_path
                );

                return isConfirm;
            }.bind(this);

            // 階層金額取得表示
            this.calcLayerTotalPrice();

            // 行追加や削除可能かどうかをグリッドにセットする
            var kbnIdList = this.getTreeKbnId(this.wjTreeViewControl[this.focusVersion]);
            this.setGridCtrl(kbnIdList.top_flg);
        },
        
        // ******************** 関数 ********************
        // オートコンプリート　手入力した値を選択する
        setTextChanged: function(sender) {
            this.setAutoCompleteValue(sender);
        },
        // 選択している階層の分類などのIDを返す
        getTreeKbnId(selectedTree){
            var result = {
                top_flg     : null,
                layer_flg   : null,
                depth       : null,
                filter_tree_path    : null,
                construction_id     : null,
                add_flg     : null,
                to_layer_flg : null,
                sales_use_flg : null,
            }
            
            result.top_flg  = selectedTree.selectedItem.top_flg;
            result.layer_flg= selectedTree.selectedItem.layer_flg; 
            result.depth    = selectedTree.selectedItem.depth;
            result.filter_tree_path = selectedTree.selectedItem.filter_tree_path;
            result.construction_id  = selectedTree.selectedItem.construction_id;
            result.add_flg  = selectedTree.selectedItem.add_flg;
            result.to_layer_flg  = selectedTree.selectedItem.to_layer_flg;
            result.sales_use_flg  = selectedTree.selectedItem.sales_use_flg;
            return result;
        },
        // 受注確定済みのグリッドが存在するか
        hasReceivedOrderInTree(filterTreePath){
            var findIdx = this.wjMultiRowControl[this.focusVersion].collectionView.sourceCollection.findIndex((rec) => {
                var result = false;
                // filter_tree_path前方一致 && 受注確定フラグがON
                if (rec.filter_tree_path.indexOf(filterTreePath) === 0 &&
                    rec.received_order_flg === this.FLG_ON) {
                    result = true;
                }
                return result;
            });
            return (findIdx == -1) ? false:true;
        },
        // 保存用のデータが変更されていないか（mounted以降）。
        isSameSaveData(){
            var isOk = true;

            // 版の数が異なるならNG
            if (this.keepMain.version_list.length !== this.main.version_list.length) {
                isOk = false;
            }

            // 変更前データを主体に処理を回す
            const compareSameData = function (before, after) {
                var isSame = true;
                for (const key in before) {
                    const beforeItem = before[key];
                    const afterItem = after[key];
                    if (beforeItem && Array.isArray(beforeItem)) {
                        // 配列
                        // keyの数が異なる or 内容が同じでない
                        if ((beforeItem.length != afterItem.length) || !compareSameData(beforeItem, afterItem)) {
                            // NG
                            isSame = false;
                            break;
                        }
                    }else if(beforeItem && typeof beforeItem === 'object'){
                        // オブジェクト　※配列もtrueになるので事前にパターンを潰しておく
                        // keyの数が異なる or 内容が同じでない
                        if ((Object.keys(beforeItem).length != Object.keys(afterItem).length) || !compareSameData(beforeItem, afterItem)) {
                            // NG
                            isSame = false;
                            break;
                        }
                    }else if(beforeItem != afterItem){
                        isSame = false;

                        // 例外パターン等
                        // nullとemptyなどは同一とみなす
                        if (this.rmUndefinedBlank(beforeItem) == this.rmUndefinedBlank(afterItem)) {
                            isSame = true;
                        }

                        if (!isSame) {
                            break;
                        }
                    }
                }
                return isSame;
            }.bind(this)

            // 版情報に差異があればNG（stringだったりintだったりするので、全てstringキャストする）
            var beforeMain = JSON.parse(JSON.stringify(this.keepMain));
            var afterMain = JSON.parse(JSON.stringify(this.main));
            if (!compareSameData(beforeMain, afterMain)) {
                isOk = false;
            }

            // 版ごとに比較
            if (isOk) {
                var beforeGrid = JSON.parse(JSON.stringify(this.keepGridDataList));
                var afterGrid = JSON.parse(JSON.stringify(this.gridDataList));
                for (const key in beforeGrid) {
                    if ((beforeGrid[key].length != afterGrid[key].length) || !compareSameData(beforeGrid[key], afterGrid[key])) {
                        isOk = false;
                        break;
                    }
                }
            }

            // [添付ファイル]アップロードリスト（未保存のファイルがあるならNG）
            if (isOk) {
                for (const key in this.uploadFileList) {
                    const item = this.uploadFileList[key];
                    var findIdx = item.findIndex((rec) => {
                        return (rec.is_tmp === true);
                    })
                    if (findIdx !== -1) {
                        isOk = false;
                        break;
                    }
                }   
            }

            // [添付ファイル]削除リスト（保存済ファイルの削除が存在するならNG）
            if (isOk) {
                for (const key in this.uploadFileList) {
                    const item = this.deleteFileList[key];
                    var findIdx = item.findIndex((rec) => {
                        return (rec.is_tmp === false);
                    })
                    if (findIdx !== -1) {
                        isOk = false;
                        break;
                    }
                }   
            }

            return isOk
        },
        // グリッドのチェックボックス変更時に呼ぶ
        // 引数で渡ってきたグリッドの行のチェック状態に合わせてグリッドと階層のチェックを外す
        changeGridCheckBox(row){
            var filterTreePath = row.filter_tree_path;
            var chk = row.chk;
            
            if(row.layer_flg === this.FLG_ON){
                var item = this.findTree(this.wjTreeViewControl[this.focusVersion].itemsSource, 'filter_tree_path', filterTreePath);
                var treeNode = this.wjTreeViewControl[this.focusVersion].getNode(item);
                treeNode.isChecked = chk;
            }else{
                if(!chk){
                    var item = this.findTree(this.wjTreeViewControl[this.focusVersion].itemsSource, 'filter_tree_path', this.getParentFilterTreePath(filterTreePath));
                    var treeNode = this.wjTreeViewControl[this.focusVersion].getNode(item);
                    this.checkedUpTree(treeNode, chk);
                }
            }

            if(!chk){
                // 上階層のグリッドのチェックを外す
                this.checkedUpTreeGrid(filterTreePath, chk);
            }  
        },

        // 上の階層のチェックを変更する(イベント発生させない)
        checkedUpTree(node, checked){  
            if(node != undefined){
                node.element.childNodes[0].checked = checked;
                this.checkedUpTree(node.parentNode, checked);
            }else{
                return;
            }
        },
        // 下の階層のチェックを変更する(イベント発生させない)
        checkedDownTree(items, checked){
            for(var i =0;i<items.length;i++){
                //this.wjTreeViewControl[this.focusVersion].getNode(items[i]).isChecked = checked;
                // イベント発生させない
                this.wjTreeViewControl[this.focusVersion].getNode(items[i]).element.childNodes[0].checked = checked;
                this.checkedDownTree(items[i]['items'], checked);
            }
        },
        // 上の階層のグリッドのチェックを変更する
        checkedUpTreeGrid(filterTreePath, checked){
            var record = this.wjMultiRowControl[this.focusVersion].collectionView.sourceCollection.find((rec) => {
                    return (rec.filter_tree_path === filterTreePath);
                });
            if(typeof record === 'undefined'){
                return;
            }
            record.chk = checked;
            this.checkedUpTreeGrid(filterTreePath.slice(0, filterTreePath.lastIndexOf(this.TREE_PATH_SEPARATOR)), checked);
        },
        // 全グリッド行のチェックを変更する
        checkedAllTreeGrid(checked){
            for(let i in this.wjMultiRowControl[this.focusVersion].collectionView.sourceCollection){
                this.wjMultiRowControl[this.focusVersion].collectionView.sourceCollection[i].chk = checked;
            }
        },

        // グリッドの商品変更時の最小単位数チェック
        changingProduct(product, row, isCode){
            var isCancel = false;
            if(row.layer_flg === this.FLG_OFF){
                var selectedItem = product.selectedItem;
                var activeProductCode = row.product_code;
                if(isCode){
                    activeProductCode = product.control.text;
                }

                var isErr = false;

                if(selectedItem !== null){
                     if(selectedItem.product_id === PRODUCT_AUTO_COMPLETE_SETTING.DEFAULT_PRODTCT_ID){
                        alert(MSG_ERROR_NO_SELECT_PRODUCT);
                        isErr = true;
                    }
                }

                if (isCode && !isErr) {
                    if (!this.isMatchPattern(PRODUCT_CODE_REGEX, product.control.text)) {
                        alert(MSG_ERROR_ILLEGAL_VALUE + "\n" + MSG_ERROR_PRODUCT_CODE_REGEX);
                        isErr = true;
                    }   
                }

                if (product.control.isReadOnly) {
                    isErr = true;
                }

                if (product.control.text == product.beforeText) {
                    isErr = true;
                }

                if(isErr){
                    // 入力していた値の復元
                    product.control.text = product.beforeText;
                    isCancel = true;
                }else{
                    if(selectedItem === null){
                        row.product_id = QUOTE_INIT_ROW.product_id;
                        row.product_maker_id = QUOTE_INIT_ROW.product_maker_id;
                        if(this.rmUndefinedBlank(activeProductCode) === ''){
                            if(isCode){
                                // 無形品への変更
                                if(row.intangible_flg !== this.FLG_ON){
                                    alert(MSG_ALERT_TO_INTANGIBLE.replace('$min_quantity', QUOTE_INIT_ROW.min_quantity));
                                }
                                row.min_quantity = QUOTE_INIT_ROW.min_quantity;
                                row.order_lot_quantity = QUOTE_INIT_ROW.order_lot_quantity;
                                row.intangible_flg = this.FLG_ON;
                            }
                        }else{
                            if(row.intangible_flg === this.FLG_ON){
                                row.min_quantity = this.INIT_ROW_MIN_QUANTITY;
                                row.order_lot_quantity = this.INIT_ROW_ORDER_LOT_QUANTITY;
                            }
                            row.intangible_flg = this.FLG_OFF;
                        }

                        if(!this.treeGridQuantityIsMultiple(row.quote_quantity, row.order_lot_quantity)){
                            // 見積数が発注ロットの倍数で無い場合に見積数をクリア
                            row.quote_quantity = QUOTE_INIT_ROW.quote_quantity;       // 見積数
                        }
                        // 再計算
                        this.calcTreeGridRowData(row, 'quote_quantity');
                        this.calcGridCostSalesTotal();
                    }
                }
            }else{
                if (product.control.isReadOnly) {
                    isErr = true;
                }

                if(isErr){
                    // 入力していた値の復元
                    product.control.text = product.beforeText;
                    isCancel = true;
                }
            }
            return isCancel;
        },

        // グリッドの商品変更時
        async changeProduct(product, row, isCode){
            // 商品名
            if(product !== null){
                // 商品を変更したか
                if(row.product_id !== product.product_id && row.is_cancel !== true){
                    var beforeProductCodeText = this.acProductCodeList[this.focusVersion].beforeText;
                    var beforeProductNameText = this.acProductNameList[this.focusVersion].beforeText;

                    // 変更がない場合は商品マスタの情報をセットしない
                    if((isCode && beforeProductCodeText === null) || (!isCode && beforeProductNameText === null) || 
                      (isCode && product.product_code !== beforeProductCodeText) || (!isCode && product.product_name !== beforeProductNameText)
                    ){
                        // 非同期で取得
                        var productInfo = await this.getProductInfo(product.product_id, this.rmUndefinedZero(this.main.customer_id));
                        if(productInfo !== undefined){
                            var productData = productInfo['product'];
                            
                            row.product_code = productData.product_code;                                // 商品コード
                            if(isCode){
                                row.product_name = productData.product_name;                            // 商品名
                            }else{
                                // row.product_code = productData.product_code;                            // 商品コード
                            }
                            row.product_id = productData.product_id                                     // 商品ID
                            row.model = productData.model;                                              // 型式・規格
                            row.maker_id = productData.maker_id                                         // メーカーID
                            for(var i = 0; i < this.makerList.length; i++){
                                if (this.makerList[i].id == row.maker_id) {
                                    row.maker_name = this.makerList[i].supplier_name;                   // メーカー名
                                    break;
                                }
                            }
                            row.min_quantity = parseFloat(productData.min_quantity);                    // 最小単位数
                            row.unit = productData.unit;                                                // 単位
                            row.stock_unit = productData.stock_unit;                                    // 管理数単位
                            row.order_lot_quantity = parseFloat(productData.order_lot_quantity);        // 発注ロット数
                            row.quantity_per_case = parseFloat(this.rmUndefinedZero(productData.quantity_per_case));           // 入り数
                            row.set_kbn = productData.set_kbn;                                          // セット区分
                            row.class_big_id = this.rmUndefinedZero(productData.class_big_id);          // 大分類ID
                            row.class_middle_id = this.rmUndefinedZero(productData.class_middle_id);    // 中分類ID
                            row.class_small_id = this.rmUndefinedZero(productData.class_small_id_1);    // 小分類ID
                            row.tree_species = this.rmUndefinedZero(productData.tree_species);          // 樹種
                            row.grade = this.rmUndefinedZero(productData.grade);                        // 等級
                            row.length = this.rmUndefinedZero(productData.length);                      // 長さ
                            row.thickness = this.rmUndefinedZero(productData.thickness);                // 厚み
                            row.width = this.rmUndefinedZero(productData.width);                        // 幅
                            row.regular_price = productData.price;                                      // 定価

                            row.intangible_flg = productData.intangible_flg;                            // 無形品フラグ

                            // 仕入・販売単価
                            this.setTreeGridUnitPriceNew(row, true, productInfo['costProductPriceList'], false);       // 仕入単価
                            this.setTreeGridUnitPriceNew(row, false, productInfo['salesProductPriceList'], true);     // 販売単価
                            // 仕入・販売掛率再計算
                            this.calcTreeGridChangeUnitPrice(row, true); // 仕入掛率
                            this.calcTreeGridChangeUnitPrice(row, false);  // 販売掛率 

                            row.product_maker_id = productData.maker_id; // 商品マスタのメーカーID

                            var isFind = false;
                            // 選んだ商品のメーカーが無い場合は変更しない
                            if(this.rmUndefinedZero(row.product_maker_id) !== 0){
                                for(var i = 0; i < this.makerList.length; i++){
                                    if (this.makerList[i].id == row.product_maker_id) {
                                        row.maker_id = row.product_maker_id;                 // メーカーID
                                        row.maker_name = this.makerList[i].supplier_name; // メーカー
                                        isFind = true;
                                        break;
                                    }
                                }
                                if(!isFind){
                                    row.maker_id = QUOTE_INIT_ROW.maker_id;        // メーカーID
                                    row.maker_name = QUOTE_INIT_ROW.maker_name;    // メーカー名
                                }
                            }

                            // 選択している仕入先が新しく選択したメーカーのリストに存在するか
                            if(this.supplierMakerList[row.maker_id] !== undefined){
                                var findIdx = this.supplierMakerList[row.maker_id].findIndex((rec) => {
                                    return (rec.supplier_id == row.supplier_id);
                                });
                                if(findIdx === -1){
                                    row.supplier_id = QUOTE_INIT_ROW.supplier_id;         // 仕入先ID
                                    row.supplier_name = QUOTE_INIT_ROW.supplier_name;     // 仕入先名
                                }
                            }else{
                                row.supplier_id = QUOTE_INIT_ROW.supplier_id;             // 仕入先ID
                                row.supplier_name = QUOTE_INIT_ROW.supplier_name;         // 仕入先名
                            }

                            // 再計算
                            this.calcTreeGridRowData(row, 'quote_quantity');
                            this.calcGridCostSalesTotal();
                        }
                    }
                }
            }else{
                // 階層名変更の場合
                if(row.layer_flg === this.FLG_ON){
                    var productName = row.product_name;
                    if(this.rmUndefinedBlank(productName) !== ''){
                        var item = this.findTree(this.wjTreeViewControl[this.focusVersion].itemsSource, 'filter_tree_path', row.filter_tree_path);
                        item['header'] = productName;
                        this.wjTreeViewControl[this.focusVersion].loadTree();

                        this.checkedDownTree(this.wjTreeViewControl[this.focusVersion].nodes[0].dataItem['items'], false);
                        // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
                        this.checkedAllTreeGrid(false);
                    }
                }else{
                    
                }
            }
        },
        /**
         * 仕入/販売区分変更時に呼び出す
         * @param row   行
         */
        async changeCostSalesKbn(row, isCost){
            // 非同期で取得
            var unitPriceInfo           = await this.getUnitPrice(row.product_id, this.rmUndefinedZero(this.main.customer_id));
            if(unitPriceInfo !== undefined){
                if (isCost) {
                    this.setTreeGridUnitPriceNew(row, true, unitPriceInfo['costProductPriceList'], false);       // 仕入単価
                    this.calcTreeGridChangeUnitPrice(row, true);
                }else{
                    this.setTreeGridUnitPriceNew(row, false, unitPriceInfo['salesProductPriceList'], false);     // 販売単価
                    this.calcTreeGridChangeUnitPrice(row, false);
                }
                this.calcTreeGridRowData(row, 'quote_quantity');
                this.calcGridCostSalesTotal();
                this.calcLayerTotalPrice();
            }
        },
        // ******************** グリッド計算 ********************
        // 仕入れ総額と販売総額を階層ごとに計算する
        calcGridCostSalesTotal(){
            var ttl = this.calcTreeGridCostSalesTotal(this.wjMultiRowControl[this.focusVersion], this.wjTreeViewControl[this.focusVersion].nodes[0].dataItem['items'], 'quote_quantity');
            // 総額
            this.costTotal = ttl.cost;
            this.salesTotal = ttl.sales;
            this.profitTotal = ttl.sales - ttl.cost;
            var versionListNo = this.main.version_list.length - this.focusVersion - 1;
            this.$set(this.main.version_list[versionListNo], 'cost_total', this.costTotal);
            this.$set(this.main.version_list[versionListNo], 'sales_total', this.salesTotal);
            this.$set(this.main.version_list[versionListNo], 'profit_total', this.profitTotal);
            var profitRate = 0;
            if (this.salesTotal != 0 ) {
                profitRate =
                    // 33.34 
                    this.roundDecimalRate(
                        // 0.333333… × 100 = 33.3333…
                        this.bigNumberTimes(
                            // (15000-10000)÷15000 = 0.333333…
                            this.bigNumberDiv(this.profitTotal, this.salesTotal)
                        , 100)
                    );
            }
            this.version_profit_rate = profitRate;

            this.wjMultiRowControl[this.focusVersion].collectionView.refresh();
        },
        // グリッドの新規入力行制御
        setGridCtrl(topFlg){
            this.wjMultiRowControl[this.focusVersion].isReadOnly = this.isReadOnly;
            var versionListNo = this.main.version_list.length - this.focusVersion - 1;  // main.version_listは版番号と逆順の配列になっている
            // 『allowDelete:false』は行削除出来なくなる代わりに、『DELETE』押下時に入力可能なセルの値が削除されるようになるので、ここでは制御しない
            if(topFlg === this.FLG_ON || this.focusVersion == this.ZERO ||
               this.main.version_list[versionListNo].status == this.STATUS.APPLYING || this.main.version_list[versionListNo].status == this.STATUS.APPROVED
            ){
                // トップ階層 or 0版　新規入力行なし or 版が申請中でない or 版が承認済でない
                this.wjMultiRowControl[this.focusVersion].allowAddNew = false;
            }else{
                this.wjMultiRowControl[this.focusVersion].allowAddNew = !this.isReadOnly;
            }
        },

        // ******************** グリッド ********************
        // グリッドレイアウト定義取得
        getGridLayout(isZero) {
            // 価格区分
            var priceKbnMap = new wjGrid.DataMap(this.priceList, 'value_code', 'value_text_1');

            return [
                { cells: [
                    { name: 'btn_up', binding: 'btn_up', header: '　', width: 30, isReadOnly: true },
                    { name: 'btn_down', binding: 'btn_down', header: '　', width: 30, isReadOnly: true },
                ] },
                { cells: [{ name: 'chk', binding: 'chk', header: '選択', width: 30, isReadOnly: false }] },
                { cells: [{ name: 'product_code', binding: 'product_code', header: '品番', width: 150, isReadOnly: false }] },
                { cells: [
                    { name: 'product_name', binding: 'product_name', header: '商品名', width: 210, isReadOnly: false },
                    { name: 'model', binding: 'model', header: '型式・規格', width: 210, isReadOnly: false },
                ] },
                { cells: [
                    { name: 'maker_name', binding: 'maker_name', header: 'メーカー名', width: 100, isReadOnly: false },
                    { name: 'supplier_name', binding: 'supplier_name', header: '仕入先名', width: 100, isReadOnly: false },
                ] },
                { cells: [
                    { name: 'quote_quantity', binding: 'quote_quantity', header: '見積数', width: 60, isReadOnly: isZero, isRequired: false },
                    { name: 'unit', binding: 'unit', header: '単位', width: 60, isReadOnly: false },
                ] },
                { cells: [
                    { name: 'stock_quantity', binding: 'stock_quantity', header: '管理数', width: 85, isReadOnly: true },
                    { name: 'stock_unit', binding: 'stock_unit', header: '管理数単位', width: 85, isReadOnly: false },
                ] },
                { cells: [
                    { name: 'regular_price', binding: 'regular_price', header: '定価', width: 75, isReadOnly: false, isRequired: false },
                ] },
                { cells: [
                    { name: 'cost_kbn', binding: 'cost_kbn', header: '仕入区分', width: 110, dataMap: priceKbnMap, isReadOnly: false },
                    { name: 'sales_kbn', binding: 'sales_kbn', header: '販売区分', width: 110, dataMap: priceKbnMap, isReadOnly: false },
                ] },
                { cells: [
                    { name: 'cost_unit_price', binding: 'cost_unit_price', header: '仕入単価', width: 75, isReadOnly: false, isRequired: false },
                    { name: 'sales_unit_price', binding: 'sales_unit_price', header: '販売単価', width: 75, isReadOnly: false, isRequired: false },
                ] },
                { cells: [
                    { name: 'cost_makeup_rate', binding: 'cost_makeup_rate', header: '仕入掛率', width: 70, isReadOnly: false, isRequired: false },
                    { name: 'sales_makeup_rate', binding: 'sales_makeup_rate', header: '販売掛率', width: 70, isReadOnly: false, isRequired: false },
                ] },
                { cells: [
                    { name: 'cost_total', binding: 'cost_total', header: '仕入総額', width: 75, isReadOnly: true },
                    { name: 'sales_total', binding: 'sales_total', header: '販売総額', width: 75, isReadOnly: true },
                ] },
                { cells: [
                    { name: 'gross_profit_rate', binding: 'gross_profit_rate', header: '粗利率', width: 75, isReadOnly: false, isRequired: false },
                    { name: 'profit_total', binding: 'profit_total', header: '粗利総額', width: 75, isReadOnly: true },
                ] },
                { cells: [{ name: 'memo', binding: 'memo', header: '備考', width: 60, isReadOnly: false }] },
                { cells: [{ name: 'row_print_flg', binding: 'row_print_flg', header: '明細印字', wordWrap: true, width: 50, isReadOnly: false }] },
                { cells: [{ name: 'price_print_flg', binding: 'price_print_flg', header: '売価印字', wordWrap: true, width: 50, isReadOnly: false, }] },
                { cells: [{ name: 'sales_use_flg', binding: 'sales_use_flg', header: '階層販売金額利用', wordWrap: true, width: 70, isReadOnly: isZero }] },
                /******************** ↓非表示項目 ********************/
                { cells: [{ name: 'quote_detail_id', binding: 'quote_detail_id', header: 'ID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'construction_id', binding: 'construction_id', header: '工事区分ID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'layer_flg', binding: 'layer_flg', header: '階層フラグ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'parent_quote_detail_id', binding: 'parent_quote_detail_id', header: '親見積明細ID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'seq_no', binding: 'seq_no', header: '連番', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'depth', binding: 'depth', header: '深さ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'filter_tree_path', binding: 'filter_tree_path', header: '階層キー', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'set_flg', binding: 'set_flg', header: '一式フラグ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'product_id', binding: 'product_id', header: '商品ID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'maker_id', binding: 'maker_id', header: 'メーカーID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'supplier_id', binding: 'supplier_id', header: '仕入先ID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'min_quantity', binding: 'min_quantity', header: '最小単位数', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'order_lot_quantity', binding: 'order_lot_quantity', header: '発注ロット数', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'quantity_per_case', binding: 'quantity_per_case', header: '入り数', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'set_kbn', binding: 'set_kbn', header: 'セット区分', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'class_big_id', binding: 'class_big_id', header: '大分類ID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'class_middle_id', binding: 'class_middle_id', header: '中分類ID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'class_small_id', binding: 'class_small_id', header: '小分類ID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'tree_species', binding: 'tree_species', header: '樹種', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'grade', binding: 'grade', header: '等級', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'length', binding: 'length', header: '長さ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'thickness', binding: 'thickness', header: '厚み', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'width', binding: 'width', header: '幅', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'received_order_flg', binding: 'received_order_flg', header: '受注確定フラグ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'complete_flg', binding: 'complete_flg', header: '積算完了フラグ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'copy_quote_detail_id', binding: 'copy_quote_detail_id', header: 'コピー元見積明細ID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'copy_received_order_flg', binding: 'copy_received_order_flg', header: 'コピー元受注確定フラグ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'copy_complete_flg', binding: 'copy_complete_flg', header: 'コピー元積算完了フラグ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'add_flg', binding: 'add_flg', header: '追加部材フラグ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'over_quote_detail_id', binding: 'over_quote_detail_id', header: '数量超過元明細ID', visible: false, isReadOnly: true }] },

                { cells: [{ name: 'intangible_flg', binding: 'intangible_flg', header: '無形品フラグ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'order_id_list', binding: 'order_id_list', header: '発注IDリスト', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'product_maker_id', binding: 'product_maker_id', header: '商品メーカーID', visible: false, isReadOnly: true }] },
            ];
        },
        // グリッドコントロール作成
        createGridCtrl(gridItemSource) {
            var targetGridDivId = '#quoteDetailGrid-' + this.focusVersion;
            var gridCtrl = new wjMultiRow.MultiRow(targetGridDivId, {
                itemsSource: gridItemSource,
                layoutDefinition: this.gridLayout,
                allowAddNew: (!this.isReadOnly),
                allowDelete: (!this.isReadOnly),
                allowSorting: false,
                showSort: false,
                keyActionEnter: wjGrid.KeyAction.None,
                autoClipboard: false,
            });
            gridCtrl.isReadOnly = this.gridReadOnlyFlg;
            gridCtrl.rowHeaders.columns[0].width = 30;

            // グリッドに対して右クリックメニューを紐づける
            var contextMenuCtrl = this.setTreeGridRightCtrl(wjGrid, wjcInput, gridCtrl);

            // セル編集直前のイベント：コンボをセットする
            gridCtrl.beginningEdit.addHandler(function (s, e) {
                // 通常セルに対してctrl+vを有効にするため
                s.autoClipboard = true;

                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);
                var row = s.collectionView.currentItem;
                var versionListNo = this.main.version_list.length - this.focusVersion - 1;  // main.version_listは版番号と逆順の配列になっている

                this.acProductCodeList[this.focusVersion].changeItemsSource([]);
                this.acProductNameList[this.focusVersion].changeItemsSource([]);
                this.acMakerList[this.focusVersion].changeItemsSource(this.makerList);
                this.acSupplierList[this.focusVersion].changeItemsSource(this.supplierMakerList[row.maker_id]);
                this.acProductCodeList[this.focusVersion].control.isReadOnly = false;
                this.acProductNameList[this.focusVersion].control.isReadOnly = false;
                this.acMakerList[this.focusVersion].control.isReadOnly = false;
                this.acSupplierList[this.focusVersion].control.isReadOnly = false;

                // 受注確定済みの場合、コンボボックス変更不可
                if (row.received_order_flg == this.FLG_ON) {
                    this.acProductCodeList[this.focusVersion].control.isReadOnly = true;
                    this.acProductNameList[this.focusVersion].control.isReadOnly = true;
                    this.acMakerList[this.focusVersion].control.isReadOnly = true;
                    this.acSupplierList[this.focusVersion].control.isReadOnly = true;
                }

                // 階層 Or 一式の場合、コンボボックスを表示させない
                if (row.layer_flg == this.FLG_ON || row.set_flg == this.FLG_ON) {
                    this.acProductCodeList[this.focusVersion].changeItemsSource(null);
                    this.acProductNameList[this.focusVersion].changeItemsSource(null);
                    // 子に受注確定行が存在するなら変更不可
                    if (this.hasReceivedOrderInTree(row.filter_tree_path)) {
                        this.acProductNameList[this.focusVersion].control.isReadOnly = true;
                    }
                }

                // セル個別処理
                switch (col.name) {
                    case 'product_code':
                    case 'product_name':
                        if(this.isKeyPressDeleteOrBackspace()){
                            // オートコンプリート上でdelete key無効化
                            e.cancel = true;
                        }
                        break;
                    case 'quote_quantity':
                        // 階層行 Or 一式行は変更NG
                        if (row.layer_flg === this.FLG_ON || row.set_flg === this.FLG_ON) {
                            e.cancel = true;
                        }
                        break;
                    case 'maker_name':
                        // 商品マスタにメーカーIDがある場合
                        if(this.rmUndefinedZero(row.product_maker_id) !== 0){
                            this.acMakerList[this.focusVersion].control.isReadOnly = true;
                            e.cancel = true;
                        }else{
                            this.acMakerList[this.focusVersion].control.isReadOnly = false;
                        }
                        if(this.isKeyPressDeleteOrBackspace()){
                            // オートコンプリート上でdelete key無効化
                            e.cancel = true;
                        }
                        break;
                    case 'supplier_name':
                        this.acSupplierList[this.focusVersion].changeItemsSource(this.supplierMakerList[row.maker_id]);
                        if(this.isKeyPressDeleteOrBackspace()){
                            // オートコンプリート上でdelete key無効化
                            e.cancel = true;
                        }
                        break;
                    default:
                        break;
                }

                // 申請中 Or 承認済の場合は行チェック、明細印字、売価印字チェック以外は操作不可
                // 　※チェックさせない等の制御をやりたい場合はitemFormatterで行う
                if (this.main.version_list[versionListNo].status == this.STATUS.APPLYING || this.main.version_list[versionListNo].status == this.STATUS.APPROVED) {
                    this.acProductCodeList[this.focusVersion].control.isReadOnly = true;
                    this.acProductNameList[this.focusVersion].control.isReadOnly = true;
                    this.acMakerList[this.focusVersion].control.isReadOnly = true;
                    this.acSupplierList[this.focusVersion].control.isReadOnly = true;
                    switch (col.name) {
                        case 'chk':
                        case 'row_print_flg':
                        case 'price_print_flg':
                            break;
                        default:
                            e.cancel = true;
                            break;
                    }
                }

                if (this.gridIsReadOnlyCell(gridCtrl, e.row, e.col)) {
                    e.cancel = true;
                }
            }.bind(this));
            // クリックイベント
            gridCtrl.addEventListener(gridCtrl.hostElement, "click", e => {
                let ht = gridCtrl.hitTest(e);
                if(ht.cellType === wjGrid.CellType.Cell){
                    var record = ht.panel.rows[ht.row].dataItem;
                    if(typeof record !== 'undefined'){
                        var col = gridCtrl.getBindingColumn(ht.panel, ht.row, ht.col);
                        var loadTreeFlg = false;
                        switch (col.name) {
                            case 'btn_up':
                                if (!this.isReadOnly) {
                                    loadTreeFlg = this.treeGridUpDownBtnClick(gridCtrl, this.wjTreeViewControl[this.focusVersion], record, true);   
                                }
                                break;
                            case 'btn_down':
                                if (!this.isReadOnly) {
                                    loadTreeFlg = this.treeGridUpDownBtnClick(gridCtrl, this.wjTreeViewControl[this.focusVersion], record, false);
                                }
                                break;
                        }

                        if(loadTreeFlg){
                            this.wjTreeViewControl[this.focusVersion].loadTree();
                            // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
                            this.checkedAllTreeGrid(false);
                        }
                    }       
                }
            });
            // ダブルクリックイベント
            gridCtrl.addEventListener(gridCtrl.hostElement, "dblclick", e => {
                let ht = gridCtrl.hitTest(e);
                if(ht.cellType === wjGrid.CellType.RowHeader){
                    var record = ht.panel.rows[ht.row].dataItem;
                    if(typeof record !== 'undefined' && record.layer_flg === this.FLG_ON){
                        this.selectTree(this.wjTreeViewControl[this.focusVersion], 'filter_tree_path', record.filter_tree_path);
                    }
                }
            });
            // 右クリックイベント
            contextMenuCtrl.itemClicked.addHandler(function(s, e) {
                // メニュークリック
                var rowList = [];
                // クリックした行
                var clickRowDataItem = contextMenuCtrl.row;
                // 選択した行
                var selectedRows = gridCtrl.selectedRows;
                // クリックした行が何番目かを取得する
                var clickRowDataItemIndex = contextMenuCtrl.rowIndex;
                // クリップボードにコピーした文字列
                var clipboardText = this.rightClickInfo.clipboardText;
                // 開かれているタブの階層
                var treeCtrl = this.wjTreeViewControl[this.focusVersion];
                // 今開いている階層の情報
                var kbnIdList = this.getTreeKbnId(treeCtrl);
                // コピー行の貼り付け時のみ使用
                var isAddUnder = true;

                // 選択した項目によって処理を分岐させる
                switch(contextMenuCtrl.selectedValue){
                    case 'copy':
                        // コピー
                        if(selectedRows.length === 0){
                            alert(MSG_ERROR_NO_SELECT);
                        }else if(selectedRows.length % 2 !== 0){
                            alert(MSG_ERR_GRID_SELECTED_ROW);
                        }else if(gridCtrl.selectedRanges[0].columnSpan !== gridCtrl.columns.length){
                            alert(MSG_ERR_GRID_SELECTED_ROW);
                        }else{
                            this.wjMultiRowCopyClipboard(wjCore.Clipboard, gridCtrl, selectedRows);
                        }
                        break;
                    case 'paste':
                        // 貼付け
                        var layount = gridCtrl.layoutDefinition;
                        var clipboardData = this.toWjMultiRowPasteTextFormat(clipboardText);
                        if(selectedRows.length === 0){
                            alert(MSG_ERROR_NO_SELECT);
                        }else if(selectedRows.length % 2 !== 0){
                            alert(MSG_ERR_GRID_SELECTED_ROW);
                        }else if(gridCtrl.selectedRanges[0].columnSpan !== gridCtrl.columns.length){
                            alert(MSG_ERR_GRID_SELECTED_ROW);
                        }else{
                            if(this.wjMultiRowClipBoardValidation(gridCtrl, clipboardText)){ 
                                var pastedRowList = this.wjMultiRowPasteClipboard(clipboardData, gridCtrl, this.NON_PASTING_COLS);
                                for(let i in pastedRowList){
                                    // 階層とグリッドのチェックボックスを外す
                                    this.changeGridCheckBox(pastedRowList[i]);
                                    // 階層の場合、階層名変更
                                    this.changeProduct(null, pastedRowList[i], false);
                                    // 行の金額を計算
                                    this.calcTreeGridRowData(pastedRowList[i], 'quote_quantity');
                                }
                                // 全体の計算
                                this.calcGridCostSalesTotal();
                                this.filterGrid();
                            }
                        }
                        break;
                    case 'upAddRowPaste':
                        // コピーした行を上に貼り付け
                        isAddUnder = false;
                    case 'downAddRowPaste':
                        // コピーした行を下に貼り付け
                        if(this.addRowIsValid(gridCtrl, kbnIdList)){
                            var clipboardData = this.toWjMultiRowPasteTextFormat(clipboardText);
                            var isValid = true;
                            if(clipboardData.length%2 !== 0 || clipboardData.length === 0){
                                alert(MSG_ERROR_PASTE_FORMAT);
                                isValid = false;
                            }else{
                                // 列数が同じかどうかのチェック
                                for(var j=0; j<clipboardData.length && isValid; j++){
                                    var clipboardDataRecord = clipboardData[j].split(this.WJ_MULTI_ROW_CLIPBOARD_CTRL_OPTION.DELIMITER);
                                    if(gridCtrl.layoutDefinition.length !== clipboardDataRecord.length){
                                        alert(MSG_ERROR_PASTE_FORMAT);
                                        isValid = false;
                                        break;
                                    }
                                }
                            }
                            
                            if(isValid){
                                var addList = this.addTreeGridByRightClick(gridCtrl, treeCtrl, clickRowDataItem, clickRowDataItemIndex, clipboardData, this.NON_PASTING_COLS, QUOTE_INIT_ROW, isAddUnder); 
                                for(let i in addList){
                                    // 階層とグリッドのチェックボックスを外す
                                    this.changeGridCheckBox(addList[i]);
                                    // 階層の場合、階層名変更
                                    this.changeProduct(null, addList[i], false);
                                    // 行の金額を計算
                                    this.calcTreeGridRowData(addList[i], 'quote_quantity');
                                }
                                // 全体の計算
                                this.calcGridCostSalesTotal();
                                this.filterGrid();
                            }
                        }
                        break;
                    case 'toLayer':
                        // 階層作成
                        rowList.push(clickRowDataItem);
                        if (this.toLayerIsValid(clickRowDataItem)) {
                            // 階層へ
                            this.treeGridDetailRecordToLayer(gridCtrl.itemsSource.items, treeCtrl, rowList);
                            this.toLayerSetInitProp(clickRowDataItem, true);
                            // ツリー再読み込み
                            treeCtrl.loadTree();
                            // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
                            this.checkedAllTreeGrid(false);
                            // 再計算
                            this.calcTreeGridRowData(clickRowDataItem, 'quote_quantity');
                            // 全体の計算
                            this.calcGridCostSalesTotal();
                            this.filterGrid();
                        }
                        break;
                    case 'toSet':
                        // 一式作成
                        rowList.push(clickRowDataItem);
                        if(this.toSetIsValid(clickRowDataItem)){
                            // 一式化する
                            this.treeGridDetailRecordToSet(gridCtrl.itemsSource.items, treeCtrl, rowList);
                            this.toLayerSetInitProp(clickRowDataItem, false);
                            // ツリー再読み込み
                            treeCtrl.loadTree();
                            // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
                            this.checkedAllTreeGrid(false);
                            // 再計算
                            this.calcTreeGridRowData(clickRowDataItem, 'quote_quantity');
                            // 全体の計算
                            this.calcGridCostSalesTotal();
                            this.filterGrid();
                        }
                        break;
                    case 'addRow':
                        // 空の行を上に挿入
                        // チェック処理
                        if (this.addRowIsValid(gridCtrl)) {
                            // 全体から見て何番目かを取得
                            var rowList = [];
                            rowList[clickRowDataItemIndex] = clickRowDataItem;
                            // 行追加
                            var initRow = Vue.util.extend({}, QUOTE_INIT_ROW);
                            // 親階層の販売額利用フラグが立っている場合は配下行の売価印字フラグのデフォルトをオフにする
                            if (this.getAncestorSalesUseFlg(kbnIdList.filter_tree_path)) {
                                initRow.price_print_flg = false;
                            }
                            var addList = this.addTreeGrid(gridCtrl, treeCtrl, kbnIdList.depth, kbnIdList.filter_tree_path, initRow, false, rowList);
                            // 階層とグリッドのチェックボックスを外す(今開いている階層のみにしか追加しないので末尾の行を渡す)
                            this.changeGridCheckBox(addList[addList.length-1]);

                            // グリッド再描画
                            gridCtrl.collectionView.refresh();
                        }
                        break;
                    case 'deleteRow':
                        // 行削除（受注確定行を含むレコードは削除不可）
                        if(selectedRows.length === 0){
                            alert(MSG_ERROR_NO_SELECT);
                        }else if(selectedRows.length % 2 !== 0){
                            alert(MSG_ERR_GRID_SELECTED_ROW);
                        }else if(gridCtrl.selectedRanges[0].columnSpan !== gridCtrl.columns.length){
                            alert(MSG_ERR_GRID_SELECTED_ROW);
                        }else if(this.deleteRowIsValid(gridCtrl, clickRowDataItem)){
                            if (this.confirm(MSG_CONFIRM_DELETE)) {
                                for (const key in selectedRows) {
                                    const item = selectedRows[key];
                                    if(item.dataItem !== undefined){
                                        this.deleteTreeGridRow(gridCtrl.collectionView.sourceCollection, treeCtrl, item.dataItem.filter_tree_path);
                                    }
                                }
                                treeCtrl.loadTree();
                                // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
                                this.checkedAllTreeGrid(false);
                                this.calcGridCostSalesTotal();
                                this.filterGrid();
                            }
                        }
                        break;
                    default :
                        break;
                }
                
            }.bind(this));
            // 行追加イベント：グリッドの非表示カラムに階層情報をセット
            gridCtrl.rowAdded.addHandler(function (s, e) {
                var kbnIdList = this.getTreeKbnId(this.wjTreeViewControl[this.focusVersion]);
                if(this.rmUndefinedBlank(this.wjTreeViewControl[this.focusVersion].selectedNode) !== ''){
                    // 親階層の販売額利用フラグが立っている場合は配下行の売価印字フラグのデフォルトをオフにする
                    if (this.getAncestorSalesUseFlg(kbnIdList.filter_tree_path)) {
                        s.collectionView.currentAddItem.price_print_flg = false;
                    }
                    this.setTreeGridInvisibleData(s, this.wjTreeViewControl[this.focusVersion]);
                    this.changeGridCheckBox(s.collectionView.currentAddItem);
                }
            }.bind(this));
            // 行削除イベント
            gridCtrl.deletingRow.addHandler(function (s, e) {
                e.cancel = true;
            }.bind(this));
            // セル編集直後イベント：
            gridCtrl.cellEditEnding.addHandler(function (s, e) {
                s.autoClipboard = false;

                var row = s.collectionView.currentItem;
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);
                
                switch (col.name) {
                    case 'chk':
                    case 'row_print_flg':
                    case 'price_print_flg':
                        break;
                    case 'product_code':
                        row.is_cancel = this.changingProduct(this.acProductCodeList[this.focusVersion], row, true);
                        break;
                    case 'product_name':
                        row.is_cancel = this.changingProduct(this.acProductNameList[this.focusVersion], row, false);
                        break;
                    case 'cost_makeup_rate':
                    case 'sales_makeup_rate':
                        if(this.rmUndefinedZero(row.regular_price) === 0){
                            e.cancel = true;
                        }
                        break;
                    default:
                        // 受注確定行の場合は変更NG
                        if (row.received_order_flg == this.FLG_ON) {
                            e.cancel = true;
                        }
                        break;
                }
            }.bind(this));
            // セル編集後イベント：行内のデータ自動セット
            gridCtrl.cellEditEnded.addHandler(function(s, e) {
                var row = s.collectionView.currentItem;
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);

                switch (col.name) {
                    case 'chk':
                        this.changeGridCheckBox(s.collectionView.currentItem);
                        break;
                    case 'product_code':
                        // 商品コード
                        var product = this.acProductCodeList[this.focusVersion].selectedItem;
                        this.changeProduct(product, row, true);
                        break;
                    case 'product_name':
                        // 商品名
                        var product = this.acProductNameList[this.focusVersion].selectedItem;
                        this.changeProduct(product, row, false);
                        break;
                    case 'maker_name':
                        // メーカー名
                        if(!this.acMakerList[this.focusVersion].control.isReadOnly){
                            var maker = this.acMakerList[this.focusVersion].selectedItem;
                            if(maker !== null){
                                if(maker.id !== row.maker_id){
                                    row.maker_id = maker.id;
                                    this.acSupplierList[this.focusVersion].changeItemsSource(this.supplierMakerList[row.maker_id]);
                                    // 仕入先リストの中からに選択されている仕入先名と同一のものを検索
                                    var findIdx = -1;
                                    if(this.acSupplierList[this.focusVersion].control.itemsSource !== null){
                                        findIdx = this.acSupplierList[this.focusVersion].control.itemsSource.findIndex((rec) => {
                                            return (rec.supplier_id == row.supplier_id);
                                        });
                                    }
                                    if (findIdx == -1) {
                                        row.supplier_id     = QUOTE_INIT_ROW.supplier_id;
                                        row.supplier_name   = QUOTE_INIT_ROW.supplier_name;
                                    }
                                }
                            }else{
                                row.maker_id        = QUOTE_INIT_ROW.maker_id;
                                row.maker_name      = QUOTE_INIT_ROW.maker_name;
                                row.supplier_id     = QUOTE_INIT_ROW.supplier_id;
                                row.supplier_name   = QUOTE_INIT_ROW.supplier_name;
                            }
                        }else{
                            if(this.rmUndefinedZero(row.maker_id) === 0){
                                row.maker_name = QUOTE_INIT_ROW.maker_name;
                            }else{
                                for(var i = 0; i < this.makerList.length; i++){
                                    if (this.makerList[i].id == row.maker_id) {
                                        row.maker_name = this.makerList[i].supplier_name;
                                        break;
                                    }
                                }
                            }
                        }
                        break;
                    case 'supplier_name':
                        // 仕入先名
                        if(!this.acSupplierList[this.focusVersion].control.isReadOnly){
                            var supplier = this.acSupplierList[this.focusVersion].selectedItem;
                            if(supplier !== null){
                                row.supplier_id = supplier.supplier_id;
                            }else{
                                row.supplier_id = QUOTE_INIT_ROW.supplier_id;
                                row.supplier_name = QUOTE_INIT_ROW.supplier_name;
                            }
                        }else{
                            if(this.rmUndefinedZero(row.supplier_id) === 0){
                                row.supplier_name = QUOTE_INIT_ROW.supplier_name;
                            }else{
                                for(var i = 0; i < this.supplierList.length; i++){
                                    if (this.supplierList[i].id == row.supplier_id) {
                                        row.supplier_name = this.supplierList[i].supplier_name;
                                        break;
                                    }
                                }
                            }
                        }
                        break;
                    case 'quote_quantity':
                        // 数量
                        this.calcTreeGridRowData(row, 'quote_quantity');
                        this.calcGridCostSalesTotal();
                        break;
                    case 'regular_price':
                        //  定価
                        this.calcTreeGridChangeRegularPrice(row);
                        this.calcTreeGridRowData(row, 'quote_quantity');
                        this.calcGridCostSalesTotal();
                        break;
                    case 'cost_kbn':
                        // 仕入区分
                        if(
                            e.data !== row.cost_kbn &&
                            this.rmUndefinedBlank(row.cost_kbn) !== ''　&& this.rmUndefinedZero(row.product_id) !== 0)
                        {
                            this.changeCostSalesKbn(row, true);
                        }
                        break;
                    case 'sales_kbn':
                        // 販売区分
                        if(
                            e.data !== row.sales_kbn &&
                            this.rmUndefinedBlank(row.sales_kbn) !== ''　&& this.rmUndefinedZero(row.product_id) !== 0)
                        {
                            this.changeCostSalesKbn(row, false);
                        }
                        break;
                    case 'cost_unit_price':
                        // 仕入単価
                        this.calcTreeGridChangeUnitPrice(row, true);
                        this.calcTreeGridRowData(row, 'quote_quantity');
                        this.calcGridCostSalesTotal();
                        break;
                    case 'sales_unit_price':
                        // 販売単価
                        this.calcTreeGridChangeUnitPrice(row, false);
                        this.calcTreeGridRowData(row, 'quote_quantity');
                        this.calcGridCostSalesTotal();
                        break;
                    case 'cost_makeup_rate':
                        // 仕入掛率
                        if(this.rmUndefinedZero(row.regular_price) !== 0){
                            this.calcTreeGridChangeMakeupRate(row, true);
                            this.calcTreeGridRowData(row, 'quote_quantity');
                            this.calcGridCostSalesTotal();
                        }
                        break;
                    case 'sales_makeup_rate':
                        // 販売掛率
                        if(this.rmUndefinedZero(row.regular_price) !== 0){
                            this.calcTreeGridChangeMakeupRate(row, false);
                            this.calcTreeGridRowData(row, 'quote_quantity');
                            this.calcGridCostSalesTotal();
                        }
                        break;
                    case 'gross_profit_rate':
                        // 粗利率
                        this.calcTreeGridChangeGrossProfitRate(row);
                        this.calcTreeGridChangeUnitPrice(row, false);
                        this.calcTreeGridRowData(row, 'quote_quantity');
                        this.calcGridCostSalesTotal();
                        break;
                    case 'sales_use_flg':
                        // 販売額利用フラグ
                        if (!row.sales_use_flg) {
                            row.quote_quantity = this.INIT_ROW_MIN_QUANTITY;
                        }

                        this.calcTreeGridRowData(row, 'quote_quantity');
                        this.calcGridCostSalesTotal();
                        
                        // 階層行の場合は階層のデータを更新
                        if (row.layer_flg === this.FLG_ON) {
                            var item = this.findTree(this.wjTreeViewControl[this.focusVersion].itemsSource, 'filter_tree_path', row.filter_tree_path);
                            item.sales_use_flg = (row.sales_use_flg) ? true:false;                            
                        }
                        break;
                }
                gridCtrl.collectionView.commitEdit();
                
                this.filterGrid();
            }.bind(this));
            // グリッド設定
            this.settingGrid(gridCtrl);

            return gridCtrl;
        },
        // グリッド設定
        settingGrid(gridCtrl) {
            gridCtrl.columns.forEach(element => {
                // 非表示設定
                if (element.name != undefined && this.INVISIBLE_COLS.indexOf(element.name) >= 0) {
                    element.visible = false;
                }
            });
            gridCtrl.itemFormatter = function(panel, r, c, cell) {
                // 列ヘッダ中央寄せ
                if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                    cell.style.textAlign = 'center';
                    
                    // 全チェック用のチェックボックス生成
                    if (panel.columns[c].name == 'chk') {
                        var checkedCount = 0;
                        for (var i = 0; i < gridCtrl.rows.length; i++) {
                            if (gridCtrl.getCellData(i, c) == true) checkedCount++;
                        }

                        // ヘッダ部にチェックボックス追加
                        var checkBox = '<input type="checkbox">';
                        if(this.isReadOnly || this.isEditable){
                            checkBox = '<input type="checkbox" disabled="true">';
                        }
                        cell.innerHTML = checkBox;
                        var checkBox = cell.firstChild;
                        checkBox.checked = checkedCount > 0;
                        checkBox.indeterminate = checkedCount > 0 && checkedCount < gridCtrl.rows.length;

                        // 明細行にチェック状態を反映
                        checkBox.addEventListener('click', function (e) {
                            gridCtrl.beginUpdate();
                            for (var i = 0; i < gridCtrl.collectionView.items.length; i++) {
                                gridCtrl.collectionView.items[i].chk = checkBox.checked;
                                this.changeGridCheckBox(gridCtrl.collectionView.items[i]);
                            }
                            gridCtrl.endUpdate();
                        }.bind(this));
                    }
                }

                // セルごとの設定
                if (panel.cellType == wjGrid.CellType.Cell) {
                    // 背景色デフォルト設定
                    cell.style.backgroundColor = '';
                    // デフォルト左寄せ
                    cell.style.textAlign = 'left';
                    // ReadOnlyセルの背景色設定
                    this.setGridCellReadOnlyColor(gridCtrl, r, c, cell);
                    var col = gridCtrl.getBindingColumn(panel, r, c);
                    var dataItem = panel.rows[r].dataItem;

                    if(dataItem !== undefined){
                        // 販売額利用 or 一式の場合の行の色変更
                        if(dataItem.sales_use_flg || dataItem.set_flg === this.FLG_ON){
                            if(cell.style.backgroundColor !== 'lightgrey'){
                                cell.style.backgroundColor = 'seashell';
                            }
                        }
                    }

                    var versionListNo = this.main.version_list.length - this.focusVersion - 1;
                    // 横スクロールで文字位置がおかしくなるので明示的に設定
                    switch (col.name) {
                        case 'btn_up':
                            // 並び替え（上）
                            cell.style.padding = '0px';
                            cell.innerHTML = '<button type="button" class="btn btn-default multi-grid-btn"><i class="el-icon-arrow-up"></i></button>';
                            break;
                        case 'btn_down':
                            // 並び替え（下）
                            cell.style.padding = '0px';
                            cell.innerHTML = '<button type="button" class="btn btn-default multi-grid-btn"><i class="el-icon-arrow-down"></i></button>';
                            break;
                        case 'quote_quantity':
                            // 見積数
                            cell.style.textAlign = 'right';
                            if (dataItem !== undefined) {
                                if (this.bigNumberMod(dataItem.quote_quantity, dataItem.order_lot_quantity) !== 0 && !this.gridIsReadOnlyCell(gridCtrl, r, c)) {
                                    // 見積数と発注ロット数が合わない時に警告カラー
                                    cell.style.backgroundColor = 'coral';
                                }else if(dataItem.layer_flg === this.FLG_ON || dataItem.set_flg === this.FLG_ON){
                                    // 読取専用色
                                    cell.style.backgroundColor = 'lightgrey';
                                }
                            }
                            break;
                        case 'maker_name':
                            if (dataItem && this.supplierFileList[dataItem.maker_id]) {
                                var elem = document.createElement('a');
                                elem.target = '_blank';
                                elem.rel = 'noopener';
                                elem.href = '/supplier-file-open/' + dataItem.maker_id
                                elem.innerHTML = '<i class="el-icon-notebook-2"></i>';
                                cell.insertBefore(elem, cell.firstChild);
                            }
                            break;
                        case 'supplier_name':
                          if (dataItem && this.supplierFileList[dataItem.supplier_id]) {
                                var elem = document.createElement('a');
                                elem.target = '_blank';
                                elem.rel = 'noopener';
                                elem.href = '/supplier-file-open/' + dataItem.supplier_id
                                elem.innerHTML = '<i class="el-icon-notebook-2"></i>';
                                cell.insertBefore(elem, cell.firstChild);
                            }
                            break;
                        case 'stock_quantity':
                            // 管理数
                            cell.style.textAlign = 'right';
                            if (dataItem) {
                                // 無形品フラグがたっていないなら
                                if (dataItem.intangible_flg === this.FLG_ON) {
                                    cell.innerHTML = '';   
                                }   
                            }
                            break;
                        case 'regular_price':
                        case 'min_quantity':
                        case 'cost_unit_price':
                        case 'sales_unit_price':
                        case 'cost_makeup_rate':
                        case 'sales_makeup_rate':
                        case 'cost_total':
                        case 'sales_total':
                        case 'gross_profit_rate':
                        case 'profit_total':
                            // 右寄せ(見積数、管理数、定価、最小単位数、仕入単価・販売単価、仕入掛率・販売掛率、仕入総額・販売総額、粗利率・粗利総額)
                            cell.style.textAlign = 'right';
                            break;
                        case 'chk':
                            // 中央寄せ
                            cell.style.textAlign = 'center';
                            var rowNum = r / 2;
                            if (dataItem !== undefined) {
                                if (dataItem.received_order_flg == this.FLG_ON || dataItem.copy_received_order_flg == this.FLG_ON) {
                                    // 受注確定済み or コピー元受注確定済み
                                    cell.style.backgroundColor = 'yellow';
                                }
                            }
                            break;
                        case 'row_print_flg' :
                        case 'price_print_flg' :
                            cell.style.textAlign = 'center';
                            break;
                        case 'sales_use_flg' :
                            // 販売額利用フラグ
                            // 中央寄せ
                            cell.style.textAlign = 'center';

                            if(dataItem !== undefined){
                                // 一式行である Or 受注確定行である Or 申請中 Or 承認済の場合は変更させない
                                if(dataItem.set_flg === this.FLG_ON || dataItem.received_order_flg === this.FLG_ON
                                    || this.main.version_list[versionListNo].status == this.STATUS.APPLYING || this.main.version_list[versionListNo].status == this.STATUS.APPROVED
                                ){
                                    cell.childNodes[0].disabled = true;
                                }
                            }
                            break;
                    }
                }
            }.bind(this);
            // キーダウンイベント
            gridCtrl.hostElement.addEventListener('keydown', function (e) {
                // 読み取り専用セルスキップ
                this.gridKeyDownSkipReadOnlyCell(gridCtrl, e, wjGrid);
                // クリップボード処理
                if(gridCtrl.autoClipboard === false){
                    this.wjMultiRowClipboardCtrl(wjCore.Clipboard, gridCtrl, this.NON_PASTING_COLS, this.wjMultiRowClipBoardValidation, function(pastedRowList){
                        for(let i in pastedRowList){
                            // 階層とグリッドのチェックボックスを外す
                            this.changeGridCheckBox(pastedRowList[i]);
                            // 階層の場合、階層名変更
                            this.changeProduct(null, pastedRowList[i], false);
                            // 行の金額を計算
                            this.calcTreeGridRowData(pastedRowList[i], 'quote_quantity');
                        }
                        // 全体の計算
                        this.calcGridCostSalesTotal();
                        this.filterGrid();
                    }.bind(this));
                }

                // 選択行削除(DELETEキー押下時の処理)
                var selectedRowList = gridCtrl.selectedRows;
                if (event.keyCode == 46 && selectedRowList.length > 0 && selectedRowList.length%2 === 0) {
                    var isOk = true;

                    // グリッド行チェック
                    for (const key in selectedRowList) {
                        const item = selectedRowList[key].dataItem;
                        if (!isOk) { break; }
                        if (!this.deleteRowIsValid(gridCtrl, item)) {
                            isOk = false;
                        }
                    }

                    // チェック通過 && 確認ダイアログで『OK』
                    if (isOk && this.confirm(MSG_CONFIRM_DELETE)) {
                        for (const key in selectedRowList) {
                            const item = selectedRowList[key];
                            if (item.dataItem != undefined) {
                                this.deleteTreeGridRow(gridCtrl.collectionView.sourceCollection, this.wjTreeViewControl[this.focusVersion], item.dataItem.filter_tree_path);   
                            }
                        }
                        this.wjTreeViewControl[this.focusVersion].loadTree();
                        // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
                        this.checkedAllTreeGrid(false);
                        this.calcGridCostSalesTotal();
                        this.filterGrid();
                    }
                }

            }.bind(this), true);

            // 商品コードオートコンプリート ※minLength=1でないとACのreadOnlyがtrue且つ入力文字数が1文字の際に、内容の復帰できない
            this.acProductCodeList[this.focusVersion] = new CustomGridEditor(gridCtrl, 'product_code', wjcInput.AutoComplete, {
                delay: 50,
                searchMemberPath: "product_code",
                displayMemberPath: "product_code",
                selectedValuePath: "product_id",
                isRequired: false,
                minLength: 1,
                itemsSourceFunction: (text, maxItems, callback) => {
                    // 編集中の行
                    var row = gridCtrl.collectionView.currentItem;
                    // 階層の場合は何もしない
                    if(row === null || row.layer_flg === this.FLG_ON || row.set_flg === this.FLG_ON){
                        return;
                    }

                    if (!text) {
                        callback([]);
                        return;
                    }

                    // サーバ通信中の場合
                    if(this.acProductCodeList[this.focusVersion].loadingFlg){
                        return;
                    }

                    if(text.length < PRODUCT_AUTO_COMPLETE_SETTING.SEARCH_PRODUCT_CODE_LENGTH){
                        return;
                    }

                    this.setASyncAutoCompleteList(this.acProductCodeList[this.focusVersion], PRODUCT_AUTO_COMPLETE_SETTING.PRODUCT_CODE_URL, text, PRODUCT_AUTO_COMPLETE_SETTING.MAX_LIST_COUNT, PRODUCT_AUTO_COMPLETE_SETTING.OVER_PRODUCT_CODE_LIST, callback, this.getProductCodeAutoCompleteFilterData);
                }
            }, 2, 1, 2);
            // 商品名オートコンプリート ※minLength=1でないとACのreadOnlyがtrue且つ入力文字数が1文字の際に、内容の復帰できない
            this.acProductNameList[this.focusVersion] = new CustomGridEditor(gridCtrl, 'product_name', wjcInput.AutoComplete, {
                delay: 50,
                searchMemberPath: "product_name, product_short_name",
                displayMemberPath: "product_name",
                selectedValuePath: "product_id",
                isRequired: false,
                minLength: 1,
                itemsSourceFunction: (text, maxItems, callback) => {
                    // 編集中の行
                    var row = gridCtrl.collectionView.currentItem;
                    // 階層の場合は何もしない
                    if(row === null || row.layer_flg === this.FLG_ON || row.set_flg === this.FLG_ON){
                        return;
                    }

                    if (!text) {
                        callback([]);
                        return;
                    }

                    // サーバ通信中の場合
                    if(this.acProductNameList[this.focusVersion].loadingFlg){
                        return;
                    }

                    if(text.length < PRODUCT_AUTO_COMPLETE_SETTING.SEARCH_PRODUCT_NAME_LENGTH){
                        return;
                    }

                    this.setASyncAutoCompleteList(this.acProductNameList[this.focusVersion], PRODUCT_AUTO_COMPLETE_SETTING.PRODUCT_NAME_URL, text, PRODUCT_AUTO_COMPLETE_SETTING.MAX_LIST_COUNT, PRODUCT_AUTO_COMPLETE_SETTING.OVER_PRODUCT_NAME_LIST, callback, this.getProductNameAutoCompleteFilterData);
                }
            }, 2, 1, 1);
            // メーカー名オートコンプリート ※minLength=1でないと、リスト外の文字が入力出来てしまう（1文字）
            this.acMakerList[this.focusVersion] = new CustomGridEditor(gridCtrl, 'maker_name', wjcInput.AutoComplete, {
                delay: 50,
                searchMemberPath: "supplier_name, supplier_short_name",
                displayMemberPath: "supplier_name",
                itemsSource: this.makerList,
                selectedValuePath: "id",
                isRequired: false,
                maxItems: this.makerList.length,
                minLength: 1,
                textChanged: this.setTextChanged
            }, 2, 1, 1);
            // 仕入先名オートコンプリート（supplier_maker） ※minLength=1でないと、リスト外の文字が入力出来てしまう（1文字）
            this.acSupplierList[this.focusVersion] = new CustomGridEditor(gridCtrl, 'maker_name', wjcInput.AutoComplete, {
                delay: 50,
                searchMemberPath: "supplier_name, supplier_short_name",
                displayMemberPath: "supplier_name",
                itemsSource: null,
                selectedValuePath: "unique_key",
                isRequired: false,
                maxItems: this.supplierList.length,
                minLength: 1,
                textChanged: this.setTextChanged
            }, 2, 2, 1);
            
        },

        // ******************** LostFocusイベント ********************
        // 相手先担当者LostFocusイベント
        selectPerson: function(sender) {
            this.main.person_id = sender.selectedValue;
        },
        // 見積提出日LostFocusイベント
        inputPrintDate: function(sender) {
            // LostFocus時に選択した見積提出日を開いている見積版データに入れる
            var versionListNo = this.main.version_list.length - this.focusVersion - 1;
            this.main.version_list[versionListNo].quote_create_date = (sender.text == '') ? null:sender.text;   // 空白を代入したらWijmoがエラー吐く
        },
        // 見積提出期限LostFocusイベント
        inputQuoteLimitDate: function(sender) {
            // LostFocus時に選択した見積提出期限を開いている見積版データに入れる
            var versionListNo = this.main.version_list.length - this.focusVersion - 1;
            this.main.version_list[versionListNo].quote_limit_date = (sender.text == '') ? null:sender.text;   // 空白を代入したらWijmoがエラー吐く
        },
        // 支払条件LostFocusイベント
        selectPaycon: function(sender) {
            // LostFocus時に選択した支払条件を開いている見積版データに入れる
            var versionListNo = this.main.version_list.length - this.focusVersion - 1;
            this.main.version_list[versionListNo].payment_condition = this.rmUndefinedBlank(sender.selectedValue);
        },

        // ******************** ボタンクリックイベント ********************
        // 承認申請
        apply() {
            // 確認
            if (!confirm(MSG_CONFIRM_APPLY)) {
                return;
            }
            this.loading = true;

            // エラーの初期化
            this.initErr(this.errors.quote);
            for (const key in this.errors.quoteVerTab) {
                this.initErr(this.errors.quoteVerTab[key]);
            }

            // 入力値を取得
            var params = new FormData();
            this.appendParamsForQuoteSaving(params);

            axios.post('/quote-edit/apply', params, {headers: {'Content-Type': 'multipart/form-data'}})
            .then( function (response) {
                // this.loading = false;
                if (response.data) {
                    if (response.data.status) {
                        // 成功
                        // this.loading = false;

                        window.onbeforeunload = null;
                        var newQuery = '';
                        var query = this.getQuery;
                        var tmpArr = query.split('&');
                        for (var i = 0; i < tmpArr.length; i++) {
                            if (newQuery != '' && newQuery.slice(-1) != '&')  {
                                newQuery += '&';
                            }
                            if (tmpArr[i].indexOf(QUERY_MODE) >= 0) {
                                // 除外
                            } else {
                                newQuery += tmpArr[i];
                            }
                        }
                        if (newQuery.slice(0, 1) != '?') {
                            newQuery = '?' + newQuery;
                        }
                        var selfUrl = window.location.href;
                        var tmpUrl = selfUrl.split('?');
                        var newUrl = tmpUrl[0] + newQuery;
                        location.href = newUrl;
                    }else{
                        this.loading = false;
                        if(response.data.msg){
                            alert(response.data.msg);
                        }else{
                            alert(MSG_ERROR);
                        }
                    }
                }else{
                    this.loading = false;
                    alert(MSG_ERROR);
                }
            }.bind(this))
            .catch(function (error) {
                this.loading = false;
                if (error.response.data.errors) {
                    // エラーメッセージ表示
                    var quoteErr = {};
                    var quoteVerErr = {};
                    for (const key in error.response.data.errors) {
                        const errItem = error.response.data.errors[key];
                        if (key.indexOf('version_list') == -1) {
                            quoteErr[key] = errItem;
                        }else{
                            var splitKey = key.split('.');
                            var version = splitKey[1];
                            var verKey = splitKey[2];
                            if (quoteVerErr[version] == undefined) {
                                quoteVerErr[version] = {};
                            }
                            quoteVerErr[version][verKey] = errItem;
                        }
                    }
                    this.showErrMsg(quoteErr, this.errors.quote);
                    for (const version in quoteVerErr) {
                        this.showErrMsg(quoteVerErr[version], this.errors.quoteVerTab[version])
                    }
                } else {
                    if (error.response.data.message) {
                        alert(error.response.data.message)
                    } else {
                        alert(MSG_ERROR)
                    }
                }
            }.bind(this))
        },
        // 承認申請取消
        applyCancel() {
            // 確認
            if (!confirm(MSG_CONFIRM_APPLY_CANCEL)) {
                return;
            }
            this.loading = true;

            // エラーの初期化
            this.initErr(this.errors.quote);
            for (const key in this.errors.quoteVerTab) {
                this.initErr(this.errors.quoteVerTab[key]);
            }

            // 入力値を取得
            var params = new FormData();
            this.appendParamsForQuoteSaving(params);

            axios.post('/quote-edit/apply-cancel', params)
            .then( function (response) {
                // this.loading = false;
                if (response.data) {
                    if (response.data.status) {
                        // 成功
                        window.onbeforeunload = null;
                        var newQuery = '';
                        var query = this.getQuery;
                        var tmpArr = query.split('&');
                        for (var i = 0; i < tmpArr.length; i++) {
                            if (newQuery != '' && newQuery.slice(-1) != '&')  {
                                newQuery += '&';
                            }
                            if (tmpArr[i].indexOf(QUERY_MODE) >= 0) {
                                // 除外
                            } else {
                                newQuery += tmpArr[i];
                            }
                        }
                        if (newQuery.slice(0, 1) != '?') {
                            newQuery = '?' + newQuery;
                        }
                        var selfUrl = window.location.href;
                        var tmpUrl = selfUrl.split('?');
                        var newUrl = tmpUrl[0] + newQuery;
                        location.href = newUrl;
                    }else{
                        this.loading = false;
                        if(response.data.msg){
                            alert(response.data.msg);
                        }else{
                            alert(MSG_ERROR);
                        }
                    }
                }else{
                    this.loading = false;
                    alert(MSG_ERROR);
                }
            }.bind(this))
            .catch(function (error) {
                this.setErrorInfo(error);
                this.loading = false;
            }.bind(this))
        },
        // 上書き保存（新規保存もこのボタンで可能）
        save() {
            this.loading = true

            // エラーの初期化
            this.initErr(this.errors.quote);
            for (const key in this.errors.quoteVerTab) {
                this.initErr(this.errors.quoteVerTab[key]);
            }

            // 入力値を取得
            var params = new FormData();
            this.appendParamsForQuoteSaving(params);

            axios.post('/quote-edit/save', params, {headers: {'Content-Type': 'multipart/form-data'}})

            .then( function (response) {
                // this.loading = false

                if (response.data) {
                    // 成功
                    if (response.data.status == true) {
                        window.onbeforeunload = null;
                        var newQuery = '';
                        var query = this.getQuery;
                        query = query.substring(1) // ?除去
                        var tmpArr = query.split('&');
                        if (tmpArr.indexOf('matter_name=') == -1) {
                            tmpArr.unshift('matter_name='+response.data.matter_no);
                        }
                        if (tmpArr.indexOf('matter_no=') == -1) {
                            tmpArr.unshift('matter_no='+response.data.matter_no);
                        }
                        for (var i = 0; i < tmpArr.length; i++) {
                            if (newQuery != '' && newQuery.slice(-1) != '&')  {
                                newQuery += '&';
                            }
                            if (tmpArr[i].indexOf(QUERY_MODE) >= 0) {
                                if (newQuery.indexOf(QUERY_MODE) < 0) {
                                    newQuery += QUERY_MODE + '=' + MODE_EDIT;
                                }
                            } else {
                                newQuery += tmpArr[i];
                            }
                        }
                        if (newQuery.indexOf(QUERY_MODE) < 0) {
                            if (newQuery != '')  {
                                newQuery += '&';
                            }
                            newQuery += QUERY_MODE + '=' + MODE_EDIT;
                        }
                        var selfUrl = '/quote-edit/' + response.data.id + '?' + newQuery;

                        location.href = selfUrl;
                    } else {
                        this.loading = false
                        if(response.data.msg){
                            alert(response.data.msg);
                        }else{
                            alert(MSG_ERROR);
                        }
                    }
                    
                } else {
                    this.loading = false
                    alert(MSG_ERROR);
                }
            }.bind(this))

            .catch(function (error) {
                this.setErrorInfo(error);
                this.loading = false;
            }.bind(this))
        },
        // 新規作成
        createNewVersion(){
            // catch無し
            try {
                this.startProcessing();
                var okFlg = true;
                // エラーの初期化
                this.initErrArrObj(this.errors.dialog);

                if (this.newVersionName == '') {
                    // 未入力の場合メッセージ表示
                    this.errors.dialog.new_version_name = MSG_ERROR_NO_INPUT;
                    okFlg = false;
                } else if (this.newVersionName.length > this.LEN_LIMIT_VERSION_NAME) {
                    // 文字数チェック
                    this.errors.dialog.new_version_name = '' + this.LEN_LIMIT_VERSION_NAME + MSG_ERROR_LIMIT_OVER;
                    okFlg = false;
                }

                if(this.selectQuoteToCopy === this.FLG_ON){
                    if (this.rmUndefinedBlank(this.matterNameCombo.selectedValue) == "" || this.rmUndefinedBlank(this.quoteCombo.selectedValue) == "") {
                        // 未選択の場合メッセージ表示
                        this.errors.dialog.select_quote_to_copy = MSG_ERROR_NO_SELECT;
                        okFlg = false;                        
                    }
                }else{
                    if (this.selectQuoteRequest.length == 0) {
                        // 未選択の場合メッセージ表示
                        this.errors.dialog.select_quote_request = MSG_ERROR_NO_SELECT;
                        okFlg = false;
                    }
                }

                if(okFlg) {
                    // エラーの初期化
                    this.initErrArrObj(this.errors.dialog);

                    if(this.selectQuoteToCopy == this.FLG_ON){
                        var quoteNo = this.rmUndefinedBlank(this.quoteCombo.selectedValue);
                        this.setQuoteVersion(quoteNo, this.ZERO)
                        .then( function (result) {
                            if (result) {
                                this.isCopy = true;
                                this.showDlgNewVersion = false;
                                this.focusVersion = (this.main.version_list.length - 1);
                                // 追加したタブを開く
                                this.$nextTick(function() {
                                    // コピー先のタブを開く
                                    document.getElementById('tab-0').click();
                                });   
                            }
                        }.bind(this))
                        .catch( function (result) {
                            // nop
                        }.bind(this));
                    }else{
                        // 版作成
                        this.createVersion();
                        this.isCopy = true;
                        this.showDlgNewVersion = false;
                        this.focusVersion = (this.main.version_list.length - 1);
                        // 追加したタブを開く
                        this.$nextTick(function() {
                            // コピー先のタブを開く
                            document.getElementById('tab-0').click();
                        });
                    }
                }
            } finally{
                // すぐに解除すると連打を防げない為、500ms秒遅らせる
                setTimeout(() => {
                    this.endProcessing();
                }, 500);
            }
        },
        // 新規版を作成し他見積の版情報をセット
        setQuoteVersion(quoteNo, quoteVersion){
            this.loading = true;

            // 入力値の取得
            var params = new URLSearchParams();
            params.append('quote_no', quoteNo);
            params.append('quote_version', quoteVersion);
            var result = false;
            return axios.post('/quote-edit/search-quote', params)
                .then( function (response) {
                    if (response.data) {
                        if (response.data.status == true) {
                            // 成功
                            var quoteLayerList = JSON.parse(JSON.stringify(response.data.quoteLayerList));
                            var quoteDetailList = JSON.parse(JSON.stringify(response.data.quoteDetailList));    // グリッド明細データ作成
                            // 版ヘッダ情報をコピー
                            var quoteVersion = { 
                                quote_version: this.main.version_list.length, 
                                caption: this.newVersionName, 
                                caption_tab: this.newVersionName, 
                                quote_create_date: this.quoteVersionDefault.quote_create_date, 
                                quote_limit_date: null, 
                                quote_enabled_limit_date: this.quoteVersionDefault.quote_enabled_limit_date, 
                                payment_condition: this.quoteVersionDefault.payment_condition, 
                                tax_rate: this.main.version_list[0].tax_rate, 
                                complete_flg_list: [] 
                            };
                            this.main.version_list.unshift(quoteVersion);
                            this.focusVersion = quoteVersion.quote_version;
                            this.errors.quoteVerTab.push(JSON.parse(JSON.stringify(this.errors.quoteVerTabTemplate)));
                            this.acProductCodeList.push([]);
                            this.acProductNameList.push([]);
                            this.acMakerList.push([]);
                            this.acSupplierList.push([]);

                            // 【グリッド】
                            // グリッド明細データ作成
                            var rowData = [];
                            for (var i = 0; i < response.data.quoteDetailList.length; i++) {
                                var rec = response.data.quoteDetailList[i];

                                // 追加部材行はスキップ
                                if (rec.construction_id == this.addLayerId) {
                                    continue;
                                }

                                rec['quote_detail_id'] = QUOTE_INIT_ROW.quote_detail_id;
                                rec['parent_quote_detail_id'] = QUOTE_INIT_ROW.parent_quote_detail_id;
                                rec['received_order_flg'] = QUOTE_INIT_ROW.received_order_flg;
                                rec['complete_flg'] = QUOTE_INIT_ROW.complete_flg;
                                rec['copy_quote_detail_id'] = QUOTE_INIT_ROW.copy_quote_detail_id;
                                rec['copy_received_order_flg'] = QUOTE_INIT_ROW.copy_received_order_flg;
                                rec['copy_complete_flg'] = QUOTE_INIT_ROW.copy_complete_flg;
                                rec['add_flg'] = QUOTE_INIT_ROW.add_flg;
                                rec['over_quote_detail_id'] = QUOTE_INIT_ROW.over_quote_detail_id;

                                // 選択チェックボックス
                                rec['chk'] = false;
                                
                                // decimal型は文字列になっているので数値キャスト
                                if (rec['quote_quantity'] != undefined) {
                                    rec['quote_quantity'] = parseFloat(rec['quote_quantity']);
                                }
                                if (rec['min_quantity'] != undefined) {
                                    rec['min_quantity'] = parseFloat(rec['min_quantity']);
                                }
                                if (rec['order_lot_quantity'] != undefined) {
                                    rec['order_lot_quantity'] = parseFloat(rec['order_lot_quantity']);
                                }
                                if (rec['quantity_per_case'] !== undefined) {
                                    rec['quantity_per_case'] = parseFloat(this.rmUndefinedZero(rec['quantity_per_case']));
                                }
                                if (rec['regular_price'] != undefined) {
                                    rec['regular_price'] = parseInt(rec['regular_price']);
                                }
                                if (rec['cost_unit_price'] != undefined) {
                                    rec['cost_unit_price'] = parseInt(rec['cost_unit_price']);
                                }
                                if (rec['sales_unit_price'] != undefined) {
                                    rec['sales_unit_price'] = parseInt(rec['sales_unit_price']);
                                }
                                if (rec['cost_total'] != undefined) {
                                    rec['cost_total'] = parseInt(rec['cost_total']);
                                }
                                if (rec['sales_total'] != undefined) {
                                    rec['sales_total'] = parseInt(rec['sales_total']);
                                }
                                if (rec['profit_total'] != undefined) {
                                    rec['profit_total'] = parseFloat(rec['profit_total']);
                                }
                                if (rec['cost_makeup_rate'] != undefined) {
                                    rec['cost_makeup_rate'] = parseFloat(rec['cost_makeup_rate']);
                                }
                                if (rec['sales_makeup_rate'] != undefined) {
                                    rec['sales_makeup_rate'] = parseFloat(rec['sales_makeup_rate']);
                                }
                                if (rec['gross_profit_rate'] != undefined) {
                                    rec['gross_profit_rate'] = parseFloat(rec['gross_profit_rate']);
                                }
                                if (rec['row_print_flg'] == undefined) {
                                    rec['row_print_flg'] = false;
                                } else {
                                    if (rec['row_print_flg'] == this.FLG_OFF) {
                                        rec['row_print_flg'] = false;
                                    } else {
                                        rec['row_print_flg'] = true;
                                    }
                                }
                                if (rec['price_print_flg'] == undefined) {
                                    rec['price_print_flg'] = false;
                                } else {
                                    if (rec['price_print_flg'] == this.FLG_OFF) {
                                        rec['price_print_flg'] = false;
                                    } else {
                                        rec['price_print_flg'] = true;
                                    }
                                }
                                if (rec['sales_use_flg'] == undefined) {
                                    rec['sales_use_flg'] = false;
                                } else {
                                    if (rec['sales_use_flg'] == this.FLG_OFF) {
                                        rec['sales_use_flg'] = false;
                                    } else {
                                        rec['sales_use_flg'] = true;
                                    }
                                }
                                // 【フロント制御用】無形品フラグ
                                if (rec['intangible_flg'] == undefined || rec['intangible_flg'] == null) {
                                    if (rec['product_code'] == '') {
                                        rec['intangible_flg'] = this.FLG_ON;
                                    }else{
                                        rec['intangible_flg'] = this.FLG_OFF;
                                    }
                                }
                                rowData.push(rec);
                            }

                            // グリッド作成
                            this.gridDataList.push(rowData);
                            var gridItemSource = new wjCore.CollectionView(this.gridDataList[this.focusVersion], {
                                newItemCreator: function () {
                                    return Vue.util.extend({}, QUOTE_INIT_ROW);
                                }
                            });
                            this.$nextTick(function() {
                                var gridCtrl = this.createGridCtrl(gridItemSource);
                                // グリッドコントロール配列の最後に追加
                                this.wjMultiRowControl.push(gridCtrl);
                            });

                            // 【ツリー】
                            //  階層作成
                            for (var i = 0; i < quoteLayerList.length; i++) {
                                var layerEle = quoteLayerList[i][0].items;
                                if (layerEle.length > 0) {
                                    for (var j = 0; j < layerEle.length; j++) {
                                        // 依頼項目に存在するかチェック
                                        for (var x = 0; x < requestedItems.length; x++) {
                                            if (requestedItems[x] == layerEle[j].construction_id) {
                                                layerEle[j].link_flg = true;
                                                layerEle[j].tab_id = layerEle[j].construction_id;
                                                break;
                                            }
                                        }
                                    }
                                }

                                this.treeDataList.push(quoteLayerList[i]);
                            }
                            // 追加部材階層削除
                            var addConstructionIdx = this.treeDataList[this.focusVersion][0].items.findIndex((rec) => { return (rec.construction_id == this.addLayerId);})
                            if (addConstructionIdx != -1) {
                                this.treeDataList[this.focusVersion][0].items.splice(addConstructionIdx, 1)
                            }

                            // TreeView追加
                            this.$nextTick(function() {
                                var targetTreeDivId = '#quoteLayerTree-' + this.focusVersion;
                                var treeItemsSource = this.treeDataList[this.focusVersion];
                                var treeCtrl = this.createTreeCtrl(targetTreeDivId, treeItemsSource);
                                // ツリーコントロール配列の最後に追加
                                this.wjTreeViewControl.push(treeCtrl);
                                this.selectTree(treeCtrl, 'top_flg', this.FLG_ON);
                            });

                            // アップロードリスト追加
                            this.uploadFileList.push([]);
                            this.deleteFileList.push([]);

                            result = true;
                        } else {
                            // 失敗
                            alert(MSG_ERROR);
                        }
                    } else {
                        // 失敗
                        alert(MSG_ERROR);
                    }
                    return result;
                }.bind(this))
                .catch(function (error) {
                    alert(MSG_ERROR);
                    return result;
                }.bind(this))
                .finally(function () {
                    this.loading = false;
                }.bind(this))
        },
        // 版作成
        createVersion(){
            var quoteVersion = { 
                                    quote_version: this.main.version_list.length, 
                                    caption: this.newVersionName, 
                                    caption_tab: this.newVersionName, 
                                    quote_create_date: this.quoteVersionDefault.quote_create_date, 
                                    quote_limit_date: null, 
                                    quote_enabled_limit_date: this.quoteVersionDefault.quote_enabled_limit_date, 
                                    payment_condition: this.quoteVersionDefault.payment_condition, 
                                    tax_rate: this.main.version_list[0].tax_rate, 
                                    complete_flg_list: [] 
                                };
            this.main.version_list.unshift(quoteVersion);
            this.focusVersion = quoteVersion.quote_version;
            this.errors.quoteVerTab.push(JSON.parse(JSON.stringify(this.errors.quoteVerTabTemplate)));

            // グリッド明細データ作成
            var rowData = [];
            // 階層データ作成
            var childLayer = [];
            // var grandChildLayer = [];
            var tmpQreqList = this.qreqList;
            // var tmpQreqConList = this.qreqConList;
            for (var i = 0; i < this.selectQuoteRequest.length; i++) {
                var selectQreqKbnId = this.selectQuoteRequest[i];
                // 選択肢のconstruction_idとチェックを付けたconstruction_idが一致したもののconstruction_nameを階層の名称に使用
                // tmpQreqList.forEach(function(qreq) {
                for (var j in tmpQreqList) {
                    var qreq = tmpQreqList[j];
                    if (qreq.construction_id == selectQreqKbnId) {
                        // 依頼項目に存在するかチェック
                        var linkFlg = false;
                        for (var x = 0; x < requestedItems.length; x++) {
                            if (requestedItems[x] == qreq.construction_id) {
                                linkFlg = true;
                                break;
                            }
                        }
                        // branch
                        var childObj = {
                            id: 0,
                            construction_id: qreq.construction_id,
                            layer_flg: this.FLG_ON,
                            parent_quote_detail_id: 0,
                            seq_no: childLayer.length,
                            depth: this.QUOTE_CONSTRUCTION_DEPTH,
                            tree_path: '',
                            row_print_flg: false,
                            price_print_flg: false,
                            sales_use_flg: false,
                            product_name: qreq.construction_name,
                            add_flg: this.FLG_OFF,
                            top_flg: this.FLG_OFF,
                            header: qreq.construction_name,
                            filter_tree_path: '' + childLayer.length,
                            to_layer_flg: this.FLG_OFF,
                            link_flg: linkFlg,
                            tab_id: qreq.construction_id,
                            items: [],
                        };
                        childLayer.push(childObj);

                        // grid row
                        rowData[i] = Vue.util.extend({}, QUOTE_INIT_ROW);
                        rowData[i].construction_id = qreq.construction_id;
                        rowData[i].product_name = qreq.construction_name;
                        rowData[i].layer_flg = this.FLG_ON;
                        rowData[i].filter_tree_path = '' + childObj.filter_tree_path;
                        rowData[i].seq_no = childObj.seq_no;
                        rowData[i].min_quantity    = this.INIT_ROW_MIN_QUANTITY;
                        rowData[i].order_lot_quantity  = this.INIT_ROW_ORDER_LOT_QUANTITY;

                        // 管理数等を計算させる為
                        this.calcTreeGridRowData(rowData[i], 'quote_quantity');
                    }
                }
            }
            // 工事区分の階層を追加
            this.treedata[0].items = childLayer;
            // ツリーデータ配列の最後に追加
            this.treeDataList.push(this.treedata);

            // グリッド追加
            this.gridDataList.push(rowData);
            var gridItemSource = new wjCore.CollectionView(this.gridDataList[this.focusVersion], {
                newItemCreator: function () {
                    return Vue.util.extend({}, QUOTE_INIT_ROW);
                }
            });
            this.acProductCodeList.push([]);
            this.acProductNameList.push([]);
            this.acMakerList.push([]);
            this.acSupplierList.push([]);

            this.$nextTick(function() {
                var gridCtrl = this.createGridCtrl(gridItemSource);
                // グリッドコントロール配列の最後に追加
                this.wjMultiRowControl.push(gridCtrl);
            });
            
            // TreeView追加
            this.$nextTick(function() {
                var targetTreeDivId = '#quoteLayerTree-' + this.focusVersion;
                var treeItemsSource = this.treeDataList[this.focusVersion];
                var treeCtrl = this.createTreeCtrl(targetTreeDivId, treeItemsSource);
                // ツリーコントロール配列の最後に追加
                this.wjTreeViewControl.push(treeCtrl);
                this.selectTree(treeCtrl, 'top_flg', this.FLG_ON);
            });

            // アップロードリスト追加
            this.uploadFileList.push([]);
            this.deleteFileList.push([]);
        },
        // 複製して新規作成
        createCopyVersion() {
            // エラーの初期化
            this.initErrArrObj(this.errors.dialog);

            var okFlg = true;
            if (this.newVersionName == '') {
                // 未入力の場合メッセージ表示
                this.errors.dialog.new_version_name = MSG_ERROR_NO_INPUT;
                okFlg = false;
            } else if (this.newVersionName.length > this.LEN_LIMIT_VERSION_NAME) {
                // 文字数チェック
                this.errors.dialog.new_version_name = '' + this.LEN_LIMIT_VERSION_NAME + MSG_ERROR_LIMIT_OVER;
                okFlg = false;
            }

            if (!okFlg) {
                return;
            }

            // 版ヘッダ情報をコピー
            var versionListNo = this.main.version_list.length - this.focusVersion - 1;  // main.version_listは版番号と逆順の配列になっている
            var verHeader = Vue.util.extend({}, this.main.version_list[versionListNo]);
            this.main.version_list.unshift(verHeader);
            this.main.version_list[0].quote_version_id = 0;
            this.main.version_list[0].quote_version = this.main.version_list.length - 1;
            this.main.version_list[0].caption = this.newVersionName;
            this.main.version_list[0].caption_tab = this.newVersionName;
            this.main.version_list[0].status = this.STATUS.EDITING;
            this.main.version_list[0].approval_status = null;

            // グリッド作成
            // 階層データセット 配列ディープコピー
            var grid = this.gridDataList[this.focusVersion];
            if (grid != undefined) {
                var jsonCopyGrid = JSON.stringify(grid);
                grid = JSON.parse(jsonCopyGrid);

                // 追加部材のグリッド行を削除
                // var addConstructionIdx = grid.findIndex((rec) => { return (rec.construction_id == this.addLayerId) });
                // if (addConstructionIdx != -1) {
                //     grid.splice(addConstructionIdx, 1);
                // }
                do {
                    var addConstructionIdx = grid.findIndex((rec) => { return (rec.construction_id == this.addLayerId) });
                    if (addConstructionIdx != -1) {
                        grid.splice(addConstructionIdx, 1);
                    }
                } while (addConstructionIdx != -1);

                for (var i = 0; i < grid.length; i++) {
                    // コピー元見積明細ID ※コピー元受注選択フラグが立っている場合は上書きしない
                    if (grid[i]['copy_received_order_flg'] == this.FLG_OFF) {
                        grid[i]['copy_quote_detail_id'] = grid[i]['quote_detail_id'];
                    }
                    // コピー元受注選択フラグ
                    grid[i]['copy_received_order_flg'] = grid[i]['received_order_flg'];
                    // コピー元積算完了フラグ
                    grid[i]['copy_complete_flg'] = grid[i]['complete_flg'];

                    // コピー分は新規登録させるために見積明細IDを空にする
                    grid[i]['quote_detail_id'] = '';
                    // 積算完了フラグ
                    grid[i]['complete_flg'] = this.FLG_OFF;
                }
            }
            this.gridDataList.push(grid);
            var gridItemSource = new wjCore.CollectionView(this.gridDataList[this.gridDataList.length - 1], {
                newItemCreator: function () {
                    return Vue.util.extend({}, QUOTE_INIT_ROW);
                }
            });
            this.acProductCodeList.push([]);
            this.acProductNameList.push([]);
            this.acMakerList.push([]);
            this.acSupplierList.push([]);
            this.errors.quoteVerTab.push(JSON.parse(JSON.stringify(this.errors.quoteVerTabTemplate)));

            this.$nextTick(function() {
                var gridCtrl = this.createGridCtrl(gridItemSource);
                this.wjMultiRowControl.push(gridCtrl);
            });

            // 階層ディープコピー
            var jsonCopyTree = JSON.stringify(this.treeDataList[this.focusVersion]);
            this.treedata = JSON.parse(jsonCopyTree);

            // 追加部材階層削除
            addConstructionIdx = this.treedata[0].items.findIndex((rec) => { return (rec.construction_id == this.addLayerId);})
            if (addConstructionIdx != -1) {
                this.treedata[0].items.splice(addConstructionIdx, 1)
            }

            this.treeDataList.push(this.treedata);
            // TreeView追加
            this.$nextTick(function() {
                var targetTreeDivId = '#quoteLayerTree-' + (this.treeDataList.length - 1);
                var treeItemsSource = this.treeDataList[this.treeDataList.length - 1];
                var treeCtrl = this.createTreeCtrl(targetTreeDivId, treeItemsSource);
                this.wjTreeViewControl.push(treeCtrl);
                this.selectTree(treeCtrl, 'top_flg', this.FLG_ON);
            });

            // 添付ファイルディープコピー
            var jsonCopyFile = JSON.stringify(this.uploadFileList[this.focusVersion]);
            var copyFile = JSON.parse(jsonCopyFile);
            for (var i = 0; i < copyFile.length; i++) {
                copyFile[i].copy_flg = true;
                copyFile[i].parent_version_id = this.maindata.version_list[versionListNo + 1].quote_version_id;
            }
            this.uploadFileList.push(copyFile);
            this.deleteFileList.push([]);

            this.$nextTick(function() {
                // コピー先のタブを開く
                document.getElementById('tab-0').click();
            });
            this.focusVersion = (this.main.version_list.length - 1);

            this.isCopy = true;
 
            //  ダイアログを閉じる
            this.showDlgCopyVersion = false;
        },
        // 印刷
        print(quoteVersionId) {
            if (this.isReadOnly) {
                // 印刷プレビュー画面を別タブで開く
                var url = '/quote-report/print/' + quoteVersionId;
                window.open(url, "_blank");
            } else {
                // 編集モードのときは保存処理を先に通す
                this.loading = true;

                // エラーの初期化
                this.initErr(this.errors.quote);
                for (const key in this.errors.quoteVerTab) {
                    this.initErr(this.errors.quoteVerTab[key]);
                }

                // 入力値を取得
                var params = new FormData();
                this.appendParamsForQuoteSaving(params);
                var postUrl = '/quote-edit/save';
                axios.post(postUrl, params, {headers: {'Content-Type': 'multipart/form-data'}})
                    .then( function (response) {
                        this.loading = false

                        if (response.data) {
                            // 成功
                            if (response.data.status == true) {
                                // 印刷プレビュー画面を別タブで開く
                                var url = '/quote-report/print/' + quoteVersionId;
                                window.open(url, "_blank");
                            } else {
                                alert(response.data.msg);
                            }
                            
                        } else {
                            // 失敗
                            alert(MSG_ERROR);
                        }
                    }.bind(this))

                    .catch(function (error) {
                        this.setErrorInfo(error);
                        this.loading = false;
                    }.bind(this));
            }
        },
        // 指定印刷
        printTarget(quoteVersionId) {
            // チェックレコード取得
            var quoteDetailIdList = [];
            var gridData = this.gridDataList[this.focusVersion];
            for (var i = 0; i < gridData.length; i++) {
                if (gridData[i].chk == true) {
                    if (this.rmUndefinedZero(gridData[i].quote_detail_id) == 0) {
                        // チェックが付いている明細にIDがない
                        alert(MSG_ERROR_NOT_SAVED);
                        return;
                    }
                    quoteDetailIdList.push(gridData[i].quote_detail_id);
                }
            }
            if (quoteDetailIdList.length == 0) {
                // チェックが付いている明細なし
                alert(MSG_ERROR_NO_SELECT);
                return;
            }
            
            this.loading = true

            // エラーの初期化
            this.initErr(this.errors.quote);
            for (const key in this.errors.quoteVerTab) {
                this.initErr(this.errors.quoteVerTab[key]);
            }

            // 入力値を取得
            var params = new FormData();
            this.appendParamsForQuoteSaving(params);
            // チェックが付いている明細リスト
            params.append('quote_detail_ids', JSON.stringify(quoteDetailIdList));

            axios.post('/quote-edit/print-target', params, {headers: {'Content-Type': 'multipart/form-data'}})

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    if (response.data.status == true) {
                        // 印刷プレビュー画面を別タブで開く
                        var url = '/quote-report/print-target/' + quoteVersionId;
                        window.open(url, "_blank");
                    } else {
                        if(response.data.msg){
                            alert(response.data.msg);
                        }else{
                            alert(MSG_ERROR);
                        }
                    }
                    
                } else {
                    // 失敗
                    alert(MSG_ERROR)
                }
            }.bind(this))

            .catch(function (error) {
                this.setErrorInfo(error);
                this.loading = false;
            }.bind(this));
        },
        // 受注登録
        receivedOrder() {
            var quoteDetailIdList = [];
            var gridData = this.gridDataList[this.focusVersion];
            for (var i = 0; i < gridData.length; i++) {
                if (gridData[i].chk == true) {
                    // 受注確定行はスキップ
                    if (gridData[i].received_order_flg == this.FLG_ON) {
                        continue;
                    }
                    if (this.rmUndefinedZero(gridData[i].quote_detail_id) == 0) {
                        // チェックが付いている明細にIDがない
                        alert(MSG_ERROR_NOT_SAVED);
                        return;
                    }

                    quoteDetailIdList.push(gridData[i].quote_detail_id);
                }
            }
            if (quoteDetailIdList.length == 0) {
                // チェックが付いている明細なし
                alert(MSG_ERROR_NO_SELECT);
                return;
            }
            // 確認
            if (!confirm(MSG_CONFIRM_RECIVED_ORDER)) {
                return;
            }

            this.loading = true;

            // エラーの初期化
            this.initErr(this.errors.quote);
            for (const key in this.errors.quoteVerTab) {
                this.initErr(this.errors.quoteVerTab[key]);
            }

            // 入力値を取得
            var params = new FormData();
            this.appendParamsForQuoteSaving(params);
            // チェックが付いている明細リスト
            params.append('quote_detail_ids', JSON.stringify(quoteDetailIdList));

            axios.post('/quote-edit/received-order', params, {headers: {'Content-Type': 'multipart/form-data'}})
            .then( function (response) {
                // this.loading = false;

                if (response.data) {
                    // 成功
                    if (response.data.status == true) {
                        window.onbeforeunload = null;

                        var selfUrl = window.location.href;
                        var tmpArr = selfUrl.split('?');
                        var newUrl = tmpArr[0] + this.getQuery;
                        location.href = newUrl;
                    } else {
                        this.loading = false;
                        if(response.data.msg){
                            alert(response.data.msg);
                        }else{
                            alert(MSG_ERROR);
                        }
                    }
                }else{
                    // 失敗
                    this.loading = false;
                    alert(MSG_ERROR);
                }
            }.bind(this))
            .catch(function (error) {
                this.setErrorInfo(error);
                this.loading = false;         
            }.bind(this))

        },
        // 積算完了ダイアログ表示
        showDlgCompEstimationPrepare() {
            var versionListNo = this.main.version_list.length - this.focusVersion - 1;
            this.selectEstimationList = [];
            // 階層に存在する大分類を取得
            this.choiceEstimationList = [];
            var currentTree = this.treeDataList[this.focusVersion];
            if (currentTree.length > 0) {
                var top = currentTree[0];
                if (top.items != undefined) {
                    for (var i = 0; i < top.items.length; i++) {
                        if (this.rmUndefinedZero(top.items[i].id) == 0) {
                            // チェックが付いている工事区分階層にIDがない
                            alert(MSG_ERROR_NOT_SAVED);
                            return;
                        }
                        // 積算完了解除の場合、選択肢として表示するのは積算完了されているもののみ
                        if (this.showDlgReleaseEstimation && this.main.version_list[versionListNo].complete_flg_list.indexOf(top.items[i].construction_id) == -1) {
                            continue;
                        }
                        // 工事区分階層
                        this.choiceEstimationList.push({'construction_id': top.items[i].construction_id, 'construction_name': top.items[i].header});
                    }
                }
            }
            
            // ダイアログ表示
            this.showDlgCompEstimation = true;
        },
        // 積算完了
        compEstimation() {
            // エラーの初期化（ダイアログ）
            this.initErrArrObj(this.errors.dialog);

            if (this.selectEstimationList.length == 0) {
                // チェックが付いている明細なし
                this.errors.dialog.select_estimation = MSG_ERROR_NO_SELECT;
                return;
            }

            this.loading = true;

            // エラーの初期化（見積、見積版）
            this.initErr(this.errors.quote);
            for (const key in this.errors.quoteVerTab) {
                this.initErr(this.errors.quoteVerTab[key]);
            }

            var params = new URLSearchParams();
            this.appendParamsForQuoteSaving(params);
            if (this.showDlgReleaseEstimation) {
                params.append('is_estimation_release', this.FLG_ON);
            }else{
                params.append('is_estimation_release', this.FLG_OFF);
            }
            params.append('estimation_ids', JSON.stringify(this.selectEstimationList));   // 積算完了する工事区分IDのリストをJSON化
            
            axios.post('/quote-edit/complete-estimation', params)
            .then( function (response) {
                // this.loading = false;
                if (response.data) {
                    if (response.data.status == true) {
                        // 成功
                        this.showDlgCompEstimation = false;

                        window.onbeforeunload = null;
                        // location.reload();

                        var selfUrl = window.location.href;
                        var tmpArr = selfUrl.split('?');
                        var newUrl = tmpArr[0] + this.getQuery;
                        location.href = newUrl;
                    } else {
                        // 失敗
                        this.loading = false;
                        if(response.data.msg){
                            alert(response.data.msg);
                        }else{
                            alert(MSG_ERROR);
                        }
                    }
                }else{
                    this.loading = false;
                    alert(MSG_ERROR);
                }
            }.bind(this))
            .catch(function (error) {
                this.setErrorInfo(error);
                this.loading = false;
            }.bind(this))
        },
        // 見積削除
        del() {
            // 確認
            if (!confirm(MSG_CONFIRM_QUOTE_DELETE)) {
                return;
            }
            this.loading = true;

            var params = new URLSearchParams();
            params.append('quote_id', this.rmUndefinedBlank(this.main.quote_id));
            params.append('quote_no', this.rmUndefinedBlank(this.main.quote_no));
            params.append('matter_id', this.rmUndefinedBlank(this.main.matter_id));
            params.append('matter_no', this.rmUndefinedBlank(this.main.matter_no));

            axios.post('/quote-edit/delete', params)
            .then( function (response) {
                // this.loading = false;

                if (response.data == true) {
                    // 成功
                    var query = window.location.search;
                    var rtnUrl = this.getLocationUrl(query);
                    if (rtnUrl == '') {
                        rtnUrl = '/quote-list';
                    }
                    var listUrl = rtnUrl + this.getQuery;

                    window.onbeforeunload = null;
                    location.href = listUrl;
                } else {
                    // 失敗
                    window.onbeforeunload = null;
                    // location.reload();

                    var selfUrl = window.location.href;
                    var tmpArr = selfUrl.split('?');
                    var newUrl = tmpArr[0] + this.getQuery;
                    location.href = newUrl;
                }
            }.bind(this))
            .catch(function (error) {
                if (error.response.data.errors) {
                    // // エラーメッセージ表示
                    // this.showErrMsg(error.response.data.errors, this.errors);
                } else {
                    this.loading = false;
                    if (error.response.data.message) {
                        alert(error.response.data.message);
                    } else {
                        alert(MSG_ERROR);
                    }
                }
                this.loading = false;
            }.bind(this))
        },
        // 戻る
        back() {
            // 照会モード以外の場合は編集箇所があるか確認する
            if (!this.isReadOnly) {
                var isSame = this.isSameSaveData();
                // 編集箇所が存在する && 確認ダイアログで『キャンセル』
                if (!isSame && !this.confirm(MSG_CONFIRM_LEAVE_EDITED)) {
                    return false;
                }   
            }
            // 遷移元URL取得
            var query = window.location.search
            var rtnUrl = this.getLocationUrl(query)
            if (rtnUrl == '') {
                rtnUrl = '/quote-list';
            }

            var listUrl = rtnUrl;
            // IDがある時はクエリをくっつける
            if (this.main.quote_id) {
                listUrl += this.getQuery;
            }

            if (!this.isReadOnly && this.main.matter_id && this.main.quote_id) {
                this.loading = true;

                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'quote-edit');
                params.append('keys', this.rmUndefinedBlank(this.main.quote_id) + '.' + this.rmUndefinedBlank(this.main.matter_id));

                axios.post('/common/release-lock', params)
                .then( function (response) {
                    // this.loading = false;
                    if (response.data) {
                        window.onbeforeunload = null;
                        location.href = listUrl
                    } else {
                        window.onbeforeunload = null;
                        // location.reload();

                        var selfUrl = window.location.href;
                        var tmpArr = selfUrl.split('?');
                        var newUrl = tmpArr[0] + this.getQuery;
                        location.href = newUrl;
                    }
                }.bind(this))
                .catch(function (error) {
                    this.loading = false;
                    if (error.response.data.message) {
                        alert(error.response.data.message);
                    } else {
                        alert(MSG_ERROR);
                    }
                }.bind(this))
            } else {
                window.onbeforeunload = null;
                location.href = listUrl;
            }
        },
        // 編集モード
        edit() {
            this.loading = true
            var params = new URLSearchParams();
            params.append('screen', 'quote-edit');
            params.append('keys', this.rmUndefinedBlank(this.main.quote_id) + '.' + this.rmUndefinedBlank(this.main.matter_id));

            axios.post('/common/lock', params)
            .then( function (response) {
                // this.loading = false;
                if (response.data.status) {
                    if (response.data.isLocked) {
                        alert(MSG_EDITING);
                        // location.reload();

                        var selfUrl = window.location.href;
                        var tmpArr = selfUrl.split('?');
                        var newUrl = tmpArr[0] + this.getQuery;
                        location.href = newUrl;
                    } else {
                        this.isReadOnly = false;
                        this.isShowEditBtn = false;
                        this.lock = response.data.lockdata;

                        // グリッドのReadOnly解除のためにリロード
                        window.onbeforeunload = null;
                        // location.reload();

                        var selfUrl = window.location.href;
                        var tmpArr = selfUrl.split('?');
                        var newUrl = tmpArr[0] + this.getQuery;
                        location.href = newUrl;
                    }
                } else {
                    window.onbeforeunload = null;
                    // location.reload();

                    var selfUrl = window.location.href;
                    var tmpArr = selfUrl.split('?');
                    var newUrl = tmpArr[0] + this.getQuery;
                    location.href = newUrl;
                }
            }.bind(this))
            .catch(function (error) {
                this.loading = false;
                if (error.response.data.message) {
                    alert(error.response.data.message);
                } else {
                    alert(MSG_ERROR);
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
            params.append('screen', 'quote-edit');
            params.append('keys', this.rmUndefinedBlank(this.main.quote_id) + '.' + this.rmUndefinedBlank(this.main.matter_id));

            axios.post('/common/gain-lock', params)
            .then( function (response) {
                // this.loading = false;
                if (response.data.status) {
                    // 編集中状態へ
                    this.isLocked = false;
                    this.isReadOnly = false;
                    this.isShowEditBtn = false;
                    this.lock = response.data.lockdata;

                    // グリッドのReadOnly解除のためにリロード
                    window.onbeforeunload = null;
                    // location.reload();

                    var selfUrl = window.location.href;
                    var tmpArr = selfUrl.split('?');
                    var newUrl = tmpArr[0] + this.getQuery;
                    location.href = newUrl;
                } else {
                    // ロック取得失敗
                    window.onbeforeunload = null;
                    // location.reload();

                    var selfUrl = window.location.href;
                    var tmpArr = selfUrl.split('?');
                    var newUrl = tmpArr[0] + this.getQuery;
                    location.href = newUrl;
                }
            }.bind(this))
            .catch(function (error) {
                this.loading = false;
                if (error.response.data.message) {
                    alert(error.response.data.message);
                } else {
                    alert(MSG_ERROR);
                }
            }.bind(this))
        },
        // ロック解除
        lockRelease() {
            if (!confirm(MSG_CONFIRM_LOCK_RELEASE)) {
                return;
            }

            this.loading = true;

            // ロック解放
            var params = new URLSearchParams();
            params.append('screen', 'quote-edit');
            params.append('keys', this.rmUndefinedBlank(this.main.quote_id) + '.' + this.rmUndefinedBlank(this.main.matter_id));

            axios.post('/common/release-lock', params)
            .then( function (response) {
                // this.loading = false;
                window.onbeforeunload = null;

                var newQuery = '';
                var query = this.getQuery;
                var tmpArr = query.split('&');
                for (var i = 0; i < tmpArr.length; i++) {
                    if (newQuery != '' && newQuery.slice(-1) != '&')  {
                        newQuery += '&';
                    }
                    if (tmpArr[i].indexOf(QUERY_MODE) >= 0) {
                        // 除外
                    } else {
                        newQuery += tmpArr[i];
                    }
                }
                if (newQuery.slice(0, 1) != '?') {
                    newQuery = '?' + newQuery;
                }
                var selfUrl = window.location.href;
                var tmpUrl = selfUrl.split('?');
                var newUrl = tmpUrl[0] + newQuery;
                location.href = newUrl;

            }.bind(this))
            .catch(function (error) {
                this.loading = false;
                if (error.response.data.message) {
                    alert(error.response.data.message);
                } else {
                    alert(MSG_ERROR);
                }
            }.bind(this))
        },
        // 階層化したあとに初期値をセット
        toLayerSetInitProp(row, isLayer){
            if (!isLayer) {
                row.sales_use_flg = true;
            }
            row.intangible_flg   = this.FLG_ON;
            row.product_id       = QUOTE_INIT_ROW.product_id;
            row.product_maker_id = QUOTE_INIT_ROW.product_maker_id;
            row.min_quantity     = this.INIT_ROW_MIN_QUANTITY;
            row.order_lot_quantity = this.INIT_ROW_ORDER_LOT_QUANTITY;
            row.quote_quantity   = this.INIT_ROW_MIN_QUANTITY;
        },
        /**
         * 行追加
         * @param {*} isAddunder true:行追加（下） false:行追加（上） 
         */
        addRow(isAddUnder) {
            // チェックが付いている明細行を特定
            var gridData = this.wjMultiRowControl[this.focusVersion].collectionView.sourceCollection;
            var kbnIdList = this.getTreeKbnId(this.wjTreeViewControl[this.focusVersion]);

            // 今開いている階層のチェックが付いたレコードを取得
            var rowList = [];
            for (var i = 0; i < gridData.length; i++) {
                if (gridData[i].chk && gridData[i].depth === (kbnIdList.depth+1) && gridData[i].filter_tree_path.indexOf(kbnIdList.filter_tree_path) === 0) {
                    rowList[i] = gridData[i];
                }
            }

            if(rowList.length === 0){
                // チェックが付いている明細なし
                alert(MSG_ERROR_NO_SELECT);
                return;
            }

            if (this.addRowIsValid(this.wjMultiRowControl[this.focusVersion])) {
                var initRow = Vue.util.extend({}, QUOTE_INIT_ROW);
                // 親階層の販売額利用フラグが立っている場合は配下行の売価印字フラグのデフォルトをオフにする
                if (this.getAncestorSalesUseFlg(kbnIdList.filter_tree_path)) {
                    initRow.price_print_flg = false;
                }

                // 行追加
                var addList = this.addTreeGrid(this.wjMultiRowControl[this.focusVersion], this.wjTreeViewControl[this.focusVersion], kbnIdList.depth, kbnIdList.filter_tree_path, initRow, isAddUnder);
                // 階層とグリッドのチェックボックスを外す(今開いている階層のみにしか追加しないので末尾の行を渡す)
                this.changeGridCheckBox(addList[addList.length-1]);
                // グリッド再描画
                this.wjMultiRowControl[this.focusVersion].collectionView.refresh();   
            }
        },
        // 行削除
        deleteRows() {
            // チェックが付いている明細行を特定
            var rowList = [];
            var gridData = this.wjMultiRowControl[this.focusVersion].collectionView.sourceCollection;
            var kbnIdList = this.getTreeKbnId(this.wjTreeViewControl[this.focusVersion]);

            var isOk = true;

            // 0版 OR 照会モード OR 申請中 OR 承認済みはNG
            var versionListNo = this.main.version_list.length - this.focusVersion - 1;  // main.version_listは版番号と逆順の配列になっている
            if (this.focusVersion === this.ZERO || this.isReadOnly ||
                this.main.version_list[versionListNo].status == this.STATUS.APPLYING || this.main.version_list[versionListNo].status == this.STATUS.APPROVED
            ){
                alert(MSG_ERROR_DELETE_ROW);
                isOk = false;
            }

            // 対象行洗い出し
            for (var i = 0; i < gridData.length; i++) {
                if (!isOk) { break; }
                if (gridData[i].chk) {
                    if((kbnIdList.top_flg === this.FLG_ON && gridData[i].depth === this.QUOTE_CONSTRUCTION_DEPTH) || 
                      (gridData[i].depth === (kbnIdList.depth+1) && gridData[i].filter_tree_path.indexOf(kbnIdList.filter_tree_path) === 0)
                    ){
                        rowList[i] = gridData[i];
                    }
                }
            }
            
            // 削除対象が存在しない
            if (isOk && rowList.length == 0) {
                alert(MSG_ERROR_NO_SELECT);
                isOk = false;
            }
            
            // グリッド行チェック
            for (const key in rowList) {
                if(!isOk){ break; }
                if (isOk && !this.deleteRowIsValid(this.wjMultiRowControl[this.focusVersion], rowList[key])) {
                    isOk = false;
                }
            }

            if (isOk && this.confirm(MSG_CONFIRM_DELETE)) {
                // 行削除
                this.deleteTreeGridByGrid(this.wjMultiRowControl[this.focusVersion], this.wjTreeViewControl[this.focusVersion], kbnIdList.top_flg, kbnIdList.depth, kbnIdList.filter_tree_path);

                this.wjTreeViewControl[this.focusVersion].loadTree();
                // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
                this.checkedAllTreeGrid(false);
                this.calcGridCostSalesTotal();
                this.filterGrid();
            }
        },
        // CSVダウンロード
        downloadCSV() {
            // 現在開いている見積版データ取得
            var data = this.wjMultiRowControl[this.focusVersion].itemsSource

            var params = new URLSearchParams();
            params.append('quote_no', this.rmUndefinedBlank(this.main.quote_no));
            params.append('quote_version', this.focusVersion);

            axios.post('/quote-edit/export-csv', params, {responseType: 'blob' }
            ).then( function (response) {
                // ContentDispositionからファイル名取得
                const contentDisposition = response.headers['content-disposition'];
                const regex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                const matches = regex.exec(contentDisposition);
                var filename = '';
                if (matches != null && matches[1]) {
                    const name = matches[1].replace(/['"]/g, '');
                    filename = decodeURI(name)
                } else {
                    filename = null;
                }

                // CSVファイルのダウンロード
                const url = URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', filename); 
                document.body.appendChild(link);
                link.click();
                URL.revokeObjectURL(link);
            }.bind(this))
            .catch(function (error) {
                if (error.response.data.errors) {
                    // // エラーメッセージ表示
                    // this.showErrMsg(error.response.data.errors, this.errors);
                } else {
                    if (error.response.data.message) {
                        alert(error.response.data.message);
                    } else {
                        alert(MSG_ERROR);
                    }
                }
                this.loading = false;
            }.bind(this))
        },
        // 端数丸めダイアログを開く
        showRoundFraction() {
            this.roundUnit = this.ROUND_UNIT.JU;
            this.roundType = this.ROUND_TYPE.CEIL;
            var quoteDetailList = [];
            var gridData = this.gridDataList[this.focusVersion];
            for (var i = 0; i < gridData.length; i++) {
                if (gridData[i].chk == true) {
                    if (gridData[i].layer_flg == this.FLG_ON && !gridData[i].sales_use_flg) {
                        // 階層行　かつ　販売額利用ではない
                        continue;
                    }
                    if (gridData[i].received_order_flg == this.FLG_ON) {
                        // 受注確定行
                        continue;
                    }

                    quoteDetailList.push(gridData[i]);
                }
            }
            if (quoteDetailList.length == 0) {
                // チェックが付いている明細なし
                alert(MSG_ERROR_NO_SELECT);
                return;
            } else {
                // ダイアログを開く
                this.showDlgRoundFraction = true;
            }
        },
        // 端数丸め
        roundFraction() {
            var gridData = this.gridDataList[this.focusVersion];

            for (var i = 0; i < gridData.length; i++) {
                if (gridData[i].chk == true) {
                    if (gridData[i].layer_flg == this.FLG_ON && !gridData[i].sales_use_flg) {
                        // 階層行　かつ　販売額利用ではない
                        continue;
                    }
                    if (gridData[i].received_order_flg == this.FLG_ON) {
                        // 受注確定行
                        continue;
                    }

                    // 販売単価
                    var prevSalesUnitPrice = gridData[i].sales_unit_price;

                    switch (this.roundType) {
                        case this.ROUND_TYPE.FLOOR:
                            prevSalesUnitPrice = Math.floor(prevSalesUnitPrice / this.roundUnit) * this.roundUnit;
                            break;
                        case this.ROUND_TYPE.CEIL:
                            prevSalesUnitPrice = Math.ceil(prevSalesUnitPrice / this.roundUnit) * this.roundUnit;
                            break;
                        case this.ROUND_TYPE.ROUND:
                            prevSalesUnitPrice = Math.round(prevSalesUnitPrice / this.roundUnit) * this.roundUnit;
                            break;
                    }

                    gridData[i].sales_unit_price = prevSalesUnitPrice;

                    this.calcTreeGridChangeUnitPrice(gridData[i], false);
                    this.calcTreeGridRowData(gridData[i], 'quote_quantity');
                    this.calcGridCostSalesTotal();
                }
            }

            // 全体の金額再計算
            this.calcGridCostSalesTotal();
            this.calcLayerTotalPrice();
            
            this.wjMultiRowControl[this.focusVersion].refresh();
            // ダイアログを閉じる
            this.showDlgRoundFraction = false;
        },
        // 粗利一括設定ダイアログを開く
        showGrossProfitSetting() {
            var quoteDetailList = [];
            var gridData = this.gridDataList[this.focusVersion];
            for (var i = 0; i < gridData.length; i++) {
                if (gridData[i].chk == true) {
                    if (gridData[i].layer_flg == this.FLG_ON && !gridData[i].sales_use_flg) {
                        // 階層行　かつ　販売額利用ではない
                        continue;
                    }
                    if (gridData[i].received_order_flg == this.FLG_ON) {
                        // 受注確定行
                        continue;
                    }

                    quoteDetailList.push(gridData[i]);
                }
            }
            if (quoteDetailList.length == 0) {
                // チェックが付いている明細なし
                alert(MSG_ERROR_NO_SELECT);
                return;
            } else {
                // ダイアログを開く
                this.showDlgGrossProfitSetting = true;
            }
        },
        // 粗利一括設定
        changeGrossProfit() {
            var gridData = this.gridDataList[this.focusVersion];

            for (var i = 0; i < gridData.length; i++) {
                if (gridData[i].chk == true) {
                    if (gridData[i].layer_flg == this.FLG_ON && !gridData[i].sales_use_flg) {
                        // 階層行　かつ　販売額利用ではない
                        continue;
                    }
                    if (gridData[i].received_order_flg == this.FLG_ON) {
                        // 受注確定行
                        continue;
                    }

                    // 粗利率セット
                    gridData[i].gross_profit_rate = this.grossProfitRate;

                    this.calcTreeGridChangeGrossProfitRate(gridData[i]);
                    this.calcTreeGridChangeUnitPrice(gridData[i], true);
                    this.calcTreeGridChangeUnitPrice(gridData[i], false);
                    this.calcTreeGridRowData(gridData[i], 'quote_quantity');
                }
            }

            // 全体の金額再計算
            this.calcGridCostSalesTotal();
            this.calcLayerTotalPrice();
            
            this.wjMultiRowControl[this.focusVersion].refresh();
            // ダイアログを閉じる
            this.showDlgGrossProfitSetting = false;
        },
        // 見積ダイアログを開く
        showQuoteImport(){
            var kbnIdList = this.getTreeKbnId(this.wjTreeViewControl[this.focusVersion]);

            // トップ階層で見積の取込は禁止
            if (kbnIdList.top_flg === this.FLG_ON) {
                alert(MSG_ERROR_SELECT_TOP_TREE);
                return false;
            }
            this.showDlgQuoteImport = true;   
        },
        // 見積取込
        quoteImport(){
            // catch無し
            if (!this.quoteImportFile.content) {
                return;
            }
            try {
                this.startProcessing();
                this.loading = true;
                // エラー初期化
                this.initErrArrObj(this.errors.dialog);
                let reader = new FileReader();
                switch (this.quoteImportKbn) {
                    case this.QUOTE_IMPORT.QUOTE_DETAIL_EXCEL.KBN:
                        /***********************************/
                        /*  見積明細取込用Excelフォーマット  */
                        /**********************************/
                        // 拡張子チェック
                        var ext = this.QUOTE_IMPORT.QUOTE_DETAIL_EXCEL.ACCEPT.split(',');
                        if (ext.indexOf(this.getExt(this.quoteImportFile.content.name, true)) === -1) {
                            // エラー時の処理
                            this.errors.dialog.quote_import = MSG_ERROR_ILLEGAL_FILE_EXTENSION;
                            return;
                        }
                        reader.readAsDataURL(this.quoteImportFile.content);
                        reader.onload = (e) => {
                            var workbook = new wjcXlsx.Workbook();
                            workbook.loadAsync(reader.result, (result) => {
                                this.quoteImportData = result.sheets[0];

                                // 取込データが有効かチェック
                                if (this.importQuoteDetailExcelIsValid(this.quoteImportData)) {
                                    // EXCELの内容を連想配列に変換
                                    var data = this.convertExcelSheetToArray(
                                                this.quoteImportData
                                                , this.QUOTE_IMPORT.QUOTE_DETAIL_EXCEL.START_ROW_NUMBER
                                                , this.QUOTE_IMPORT.QUOTE_DETAIL_EXCEL.COLS_OF_LOWER_END
                                            );
                                    if (data.length > 0) {
                                        // 下記のような連想配列を作成
                                        // 例）[ '品番' => 'product_code', '商品名' => 'product_name' ]
                                        var columnHeaders = [];
                                        this.wjMultiRowControl[this.focusVersion].layoutDefinition.forEach(rec => {
                                            rec.cells.forEach(cell => {
                                                if (cell.header.trim() != "") {
                                                    columnHeaders[cell.header] = cell.binding;    
                                                }
                                            })
                                        })

                                        // キーが日本語なので英語に変換。他項目の個別処理など
                                        // 例）['品番' => 'xxxx', '商品名' => 'xxxx'] ⇒ ['product_code' => 'xxxx', 'product_name' => 'xxxx']
                                        data = data.map(rec => {
                                            var newArray = [];
                                            for (const key in rec) {
                                                if (this.rmUndefinedBlank(columnHeaders[key]) !== "") {
                                                    // 数値カラムならカンマ除去
                                                    if (this.QUOTE_IMPORT.QUOTE_DETAIL_EXCEL.NUMERIC_COL.indexOf(key) !== -1) {
                                                        if (/,/.test(rec[key])) {
                                                            rec[key] = String(rec[key]).replace(/,/g, '');
                                                        }
                                                        // 暗黙的数値チェック
                                                        if (isFinite(rec[key])) {
                                                            // 小数第3位以下を切り落とす
                                                            rec[key] = Math.floor(Number(rec[key]) * 100) / 100;
                                                        }else{
                                                            // 数値項目なのに数値じゃなかったら0を代入
                                                            rec[key] = 0;
                                                        }
                                                    }
                                                    newArray[columnHeaders[key]] = rec[key];
                                                }
                                            }
                                            return newArray;
                                        })
                                    }
                                    // グリッドに取込
                                    this.importQuoteDetailExcel(data);
                                    // ダイアログを閉じる
                                    this.showDlgQuoteImport = false;
                                }
                            });
                        };
                        break;
                    case this.QUOTE_IMPORT.BEE_CONNECT_CSV.KBN:
                        /***********************************/
                        /* Bee-Connect共通フォーマット(CSV) */
                        /**********************************/
                        break;
                    case this.QUOTE_IMPORT.WOOD_CAD_CSV.KBN:
                        /**********************************/
                        /*          木材CAD(CSV)          */
                        /*********************************/
                        // 拡張子チェック
                        var ext = this.QUOTE_IMPORT.WOOD_CAD_CSV.ACCEPT.split(',');
                        if (ext.indexOf(this.getExt(this.quoteImportFile.content.name, true)) === -1) {
                            // エラー時の処理
                            this.errors.dialog.quote_import = MSG_ERROR_ILLEGAL_FILE_EXTENSION;
                            return;
                        }
                        reader.readAsText(this.quoteImportFile.content, 'shift-jis');
                        reader.onload = (e) => {
                            var result = e.target.result;
                            var data = this.convertCsvToArray(result);
                            if (this.importWoodCadCsvIsValid(data)) {
                                this.importWoodCadCsv(data);
                                // ダイアログを閉じる
                                this.showDlgQuoteImport = false;
                            }
                        };
                        break;
                    default:
                        break;
                }
            } finally {
                // すぐに解除すると連打を防げない為、500ms秒遅らせる
                setTimeout(() => {
                    this.loading = false;
                    this.endProcessing();
                }, 500);   
            }
        },
        // 商品情報を検索
        getProductsInfoForQuoteImport(productCodes, customerId){
            // 入力値の取得
            var params = new URLSearchParams();
            params.append('product_codes', JSON.stringify(productCodes));
            params.append('customer_id', customerId);
            var promise = axios.post('/quote-edit/get-products-info', params)
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
            return promise;
        },
        /**
         * [見積取込]見積明細取込Excelフォーマット
         */
        async importQuoteDetailExcel(data){
            var kbnIdList = this.getTreeKbnId(this.wjTreeViewControl[this.focusVersion]);
            var rowList = [];

            var importData = [];
            var productCodes = [];
            for (const key in data) {
                const item = data[key];
                var rowData = Vue.util.extend({}, QUOTE_INIT_ROW);
                
                /* メーカー情報（仮） */
                rowData.maker_name = item.maker_name;
                
                // メーカー名から逆引き
                if (this.rmUndefinedBlank(item.maker_name) !== "") {
                    var maker = this.makerList.filter(rec => {
                        return (rowData.maker_name == rec.supplier_name)
                    })
                    // メーカー名称からの逆引きなので、ヒット件数が1件の場合のみセットする
                    if (maker.length == 1) {
                        rowData.maker_id = maker[0].id;
                        rowData.maker_name = maker[0].supplier_name;
                    }
                }

                /* 仕入先情報（仮） */
                rowData.supplier_name = item.supplier_name;

                /* 商品情報 */
                rowData.product_code = String(item.product_code);
                rowData.product_name = item.product_name;
                rowData.model = item.model;
                rowData.regular_price = item.regular_price;
                rowData.unit = item.unit;
                rowData.stock_unit = (item.stock_unit == "")? item.unit:item.stock_unit;    // 管理数単位がブランクの場合は単位を使用する
                
                rowData.quote_quantity = parseFloat(item.quote_quantity);       // 見積数

                // 仕入情報セット
                var findCostKbnIdx = this.priceList.findIndex(rec => {
                    return (item.cost_kbn === rec.value_code);
                });
                if (findCostKbnIdx !== -1) {
                    rowData.cost_kbn = item.cost_kbn;
                }
                rowData.cost_unit_price = item.cost_unit_price;     // 仕入単価
                rowData.cost_makeup_rate = item.cost_makeup_rate;   // 仕入掛率

                // 販売情報セット
                var findSalesKbnIdx = this.priceList.findIndex(rec => {
                    return (item.sales_kbn === rec.value_code);
                });
                if (findSalesKbnIdx !== -1) {
                    rowData.sales_kbn = item.sales_kbn;
                }
                rowData.sales_unit_price = item.sales_unit_price;   // 販売単価
                rowData.sales_makeup_rate = item.sales_makeup_rate; // 仕入掛率
                rowData.memo = item.memo;                           // 備考

                if (this.rmUndefinedBlank(item.product_code) === "") {
                    // 無形品対応 ※無形品は最小単位数の入力があっても無視
                }else{
                    // 有形品対応
                    rowData.intangible_flg = this.FLG_OFF;
                    // 最小単位数・発注ロット数セット
                    if (this.bigNumberEq(item.min_quantity, 0)) {
                        // 最小単位数が未入力 ※有形品の初期値をセット
                        rowData.min_quantity = this.INIT_ROW_MIN_QUANTITY;
                        rowData.order_lot_quantity = this.INIT_ROW_ORDER_LOT_QUANTITY;   
                    }else{
                        // 最小単位数が入力済
                        rowData.min_quantity = parseFloat(item.min_quantity);
                        rowData.order_lot_quantity = parseFloat(item.min_quantity);
                    }
                }

                if (this.rmUndefinedBlank(item.product_code) !== "") {
                    productCodes.push(this.rmUndefinedBlank(item.product_code));   
                }

                importData.push(rowData);
            }

            productCodes = productCodes.filter(function (x, i, self) {
                return self.indexOf(x) === i;
            });
            var productsInfo = await this.getProductsInfoForQuoteImport(productCodes, this.main.customer_id);

            /* 商品,価格情報,メーカー・仕入先情報を調整 */
            for (const key in importData) {
                var rowData = importData[key];

                var product = null;
                var costProductPriceList = null;
                var salesProductPriceList = null;

                // 商品情報検索
                if (productsInfo !== undefined && this.rmUndefinedBlank(productsInfo['productList'][rowData.product_code]) !== '') {
                    if (Object.keys(productsInfo['productList'][rowData.product_code]).length > 0) {
                        for (const makerId in productsInfo['productList'][rowData.product_code]) {
                            // 検索結果が1件でEXCEL上でメーカーの指定がされていない Or メーカーIDが一致した
                            if (
                                (Object.keys(productsInfo['productList'][rowData.product_code]).length == 1 && rowData.maker_name == QUOTE_INIT_ROW.maker_name)
                                || makerId == rowData.maker_id
                            ) {
                                product = productsInfo['productList'][rowData.product_code][makerId];
                                break;
                            }
                        }   
                    }
                    if (this.rmUndefinedBlank(product) != "") {
                        costProductPriceList = productsInfo['costProductPriceList'][product.product_id];
                        salesProductPriceList = productsInfo['salesProductPriceList'][product.product_id];
                    }
                }

                if (this.rmUndefinedBlank(product) !== "") {
                    // 型式・規格が入力されている場合は、マスタよりEXCEL優先
                    if (rowData.model == "") {
                        rowData.model = product.model;
                    }
                    
                    rowData.product_id = product.product_id;                                    // 商品ID
                    rowData.product_name = product.product_name;                                // 商品名
                    rowData.min_quantity = parseFloat(product.min_quantity);                    // 最小単位数
                    rowData.unit = product.unit;                                                // 単位
                    rowData.stock_unit = product.stock_unit;                                    // 管理数単位
                    rowData.order_lot_quantity = parseFloat(product.order_lot_quantity);        // 発注ロット数
                    rowData.quantity_per_case = parseFloat(this.rmUndefinedZero(product.quantity_per_case));          // 入り数
                    rowData.set_kbn = product.set_kbn;                                          // セット区分
                    rowData.class_big_id = this.rmUndefinedZero(product.class_big_id);          // 大分類ID
                    rowData.class_middle_id = this.rmUndefinedZero(product.class_middle_id);    // 中分類ID
                    rowData.class_small_id = this.rmUndefinedZero(product.class_small_id_1);    // 小分類ID
                    rowData.tree_species = this.rmUndefinedZero(product.tree_species);          // 樹種
                    rowData.grade = this.rmUndefinedZero(product.grade);                        // 等級
                    rowData.length = this.rmUndefinedZero(product.length);                      // 長さ
                    rowData.thickness = this.rmUndefinedZero(product.thickness);                // 厚み
                    rowData.width = this.rmUndefinedZero(product.width);                        // 幅
                    rowData.regular_price = product.price;                                      // 定価
                    rowData.intangible_flg = product.intangible_flg;                            // 無形品フラグ
                    
                    var maker = this.makerList.find(rec => {
                        return (rec.id == product.maker_id);
                    });

                    if (maker !== undefined) {
                        rowData.maker_id = maker.id;                                                // メーカーID
                        rowData.maker_name = maker.supplier_name;                                   // メーカー名
                    
                        rowData.product_maker_id = product.maker_id;                                // 商品マスタのメーカーID
                    }
                }

                // 後始末
                if (this.rmUndefinedZero(rowData.maker_id) === 0) {
                    rowData.maker_id = QUOTE_INIT_ROW.maker_id;
                    rowData.maker_name = QUOTE_INIT_ROW.maker_name;
                }

                /* 仕入先情報 */
                var supplier = null;
                // 仕入先名がセットされている And メーカーIDがセットされている
                if (this.rmUndefinedBlank(rowData.supplier_name) !== "" && this.rmUndefinedZero(rowData.maker_id) !== 0) {
                    supplier = this.supplierMakerList[rowData.maker_id].filter(rec => {
                        return (rowData.supplier_name == rec.supplier_name);
                    });
                    // 仕入先名からの逆引きなので、ヒット件数が1件の場合のみセットする
                    if (supplier.length === 1) {
                        rowData.supplier_id = supplier[0].supplier_id;
                        rowData.supplier_name = supplier[0].supplier_name;   
                    }
                }
                // 後始末
                if (this.rmUndefinedZero(rowData.supplier_id) === 0) {
                    rowData.supplier_id = QUOTE_INIT_ROW.supplier_id;
                    rowData.supplier_name = QUOTE_INIT_ROW.supplier_name;
                }

                // 単価計算
                this.calcTreeGridChangeRegularPrice(rowData);

                // マスタの値を優先して計算するパターン
                if (product) {
                    if (rowData.cost_kbn == this.NORMAL_PRODUCT_PRICE_KBN || rowData.cost_unit_price == 0) {
                        this.setTreeGridUnitPriceNew(rowData, true, costProductPriceList, false);
                        this.calcTreeGridChangeUnitPrice(rowData, true); // 仕入掛率再計算
                    }   
                    if (rowData.sales_unit_price == 0) {
                        this.setTreeGridUnitPriceNew(rowData, false, salesProductPriceList, false);
                        this.calcTreeGridChangeUnitPrice(rowData, false);  // 販売掛率再計算
                    }   
                }

                // 行計算
                this.calcTreeGridRowData(rowData, 'quote_quantity');

                // 親階層の販売額利用フラグが立っている場合は配下行の売価印字フラグのデフォルトをオフにする
                if (this.getAncestorSalesUseFlg(kbnIdList.filter_tree_path)) {
                    rowData.price_print_flg = false;
                }
                rowList.push(rowData);
            }
            // 行追加
            var addList = this.addSelectTreeGrid(this.wjMultiRowControl[this.focusVersion], this.wjTreeViewControl[this.focusVersion], rowList);
            // 全体の計算
            this.calcGridCostSalesTotal();
            // グリッド再描画
            this.wjMultiRowControl[this.focusVersion].collectionView.refresh();   
            this.filterGrid();
        },
        /**
         * [見積取込]木材CAD
         */
        async importWoodCadCsv(data){
            var kbnIdList = this.getTreeKbnId(this.wjTreeViewControl[this.focusVersion]);
            var rowList = [];
            const IMPORT_COL = this.QUOTE_IMPORT.WOOD_CAD_CSV.IMPORT_COL;
            const getIdx = function(value){
                return value-1;
            };

            const generateProductCode = function(woodCsvInfo){
                // 【品番】生成
                var productCode = ""; 
                // 樹種コード結合
                productCode += woodCsvInfo.woodCode;
                // 等級コード結合
                if (woodCsvInfo.gradeCode.length === 1) {
                    // 等級コードの文字数が1
                    productCode +=  ("00" + woodCsvInfo.gradeCode).slice(-2);
                }else{
                    // 等級コードの文字数が2以上（length==0は想定していない）
                    productCode += woodCsvInfo.gradeCode;
                }
                // 長さ結合
                if ((woodCsvInfo.length / 1000) > 1) {
                    // 結果が1以上の場合は文字キャストをして小数点を取り除く
                    // 例）4500 ⇒ 4.5 ⇒ 45
                    productCode += String(this.bigNumberDiv(woodCsvInfo.length, 1000)).replace('.', "");
                }else{
                    // 結果が1未満
                    productCode += String(this.bigNumberDiv(woodCsvInfo.length, 1000));
                }
                // 厚み結合
                productCode += woodCsvInfo.thickness;
                // 幅結合　※厚み≠幅の場合は幅の場合のみ
                if (woodCsvInfo.thickness !== woodCsvInfo.width) {
                    productCode += woodCsvInfo.width;
                }

                return productCode;
            }.bind(this)

            var importData = [];
            var productCodes = [];
            for (const key in data) {
                // 数値項目のカンマを除去する
                this.QUOTE_IMPORT.WOOD_CAD_CSV.NUMERIC_COL.forEach(idx => {
                    data[key][getIdx(idx)] = String(data[key][getIdx(idx)]).replace(/,/g, '');
                });

                const item = data[key];
                // 配列⇒連想配列
                var woodCsvInfo = {};
                Object.keys(IMPORT_COL).forEach(function (importColKey) {
                    woodCsvInfo[importColKey] = String(item[getIdx(IMPORT_COL[importColKey])]);
                });
                woodCsvInfo.woodCode = String(this.woodList[woodCsvInfo.woodCadCode].code);
                woodCsvInfo.woodName = String(this.woodList[woodCsvInfo.woodCadCode].name);
                woodCsvInfo.gradeCode = String(this.gradeList[woodCsvInfo.gradeCadCode].code);
                woodCsvInfo.gradeName = String(this.gradeList[woodCsvInfo.gradeCadCode].name);

                var rowData = Vue.util.extend({}, QUOTE_INIT_ROW);
                rowData.intangible_flg = this.FLG_OFF;
                rowData.quote_quantity = woodCsvInfo.quantity;

                rowData.product_code = generateProductCode(woodCsvInfo);
                rowData.product_name = woodCsvInfo.woodName + ' ' + woodCsvInfo.gradeName + ' ' + woodCsvInfo.length + '×' + woodCsvInfo.thickness + '×' + woodCsvInfo.width;
                rowData.model = woodCsvInfo.kbnB;
                if (woodCsvInfo.kbnB != "" && woodCsvInfo.kbnA != "") {
                    rowData.model += ' ';
                }
                rowData.model +=  woodCsvInfo.kbnA;

                rowData.unit = woodCsvInfo.unit;
                rowData.stock_unit = woodCsvInfo.unit;
                rowData.tree_species = woodCsvInfo.woodCode;
                rowData.grade = woodCsvInfo.gradeCode;
                rowData.thickness = woodCsvInfo.thickness;
                rowData.width = woodCsvInfo.width;
                rowData.length = woodCsvInfo.length;
                rowData.regular_price = woodCsvInfo.regularPrice;
                rowData.min_quantity = this.INIT_ROW_MIN_QUANTITY;
                rowData.order_lot_quantity = this.INIT_ROW_ORDER_LOT_QUANTITY;

                if (this.rmUndefinedBlank(rowData.product_code) !== "") {
                    productCodes.push(this.rmUndefinedBlank(rowData.product_code));   
                }
                importData.push(rowData);
            }
            productCodes = productCodes.filter(function (x, i, self) {
                return self.indexOf(x) === i;
            });
            var productsInfo = await this.getProductsInfoForQuoteImport(productCodes, this.main.customer_id);

            for (const key in importData) {
                var rowData = importData[key];

                var product = null;
                var costProductPriceList = null;
                var salesProductPriceList = null;
                if (productsInfo !== undefined && this.rmUndefinedBlank(productsInfo['productList'][rowData.product_code]) !== '') {
                    // 商品マスタを生成した品番で1件に特定できた場合
                    if (Object.keys(productsInfo['productList'][rowData.product_code]).length == 1) {
                        product = productsInfo['productList'][rowData.product_code][Object.keys(productsInfo['productList'][rowData.product_code])[0]];
                    }
                    if (this.rmUndefinedBlank(product) != "") {
                        costProductPriceList = productsInfo['costProductPriceList'][product.product_id];
                        salesProductPriceList = productsInfo['salesProductPriceList'][product.product_id];
                    }   
                }

                if (this.rmUndefinedBlank(product) !== "") {
                    // 型式・規格が入力されている場合は、マスタよりCSV優先
                    if (rowData.model.trim() == "") {
                        rowData.model = product.model;
                    }
                    
                    rowData.product_id = product.product_id;                                    // 商品ID
                    rowData.product_name = product.product_name;                                // 商品名
                    rowData.min_quantity = parseFloat(product.min_quantity);                    // 最小単位数
                    rowData.unit = product.unit;                                                // 単位
                    rowData.stock_unit = product.stock_unit;                                    // 管理数単位
                    rowData.order_lot_quantity = parseFloat(product.order_lot_quantity);        // 発注ロット数
                    rowData.quantity_per_case = parseFloat(this.rmUndefinedZero(product.quantity_per_case));           // 入り数
                    rowData.set_kbn = product.set_kbn;                                          // セット区分
                    rowData.class_big_id = this.rmUndefinedZero(product.class_big_id);          // 大分類ID
                    rowData.class_middle_id = this.rmUndefinedZero(product.class_middle_id);    // 中分類ID
                    rowData.class_small_id = this.rmUndefinedZero(product.class_small_id_1);    // 小分類ID
                    rowData.tree_species = this.rmUndefinedZero(product.tree_species);          // 樹種
                    rowData.grade = this.rmUndefinedZero(product.grade);                        // 等級
                    rowData.length = this.rmUndefinedZero(product.length);                      // 長さ
                    rowData.thickness = this.rmUndefinedZero(product.thickness);                // 厚み
                    rowData.width = this.rmUndefinedZero(product.width);                        // 幅
                    rowData.regular_price = product.price;                                      // 定価
                    rowData.intangible_flg = product.intangible_flg;                            // 無形品フラグ

                    rowData.maker_id = product.maker_id                                         // メーカーID
                    for(var i = 0; i < this.makerList.length; i++){
                        if (this.makerList[i].id == rowData.maker_id) {
                            rowData.maker_name = this.makerList[i].supplier_name;               // メーカー名
                            break;
                        }
                    }

                    rowData.cost_kbn = QUOTE_INIT_ROW.cost_kbn;
                    rowData.sales_kbn = QUOTE_INIT_ROW.sales_kbn;

                    // 仕入・販売単価情報セット ※商品が特定されている場合のみ
                    this.setTreeGridUnitPriceNew(rowData, true, costProductPriceList, false);
                    this.setTreeGridUnitPriceNew(rowData, false, salesProductPriceList, true);
                }

                // 行計算
                this.calcTreeGridRowData(rowData, 'quote_quantity');

                // 親階層の販売額利用フラグが立っている場合は配下行の売価印字フラグのデフォルトをオフにする
                if (this.getAncestorSalesUseFlg(kbnIdList.filter_tree_path)) {
                    rowData.price_print_flg = false;
                }
                rowList.push(rowData);
            }
            // 行追加
            var addList = this.addSelectTreeGrid(this.wjMultiRowControl[this.focusVersion], this.wjTreeViewControl[this.focusVersion], rowList);
            // 全体の計算
            this.calcGridCostSalesTotal();
            // グリッド再描画
            this.wjMultiRowControl[this.focusVersion].collectionView.refresh();
            this.filterGrid();
        },
        // 確認ダイアログ
        confirm(msg){
            var result = window.confirm(msg);
            return result;
        },
        // 保存が伴うもの（保存、承認申請、承認申請取消、受注確定、受注取消、印刷、積算完了、積算完了解除、指定印刷）
        setErrorInfo(error){
            if (error.response.data.errors) {
                // エラーメッセージ表示
                var quoteErr = {};
                var quoteVerErr = {};
                for (const key in error.response.data.errors) {
                    const errItem = error.response.data.errors[key];
                    if (key.indexOf('version_list') != -1) {
                        var splitKey = key.split('.');
                        var version = splitKey[1];
                        var verKey = splitKey[2];
                        if (quoteVerErr[version] == undefined) {
                            quoteVerErr[version] = {};
                        }
                        quoteVerErr[version][verKey] = errItem;
                    }else if(key.indexOf('upload_file_') != -1){
                        var splitKey = key.split('_');
                        var version = splitKey[0];
                        var verKey = splitKey[1] + '_' + splitKey[2];
                        // var uploadFileIdx = splitKey[3];
                        // var fileName = this.uploadFileList[version][uploadFileIdx];
                        if (quoteVerErr[version] == undefined) {
                            quoteVerErr[version] = {};
                        }
                        if (!quoteVerErr[version][verKey]) {
                            quoteVerErr[version][verKey] = errItem;
                        }
                    }else{
                        quoteErr[key] = errItem;
                    }
                }
                this.showErrMsg(quoteErr, this.errors.quote);
                for (const version in quoteVerErr) {
                    this.showErrMsg(quoteVerErr[version], this.errors.quoteVerTab[version])
                }
            } else {
                if (error.response.data.message) {
                    alert(error.response.data.message)
                } else {
                    alert(MSG_ERROR)
                }
            }
        },
        /**
         * 押したキーがdeleteかbackspaceか
         * @returns result
         */
        isKeyPressDeleteOrBackspace(){
            var result = false;
            if(event.keyCode == 46 || event.keyCode == 8){
                result = true;
            }
            return result;
        },
        // ******************** 添付ファイル ********************
        onDrop: function(event){
            // 照会モード中は禁止
            if (this.isReadOnly) {
                return;
            }
            let fileList = event.target.files ? 
                               event.target.files:
                               event.dataTransfer.files;
            for(let i = 0; i < fileList.length; i++){
                this.uploadFileList[this.focusVersion].push({
                    file: fileList[i],
                    file_name: fileList[i].name,
                    is_tmp: true,
                });
            }
        },
        // 添付ファイル情報削除
        deleteFile(index) {
            var versionListNo = this.main.version_list.length - this.focusVersion - 1;
            var quoteVerId = this.main.version_list[versionListNo].quote_version_id;
            // 削除するファイル名を保持
            this.deleteFileList[this.focusVersion].push({
                quote_version_id: quoteVerId,
                file_name: this.uploadFileList[this.focusVersion][index].file_name,
                is_tmp: this.uploadFileList[this.focusVersion][index].is_tmp
            });
            // 表示上削除
            this.uploadFileList[this.focusVersion].splice(index, 1);
        },
        // 添付ファイルダウンロード
        async downloadFile(fileName) {
            var versionListNo = this.main.version_list.length - this.focusVersion - 1;
            var quoteVerId = this.main.version_list[versionListNo].quote_version_id;
            var existsUrl = '/quote-edit/exists/' + quoteVerId + '/' + encodeURIComponent(fileName);

            var result = await this.existsFile(existsUrl);
            if (result != undefined && result) {
                var downloadUrl = '/quote-edit/download/' + quoteVerId + '/' + encodeURIComponent(fileName);

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
        /**
         * 見積保存時の基本的なパラメータを用意する
         * @param {FormData} params
         */
        appendParamsForQuoteSaving(params, exclusion){
            // 案件
            params.append('matter_id', this.rmUndefinedBlank(this.main.matter_id));
            params.append('matter_name', this.rmUndefinedBlank(this.main.matter_name));
            params.append('matter_no', this.rmUndefinedBlank(this.main.matter_no));
            params.append('department_id', this.rmUndefinedZero(this.main.department_id));
            params.append('staff_id', this.rmUndefinedZero(this.main.staff_id));
            params.append('customer_id', this.rmUndefinedZero(this.main.customer_id));
            params.append('owner_name', this.rmUndefinedBlank(this.main.owner_name));
            params.append('architecture_type', this.rmUndefinedZero(this.main.architecture_type));

            // 見積
            params.append('quote_id', this.rmUndefinedBlank(this.main.quote_id));
            params.append('quote_no', this.rmUndefinedBlank(this.main.quote_no));
            params.append('special_flg', this.rmUndefinedBlank(this.main.special_flg));
            params.append('person_id', this.rmUndefinedZero(this.main.person_id));
            params.append('construction_period', this.rmUndefinedBlank(this.main.construction_period));
            params.append('construction_outline', this.rmUndefinedBlank(this.main.construction_outline));
            params.append('quote_report_to', this.rmUndefinedBlank(this.main.quote_report_to));

            // 見積版
            // フォーカスが当たっている版
            var versionListNo = this.main.version_list.length - this.focusVersion - 1;  // main.version_listは版番号と逆順の配列になっている
            params.append('quote_version_id', this.rmUndefinedZero(this.main.version_list[versionListNo].quote_version_id));
            params.append('quote_version', this.rmUndefinedZero(this.focusVersion));
            // 全ての版情報
            var versionListData = {};
            for (let i = 0; i < this.main.version_list.length; i++) {
                // (4-1)-0=3, (4-1)-1=2, ※3版まである場合の例
                versionListNo = (this.main.version_list.length-1) - i;
                versionListData[i] = {};
                versionListData[i]['quote_version_id'] = this.rmUndefinedZero(this.main.version_list[versionListNo].quote_version_id);
                versionListData[i]['quote_version'] = this.rmUndefinedZero(this.main.version_list[versionListNo].quote_version);
                versionListData[i]['quote_version_caption'] = this.rmUndefinedBlank(this.main.version_list[versionListNo].caption);
                versionListData[i]['quote_limit_date'] = this.rmUndefinedBlank(this.main.version_list[versionListNo].quote_limit_date);
                versionListData[i]['quote_enabled_limit_date'] = this.rmUndefinedBlank(this.main.version_list[versionListNo].quote_enabled_limit_date);
                versionListData[i]['payment_condition'] = this.rmUndefinedBlank(this.main.version_list[versionListNo].payment_condition)
                versionListData[i]['tax_rate'] = this.rmUndefinedZero(this.main.version_list[versionListNo].tax_rate)
                versionListData[i]['sales_support_comment'] = this.rmUndefinedBlank(this.main.version_list[versionListNo].sales_support_comment);
                versionListData[i]['approval_comment'] = this.rmUndefinedBlank(this.main.version_list[versionListNo].approval_comment);
                versionListData[i]['customer_comment'] = this.rmUndefinedBlank(this.main.version_list[versionListNo].customer_comment);
                versionListData[i]['quote_create_date'] = this.rmUndefinedBlank(this.main.version_list[versionListNo].quote_create_date);
                versionListData[i]['cost_total'] = this.rmUndefinedZero(this.main.version_list[versionListNo].cost_total);
                versionListData[i]['sales_total'] = this.rmUndefinedZero(this.main.version_list[versionListNo].sales_total);
                versionListData[i]['profit_total'] = this.rmUndefinedZero(this.main.version_list[versionListNo].profit_total);
            }

            // 添付ファイル
            for (let version = 0; version < this.uploadFileList.length; version++) {
                if (this.rmUndefinedBlank(this.uploadFileList[version]) != '') {
                    for (var i = 0; i < this.uploadFileList[version].length; i++) {
                        var data = this.uploadFileList[version][i];
                        params.append(version + '_upload_file_' + i, data.file);
                        // 複製データ
                        if (data.copy_flg && this.rmUndefinedBlank(data.file_name) != '') {
                            if (versionListData[version]['copy_upload_files'] == undefined) {
                                versionListData[version]['copy_upload_files'] = [];
                            }
                            versionListData[version]['copy_upload_files'].push({file_name: data.file_name})
                            versionListData[version]['copy_file_flg'] = data.copy_flg;
                            versionListData[version]['copy_version_id'] = data.parent_version_id;
                        }
                    }
                }
            }

            // 削除ファイル
            for (const version in this.deleteFileList) {
                versionListData[version]['delete_files'] = [];
                for (var i = 0; i < this.deleteFileList[version].length; i++) {
                    versionListNo = this.main.version_list.length - version - 1;  // main.version_listは版番号と逆順の配列になっている
                    if (this.rmUndefinedZero(this.deleteFileList[version][i].quote_version_id == this.main.version_list[versionListNo].quote_version_id)) {
                        versionListData[version]['delete_files'].push(this.deleteFileList[version][i].file_name);
                    }
                }
            }

            // 見積明細データ
            for (const version in this.gridDataList) {
                versionListData[version]['quote_detail'] = this.gridDataList[version];
            }
            params.append('version_list', JSON.stringify(versionListData));
        },
        
        // 親階層でsales_use_flgがたっていたらtrue
        getAncestorSalesUseFlg(filterTreePath){
            var salesUseFlg = false;
            var treeInfo = this.findTree(this.wjTreeViewControl[this.focusVersion].itemsSource,'filter_tree_path', filterTreePath);
            while (true) {
                if (treeInfo.sales_use_flg) {
                    salesUseFlg = true;
                    break;
                }else if(treeInfo.depth == 0){
                    break;
                }
                treeInfo = this.findTree(this.wjTreeViewControl[this.focusVersion].itemsSource,'filter_tree_path', this.getParentFilterTreePath(treeInfo.filter_tree_path));
            }
            return salesUseFlg;
        },
        // ******************** チェック処理 ********************/
        // 行削除時のチェック
        deleteRowIsValid(wjMultiRowControl, gridRow){
            var result = true;

            var versionListNo = this.main.version_list.length - this.focusVersion - 1;  // main.version_listは版番号と逆順の配列になっている
            // 0版 OR 照会モード OR 申請中 OR 承認済
            if (result && (
                    this.focusVersion === this.ZERO || this.isReadOnly || 
                    this.main.version_list[versionListNo].status == this.STATUS.APPLYING || this.main.version_list[versionListNo].status == this.STATUS.APPROVED
                )
            ) {
                alert(MSG_ERROR_DELETE_ROW);
                result = false;
            }
            
            // 削除データ（子も含む）に受注確定データが含まれている
            if (result) {
                var findReceivedOrderIdx = wjMultiRowControl.collectionView.sourceCollection.findIndex((rec) => {
                    return (
                        (rec.filter_tree_path === gridRow.filter_tree_path
                        || rec.filter_tree_path.indexOf(gridRow.filter_tree_path + this.TREE_PATH_SEPARATOR) === 0)
                        && rec.received_order_flg == this.FLG_ON
                    );
                });
                if (findReceivedOrderIdx != -1) {
                    alert(MSG_ERROR_DELETE_ROW);
                    result = false;
                }
            }
            return result;
        },
        // 行追加時のチェック
        addRowIsValid(wjMultiRowControl){
            var result = true;
            var kbnIdList = this.getTreeKbnId(this.wjTreeViewControl[this.focusVersion]);
            var versionListNo = this.main.version_list.length - this.focusVersion - 1;  // main.version_listは版番号と逆順の配列になっている

            // 照会モード Or 0版 or 工事区分階層　Or 申請中 Or 承認中 Or グリッドの状態が追加禁止になっている
            if (
                this.isReadOnly ||
                this.focusVersion == this.ZERO || kbnIdList.top_flg === this.FLG_ON ||
                this.main.version_list[versionListNo].status == this.STATUS.APPLYING ||
                this.main.version_list[versionListNo].status == this.STATUS.APPROVED ||
                !wjMultiRowControl.allowAddNew
            ) {
                alert(MSG_ERROR_ADD_ROW);
                result = false;
            }
            return result;
        },
        // 階層作成時のチェック
        toLayerIsValid(row){
            var result = true;

            var versionListNo = this.main.version_list.length - this.focusVersion - 1;  // main.version_listは版番号と逆順の配列になっている

            if (
                this.focusVersion == this.ZERO ||
                this.main.version_list[versionListNo].status == this.STATUS.APPLYING ||
                this.main.version_list[versionListNo].status == this.STATUS.APPROVED ||
                row.received_order_flg === this.FLG_ON || row.layer_flg === this.FLG_ON
            ) {
                // 申請中 Or 承認中 Or 0版がｱｸﾃｨﾌﾞ Or 受注確定行 Or 階層行
                alert(MSG_ERROR_CREATE_LAYER);
                result = false;
            }else if(this.treeGridDetailRecordConstructionIsAddLayer(this.wjMultiRowControl[this.focusVersion], row)){
                // 追加部材は階層化できない
                alert(MSG_ERROR_ADD_KBN_CREATE_LAYER);
                result = false;
            }else if(this.treeGridDetailRecordParentIsSetFlg(this.wjMultiRowControl[this.focusVersion], row)){
                // 一式配下(子部品)は階層化できない
                alert(MSG_ERROR_SET_PRODUCT_CREATE_LAYER);
                result = false;
            }

            return result;
        },
        pasteIsValid(){
            var result = true;
            var kbnIdList = this.getTreeKbnId(this.wjTreeViewControl[this.focusVersion]);
            var versionListNo = this.main.version_list.length - this.focusVersion - 1;  // main.version_listは版番号と逆順の配列になっている
            
            if (
                this.isReadOnly ||
                this.focusVersion === 0 || kbnIdList.top_flg === this.FLG_ON ||
                this.main.version_list[versionListNo].status == this.STATUS.APPLYING ||
                this.main.version_list[versionListNo].status == this.STATUS.APPROVED
            ) {
                alert(MSG_ERROR_PASTE);
                result = false;
            }
            return result;
        },
        // グリッドに貼り付けしたときのチェック
        wjMultiRowClipBoardValidation(wjMultiRowControl, text){
            var result = true;
            var layount = wjMultiRowControl.layoutDefinition;
            var selectedRowList = wjMultiRowControl.selectedRows;
            var clipboardData = this.toWjMultiRowPasteTextFormat(text);

            if(clipboardData.length%2 !== 0 || clipboardData.length === 0){
                // フォーマットが異なる
                alert(MSG_ERROR_PASTE_FORMAT);
                result = false;
            }else if(!this.pasteIsValid()){
                result = false;
            }else{
                for(var i=0;i<selectedRowList.length && result;i++){
                    if(typeof selectedRowList[i].dataItem === 'undefined'){
                        //alert('追加済みの行を選択して下さい');
                        // result = false;
                        continue;
                    }
                    
                    // 列数が同じかどうかのチェック
                    for(var j=0; j<clipboardData.length && result; j++){
                        var clipboardDataRecord = clipboardData[j].split(this.WJ_MULTI_ROW_CLIPBOARD_CTRL_OPTION.DELIMITER);
                        if(layount.length !== clipboardDataRecord.length){
                            alert(MSG_ERROR_PASTE_FORMAT);
                            result = false;
                            break;
                        }
                    }

                    if(i%this.WJ_MULTI_ROW_CNT === 0 && result){
                        // 選択したグリッド行をソースコレクションから取得(自身の行を取得)
                        var targetGridRecord = this.getChildGridDataList(wjMultiRowControl, selectedRowList[i].dataItem.filter_tree_path, selectedRowList[i].dataItem.depth);
                        result = this.wjMultiRowClipBoardDetailValidation(clipboardData, layount, targetGridRecord[0], i);
                    }
                }
            }
            return result;
        },
        // グリッドに貼り付けしたときのチェック(詳細)
        wjMultiRowClipBoardDetailValidation(clipboardData, layount, targetGridRecord, rowCnt){
            var result = true;
            for(var multiCnt=0; multiCnt<this.WJ_MULTI_ROW_CNT && result; multiCnt++){
                if(typeof clipboardData[rowCnt + multiCnt] !== 'undefined'){
                    var clipboardDataRecord = clipboardData[rowCnt + multiCnt].split(this.WJ_MULTI_ROW_CLIPBOARD_CTRL_OPTION.DELIMITER);
                    if(layount.length !== clipboardDataRecord.length){
                        alert(MSG_ERROR_PASTE_FORMAT);
                        result = false;
                        break;
                    }
                    // 対象貼り付け位置の値とクリップボードの値チェック
                    for(var j=0; j<layount.length && result; j++){
                        if(typeof layount[j].cells[multiCnt] !== 'undefined'){
                            // グリッドのカラム名取得
                            var bindingName = layount[j].cells[multiCnt].binding;
                            var cellValue = clipboardDataRecord[j];
                            switch(bindingName){
                                case 'received_order_flg':
                                    // 受注確定フラグが立っている場合は貼り付け禁止
                                    if(targetGridRecord.received_order_flg === this.FLG_ON) {
                                        alert(MSG_ERROR_PASTE);
                                        result = false;
                                        break;
                                    }
                                    break;
                                case 'layer_flg':
                                    if (targetGridRecord.layer_flg === this.FLG_ON) {
                                        if(!isFinite(cellValue) || parseInt(cellValue) !== this.FLG_ON){
                                            alert(MSG_ERROR_PASTE_FROM_DETAIL_TO_LAYER);
                                            result = false;
                                            break;
                                        }
                                    }
                                    break;
                                case 'set_flg':
                                    if (targetGridRecord.set_flg === this.FLG_ON) {
                                        if(!isFinite(cellValue) || parseInt(cellValue) !== this.FLG_ON){
                                            alert(MSG_ERROR_PASTE_TO_SET_PRODUCT);
                                            result = false;
                                            break;
                                        }
                                    }
                                    break;
                            }
                        }
                    }
                }
            } 
            return result;
        },
        // 見積明細取込用Excelフォーマット（有効かチェック）
        importQuoteDetailExcelIsValid(worksheet){
            var isOk = true;

            var startRow = this.QUOTE_IMPORT.QUOTE_DETAIL_EXCEL.START_ROW_NUMBER;
            var startRowIdx = startRow-1;
            var endRow = worksheet.rows.length;
            var endRowIdx = endRow-1;

            var columnsOfLowerEnd = this.QUOTE_IMPORT.QUOTE_DETAIL_EXCEL.COLS_OF_LOWER_END;

            var headerItem = worksheet.rows[startRowIdx].cells.map(rec => {
                return rec.value;
            });

            // 必須項目のいずれかがヘッダ行に存在しない場合はアウト
            if (isOk) {
                var msgShotageItem = '';
                for (let i = 0; i < this.QUOTE_IMPORT.QUOTE_DETAIL_EXCEL.REQUIRE_COL.length; i++) {
                    const item = this.QUOTE_IMPORT.QUOTE_DETAIL_EXCEL.REQUIRE_COL[i];
                    var findIdx = headerItem.findIndex(rec => {
                        return (rec == item);
                    })
                    // 必須項目のヘッダーが存在しなかったらエラー
                    if (findIdx === -1) {
                        isOk = false;
                        msgShotageItem += (item + ",")
                    }
                }
                if (!isOk) {
                    msgShotageItem = msgShotageItem.slice(0, -1);    // 末尾のカンマを削除
                    this.errors.dialog.quote_import = MSG_ERROR_ITEM_SHORTAGE + "\n" + "(" + msgShotageItem + ")";
                }
            }

            // ヘッダ行で必須項目の重複があった場合はアウト
            if (isOk) {
                var msgDuplicateItem = '';
                headerItem.filter(function (x, i, self) {
                    // 重複を検出したものを重複しないでリスト
                    return self.indexOf(x) === i && i !== self.lastIndexOf(x);
                }.bind(this)).forEach(rec => {
                    // エラーメッセージ作成
                    isOk = false;
                    msgDuplicateItem += (rec + ",");
                });
                if (!isOk) {
                    msgDuplicateItem = msgDuplicateItem.slice(0, -1);    // 末尾のカンマを削除
                    this.errors.dialog.quote_import = MSG_ERROR_ITEM_DUPLICATE + "\n" + "(" + msgDuplicateItem + ")";
                }
            }

            // 品番に不正な文字が含まれているかチェックを行う
            if (isOk) {
                // ワークシートの最終行 > 取込開始行
                if (worksheet.rows.length > startRow) {
                    // 基準となる列に値が入力されている部分までを対象範囲とする
                    if (columnsOfLowerEnd.length > 0) {
                        var columnsOfLowerEndIdx = [];
                        columnsOfLowerEnd.forEach(rec => {
                            var findIdx = worksheet.rows[startRowIdx].cells.findIndex(cell => {
                                return (cell.value == rec)
                            });
                            if (findIdx !== -1) {
                                columnsOfLowerEndIdx.push(findIdx);
                            }                    
                        });
                    }

                    // ヘッダ行の配列を作成[品番, 商品名, etc...]
                    var columnHeaders = [];
                    for (let i = 0; i < worksheet.rows[startRowIdx].cells.length; i++) {
                        const item = worksheet.rows[startRowIdx].cells[i].value;
                        if (this.rmUndefinedBlank(item).trim() != "") {
                            columnHeaders[i] = item;
                        }
                    }

                    // 下記のような連想配列を作成する
                    // [ ['品番' => '', '商品名' => '', etc...], ['品番' => '', '商品名' => '', etc...] ]
                    // startRowIdx++;  // ヘッダはいらない為+1
                    for (var i=(startRowIdx+1); i < endRow; i++) {
                        // EXCELの内容によってはrowsの連番が飛ぶので、その時点でbreak
                        if (!worksheet.rows[i]) {
                            break;
                        }

                        const rowItem = worksheet.rows[i].cells;
                        // 取込基準となる行に到達したらbreak
                        var isEnd = (columnsOfLowerEndIdx.length > 0) ? true:false;
                        for (const key in columnsOfLowerEndIdx) {
                            if (this.rmUndefinedBlank(String(rowItem[columnsOfLowerEndIdx[key]].value)).trim() != "" ) {
                                isEnd = false;
                            }
                        }
                        if(isEnd) { break }

                        // チェック処理
                        for (const key in columnHeaders) {
                            const jpnKey = columnHeaders[key];
                            if (jpnKey == '品番') {
                                if (!this.isMatchPattern(PRODUCT_CODE_REGEX, this.rmUndefinedBlank(rowItem[key].value))) {
                                    isOk = false;
                                    if (this.errors.dialog.quote_import_arr.length === 0) {
                                        this.errors.dialog.quote_import = MSG_ERROR_ILLEGAL_VALUE;
                                        var errMsgs = MSG_ERROR_PRODUCT_CODE_REGEX.split('\n');
                                        for (const key in errMsgs) {
                                            this.errors.dialog.quote_import_arr.push(errMsgs[key]);
                                        }
                                        this.errors.dialog.quote_import_arr.push('　');
                                    }
                                    this.errors.dialog.quote_import_arr.push(' - ' + (i+1) + '行目：' + jpnKey);
                                }
                            }
                        }
                    }   
                }
            }

            return isOk;
        },
        // 木材CAD（有効かチェック）
        importWoodCadCsvIsValid(data){
            var isOk = true;

            if (isOk) {
                const getIdx = function(value){
                    return value-1;
                };
                for (const rowKey in data) {
                    var row = data[rowKey];
                    // 既定のCSVのカラム数より少ない場合
                    if (isOk && data[rowKey].length < this.QUOTE_IMPORT.WOOD_CAD_CSV.NUMBER_OF_COLUMNS) {
                        this.errors.dialog.quote_import = MSG_ERROR_ITEM_SHORTAGE;
                        isOk = false;
                    }
                    // 数値項目に数値以外の値が入ってないかチェック
                    if (isOk) {
                        for (const numericColKey in this.QUOTE_IMPORT.WOOD_CAD_CSV.NUMERIC_COL) {
                            const item = row[getIdx(this.QUOTE_IMPORT.WOOD_CAD_CSV.NUMERIC_COL[numericColKey])];
                            // 暗黙的数値チェック(カンマは除去)
                            if (!isFinite(String(item).replace(/,/g, ''))) {
                                // 数値項目なのに数値じゃない場合はNG
                                this.errors.dialog.quote_import = MSG_ERROR_ILLEGAL_VALUE;
                                isOk = false;
                                break;
                            }
                        }
                    }
                }   
            }
            return isOk;
        },
        // ******************** 以下0版専用 ********************/
        // グリッドコントロール作成
        createGridCtrlZero(gridItemSource) {
            var targetGridDivId = '#quoteDetailGrid-' + this.focusVersion;
            var gridCtrl = null;
            
            // 0版の場合
            gridCtrl = new wjMultiRow.MultiRow(targetGridDivId, {
                itemsSource: gridItemSource,
                layoutDefinition: this.gridLayoutZero,
                allowAddNew: false,
                // trueだと行は削除されないがセルの内容が消える。行削除イベントを発生させた上でe.cancel=trueを行う
                allowDelete: true,
                allowSorting: false,
                showSort: false,
                keyActionEnter: wjGrid.KeyAction.None,
                autoClipboard: false,
            });
            gridCtrl.isReadOnly = this.gridReadOnlyFlg;
            gridCtrl.rowHeaders.columns[0].width = 30;

            // グリッドに対して右クリックメニューを紐づける
            var contextMenuCtrl = this.setTreeGridRightCtrl(wjGrid, wjcInput, gridCtrl, 'layoutQuoteZero');

            // セル編集直前のイベント：コンボをセットする
            gridCtrl.beginningEdit.addHandler(function (s, e) {
                // 通常セルに対してctrl+vを有効にするため
                s.autoClipboard = true;
                
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);
                var row = s.collectionView.currentItem;
                var versionListNo = this.main.version_list.length - this.focusVersion - 1;  // main.version_listは版番号と逆順の配列になっている

                this.acProductCodeList[this.ZERO].control.isReadOnly = false;
                this.acProductNameList[this.ZERO].control.isReadOnly = false;
                this.acMakerList[this.ZERO].control.isReadOnly = false;
                this.acSupplierList[this.ZERO].control.isReadOnly = false;

                this.acProductCodeList[this.ZERO].changeItemsSource(null);
                this.acProductNameList[this.ZERO].changeItemsSource(null);
                this.acMakerList[this.focusVersion].changeItemsSource(this.makerList);
                this.acSupplierList[this.focusVersion].changeItemsSource(this.supplierMakerList[row.maker_id]);

                if (row.layer_flg == this.FLG_ON || row.set_flg == this.FLG_ON) {
                    // 階層 Or 一式
                }else{
                    // 明細
                    if (this.treeGridDetailRecordConstructionIsAddLayer(this.wjMultiRowControl[this.focusVersion], row)) {
                        // 追加部材
                        switch (col.name) {
                            case 'memo':
                            case 'row_print_flg':
                            case 'price_print_flg':
                                break;
                            default:
                                e.cancel = true;
                                break;
                        }
                    }else{
                        // 通常
                        switch(col.name){
                            case 'product_code':
                            case 'product_name':
                                this.acProductCodeList[this.ZERO].control.isReadOnly = true;
                                this.acProductNameList[this.ZERO].control.isReadOnly = true;
                                e.cancel = true;
                                break;
                            case 'maker_name':
                                // 商品マスタにメーカーIDがある場合　or　既に発注している場合はメーカー変更不可
                                if(this.rmUndefinedZero(row.product_maker_id) !== 0 || this.rmUndefinedBlank(row.order_id_list) !== ''){
                                    this.acMakerList[this.ZERO].control.isReadOnly = true;
                                    e.cancel = true;
                                }else{
                                    this.acMakerList[this.ZERO].control.isReadOnly = false;
                                }
                                if(this.isKeyPressDeleteOrBackspace()){
                                    // オートコンプリート上でdelete key無効化
                                    e.cancel = true;
                                }
                                break;
                            case 'supplier_name':
                                this.acSupplierList[this.ZERO].changeItemsSource(this.supplierMakerList[row.maker_id]);
                                // 既に発注している場合はメーカー変更不可
                                if(this.rmUndefinedBlank(row.order_id_list) !== ''){
                                    this.acSupplierList[this.ZERO].control.isReadOnly = true;
                                    e.cancel = true;
                                }
                                if(this.isKeyPressDeleteOrBackspace()){
                                    // オートコンプリート上でdelete key無効化
                                    e.cancel = true;
                                }
                                break;
                            case 'cost_makeup_rate':
                            case 'sales_makeup_rate':
                                if(this.rmUndefinedZero(row.regular_price) === 0){
                                    e.cancel = true;
                                }
                                break;
                            default:
                                break;
                        }
                    }
                }

                // 申請中 Or 承認済の場合は行チェック、明細印字、売価印字チェック以外は操作不可
                // 　※チェックさせない等の制御をやりたい場合はitemFormatterで行う
                if (this.main.version_list[versionListNo].status == this.STATUS.APPLYING) {
                    this.acProductCodeList[this.ZERO].control.isReadOnly = true;
                    this.acProductNameList[this.ZERO].control.isReadOnly = true;
                    this.acMakerList[this.ZERO].control.isReadOnly = true;
                    this.acSupplierList[this.ZERO].control.isReadOnly = true;
                    switch (col.name) {
                        case 'chk':
                        case 'row_print_flg':
                        case 'price_print_flg':
                            break;
                        default:
                            e.cancel = true;
                            break;
                    }
                }

                // 読取専用セル
                if (this.gridIsReadOnlyCell(gridCtrl, e.row, e.col)) {
                    e.cancel = true;
                }

            }.bind(this));
            // クリックイベント
            gridCtrl.addEventListener(gridCtrl.hostElement, "click", e => {
                let ht = gridCtrl.hitTest(e);
                if(ht.cellType === wjGrid.CellType.Cell){
                    var record = ht.panel.rows[ht.row].dataItem;
                    if(typeof record !== 'undefined'){
                        var col = gridCtrl.getBindingColumn(ht.panel, ht.row, ht.col);
                        var loadTreeFlg = false;
                        switch (col.name) {
                            case 'btn_up':
                                if (!this.isReadOnly) {
                                    loadTreeFlg = this.treeGridUpDownBtnClick(gridCtrl, this.wjTreeViewControl[this.ZERO], record, true);
                                }
                                break;
                            case 'btn_down':
                                if (!this.isReadOnly) {
                                    loadTreeFlg = this.treeGridUpDownBtnClick(gridCtrl, this.wjTreeViewControl[this.ZERO], record, false);
                                }
                                break;
                        }

                        if(loadTreeFlg){
                            this.wjTreeViewControl[this.ZERO].loadTree();
                            // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
                            this.checkedAllTreeGrid(false);
                        }
                    }       
                }
            });
            // ダブルクリックイベント
            gridCtrl.addEventListener(gridCtrl.hostElement, "dblclick", e => {
                let ht = gridCtrl.hitTest(e);
                if(ht.cellType === wjGrid.CellType.RowHeader){
                    var record = ht.panel.rows[ht.row].dataItem;
                    if(typeof record !== 'undefined' && record.layer_flg === this.FLG_ON){
                        this.selectTree(this.wjTreeViewControl[this.ZERO], 'filter_tree_path', record.filter_tree_path);
                    }
                }
            });
            // 右クリックイベント
            contextMenuCtrl.itemClicked.addHandler(function(s, e) {
                // メニュークリック
                var rowList = [];
                // クリックした行
                var clickRowDataItem = contextMenuCtrl.row;
                // 選択した行
                var selectedRows = gridCtrl.selectedRows;
                // クリックした行が何番目かを取得する
                var clickRowDataItemIndex = contextMenuCtrl.rowIndex;
                // クリップボードにコピーした文字列
                var clipboardText = this.rightClickInfo.clipboardText;
                // 開かれているタブの階層
                var treeCtrl = this.wjTreeViewControl[this.ZERO];
                // 今開いている階層の情報
                var kbnIdList = this.getTreeKbnId(treeCtrl);

                // 選択した項目によって処理を分岐させる
                switch(contextMenuCtrl.selectedValue){
                    case 'copy':
                        // コピー
                        if(selectedRows.length === 0){
                            alert(MSG_ERROR_NO_SELECT);
                        }else if(selectedRows.length % 2 !== 0){
                            alert(MSG_ERR_GRID_SELECTED_ROW);
                        }else if(gridCtrl.selectedRanges[0].columnSpan !== gridCtrl.columns.length){
                            alert(MSG_ERR_GRID_SELECTED_ROW);
                        }else{
                            this.wjMultiRowCopyClipboard(wjCore.Clipboard, gridCtrl, selectedRows);
                        }
                        break;
                    default :
                        break;
                }
                
            }.bind(this));
            // 行追加イベント
            gridCtrl.rowAdded.addHandler(function (s, e) {
                e.cancel = true;
            }.bind(this));
            // 行削除イベント
            gridCtrl.deletingRow.addHandler(function (s, e) {
                e.cancel = true;
            }.bind(this));
            // セル編集直後イベント：
            gridCtrl.cellEditEnding.addHandler(function (s, e) {
                s.autoClipboard = false;

                var row = s.collectionView.currentItem;
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);
                
                switch (col.name) {
                    case 'product_code':
                        if(this.rmUndefinedBlank(this.acProductCodeList[this.ZERO].control.text) === row.product_code){
                            e.cancel = true;
                        }else{
                            this.changingProductZero(this.acProductCodeList[this.ZERO], row, true);
                        }
                        break;
                    case 'product_name':
                        if(this.rmUndefinedBlank(this.acProductNameList[this.ZERO].control.text) === row.product_name){
                            e.cancel = true;
                        }else{
                            this.changingProductZero(this.acProductNameList[this.ZERO], row, false);
                        }
                        break;
                    case 'cost_makeup_rate':
                    case 'sales_makeup_rate':
                        if(this.rmUndefinedZero(row.regular_price) === 0){
                            e.cancel = true;
                        }
                        break;
                    default:
                        break;
                }
            }.bind(this));
            // セル編集後イベント：行内のデータ自動セット
            gridCtrl.cellEditEnded.addHandler(function(s, e) {
                var row = s.collectionView.currentItem;
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);

                var linkRow = s.collectionView.sourceCollection.find(rec => {
                    return (rec.over_quote_detail_id == row.quote_detail_id)
                });

                switch (col.name) {
                    case 'chk':
                        this.changeGridCheckBox(s.collectionView.currentItem);
                        break;
                    case 'product_code':
                        // 商品コード
                        this.changeProductZero(null, row, true);
                        break;
                    case 'product_name':
                        // 商品名
                        this.changeProductZero(null, row, false);
                        break;
                    case 'maker_name':
                        // メーカー名
                        if(!this.acMakerList[this.ZERO].control.isReadOnly){
                            var maker = this.acMakerList[this.ZERO].selectedItem;
                            if(maker !== null){
                                if(maker.id !== row.maker_id){
                                    row.maker_id = maker.id;
                                    this.acSupplierList[this.ZERO].changeItemsSource(this.supplierMakerList[row.maker_id]);
                                    // 仕入先リストの中からに選択されている仕入先名と同一のものを検索
                                    var findIdx = -1;
                                    if(this.acSupplierList[this.ZERO].control.itemsSource !== null){
                                        findIdx = this.acSupplierList[this.ZERO].control.itemsSource.findIndex((rec) => {
                                            return (rec.supplier_id == row.supplier_id);
                                        });
                                    }
                                    if (findIdx == -1) {
                                        row.supplier_id     = QUOTE_INIT_ROW.supplier_id;
                                        row.supplier_name   = QUOTE_INIT_ROW.supplier_name;
                                    }
                                }
                            }else{
                                row.maker_id        = QUOTE_INIT_ROW.maker_id;
                                row.maker_name      = QUOTE_INIT_ROW.maker_name;
                                row.supplier_id     = QUOTE_INIT_ROW.supplier_id;
                                row.supplier_name   = QUOTE_INIT_ROW.supplier_name;
                            }
                            // 超過先レコード連動なし
                            // 　※『超過レコードが存在』=『発注済』=『異なるメーカーの商品に変わることは無い』
                        }else{
                            if(this.rmUndefinedZero(row.maker_id) === 0){
                                row.maker_name      = QUOTE_INIT_ROW.maker_name;
                            }else{
                                for(var i = 0; i < this.makerList.length; i++){
                                    if (this.makerList[i].id == row.maker_id) {
                                        row.maker_name = this.makerList[i].supplier_name;
                                        break;
                                    }
                                }
                            }
                        }
                        break;
                    case 'supplier_name':
                        // 仕入先名
                        if(!this.acSupplierList[this.ZERO].control.isReadOnly){
                            var supplier = this.acSupplierList[this.ZERO].selectedItem;
                            if(supplier !== null){
                                row.supplier_id = supplier.supplier_id;
                            }else{
                                row.supplier_id = QUOTE_INIT_ROW.supplier_id;
                                row.supplier_name = QUOTE_INIT_ROW.supplier_name;
                            }
                            // 超過先レコード連動
                            if (linkRow) {
                                linkRow.supplier_id = row.supplier_id;
                                linkRow.supplier_name = row.supplier_name;
                            }
                        }else{
                            if(this.rmUndefinedZero(row.supplier_id) === 0){
                                row.supplier_name = QUOTE_INIT_ROW.supplier_name;
                            }else{
                                for(var i = 0; i < this.supplierList.length; i++){
                                    if (this.supplierList[i].id == row.supplier_id) {
                                        row.supplier_name = this.supplierList[i].supplier_name;
                                        break;
                                    }
                                }
                            }
                        }
                        break;
                    case 'regular_price':
                        //  定価
                        this.calcTreeGridChangeRegularPrice(row);
                        this.calcTreeGridRowData(row, 'quote_quantity');
                        // 超過先レコード連動
                        if (linkRow) {
                            linkRow[col.name] = row[col.name];
                            this.calcTreeGridChangeRegularPrice(linkRow);
                            this.calcTreeGridRowData(linkRow, 'quote_quantity');
                        }

                        this.calcGridCostSalesTotal();
                        break;
                    case 'cost_kbn':
                        // 仕入区分
                        if(
                            e.data !== row.cost_kbn &&
                            this.rmUndefinedBlank(row.cost_kbn) !== ''　&& this.rmUndefinedZero(row.product_id) !== 0)
                        {
                            this.changeCostSalesKbn(row, true);
                            // 超過先レコード連動
                            if (linkRow) {
                                linkRow[col.name] = row[col.name];
                                this.changeCostSalesKbn(linkRow, true);
                            }
                        }
                        break;
                    case 'sales_kbn':
                        // 販売区分
                        if(
                            e.data !== row.sales_kbn &&
                            this.rmUndefinedBlank(row.sales_kbn) !== ''　&& this.rmUndefinedZero(row.product_id) !== 0)
                        {
                            this.changeCostSalesKbn(row, false);
                            // 超過先レコード連動
                            if (linkRow) {
                                linkRow[col.name] = row[col.name];
                                this.changeCostSalesKbn(linkRow, false);
                            }
                        }
                        break;
                    case 'cost_unit_price':
                        // 仕入単価
                        this.calcTreeGridChangeUnitPrice(row, true);
                        this.calcTreeGridRowData(row, 'quote_quantity');
                        // 超過先レコード連動
                        if (linkRow) {
                            linkRow[col.name] = row[col.name];
                            this.calcTreeGridChangeUnitPrice(linkRow, true);
                            this.calcTreeGridRowData(linkRow, 'quote_quantity');
                        }
                        this.calcGridCostSalesTotal();
                        break;
                    case 'sales_unit_price':
                        // 販売単価
                        this.calcTreeGridChangeUnitPrice(row, false);
                        this.calcTreeGridRowData(row, 'quote_quantity');
                        // 超過先レコード連動
                        if (linkRow) {
                            linkRow[col.name] = row[col.name];
                            this.calcTreeGridChangeUnitPrice(linkRow, false);
                            this.calcTreeGridRowData(linkRow, 'quote_quantity');
                        }
                        this.calcGridCostSalesTotal();
                        break;
                    case 'cost_makeup_rate':
                        // 仕入掛率
                        if(this.rmUndefinedZero(row.regular_price) !== 0){
                            this.calcTreeGridChangeMakeupRate(row, true);
                            this.calcTreeGridRowData(row, 'quote_quantity');
                            // 超過先レコード連動
                            if (linkRow) {
                                linkRow[col.name] = row[col.name];
                                this.calcTreeGridChangeMakeupRate(linkRow, true);
                                this.calcTreeGridRowData(linkRow, 'quote_quantity');
                            }
                            this.calcGridCostSalesTotal();
                        }
                        break;
                    case 'sales_makeup_rate':
                        // 販売掛率
                        if(this.rmUndefinedZero(row.regular_price) !== 0){
                            this.calcTreeGridChangeMakeupRate(row, false);
                            this.calcTreeGridRowData(row, 'quote_quantity');
                            // 超過先レコード連動
                            if (linkRow) {
                                linkRow[col.name] = row[col.name];
                                this.calcTreeGridChangeMakeupRate(linkRow, false);
                                this.calcTreeGridRowData(linkRow, 'quote_quantity');
                            }
                            this.calcGridCostSalesTotal();
                        }
                        break;
                    case 'gross_profit_rate':
                        // 粗利率
                        this.calcTreeGridChangeGrossProfitRate(row);
                        this.calcTreeGridChangeUnitPrice(row, false);
                        this.calcTreeGridRowData(row, 'quote_quantity');
                        if (linkRow) {
                            linkRow[col.name] = row[col.name];
                            this.calcTreeGridChangeGrossProfitRate(linkRow);
                            this.calcTreeGridChangeUnitPrice(linkRow, false);
                            this.calcTreeGridRowData(linkRow, 'quote_quantity');
                        }
                        this.calcGridCostSalesTotal();
                        break;
                    case 'memo':
                    case 'row_print_flg':
                    case 'price_print_flg':
                        // [超過先レコードへの連動なし]備考，明細印字フラグ，売価印字フラグ
                        break;
                    default:
                        if (linkRow) {
                            linkRow[col.name] = row[col.name];
                        }
                        break;
                }

                gridCtrl.collectionView.commitEdit();
                
                this.filterGrid();
            }.bind(this));
            gridCtrl.columns.forEach(element => {
                // 非表示設定
                if (element.name != undefined && this.INVISIBLE_COLS.indexOf(element.name) >= 0) {
                    element.visible = false;
                }
            });
            gridCtrl.itemFormatter = function(panel, r, c, cell) {
                // 列ヘッダ中央寄せ
                if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                    cell.style.textAlign = 'center';
                    
                    // 全チェック用のチェックボックス生成
                    if (panel.columns[c].name == 'chk') {
                        var checkedCount = 0;
                        for (var i = 0; i < gridCtrl.rows.length; i++) {
                            if (gridCtrl.getCellData(i, c) == true) checkedCount++;
                        }

                        // ヘッダ部にチェックボックス追加
                        var checkBox = '<input type="checkbox">';
                        if(this.isReadOnly || this.isEditable){
                            checkBox = '<input type="checkbox" disabled="true">';
                        }
                        cell.innerHTML = checkBox;
                        var checkBox = cell.firstChild;
                        checkBox.checked = checkedCount > 0;
                        checkBox.indeterminate = checkedCount > 0 && checkedCount < gridCtrl.rows.length;

                        // 明細行にチェック状態を反映
                        checkBox.addEventListener('click', function (e) {
                            gridCtrl.beginUpdate();
                            for (var i = 0; i < gridCtrl.collectionView.items.length; i++) {
                                gridCtrl.collectionView.items[i].chk = checkBox.checked;
                                this.changeGridCheckBox(gridCtrl.collectionView.items[i]);
                            }
                            gridCtrl.endUpdate();
                        }.bind(this));
                    }
                }

                // セルごとの設定
                if (panel.cellType == wjGrid.CellType.Cell) {
                    // 背景色デフォルト設定
                    cell.style.backgroundColor = '';
                    // デフォルト左寄せ
                    cell.style.textAlign = 'left';
                    // ReadOnlyセルの背景色設定
                    this.setGridCellReadOnlyColor(gridCtrl, r, c, cell);
                    var col = gridCtrl.getBindingColumn(panel, r, c);
                    var dataItem = panel.rows[r].dataItem;

                    if(dataItem !== undefined){
                        // 販売額利用 or 一式の場合の行の色変更
                        if(dataItem.sales_use_flg || dataItem.set_flg === this.FLG_ON){
                            if(cell.style.backgroundColor !== 'lightgrey'){
                                cell.style.backgroundColor = 'seashell';
                            }
                        }

                        // 追加部材行で編集不可項目は背景色を変更
                        col.isReadOnly = false;
                        if (dataItem.layer_flg == this.FLG_OFF && this.treeGridDetailRecordConstructionIsAddLayer(this.wjMultiRowControl[this.focusVersion], dataItem)) {
                            switch (col.name) {
                                case 'memo':
                                case 'row_print_flg':
                                case 'price_print_flg':
                                    break;
                                default:
                                    col.isReadOnly = true;
                                    cell.style.backgroundColor = 'lightgrey';
                                    break;
                            }
                        }
                    }

                    // 横スクロールで文字位置がおかしくなるので明示的に設定
                    switch (col.name) {
                        case 'btn_up':
                            // 並び替え（上）
                            cell.style.padding = '0px';
                            cell.innerHTML = '<button type="button" class="btn btn-default multi-grid-btn"><i class="el-icon-arrow-up"></i></button>';
                            break;
                        case 'btn_down':
                            // 並び替え（下）
                            cell.style.padding = '0px';
                            cell.innerHTML = '<button type="button" class="btn btn-default multi-grid-btn"><i class="el-icon-arrow-down"></i></button>';
                            break;
                        case 'quote_quantity':
                            // 見積数
                            cell.style.textAlign = 'right';
                            if (dataItem !== undefined) {
                                // 見積数と発注ロット数が合わない時に警告カラー
                                if (this.bigNumberMod(dataItem.quote_quantity, dataItem.order_lot_quantity) !== 0 && !this.gridIsReadOnlyCell(gridCtrl, r, c)) {
                                    cell.style.backgroundColor = 'coral';
                                }
                            }
                            break;
                        case 'maker_name':
                            if (dataItem && this.supplierFileList[dataItem.maker_id]) {
                                var elem = document.createElement('a');
                                elem.target = '_blank';
                                elem.rel = 'noopener';
                                elem.href = '/supplier-file-open/' + dataItem.maker_id
                                elem.innerHTML = '<i class="el-icon-notebook-2"></i>';
                                cell.insertBefore(elem, cell.firstChild);
                            }
                            break;
                        case 'supplier_name':
                          if (dataItem && this.supplierFileList[dataItem.supplier_id]) {
                                var elem = document.createElement('a');
                                elem.target = '_blank';
                                elem.rel = 'noopener';
                                elem.href = '/supplier-file-open/' + dataItem.supplier_id
                                elem.innerHTML = '<i class="el-icon-notebook-2"></i>';
                                cell.insertBefore(elem, cell.firstChild);
                            }
                            break;
                        case 'stock_quantity':
                            // 管理数
                            cell.style.textAlign = 'right';
                            if (dataItem) {
                                // 無形品フラグがたっていないなら
                                if (dataItem.intangible_flg === this.FLG_ON) {
                                    cell.innerHTML = '';   
                                }   
                            }
                            break;
                        case 'regular_price':
                        case 'min_quantity':
                        case 'cost_unit_price':
                        case 'sales_unit_price':
                        case 'cost_makeup_rate':
                        case 'sales_makeup_rate':
                        case 'cost_total':
                        case 'sales_total':
                        case 'gross_profit_rate':
                        case 'profit_total':
                            // 右寄せ(見積数、管理数、定価、最小単位数、仕入単価・販売単価、仕入掛率・販売掛率、仕入総額・販売総額、粗利率・粗利総額)
                            cell.style.textAlign = 'right';
                            break;
                        case 'chk':
                            // 中央寄せ
                            cell.style.textAlign = 'center';
                            var rowNum = r / 2;
                            if (dataItem !== undefined) {
                                if (dataItem.received_order_flg == this.FLG_ON || dataItem.copy_received_order_flg == this.FLG_ON) {
                                    // 受注確定済み or コピー元受注確定済み
                                    cell.style.backgroundColor = 'yellow';
                                }
                            }
                            break;
                        case 'row_print_flg' :
                        case 'price_print_flg' :
                            cell.style.textAlign = 'center';
                            break;
                        case 'sales_use_flg' :
                            // 販売額利用フラグ
                            // 中央寄せ
                            cell.style.textAlign = 'center';
                            cell.childNodes[0].disabled = true;
                            break;
                    }
                }
            }.bind(this);
            // キーダウンイベント
            gridCtrl.hostElement.addEventListener('keydown', function (e) {
                // 読み取り専用セルスキップ
                this.gridKeyDownSkipReadOnlyCell(gridCtrl, e, wjGrid);
                // クリップボード処理
                if(gridCtrl.autoClipboard === false){
                    this.wjMultiRowClipboardCtrl(wjCore.Clipboard, gridCtrl, this.NON_PASTING_COLS, this.wjMultiRowClipBoardValidation, function(pastedRowList){
                        for(let i in pastedRowList){
                            // 階層の場合、階層名変更
                            this.changeProduct(null, pastedRowList[i], false);
                            // 行の金額を計算
                            this.calcTreeGridRowData(pastedRowList[i], 'quote_quantity');
                        }
                        // 全体の計算
                        this.calcGridCostSalesTotal();
                    }.bind(this));
                }
            }.bind(this), true);

            // 商品コードオートコンプリート ※minLength=1でないとACのreadOnlyがtrue且つ入力文字数が1文字の際に、内容の復帰できない
            this.acProductCodeList[this.ZERO] = new CustomGridEditor(gridCtrl, 'product_code', wjcInput.AutoComplete, {
                searchMemberPath: "product_code",
                displayMemberPath: "product_code",
                selectedValuePath: "product_id",
                isRequired: false,
            }, 2, 1, 2);
            // 商品名オートコンプリート ※minLength=1でないとACのreadOnlyがtrue且つ入力文字数が1文字の際に、内容の復帰できない
            this.acProductNameList[this.ZERO] = new CustomGridEditor(gridCtrl, 'product_name', wjcInput.AutoComplete, {
                searchMemberPath: "product_name, product_short_name",
                displayMemberPath: "product_name",
                selectedValuePath: "product_id",
                isRequired: false,
            }, 2, 1, 1);
            // メーカー名オートコンプリート ※minLength=1でないと、リスト外の文字が入力出来てしまう（1文字）
            this.acMakerList[this.ZERO] = new CustomGridEditor(gridCtrl, 'maker_name', wjcInput.AutoComplete, {
                delay: 50,
                searchMemberPath: "supplier_name, supplier_short_name",
                displayMemberPath: "supplier_name",
                itemsSource: this.makerList,
                selectedValuePath: "id",
                isRequired: false,
                maxItems: this.makerList.length,
                minLength: 1,
                textChanged: this.setTextChanged
            }, 2, 1, 1);
            // 仕入先名オートコンプリート（supplier_maker） ※minLength=1でないと、リスト外の文字が入力出来てしまう（1文字）
            this.acSupplierList[this.ZERO] = new CustomGridEditor(gridCtrl, 'maker_name', wjcInput.AutoComplete, {
                delay: 50,
                searchMemberPath: "supplier_name, supplier_short_name",
                displayMemberPath: "supplier_name",
                itemsSource: null,
                selectedValuePath: "unique_key",
                isRequired: false,
                maxItems: this.supplierList.length,
                minLength: 1,
                textChanged: this.setTextChanged
            }, 2, 2, 1);

            return gridCtrl;
        },
        // グリッドの商品変更時の最小単位数チェック　商品IDのクリアなど
        changingProductZero(product, row, isCode){
            var isCancel = false;

            // 入力していた値の復元
            if(isCode){
                product.control.text = row.product_code;
            }else{
                product.control.text = row.product_name;
            }

            isCancel = true;
            return isCancel;
        },
        // グリッドの商品変更時
        changeProductZero(product, row, isCode){
            if(product !== null){
                // 商品を変更したか
            }else{
                // 階層名変更の場合
                if(row.layer_flg === this.FLG_ON){
                    var productName = row.product_name;
                    var item = this.findTree(this.wjTreeViewControl[this.ZERO].itemsSource, 'filter_tree_path', row.filter_tree_path);
                    item['header'] = productName;
                    this.wjTreeViewControl[this.ZERO].loadTree();

                    //this.wjTreeViewControl.getNode(item).refresh();
                    this.checkedDownTree(this.wjTreeViewControl[this.ZERO].nodes[0].dataItem['items'], false);
                    // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
                    this.checkedAllTreeGrid(false);
                }else{
                    // nop
                }
            }
        },
        // 受注確定取消
        cancelReceivedOrder() {
            var quoteDetailIdList = [];
            var gridData = this.gridDataList[this.ZERO];   // 0版固定
            for (var i = 0; i < gridData.length; i++) {
                if (gridData[i].construction_id == this.addLayerId) {
                    // 追加部材は受注取り消しさせない
                    continue;
                }
                if (gridData[i].chk == true) {
                    quoteDetailIdList.push(gridData[i].quote_detail_id);
                }
            }
            if (quoteDetailIdList.length == 0) {
                // チェックが付いている明細なし
                alert(MSG_ERROR_NO_SELECT);
                return;
            }
            // 確認
            if (!confirm(MSG_CONFIRM_CANCEL_RECIVED_ORDER)) {
                return;
            }

            this.loading = true;
            // エラーの初期化
            this.initErr(this.errors.quote);
            for (const key in this.errors.quoteVerTab) {
                this.initErr(this.errors.quoteVerTab[key]);
            }

            var params = new URLSearchParams();
            this.appendParamsForQuoteSaving(params);
            params.append('quote_detail_ids', JSON.stringify(quoteDetailIdList));

            axios.post('/quote-edit/cancel-received-order', params)
            .then( function (response) {
                // this.loading = false

                if (response.data) {
                    // 成功
                    if (response.data.status == true) {
                        window.onbeforeunload = null;

                        var selfUrl = window.location.href;
                        var tmpArr = selfUrl.split('?');
                        var newUrl = tmpArr[0] + this.getQuery;
                        location.href = newUrl;
                    } else {
                        this.loading = false
                        if(response.data.msg){
                            alert(response.data.msg);
                        }else{
                            alert(MSG_ERROR);
                        }
                    }
                    
                } else {
                    alert(MSG_ERROR);
                }
            }.bind(this))
            .catch(function (error) {
                this.setErrorInfo(error);
                this.loading = false;
            }.bind(this))
        },
    },
};

</script>
