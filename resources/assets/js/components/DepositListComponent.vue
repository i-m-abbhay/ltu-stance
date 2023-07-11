<template>
  <div>
    <loading-component :loading="loading" />
    <!-- 検索条件 -->
    <div class="search-form search-body col-sm-12 col-md-12" id="searchForm">
      <form
        id="searchForm"
        name="searchForm"
        class="form-horizontal"
        @submit.prevent="search"
      >
        <div class="row">
          <div class="col-md-2 col-sm-2">
            <label class="control-label">部門名</label>
            <wj-auto-complete
              class="form-control"
              id="acClassBig"
              search-member-path="department_name"
              display-member-path="department_name"
              selected-value-path="id"
              :selected-index="-1"
              :selected-value="searchParams.department_id"
              :is-required="false"
              :initialized="initDepartment"
              :selectedIndexChanged="changeIdxDepartment"
              :max-items="departmentlist.length"
              :items-source="departmentlist"
            ></wj-auto-complete>
          </div>
          <div class="col-md-3 col-sm-3">
            <label class="control-label">担当者名</label>
            <wj-auto-complete
              class="form-control"
              id="acClassBig"
              search-member-path="staff_name"
              display-member-path="staff_name"
              selected-value-path="id"
              :selected-index="-1"
              :selected-value="searchParams.staff_id"
              :is-required="false"
              :initialized="initStaff"
              :selectedIndexChanged="changeIdxStaff"
              :max-items="stafflist.length"
              :items-source="stafflist"
            ></wj-auto-complete>
          </div>
          <div class="col-md-3 col-sm-3">
            <label class="control-label">得意先名</label>
            <wj-auto-complete
              class="form-control"
              id="acClassBig"
              search-member-path="customer_name"
              display-member-path="customer_name"
              selected-value-path="id"
              :selected-index="-1"
              :selected-value="searchParams.customer_id"
              :is-required="false"
              :initialized="initCustomer"
              :max-items="customerlist.length"
              :items-source="customerlist"
            ></wj-auto-complete>
          </div>

          <div class="col-md-3 col-sm-3">
            <label class="control-label">請求番号</label>
            <div id="textarea">
              <input type="text" class="form-control" v-model="searchParams.request_no" />
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-3 col-sm-3">
            <label class="control-label">入金番号</label>
            <div id="textarea">
              <input type="text" class="form-control" v-model="searchParams.deposit_no" />
            </div>
          </div>

          <div class="col-md-2 col-sm-2">
            <label>入金予定月</label>
            <wj-input-date
              class="form-control"
              :value="searchParams.deposit_month"
              :selected-value="searchParams.deposit_month"
              :initialized="initDepositMonth"
              :isRequired="false"
              selectionMode="Month"
              format="yyyy/MM"
            ></wj-input-date>
          </div>

          <div class="col-md-2 col-sm-2">
            <label class="control-label">金額範囲</label>
            <div id="textarea">
              <input
                type="text"
                class="form-control"
                v-model="searchParams.amount_from"
              />
            </div>
          </div>
          <div class="col-md-1 col-sm-1 text-center">
            <label class="control-label"><br /></label>
            <div>～</div>
          </div>
          <div class="col-md-2 col-sm-2">
            <label class="control-label"><br /></label>

            <div id="textarea">
              <input type="text" class="form-control" v-model="searchParams.amount_to" />
            </div>
          </div>
        </div>

        <div class="col-md-12 col-sm-12 text-right">
          <button type="button" class="btn btn-clear" @click="clear()">クリア</button>
          <button type="submit" class="btn btn-search">検索</button>
        </div>
        <div class="clearfix"></div>
      </form>
    </div>
    <br />
    <!-- 検索結果グリッド -->
    <div class="col-md-12 col-sm-12 result-body" v-show="isSearched">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-5 col-sm-5 col-xs-12">
            <div><br /></div>
            <div class="input-group">
              <div class="row">
                <div class="col-md-12 col-xs-12">
                  <u>絞り込み機能</u>
                </div>
              </div>
              <div class="row">
                <el-checkbox
                  class="col-md-2 col-xs-12"
                  v-model="filterList.deposited"
                  :true-label="FLG_ON"
                  :false-label="FLG_OFF"
                  @input="filter($event)"
                  >入金済</el-checkbox
                >
                <el-checkbox
                  class="col-md-2 col-xs-12"
                  v-model="filterList.carried"
                  :true-label="FLG_ON"
                  :false-label="FLG_OFF"
                  @input="filter($event)"
                  >繰越済</el-checkbox
                >
                <el-checkbox
                  class="col-md-2 col-xs-12"
                  v-model="filterList.miscalculation"
                  :true-label="FLG_ON"
                  :false-label="FLG_OFF"
                  @input="filter($event)"
                  >違算有</el-checkbox
                >
                <el-checkbox
                  class="col-md-2 col-xs-12"
                  v-model="filterList.notpayment"
                  :true-label="FLG_ON"
                  :false-label="FLG_OFF"
                  @input="filter($event)"
                  >未入金</el-checkbox
                >
              </div>
            </div>
          </div>
          <div class="col-md-7 col-sm-7 col-xs-12 text-right">
            <div class="row">
              <div class="form-group">
                <div class="col-md-12 col-sm-12 text-right">
                  <button
                    type="button"
                    class="btn btn-search btn-sm form-control"
                    @click="newInput()"
                  >
                    新規入力
                  </button>
                  <button
                    type="button"
                    class="btn btn-search btn-sm form-control"
                    @click="comfirmDeposit()"
                  >
                    入金情報確定
                  </button>
                </div>
              </div>
            </div>
            <div class="row">
              <p
                class="col-md-12 col-xs-12 pull-right search-count"
                style="text-align: right"
              >
                検索結果：{{ tableData }}件
              </p>
            </div>

            <div class="col-md-12 pull-right" style="text-align: right">
              <wj-collection-view-navigator
                headerFormat="{currentPage: n0} / {pageCount: n0}"
                :byPage="true"
                :cv="credited"
              />
            </div>
          </div>

          <!-- グリッド -->
          <div class="col-md-12 col-sm-12 col-xs-12">
            <div id="wjDepositGrid"></div>
          </div>
        </div>
      </div>
    </div>
    <!-- フッタ -->
    <div class="col-md-12">
      <br />
    </div>
    <div class="row" v-show="isSearched">
      <div class="col-md-5 col-sm-5">
        <div class="row">
          <br />
        </div>

        <!-- <div class="col-md-2 col-sm-5 text-left">
          <button
            type="button"
            class="btn btn-search btn-sm form-control"
            @click="outputCreditedList()"
          >
            入金情報出力
          </button>
        </div> -->
        <div class="col-md-4 col-sm-5 text-right">
          <button
            type="button"
            class="btn btn-search btn-sm form-control btn-yellow"
            @click="importTransfer()"
          >
            振込データ取込
          </button>
        </div>
      </div>

      <div class="col-md-5 col-sm-5 text-left">
        <div class="row">
          <br />
        </div>
        <!-- <div class="row">
          <div class="col-md-4 col-sm-4 text-left">
            <button
              type="button"
              class="btn btn-search btn-sm form-control"
              @click="outputSalesDetail()"
            >
              回収予定印刷
            </button>
          </div>
        </div> -->
      </div>

      <div class="col-md-2 col-sm-2 text-right">
        <div class="row">
          <br />
        </div>
        <div class="row">
          <div class="col-md-11 offset-md-1">
            <button
              type="button"
              class="btn btn-search btn-sm form-control"
              @click="back()"
            >
              戻る
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- 新規入力ダイアログ -->
    <el-dialog title="得意先選択" :visible.sync="newInputDialog" width="80%">
      <div class="row">
        <div class="col-sm-12 text-left">
          <div class="row">
            <div class="col-md-4 col-sm-4">
              <label class="control-label">部門名</label>
              <wj-auto-complete
                class="form-control"
                id="acClassBig"
                search-member-path="department_name"
                display-member-path="department_name"
                selected-value-path="id"
                :selected-index="-1"
                :selected-value="newInputParams.department_id"
                :is-required="false"
                :initialized="initDepartmentnewInput"
                :selectedIndexChanged="changeIdxDepartmentNewInput"
                :max-items="departmentlist.length"
                :items-source="departmentlist"
              ></wj-auto-complete>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 col-sm-4">
              <label class="control-label">担当者名</label>
              <wj-auto-complete
                class="form-control"
                id="acClassBig"
                search-member-path="staff_name"
                display-member-path="staff_name"
                selected-value-path="id"
                :selected-index="-1"
                :selected-value="newInputParams.staff_id"
                :is-required="false"
                :initialized="initStaffnewInput"
                :selectedIndexChanged="changeIdxStaffNewInput"
                :max-items="stafflist.length"
                :items-source="stafflist"
              ></wj-auto-complete>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 col-sm-4">
              <label class="control-label">得意先名</label>
              <wj-auto-complete
                class="form-control"
                id="acClassBig"
                search-member-path="customer_name"
                display-member-path="customer_name"
                selected-value-path="id"
                :selected-index="-1"
                :selected-value="newInputParams.customer_id"
                :is-required="false"
                :initialized="initCustomernewInput"
                :max-items="customerlist.length"
                :items-source="customerlist"
              ></wj-auto-complete>
            </div>
          </div>
        </div>
      </div>
      <div slot="footer" class="dialog-footer">
        <el-button type="primary" @click="newInputProc">OK</el-button>
        <el-button @click="newInputDialog = false">キャンセル</el-button>
      </div>
    </el-dialog>

    <!-- 振込データ取込みダイアログ -->
    <el-dialog
      title="振込データ取込メニュー"
      id="importdialog"
      :visible.sync="importTransferDialog"
      width="95%"
      height="95%"
    >
      <div class="row">
        <div class="col-sm-12">
          <label class="control-label">ファイルを指定してください</label>
          <div style="display: flex">
            <input
              @change="selectedFile"
              type="file"
              class="uploadfile"
              accept=".csv"
              ref="inputfile"
            />

            {{ "   　　 " }}　 {{ this.inputData.fileDate }}　 {{ this.inputData.count }}
            <div v-show="this.inputData.count != null">件</div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-sm-8">
          <!-- グリッド -->
          <div class="grid-form">
            <el-table
              :data="inputData.csvList"
              height="370"
              border
              v-loading="loading"
              :row-class-name="tableRowClassName"
              style="width: 100%"
            >
              <el-table-column label="振込名カナ" width="160">
                <template slot-scope="scope"
                  ><div class="grid-column">
                    {{ scope.row.customer_name_kana }}
                  </div></template
                >
              </el-table-column>

              <el-table-column label="振込金額" width="120">
                <template slot-scope="scope"
                  ><div class="grid-column text-right">
                    {{ comma_format(scope.row.amount) }}
                  </div></template
                >
              </el-table-column>

              <el-table-column label="得意先名" width="160">
                <template slot-scope="scope"
                  ><div class="grid-column">{{ scope.row.customer_name }}</div></template
                >
              </el-table-column>

              <el-table-column label="入金日" width="100">
                <template slot-scope="scope"
                  ><div class="grid-column">{{ scope.row.credited_date }}</div></template
                >
              </el-table-column>

              <el-table-column label="請求番号" width="130">
                <template slot-scope="scope"
                  ><div class="grid-column">{{ scope.row.request_no }}</div></template
                >
              </el-table-column>

              <el-table-column label="入金予定金額" width="120">
                <template slot-scope="scope"
                  ><div class="grid-column text-right">
                    {{ comma_format(scope.row.total_sales) }}
                  </div></template
                >
              </el-table-column>

              <el-table-column label="振込料" width="100">
                <template slot-scope="scope"
                  ><div class="grid-column">
                    <input
                      type="number"
                      v-model="scope.row.transfer_amount"
                      style="width: 95%; text-align: right"
                    />
                    <!-- {{ comma_format(scope.row.transfer_amount) }} -->
                  </div></template
                >
              </el-table-column>

              <el-table-column label="入金差額" width="120">
                <template slot-scope="scope">
                  <div class="grid-column text-right">
                    {{
                      comma_format(
                        scope.row.total_sales -
                          scope.row.amount -
                          scope.row.transfer_amount
                      )
                    }}
                  </div></template
                >
              </el-table-column>

              <el-table-column label="選択" width="75">
                <template slot-scope="scope">
                  <div v-show="scope.row.select == 0">
                    <button
                      class="btn btn-info btn-red"
                      @click="onClickRight(scope.$index)"
                    >
                      →
                    </button>
                  </div>
                  <div v-show="scope.row.select == 1">
                    <button
                      class="btn btn-info btn-blue"
                      @click="onClickLeft(scope.$index)"
                    >
                      ←
                    </button>
                  </div>
                  <div v-show="scope.row.select == 2">
                    <button disabled="false" class="btn btn-info btn-gray">←</button>
                  </div>
                </template>
              </el-table-column>

              <el-table-column label="除外" width="75">
                <template slot-scope="scope">
                  <div v-show="scope.row.out == 0">
                    <button disabled="false" class="btn btn-info btn-gray">除外</button>
                  </div>
                  <div v-show="scope.row.out == 1">
                    <button
                      class="btn btn-info btn-blue"
                      @click="onClickOut(scope.$index)"
                    >
                      除外
                    </button>
                  </div>
                  <div v-show="scope.row.out == 2">
                    <button
                      class="btn btn-info btn-red"
                      @click="onClickReturn(scope.$index)"
                    >
                      戻す
                    </button>
                  </div>
                </template>
              </el-table-column>

              <el-table-column label="登録" width="40">
                <template slot-scope="scope">
                  <el-checkbox
                    true-label="1"
                    false-label="0"
                    v-model="scope.row.check"
                  ></el-checkbox>
                </template>
              </el-table-column>
            </el-table>
          </div>
        </div>

        <div class="col-sm-4">
          <!-- グリッド -->
          <div class="grid-form">
            <el-table
              :data="inputData.creditedList"
              :row-class-name="TableRowRadio"
              height="370"
              border
              v-loading="loading"
              style="width: 100%"
            >
              <el-table-column label="" width="40">
                <template slot-scope="scope">
                  <input
                    type="radio"
                    :disabled="scope.row.radio_button == 1"
                    name="myRadio"
                    :value="scope.$index"
                    v-model="radioData"
                  />
                </template>
              </el-table-column>

              <el-table-column label="得意先名" width="160">
                <template slot-scope="scope"
                  ><div class="grid-column">{{ scope.row.customer_name }}</div></template
                >
              </el-table-column>

              <el-table-column label="入金予定日" width="100">
                <template slot-scope="scope"
                  ><div class="grid-column">
                    {{ scope.row.expecteddeposit_at }}
                  </div></template
                >
              </el-table-column>

              <el-table-column label="請求番号" width="150">
                <template slot-scope="scope"
                  ><div class="grid-column">{{ scope.row.request_no }}</div></template
                >
              </el-table-column>
              <el-table-column label="入金予定金額" width="80">
                <template slot-scope="scope"
                  ><div class="grid-column text-right">
                    {{ comma_format(scope.row.total_sales) }}
                  </div></template
                >
              </el-table-column>
            </el-table>
          </div>
        </div>
      </div>

      <div slot="footer" class="dialog-footer">
        <el-button type="primary" @click="completeImport()">完了</el-button>
        <el-button @click="importTransferDialog = false">キャンセル</el-button>
      </div>
    </el-dialog>

    <!-- 違算処理ダイアログ -->
    <el-dialog title="違算処理" :visible.sync="miscalculationDialog" width="80%">
      <div class="row">
        <div class="col-sm-12">
          <label class="control-label">違算理由</label>
          <textarea
            class="form-control"
            placeholder="理由とその対策を必ず入れること"
            rows="8"
            v-model="activeRow.miscalculation_app_com"
            :readonly="activeRow.status != 1"
          ></textarea>
        </div>
      </div>

      <div class="row">
        <div class="col-sm-12">
          <label class="control-label">{{ activeRow.miscalculation_app_info }}</label>
        </div>
      </div>
      <div class="row">
        <div class="col-sm-12">
          <br />
        </div>
      </div>

      <div class="row">
        <div class="col-sm-12">
          <textarea
            class="form-control"
            placeholder="上長コメント（否認する場合は理由を必ず入力）"
            rows="3"
            v-model="activeRow.miscalculation_auth_com"
            :readonly="activeRow.miscalculation_status == 0 || activeRow.status != 1"
          ></textarea>
        </div>
      </div>

      <div class="row">
        <div class="col-sm-12">
          <label
            class="control-label"
            style="white-space: pre-wrap; word-wrap: break-word"
            >{{ activeRow.miscalculation_auth_info }}</label
          >
        </div>
      </div>
      <!-- <div class="row">
          <div class="col-sm-12">
            <br />
          </div>
        </div> -->
      <div
        class="row"
        v-show="activeRow.miscalculation_status != 0 && activeRow.status == 1"
      >
        <div class="col-sm-12">
          <el-button
            type="primary"
            @click="miscalculationProc(miscalculationBtnKind.APPROVAL)"
            >承認</el-button
          >
          <el-button
            type="primary"
            @click="miscalculationProc(miscalculationBtnKind.DENIAL)"
            style="background: red"
            >否認</el-button
          >
        </div>
      </div>

      <div slot="footer" class="dialog-footer">
        <div class="col-sm-7">
          <el-button
            type="primary"
            v-show="
              activeRow.miscalculation_status == 1 &&
              activeRow.miscalculation_app_id == initsearchparams.staff_id &&
              activeRow.status == 1
            "
            @click="miscalculationProc(miscalculationBtnKind.CANCEL)"
            style="background: red"
            >取り消し</el-button
          >
        </div>

        <el-button
          type="primary"
          v-show="activeRow.miscalculation_status == 0 && activeRow.status == 1"
          @click="miscalculationProc(miscalculationBtnKind.APPLYING)"
          >申請</el-button
        >
        <el-button @click="miscalculationDialog = false">戻る</el-button>
      </div>
    </el-dialog>

    <!-- 入金情報出力 -->
    <el-dialog title="出力メニュー" :visible.sync="outputDialog" width="80%">
      <div class="row">
        <div class="col-sm-12">
          <div class="col-md-3 col-sm-3">
            <wj-input-date
              class="form-control"
              :value="searchParams.deposit_month"
              :selected-value="searchParams.deposit_month"
              :initialized="initDepositMonth"
              :isRequired="false"
            ></wj-input-date>
          </div>
          <div class="col-md-1 col-sm-1 text-center">～</div>

          <div class="col-md-3 col-sm-3">
            <wj-input-date
              class="form-control"
              :value="searchParams.deposit_month"
              :selected-value="searchParams.deposit_month"
              :initialized="initDepositMonth"
              :isRequired="false"
            ></wj-input-date>
          </div>

          <div class="col-md-3 col-sm-3">
            <wj-auto-complete
              class="form-control"
              id="acClassBig"
              search-member-path="department_name"
              display-member-path="department_name"
              selected-value-path="id"
              :selected-index="-1"
              :selected-value="department_id"
              :is-required="false"
              :max-items="departmentlist.length"
              :items-source="departmentlist"
            ></wj-auto-complete>
            <input type="checkbox" id="checkbox" v-model="checkedDepartment" />
            <label for="checkbox">受取部門</label>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-sm-12" style="margin-left: 20px">
          <input type="radio" id="all" value="0" v-model="outputRadio" />
          <label for="all">全て</label>
          <br />
          <input type="radio" id="select" value="1" v-model="outputRadio" />
          <label for="select">選択分のみ</label>
          <br />
          <input type="radio" id="notcomlete" value="2" v-model="outputRadio" />
          <label for="notcomlete">会計未済み分のみ</label>
          <br />
        </div>
      </div>

      <div class="row">
        <br />
      </div>
      <div class="row">
        <div class="col-sm-12">
          <div class="col-md-3 col-sm-3">
            <label>出力方法を選択してください</label>
            <wj-input-date
              class="form-control"
              :value="searchParams.deposit_month"
              :selected-value="searchParams.deposit_month"
              :initialized="initDepositMonth"
              :isRequired="false"
            ></wj-input-date>
          </div>
        </div>
      </div>

      <div slot="footer" class="dialog-footer">
        <el-button
          type="primary"
          @click="miscalculationProc(miscalculationBtnKind.APPLYING)"
          >実行</el-button
        >
        <el-button @click="miscalculationDialog = false">キャンセル</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import * as wjCore from "@grapecity/wijmo";
import * as wjcInput from "@grapecity/wijmo.input";
import * as wjGrid from "@grapecity/wijmo.grid";
import * as wjMultiRow from "@grapecity/wijmo.grid.multirow";
import * as wjDetail from "@grapecity/wijmo.grid.detail";
import { forEach, isSet } from "lodash";
import { CustomGridEditor } from '../CustomGridEditor.js';

export default {
  data: () => ({
    loading: false,
    tableData: 0,
    urlparam: "",
    queryparam: "",
    FLG_ON: 1,
    FLG_OFF: 0,
    ERROR_MSG: MSG_ERROR_NOT_IMAGE,
    isSearched: false,

    //新規入力ダイアログ
    newInputDialog: false,

    //振込データ取込
    importTransferDialog: false,
    isSelectedFile: false,
    inputData: {
      csvList: [],
      creditedList: [],
      fileName: null,
      fileDate: null,
      crc: null,
      count: null,
    },
    radioData: 0,

    //入金情報出力
    outputDialog: false,
    department_id: null,
    checkedDepartment: false,
    outputRadio: 0,

    //違算処理
    miscalculationDialog: false,
    activeRow: {
      miscalculation_status: null,
      miscalculation_app_com: null,
      miscalculation_app_info: null,
      miscalculation_auth_com: null,
      miscalculation_auth_info: null,
    },
    miscalculationBtnKind: {
      APPLYING: "APPLYING", //申請
      APPROVAL: "APPROVAL", //承認
      DENIAL: "DENIAL", //否認
      CANCEL: "CANCEL", //取り消し
    },

    //ステータス
    DEPOSITED: { value: 3, display: "入金済", class: "deposited" },
    CARRIED: { value: 2, display: "繰越済", class: "carried" },
    MISCALCULATION: { value: 1, display: "違算有", class: "miscalculation" },
    NOTPAYMENT: { value: 0, display: "未入金", class: "notpayment" },

    //違算ステータス
    MIS_UNAPPLIED: { value: 0, display: "未申請", class: "miscalculation_status" },
    MIS_APPROVAL_PENDING: { value: 1, display: "承認待", class: "miscalculation_status" },
    MIS_APPROVED: { value: 2, display: "承認済", class: "miscalculation_status" },

    //ステータス（明細）
    UNAPPLIED: { value: 0, display: "未申請", class: "unapplied" },
    APPLYING: { value: 1, display: "申請中", class: "applying" },
    APPROVAL: { value: 2, display: "承認", class: "approval" },

    //回収サイト
    THIS_MONTH: { value: 0, display: "当月" },
    MONTH_LATER1: { value: 1, display: "翌月" },
    MONTH_LATER2: { value: 2, display: "翌々月" },
    MONTH_LATER3: { value: 3, display: "3ヶ月後" },
    MONTH_LATER4: { value: 4, display: "4ヶ月後" },

    //回収区分
    CASH: { value: 1, display: "現金" },
    TRANSFER: { value: 2, display: "振込" },
    BILLS: { value: 3, display: "手形" },
    BILLSCASH: { value: 4, display: "手形現金" },
    BILLSTRANSFER: { value: 5, display: "手形振込" },
    UNDECIDED: { value: 9, display: "未定" },

    //会計フラグ
    DONE: "済み",
    UNPAID: "未済",

    //絞り込み
    filterList: {
      deposited: 0, //入金済み
      carried: 0, //繰越済
      miscalculation: 1, //違算有
      notpayment: 1, //未入金
    },

    credited: new wjCore.CollectionView(),

    creditedList: [],
    creditedDetailList: [],
    // 子グリッドの管理者
    gridDetailProvider: null,

    layoutDefinition: null,
    layoutDetailDefinition: null,
    keepDOM: {},
    keepDetailDOM: {},
    // グリッド設定用
    gridSetting: {
      // リサイジング不可[ 写真データ ]
      deny_resizing_col: [],
      // 非表示[ ID ]
      invisible_col: [16, 17, 18],
    },
    // 子グリッド設定用
    detailGridSetting: {
      // リサイジング不可[ 印刷 ]
      deny_resizing_col: [],
      // 非表示[ ID ]
      invisible_col: [13, 14, 15, 16],
    },
    gridPKCol: 16,
    detailGridPKCol: 13,

    checkedRows: {},

    newInputParams: {
      customer_id: null,
      department_id: null,
      staff_id: null,
    },

    wjnewInputObj: {
      customer: {},
      department: {},
      staff: {},
    },

    searchParams: {
      customer_id: null,
      department_id: null,
      staff_id: null,
      deposit_month: null,
      request_no: null,
      deposit_no: null,
      amount_from: null,
      amount_to: null,
    },

    wjSearchObj: {
      customer: {},
      department: {},
      staff: {},
      deposit_no: {},
      deposit_month: {},
      amount_from: {},
      amount_to: {},
    },
    wjCreditedGrid: null,
  }),
  props: {
    customerlist: Array,
    banklist: Array,
    branchlist: Array,
    stafflist: Array,
    departmentlist: Array,
    authclosing: Number,
    authinvoice: Number,
    initsearchparams: {
      department_id: String,
      staff_id: Number,
    },
  },
  created: function () {
    Vue.config.devtools = true;
    this.queryparam = window.location.search;

    this.layoutDefinition = this.getLayout();
    this.layoutDetailDefinition = this.detailLayout();
  },
  mounted: function () {
    this.changeIdxDepartment(this.wjSearchObj.department);
    this.changeIdxStaff(this.wjSearchObj.staff);

    // 初回の検索条件をセット
    this.setInitSearchParams(this.searchParams, this.initsearchparams);

    // グリッド初期表示
    var targetDiv = "#wjDepositGrid";

    this.$nextTick(function () {
      var _this = this;
      // var gridItemSource = this.returns;
      // gridItemSource.refresh();
      this.wjCreditedGrid = this.createGrid(targetDiv, this.credited);
      this.applyGridSettings(this.wjCreditedGrid);
    });

    //通知から飛んできた場合、初期検索
    var params = new URL(document.location).searchParams;
    var request_no = params.get("request_no");
    var customer_id = params.get("customer_id");

    if (request_no != null || customer_id != null) {
      var wjSearchObj = this.wjSearchObj;
      Object.keys(wjSearchObj).forEach(function (key) {
        wjSearchObj[key].selectedValue = null;
        wjSearchObj[key].value = null;
        wjSearchObj[key].text = null;
      });
      this.searchParams.department_id = null;
      this.searchParams.staff_id = null;
      this.searchParams.request_no = request_no;
      this.searchParams.customer_id = Number(customer_id);
      this.wjSearchObj.customer.selectedValue = Number(customer_id);
      this.search();
    }
  },

  methods: {
    // グリッド生成
    createGrid(divId, gridItems) {
      var gridCtrl = new wjMultiRow.MultiRow(divId, {
        itemsSource: gridItems,
        layoutDefinition: this.layoutDefinition,
        showSort: false,
        allowAddNew: false,
        allowDelete: false,
        allowSorting: false,
        // headersVisibility: wjGrid.HeadersVisibility.Column,
        keyActionEnter: wjGrid.KeyAction.None,
        isReadOnly: true,
        // selectionMode: wjGrid.SelectionMode.None,
      });

      var _this = this;

      // 行高さ
      gridCtrl.rows.defaultSize = 30;

      // セル編集後イベント
      gridCtrl.cellEditEnded.addHandler(
        function (s, e) {
          var row = s.collectionView.currentItem;
          var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);

          gridCtrl.collectionView.commitEdit();
        }.bind(this)
      );

      // 編集前イベント
      gridCtrl.beginningEdit.addHandler((s, e) => {});

      var _this = this;
      // itemFormatterセット
      gridCtrl.itemFormatter = function (panel, r, c, cell) {
        // 列ヘッダのセンタリング
        if (panel.cellType == wjGrid.CellType.ColumnHeader) {
          var col = panel.columns[c];
          cell.style.textAlign = "center";
          var green = "wj-green-color";
          var orange = "wj-orange-color";

          switch (col.name) {
            case "cash_total":
              cell.classList.add(green);

              break;

            case "cheque_total":
              cell.classList.add(green);

              break;

            case "transfer_total":
              cell.classList.add(green);

              break;

            case "bills_total":
              cell.classList.add(green);

              break;

            case "offset_others":
              cell.classList.add(orange);

              break;
          }
        }

        // セルごとの設定
        if (panel.cellType == wjGrid.CellType.Cell) {
          var col = panel.columns[c];
          var dataItem = panel.rows[r].dataItem;
          var item = panel.rows[r];
          var _this = this;
          cell.style.color = "";

          switch (col.name) {
            case "status":
              if (this.keepDOM[panel.getCellData(r, this.gridPKCol)]) {
                cell.appendChild(
                  this.keepDOM[panel.getCellData(r, this.gridPKCol)].status
                );
              }

              cell.style.textAlign = "center";
              break;

            case "customer_name":
              if (item.recordIndex == 0) {
                cell.style.textAlign = "left";
              } else {
                cell.style.textAlign = "left";
              }
              break;

            case "department_name":
              if (item.recordIndex == 0) {
                cell.style.textAlign = "left";
              } else {
                cell.style.textAlign = "left";
              }
              break;

            case "closing_day":
              if (item.recordIndex == 0) {
                cell.style.textAlign = "left";
              } else {
                cell.style.textAlign = "left";
              }
              break;

            case "carryforward_amount":
              if (item.recordIndex == 0) {
                cell.style.textAlign = "right";
              } else {
                cell.style.textAlign = "right";
              }
              break;

            case "cash_total":
              if (item.recordIndex == 0) {
                cell.style.textAlign = "right";
              } else {
                cell.style.textAlign = "right";
              }
              break;

            case "cheque_total":
              if (item.recordIndex == 0) {
                cell.style.textAlign = "right";
              } else {
                cell.style.textAlign = "right";
              }
              break;

            case "transfer_total":
              if (item.recordIndex == 0) {
                cell.style.textAlign = "right";
              } else {
                cell.style.textAlign = "right";
              }
              break;

            case "bills_total":
              if (item.recordIndex == 0) {
                cell.style.textAlign = "right";
              } else {
                cell.style.textAlign = "right";
              }
              break;

            case "offset_others":
              if (item.recordIndex == 0) {
                cell.style.textAlign = "right";
              } else {
                cell.style.textAlign = "right";
              }
              break;

            case "miscalculation_proc":
              if (item.recordIndex == 0) {
                cell.innerHTML = "";
                cell.appendChild(
                  this.keepDOM[panel.getCellData(r, this.gridPKCol)]
                    .miscalculation_proc_btn
                );
                break;
              } else {
                cell.style.textAlign = "right";
              }
              break;

            case "miscalculation_status":
              if (item.recordIndex == 0) {
                cell.appendChild(
                  this.keepDOM[panel.getCellData(r, this.gridPKCol)].miscalculation_status
                );
              } else {
                cell.appendChild(
                  this.keepDOM[panel.getCellData(r, this.gridPKCol)].discount_status
                );
              }
              break;
            //明細追加
            case "add":
              if (this.keepDOM[panel.getCellData(r, this.gridPKCol)]) {
                cell.appendChild(
                  _this.keepDOM[panel.getCellData(r, _this.gridPKCol)].add_btn
                );
              }
              cell.style.textAlign = "center";

              break;
            //明細削除
            case "delete":
              if (this.keepDOM[panel.getCellData(r, this.gridPKCol)]) {
                cell.appendChild(
                  _this.keepDOM[panel.getCellData(r, _this.gridPKCol)].delete_btn
                );
              }
              cell.style.textAlign = "center";

              break;

            //戻す
            case "back":
              if (this.keepDOM[panel.getCellData(r, this.gridPKCol)]) {
                cell.appendChild(
                  _this.keepDOM[panel.getCellData(r, _this.gridPKCol)].back_btn
                );
              }
              cell.style.textAlign = "center";

              break;
          }
        }
      }.bind(this);

      var _this = this;
      // 子グリッド定義
      this.gridDetailProvider = new wjDetail.FlexGridDetailProvider(gridCtrl, {
        isAnimated: false,
        maxHeight: 200,
        // detailVisibilityMode: wjDetail.DetailVisibilityMode.ExpandMulti,

        createDetailCell: function (row) {
          var cell = document.createElement("div");
          gridCtrl.hostElement.appendChild(cell);
          var detailGrid = new wjMultiRow.MultiRow(cell, {
            headersVisibility: wjGrid.HeadersVisibility.Column,
            autoGenerateColumns: false,
            itemsSource: _this.getDetails(row.dataItem),
            allowAddNew: false,
            layoutDefinition: _this.layoutDetailDefinition,
            keyActionEnter: wjGrid.KeyAction.Cycle,
            keyActionTab: wjGrid.KeyAction.Cycle,
            // selectionMode: wjGrid.SelectionMode.None,
          });

          // 編集前イベント
          detailGrid.beginningEdit.addHandler((s, e) => {
            // 編集行データ取得
            var status = s.rows[e.row].dataItem.status;
            if (
              row.dataItem.status == _this.DEPOSITED.value ||
              row.dataItem.status == _this.CARRIED.value ||
              (row.dataItem.status == this.MISCALCULATION.value &&
                (status === 1 || status === 2))
            ) {
              e.cancel = true;
              _this.gridDetailProvider.credited_date.control.isDisabled = true;
              _this.gridDetailProvider.actual_credited_date.control.isDisabled = true;
              _this.gridDetailProvider.bills_date.control.isDisabled = true;
              _this.gridDetailProvider.credit_flg.control.isDisabled = true;
              _this.gridDetailProvider.bills_date.control.isDisabled = true;
            }
          });

          // // セル編集後イベント
          // detailGrid.cellEditEnded.addHandler(
          //   function (s, e) {
          //     detailGrid.beginUpdate();
          //     // 編集したカラムを特定
          //     var editColNm = detailGrid.getBindingColumn(e.panel, e.row, e.col).name; // 取れるのは上段の列名
          //     // 編集行データ取得
          //     var row = s.collectionView.currentItem;

          //     //日付のフォーマット
          //     if (editColNm == "credited_date") {
          //       var item = s.rows[e.row].dataItem.credited_date;
          //       if (
          //         item != null &&
          //         Object.prototype.toString.call(item) == "[object Date]"
          //       ) {
          //         s.rows[e.row].dataItem.credited_date =
          //           item.getFullYear() +
          //           "-" +
          //           ("00" + (1 + item.getMonth())).slice(-2) +
          //           "-" +
          //           ("00" + item.getDate()).slice(-2);
          //       }
          //     }
          //     if (editColNm == "actual_credited_date") {
          //       var item = s.rows[e.row].dataItem.actual_credited_date;
          //       if (
          //         item != null &&
          //         Object.prototype.toString.call(item) == "[object Date]"
          //       ) {
          //         s.rows[e.row].dataItem.actual_credited_date =
          //           item.getFullYear() +
          //           "-" +
          //           ("00" + (1 + item.getMonth())).slice(-2) +
          //           "-" +
          //           ("00" + item.getDate()).slice(-2);
          //       }
          //     }

          //     detailGrid.endUpdate();
          //   }.bind(this)
          // );
          
          detailGrid.cellEditEnded.addHandler(function(s, e) {
            detailGrid.beginUpdate();
            // 編集したカラムを特定
            var editColNm = detailGrid.getBindingColumn(e.panel, e.row, e.col).name;       // 取れるのは上段の列名
            // 編集行データ取得　　　　　TODO: たまにrowが取得できていなくてエラーが出る
            var row = s.collectionView.currentItem;

            switch (editColNm) {
              case 'bank_code':
                  if (this.gridDetailProvider.bank_code.selectedItem != null) {
                    if (row.bank_code != this.gridDetailProvider.bank_code.selectedItem.bank_code) {
                      row.branch_code = '';
                      row.branch_name = '';
                    }
                    var bank = this.gridDetailProvider.bank_code.selectedItem;
                    row.bank_code = bank.bank_code;
                    row.bank_name = bank.bank_name;
                  } else {
                    row.bank_code = '';
                    row.bank_name = '';
                    row.branch_code = '';
                    row.branch_name = '';
                  }
                  break;
              case 'branch_code':
                  if (this.gridDetailProvider.branch_code.selectedItem != null && this.rmUndefinedBlank(row.bank_code) != '') {
                    var branch = this.gridDetailProvider.branch_code.selectedItem;
                    row.branch_code = branch.branch_code;
                    row.branch_name = branch.branch_name;
                  } else {
                    row.branch_code = '';
                    row.branch_name = '';
                  }
              case 'credited_date':
                  if (this.rmUndefinedBlank(row.credited_date) != '') {
                    row.credited_date = this.dateFormat(row.credited_date);
                  }
                  break;
              case 'actual_credited_date':
                  if (this.rmUndefinedBlank(row.actual_credited_date) != '') {
                    row.actual_credited_date = this.dateFormat(row.actual_credited_date);
                  }
                  break;
              case 'bills_date':
                  row.bills_date = this.dateFormat(row.bills_date);
                  break;
            }

            detailGrid.endUpdate();
          }.bind(this));

          detailGrid.itemFormatter = function (panel, r, c, cell) {
            // 列ヘッダのセンタリング
            if (panel.cellType == wjGrid.CellType.ColumnHeader) {
              var col = gridCtrl.getBindingColumn(panel, r, c);
              var item = panel.rows[r];
              cell.style.textAlign = "center";
              var green = "wj-green-color";
              var orange = "wj-orange-color";
              switch (col.name) {
                //現金
                case "cash":
                  cell.classList.add(green);

                  break;

                //小切手
                case "cheque":
                  cell.classList.add(green);
                  break;

                //振込
                case "transfer":
                  if (r == 1) {
                    cell.classList.add(green);
                  } else {
                    cell.classList.add(orange);
                  }
                  break;

                //手形
                case "bills":
                  cell.classList.add(green);
                  break;

                //銀行名
                case "bank_code":
                  cell.classList.add(green);
                  break;

                //手形番号
                case "bills_no":
                  cell.classList.add(green);
                  break;

                //販売促進費
                case "sales_promotion_expenses":
                  cell.classList.add(orange);
                  break;

                //買掛金相殺
                case "accounts_payed":
                  cell.classList.add(orange);
                  break;

                //値引き申請
                case "status":
                  cell.classList.add(orange);
                  break;
              }
            }

            // セルごとの設定
            if (panel.cellType == wjGrid.CellType.Cell) {
              // var col = panel.columns[c];
              var col = gridCtrl.getBindingColumn(panel, r, c);
              var dataItem = panel.rows[r].dataItem;
              var item = panel.rows[r];
              cell.style.color = "";
              cell.style.textAlign = "left";
              if (cell.classList.contains("text-right")) {
                cell.style.textAlign = "right";
              }

              //回収区分取得
              var collectionKbnCode = dataItem.collection_kbn_code;
              var backColor = "silver";

              switch (col.name) {
                //入金番号
                case "checked":
                  cell.style.textAlign = "center";
                  break;

                //入金番号
                case "credited_no":
                  cell.style.textAlign = "left";

                  break;

                //会計
                case "financials_flg":
                  cell.style.textAlign = "center";

                  break;

                //現金
                case "cash":
                  if (item.recordIndex == 0) {
                    if (collectionKbnCode != 1) {
                      cell.style.backgroundColor = backColor;
                    }
                  }
                  //クレジット
                  else if (item.recordIndex == 1) {
                    cell.style.textAlign = "center";
                    if (collectionKbnCode != 1) {
                      cell.style.backgroundColor = backColor;
                    }
                  }
                  break;

                //小切手
                case "cheque":
                  if (item.recordIndex == 0) {
                    if (collectionKbnCode != 1) {
                      cell.style.backgroundColor = backColor;
                    }
                  }
                  //受取部門
                  else if (item.recordIndex == 1) {
                    cell.style.textAlign = "center";
                    if (collectionKbnCode != 1) {
                      cell.style.backgroundColor = backColor;
                    }
                  }
                  break;

                //振込
                case "transfer":
                  if (item.recordIndex == 0) {
                    if (collectionKbnCode != 2) {
                      cell.style.backgroundColor = backColor;
                    }
                  }
                  //振込料
                  else if (item.recordIndex == 1) {
                    if (collectionKbnCode != 2) {
                      cell.style.backgroundColor = backColor;
                    }
                  }
                  break;

                //手形
                case "bills":
                  if (item.recordIndex == 0) {
                    if (collectionKbnCode != 3) {
                      cell.style.backgroundColor = backColor;
                    }
                  }
                  //手形期日
                  else if (item.recordIndex == 1) {
                    cell.style.textAlign = "center";
                    if (collectionKbnCode != 3) {
                      cell.style.backgroundColor = backColor;
                    }
                  }
                  break;

                //銀行名
                case "bank_code":
                  cell.style.textAlign = "left";
                  if (item.recordIndex == 0) {
                    if (collectionKbnCode != 3) {
                      cell.style.backgroundColor = backColor;
                    }
                  }
                  //支店名
                  else if (item.recordIndex == 1) {
                    if (collectionKbnCode != 3) {
                      cell.style.backgroundColor = backColor;
                    }
                  }
                  break;

                //手形番号
                case "bills_no":
                  if (item.recordIndex == 0) {
                    cell.style.textAlign = "left";
                    if (collectionKbnCode != 3) {
                      cell.style.backgroundColor = backColor;
                    }
                  } else {
                    cell.style.textAlign = "center";
                    if (collectionKbnCode != 3) {
                      cell.style.backgroundColor = backColor;
                    }
                  }
                  break;

                //値引き申請
                case "status":
                  if (
                    typeof panel.getCellData(r, _this.detailGridPKCol) !== "undefined" &&
                    panel.getCellData(r, _this.detailGridPKCol) !== null &&
                    typeof _this.keepDetailDOM[
                      panel.getCellData(r, _this.detailGridPKCol)
                    ] !== "undefined" &&
                    _this.keepDetailDOM[panel.getCellData(r, _this.detailGridPKCol)] !==
                      null
                  ) {
                    cell.appendChild(
                      _this.keepDetailDOM[panel.getCellData(r, _this.detailGridPKCol)]
                        .status
                    );
                    cell.appendChild(
                      _this.keepDetailDOM[panel.getCellData(r, _this.detailGridPKCol)]
                        .approval_btn
                    );
                    cell.appendChild(
                      _this.keepDetailDOM[panel.getCellData(r, _this.detailGridPKCol)]
                        .denial_btn
                    );
                  }
                  break;
              }
            }
          }.bind(this);

          // this.gridDetailProvider.credited_date = new CustomGridEditor(
          //   detailGrid,
          //   'credited_date',
          //   wjcInput.InputDate,
          //   {
          //     format: "d",
          //     isRequired: false,
          //   },
          //   2,
          //   1,
          //   1
          // );

          // this.gridDetailProvider.actual_credited_date = new CustomGridEditor(
          //   detailGrid,
          //   'credited_date',
          //   wjcInput.InputDate,
          //   {
          //     format: "d",
          //     isRequired: false,
          //   },
          //   2,
          //   2,
          //   1
          // );
          // this.gridDetailProvider.bills_date = new CustomGridEditor(
          //   detailGrid,
          //   'bills',
          //   wjcInput.InputDate,
          //   {
          //     format: "d",
          //     isRequired: false,
          //   },
          //   2,
          //   2,
          //   1
          // );
          this.gridDetailProvider.actual_credited_date = new CustomGridEditor(detailGrid, 'credited_date', wjcInput.InputDate, {
              format: "d",
              isRequired: false,
          }, 2, 1, 1);
          this.gridDetailProvider.arrival_plan_date = new CustomGridEditor(detailGrid, 'credited_date', wjcInput.InputDate, {
              format: "d",
              isRequired: false,
          }, 2, 2, 1);
          this.gridDetailProvider.bills_date = new CustomGridEditor(detailGrid, 'bills', wjcInput.InputDate, {
              format: "d",
              isRequired: false,
          }, 2, 2, 1);

          // 支店名
          this.gridDetailProvider.branch_code = new CustomGridEditor(detailGrid, 'bank_code', wjcInput.AutoComplete, {
            searchMemberPath: "branch_name",
            displayMemberPath: "branch_name",
            selectedValuePath: "branch_code",
            isRequired: false,
            minLength: 1,
            maxItems: this.branchlist.length,
            itemsSource: [],
          }, 2, 2, 1);
          // 銀行名
          this.gridDetailProvider.bank_code = new CustomGridEditor(detailGrid, 'bank_code', wjcInput.AutoComplete, {
            searchMemberPath: "bank_name",
            displayMemberPath: "bank_name",
            selectedValuePath: "bank_code",
            isRequired: false,
            minLength: 1,
            maxItems: this.banklist.length,
            itemsSource: this.banklist,
            selectedIndexChanged: this.changeIdxBank,
          }, 2, 1, 1);

          this.gridDetailProvider.grid_detail = detailGrid;
          return cell;
        }.bind(this),

        rowHasDetail: function (row) {
          var rtn = true;
          // 奇数行の展開ボタン削除
          if (row.recordIndex % 2 == 0) {
            rtn = false;
          }
          // // 未承認の親グリッドから展開ボタン削除
          // if (
          //   row.dataItem.approval_status.val == _this.FLG_NOT_APPROVAL ||
          //   row.dataItem.approval_status.val == _this.FLG_REJECT
          // ) {
          //   rtn = false;
          // }

          return rtn;
        }.bind(this),
      });
      gridCtrl.addEventListener(gridCtrl.hostElement, "click", (e) => {
        let ht = gridCtrl.hitTest(e.pageX, e.pageY);
        if (ht.cellType === wjGrid.CellType.RowHeader) {
          if (this.gridDetailProvider.isDetailVisible(ht.row)) {
            this.gridDetailProvider.hideDetail(ht.row);
          } else {
            this.gridDetailProvider.showDetail(ht.row);
          }
        }
      });

      return gridCtrl;
    },
    changeIdxBank: function(sender) {
      // 銀行を選択したら支店を絞り込む
      var tmpBranch = [];
      if (sender.selectedValue) {
          tmpBranch = [];
          for(var key in this.branchlist) {
              if (sender.selectedValue == this.branchlist[key].bank_code) {
                  tmpBranch.push(this.branchlist[key]);
              }
          }
      }
      this.gridDetailProvider.branch_code.changeItemsSource(tmpBranch);
      this.gridDetailProvider.branch_code.control.selectedIndex = -1;
    },
    changeIdxDepartment: function (sender) {
      // 部門を変更したら担当者を絞り込む
      var tmpStaff = this.stafflist;
      if (sender.selectedValue) {
        tmpStaff = [];
        for (var key in this.stafflist) {
          if (sender.selectedValue == this.stafflist[key].department_id) {
            tmpStaff.push(this.stafflist[key]);
          }
        }
      } else {
        var tmpStaff = [];
        this.stafflist.forEach((element) => {
          var isExist = false;
          tmpStaff.forEach((tmp) => {
            if (element.id === tmp.id) {
              isExist = true;
            }
          });

          if (!isExist) {
            tmpStaff.push(element);
          }
        });
      }
      this.wjSearchObj.staff.itemsSource = tmpStaff;
      this.wjSearchObj.staff.selectedIndex = -1;
    },
    changeIdxStaff: function (sender) {
      // 担当者を変更したら得意先を絞り込む
      var tmpCustomer = this.customerlist;
      if (sender.selectedValue) {
        tmpCustomer = [];

        for (var key in this.customerlist) {
          if (
            sender.selectedValue == this.customerlist[key].charge_staff_id &&
            (this.wjSearchObj.department.selectedValue == null
              ? true
              : this.wjSearchObj.department.selectedValue ==
                this.customerlist[key].charge_department_id)
          ) {
            tmpCustomer.push(this.customerlist[key]);
          }
        }
      }
      this.wjSearchObj.customer.itemsSource = tmpCustomer;
      this.wjSearchObj.customer.selectedIndex = -1;
    },

    changeIdxDepartmentNewInput: function (sender) {
      // 部門を変更したら担当者を絞り込む
      var tmpStaff = this.stafflist;
      if (sender.selectedValue) {
        tmpStaff = [];
        for (var key in this.stafflist) {
          if (sender.selectedValue == this.stafflist[key].department_id) {
            tmpStaff.push(this.stafflist[key]);
          }
        }
      }
      this.wjnewInputObj.staff.itemsSource = tmpStaff;
      this.wjnewInputObj.staff.selectedIndex = -1;
    },
    changeIdxStaffNewInput: function (sender) {
      // 担当者を変更したら得意先を絞り込む
      var tmpCustomer = this.customerlist;
      if (sender.selectedValue) {
        tmpCustomer = [];

        for (var key in this.customerlist) {
          if (
            sender.selectedValue == this.customerlist[key].charge_staff_id &&
            this.wjnewInputObj.department.selectedValue ==
              this.customerlist[key].charge_department_id
          ) {
            tmpCustomer.push(this.customerlist[key]);
          }
        }
      }
      this.wjnewInputObj.customer.itemsSource = tmpCustomer;
      this.wjnewInputObj.customer.selectedIndex = -1;
    },

    //登録
    process() {
      var table = this.dispTable();

      if (table.length <= 0) {
        return;
      }

      //発注点入力値確認
      for (var i = 0; i < table.length; i++) {
        if (document.getElementById("text_order_limit_" + table[i].id) == null) {
          continue;
        }

        var input = Number(
          document.getElementById("text_order_limit_" + table[i].id).value
        );

        //nullは0として新規登録
        if (input == null || input == "") {
          input = 0;
        }

        if (!Number.isInteger(Number(input)) || input < 0) {
          alert("発注点の入力値が不正です。0以上の整数を入力してください。");
          return;
        } else {
          table[i].input_order_limit = input;
        }
      }

      //保存
      axios
        .post("/order-point-list/save", table)

        .then(
          function (response) {
            this.loading = false;

            if (response.data) {
              // 成功
              this.search();
            } else {
              // 失敗
              window.onbeforeunload = null;
              location.reload();
            }
          }.bind(this)
        )

        .catch(
          function (error) {
            if (error.response.data.errors) {
              // エラーメッセージ表示
              this.showErrMsg(error.response.data.errors, this.errors);
            } else {
              if (error.response.data.message) {
                alert(error.response.data.message);
              } else {
                alert(MSG_ERROR);
              }
              window.onbeforeunload = null;
              location.reload();
            }
            this.loading = false;
          }.bind(this)
        );
    },

    // 検索
    search(id = null) {
      this.loading = true;

      var params = new URLSearchParams();

      params.append(
        "customer_id",
        this.rmUndefinedBlank(this.wjSearchObj.customer.selectedValue)
      );
      params.append(
        "department_id",
        this.rmUndefinedBlank(this.wjSearchObj.department.selectedValue)
      );

      params.append(
        "staff_id",
        this.rmUndefinedZero(this.wjSearchObj.staff.selectedValue)
      );
      params.append(
        "deposit_month",
        this.rmUndefinedBlank(this.wjSearchObj.deposit_month.text.replace("/", "-"))
      );

      params.append("request_no", this.rmUndefinedBlank(this.searchParams.request_no));
      params.append("deposit_no", this.rmUndefinedBlank(this.searchParams.deposit_no));
      params.append(
        "request_amount_from",
        this.rmUndefinedBlank(this.searchParams.amount_from)
      );
      params.append(
        "request_amount_to",
        this.rmUndefinedBlank(this.searchParams.amount_to)
      );

      axios
        .post("/deposit-list/search", params)

        .then(
          function (response) {
            if (response.data) {
              var itemsSource = [];

              this.creditedList = response.data.creditedList;
              this.creditedDetailList = response.data.creditedDetailList;
              var dataLength = 0;

              this.creditedList.forEach((element) => {
                var row = this.rowFormat(element);
                //値セット
                itemsSource.push(row);
                dataLength++;
              });
              this.tableData = dataLength;

              // データセット
              // グリッドのページング設定
              var view = new wjCore.CollectionView(itemsSource, {
                pageSize: 50,
              });

              this.credited = view;
              this.wjCreditedGrid.itemsSource = this.credited;
              this.filter();
              // 設定更新
              this.wjCreditedGrid = this.applyGridSettings(this.wjCreditedGrid);

              // 描画更新
              this.wjCreditedGrid.refresh();

              //子グリッド展開
              if (id !== null) {
                for (var i = 0; i < this.wjCreditedGrid.rows.length; i++) {
                  //2行で1レコードなので偶数行スキップ
                  if (i % 2 == 0) {
                    continue;
                  }
                  //子グリッド展開
                  var target = this.wjCreditedGrid.rows[i].dataItem.id;
                  if (target == id) {
                    this.gridDetailProvider.showDetail(i);
                  }
                }
              }
            }

            this.isSearched = true;
            this.loading = false;
          }.bind(this)
        )

        .catch(
          function (error) {
            this.loading = false;
            if (error.response.data.message) {
              alert(error.response.data.message);
            } else {
              alert(MSG_ERROR);
            }
            location.reload();
          }.bind(this)
        );
    },

    //グリッドの行データを受取り、デザインを生成する
    rowFormat: function (element) {
      // DOM生成
      // itemFormatterでDOM要素が消えないようにする為、行ごとに生成するDOMを保存しておく必要がある
      this.keepDOM[element.id] = {
        status: document.createElement("div"),
        miscalculation_proc_btn: document.createElement("div"),
        miscalculation_request_btn: document.createElement("div"),
        discount_approval_btn: document.createElement("div"),
        miscalculation_status: document.createElement("div"),
        discount_status: document.createElement("div"),
        add_btn: document.createElement("div"),
        delete_btn: document.createElement("div"),
        back_btn: document.createElement("div"),
      };

      var _this = this;
      //ボタンのパラメータ設定
      // this.display_btn_args[element.id] = {
      //   status: element.status,
      //   customer_id: element.customer_id,
      //   request_id: element.request_id,
      //   status_code: element.status_code,
      //   request_mon: element.request_mon,
      // };

      //違算処理ボタン
      this.keepDOM[element.id].miscalculation_proc_btn.innerHTML =
        "<button data-id=" +
        JSON.stringify(element.id) +
        ' class="btn btn-search grid-btn">違算処理</button>';
      this.keepDOM[element.id].miscalculation_proc_btn.addEventListener(
        "click",
        function (e) {
          if (e.target.dataset.id) var id = e.target.dataset.id;
          _this.onClickMiscalculation(element);
        }
      );
      //明細追加
      this.keepDOM[element.id].add_btn.innerHTML =
        "<button data-id=" +
        JSON.stringify(element.id) +
        ' class="btn btn-primary grid-add">追加</button>';
      this.keepDOM[element.id].add_btn.addEventListener("click", function (e) {
        if (e.target.dataset.id) var id = e.target.dataset.id;
        _this.onClickAdd(element);
      });
      //明細削除
      this.keepDOM[element.id].delete_btn.innerHTML =
        "<button data-id=" +
        JSON.stringify(element.id) +
        ' class="btn btn-primary grid-delete">削除</button>';
      this.keepDOM[element.id].delete_btn.addEventListener("click", function (e) {
        if (e.target.dataset.id) var id = e.target.dataset.id;
        _this.onClickDelete(element);
      });
      //戻す
      this.keepDOM[element.id].back_btn.innerHTML =
        "<button data-id=" +
        JSON.stringify(element.id) +
        ' class="btn btn-primary grid-back">解除</button>';
      this.keepDOM[element.id].back_btn.addEventListener("click", function (e) {
        if (e.target.dataset.id) var id = e.target.dataset.id;
        _this.onClickBack(element);
      });

      // ステータス
      switch (element.status) {
        case this.NOTPAYMENT.value:
          this.keepDOM[element.id].status.innerHTML = this.NOTPAYMENT.display;
          this.keepDOM[element.id].status.classList.add(
            this.NOTPAYMENT.class,
            this.NOTPAYMENT.class
          );
          break;
        case this.MISCALCULATION.value:
          this.keepDOM[element.id].status.innerHTML = this.MISCALCULATION.display;
          this.keepDOM[element.id].status.classList.add(
            this.MISCALCULATION.class,
            this.MISCALCULATION.class
          );
          break;
        case this.CARRIED.value:
          this.keepDOM[element.id].status.innerHTML = this.CARRIED.display;
          this.keepDOM[element.id].status.classList.add(
            this.CARRIED.class,
            this.CARRIED.class
          );
          break;
        case this.DEPOSITED.value:
          this.keepDOM[element.id].status.innerHTML = this.DEPOSITED.display;
          this.keepDOM[element.id].status.classList.add(
            this.DEPOSITED.class,
            this.DEPOSITED.class
          );
          break;
      }

      //違算申請
      if (element.status == this.MISCALCULATION.value) {
        switch (element.miscalculation_status) {
          case this.MIS_UNAPPLIED.value:
            this.keepDOM[
              element.id
            ].miscalculation_status.innerHTML = this.MIS_UNAPPLIED.display;
            this.keepDOM[element.id].miscalculation_status.classList.add(
              this.MIS_UNAPPLIED.class,
              this.MIS_UNAPPLIED.class
            );
            break;
          case this.MIS_APPROVAL_PENDING.value:
            this.keepDOM[
              element.id
            ].miscalculation_status.innerHTML = this.MIS_APPROVAL_PENDING.display;
            this.keepDOM[element.id].miscalculation_status.classList.add(
              this.MIS_APPROVAL_PENDING.class,
              this.MIS_APPROVAL_PENDING.class
            );
            break;
          case this.MIS_APPROVED.value:
            this.keepDOM[
              element.id
            ].miscalculation_status.innerHTML = this.MIS_APPROVED.display;
            this.keepDOM[element.id].miscalculation_status.classList.add(
              this.MIS_APPROVED.class,
              this.MIS_APPROVED.class
            );
            break;
        }
      }

      //値引申請
      if (element.status_applying != null) {
        this.keepDOM[element.id].discount_status.innerHTML =
          "値引" + this.APPLYING.display;
        this.keepDOM[element.id].discount_status.classList.add(
          "discount_" + this.APPLYING.class,
          "discount_" + this.APPLYING.class
        );
      } else if (element.status_approval != null) {
        this.keepDOM[element.id].discount_status.innerHTML =
          "値引" + this.APPROVAL.display;
        this.keepDOM[element.id].discount_status.classList.add(
          "discount_" + this.APPROVAL.class,
          "discount_" + this.APPROVAL.class
        );
      } else {
        this.keepDOM[element.id].discount_status.innerHTML =
          "値引" + this.UNAPPLIED.display;
        this.keepDOM[element.id].discount_status.classList.add(
          "discount_" + this.UNAPPLIED.class,
          "discount_" + this.UNAPPLIED.class
        );
      }

      //入金サイトの表示対応
      var collection_sight = "";
      switch (element.collection_sight) {
        case this.THIS_MONTH.value:
          collection_sight = this.THIS_MONTH.display;
          break;

        case this.MONTH_LATER1.value:
          collection_sight = this.MONTH_LATER1.display;
          break;

        case this.MONTH_LATER2.value:
          collection_sight = this.MONTH_LATER2.display;
          break;

        case this.MONTH_LATER3.value:
          collection_sight = this.MONTH_LATER3.display;
          break;

        case this.MONTH_LATER4.value:
          collection_sight = this.MONTH_LATER4.display;
          break;

        default:
          break;
      }

      //金種の表示対応
      var collection_kbn = "";
      switch (element.collection_kbn) {
        case this.CASH.value:
          collection_kbn = this.CASH.display;
          break;

        case this.TRANSFER.value:
          collection_kbn = this.TRANSFER.display;
          break;

        case this.BILLS.value:
          collection_kbn = this.BILLS.display;
          break;

        case this.BILLSCASH.value:
          collection_kbn = this.BILLSCASH.display;
          break;

        case this.BILLSTRANSFER.value:
          collection_kbn = this.BILLSTRANSFER.display;
          break;

        case this.UNDECIDED.value:
          collection_kbn = this.UNDECIDED.display;
          break;

        default:
          break;
      }

      //手形サイトの表示対応
      var bill_sight = "";
      if (element.bill_sight != null) {
        bill_sight = element.bill_sight;
      }

      return {
        // itemFormatterでDOM要素を書き換えてもフィルター機能でヒットするのはitemsSourceにセットした時の値
        id: element.id,
        status: element.status,
        request_no: element.request_no,
        customer_name: element.customer_name,
        sight: collection_sight + " " + collection_kbn + " " + bill_sight,
        department_name: element.department_name,
        charge_staff_name: element.charge_staff_name,
        closing_day: element.closing_day,
        expecteddeposit_at: element.expecteddeposit_at,
        carryforward_amount: element.carryforward_amount,
        request_amount: element.request_amount,
        cash_total: element.cash_total,
        cheque_total: element.cheque_total,
        transfer_total: element.transfer_total,
        bills_total: element.bills_total,
        credited_total: element.credited_total,
        offset_others: element.offset_others,
        request_amount: element.request_amount,
        balance: element.balance,
        count: element.count,
        new_date: element.new_date,
        miscalculation_status: element.miscalculation_status,
        customer_id: element.customer_id,
        sales_category: element.sales_category,
      };
    },

    // 検索条件のクリア
    clear: function () {
      // this.searchParams = this.initParams;
      var wjSearchObj = this.wjSearchObj;
      Object.keys(wjSearchObj).forEach(function (key) {
        wjSearchObj[key].selectedValue = null;
        wjSearchObj[key].value = null;
        wjSearchObj[key].text = null;
      });
      this.searchParams.request_no = null;
      this.searchParams.deposit_no = null;
      this.searchParams.amount_from = null;
      this.searchParams.amount_to = null;
    },

    // 詳細行データ取得
    getDetails(item) {
      // if (this.creditedDetailList[item.id] == null) {
      //   this.creditedDetailList[item.id] = [
      //     {
      //       credited_id: item.id,
      //       checked: false,
      //       cash: 0,
      //       credit_flg: false,
      //       cheque: 0,
      //       transfer: 0,
      //       transfer_charges: 0,
      //       bills: 0,
      //       endorsement_flg: false,
      //       sales_promotion_expenses: 0,
      //       deposits: 0,
      //       accounts_payed: 0,
      //       discount: 0,
      //     },
      //   ];
      //   return this.creditedDetailList[item.id];
      // }
      var rtnArray = [];
      if (this.creditedDetailList[item.id] != undefined) {
        this.creditedDetailList[item.id].forEach((element) => {
          // DOM生成
          // itemFormatterでDOM要素が消えないようにする為、行ごとに生成するDOMを保存しておく必要がある
          this.keepDetailDOM[element.id] = {
            status: document.createElement("div"),
            approval_btn: document.createElement("div"),
            denial_btn: document.createElement("div"),
          };

          var _this = this;

          // ステータス
          switch (element.status) {
            case this.UNAPPLIED.value:
              this.keepDetailDOM[element.id].status.innerHTML = this.UNAPPLIED.display;
              this.keepDetailDOM[element.id].status.classList.add(
                this.UNAPPLIED.class,
                this.UNAPPLIED.class
              );
              break;
            case this.APPLYING.value:
              this.keepDetailDOM[element.id].status.innerHTML = this.APPLYING.display;
              this.keepDetailDOM[element.id].status.classList.add(
                this.APPLYING.class,
                this.APPLYING.class
              );
              break;
            case this.APPROVAL.value:
              this.keepDetailDOM[element.id].status.innerHTML = this.APPROVAL.display;
              this.keepDetailDOM[element.id].status.classList.add(
                this.APPROVAL.class,
                this.APPROVAL.class
              );
              break;
          }
          //承認ボタン
          this.keepDetailDOM[element.id].approval_btn.innerHTML =
            "<button data-id=" +
            JSON.stringify(element.id) +
            ' class="btn btn-primary grid-approval">承認</button>';
          this.keepDetailDOM[element.id].approval_btn.addEventListener(
            "click",
            function (e) {
              if (e.target.dataset.id) var id = e.target.dataset.id;
              _this.onClickDiscountOK(element);
            }
          );
          //否認ボタン
          this.keepDetailDOM[element.id].denial_btn.innerHTML =
            "<button data-id=" +
            JSON.stringify(element.id) +
            ' class="btn btn-primary grid-denial">否認</button>';
          this.keepDetailDOM[element.id].denial_btn.addEventListener("click", function (e) {
            if (e.target.dataset.id) var id = e.target.dataset.id;
            _this.onClickDiscountNG(element);
          });
        });
        rtnArray = this.creditedDetailList[item.id];
      }

      return rtnArray;
    },
    itemDetailFormatter: function (panel, r, c, cell) {},
    //明細レイアウト
    detailLayout() {
      return [
        {
          header: "選択",
          cells: [
            {
              name: "checked",
              binding: "checked",
              header: "選択",
              isReadOnly: false,
              width: 45,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
          ],
        },
        {
          header: "入金番号",
          cells: [
            {
              name: "credited_no",
              binding: "credited_no",
              header: "入金番号",
              isReadOnly: true,
              width: 120,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
          ],
        },
        {
          header: "入金処理日",
          cells: [
            {
              name: "credited_date",
              binding: "credited_date",
              header: "入金処理日",
              isReadOnly: false,
              width: 100,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
            {
              name: "actual_credited_date",
              binding: "actual_credited_date",
              header: "実入金日",
              isReadOnly: false,
              width: 100,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
          ],
        },
        {
          header: "会計",
          cells: [
            {
              name: "financials_flg",
              binding: "financials_flg",
              header: "会計",
              isReadOnly: true,
              width: 45,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
              dataMap: this.financialsFlgDataMap(),
            },
          ],
        },

        {
          header: "現金",
          cells: [
            {
              name: "cash",
              binding: "cash",
              header: "現金",
              isReadOnly: false,
              width: 120,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
              cssClass: "text-right",
            },
            {
              name: "credit_flg",
              binding: "credit_flg",
              header: "クレジット",
              isReadOnly: false,
              width: 120,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
          ],
        },
        {
          header: "小切手",
          cells: [
            {
              name: "cheque",
              binding: "cheque",
              header: "小切手",
              isReadOnly: false,
              width: 120,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
              cssClass: "text-right",
            },
            {
              name: "receivingdept_id",
              binding: "receivingdept_id",
              header: "受取部門",
              width: 120,
              isReadOnly: false,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
              dataMap: this.departmentDataMap(),
            },
          ],
        },
        {
          header: "振込",
          cells: [
            {
              name: "transfer",
              binding: "transfer",
              header: "振込",
              isReadOnly: false,
              width: 120,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
              cssClass: "text-right",
            },
            {
              name: "transfer_charges",
              binding: "transfer_charges",
              header: "（振込料）",
              isReadOnly: false,
              width: 120,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
              cssClass: "text-right",
            },
          ],
        },
        {
          header: "手形",
          cells: [
            {
              name: "bills",
              binding: "bills",
              header: "手形",
              isReadOnly: false,
              width: 120,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
              cssClass: "text-right",
            },
            {
              name: "bills_date",
              binding: "bills_date",
              header: "手形期日",
              isReadOnly: false,
              width: 120,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
          ],
        },
        {
          header: "銀行名",
          cells: [
            {
              name: "bank_code",
              binding: "bank_name",
              header: "銀行名",
              width: 120,
              minWidth: GRID_COL_MIN_WIDTH,
              // isRequired: false,
              isReadOnly: false,
              // dataMap: this.bankDataMap(),
            },
            {
              name: "branch_code",
              binding: "branch_name",
              header: "支店名",
              width: 120,
              minWidth: GRID_COL_MIN_WIDTH,
              // isRequired: false,
              isReadOnly: false,
              // dataMap: this.branchDataMap(),
            },
          ],
        },
        {
          header: "手形番号",
          cells: [
            {
              name: "bills_no",
              binding: "bills_no",
              header: "手形番号",
              isReadOnly: false,
              width: 120,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
            {
              //裏書手形
              name: "endorsement_flg",
              binding: "endorsement_flg",
              header: "裏書手形",
              isReadOnly: false,
              width: 120,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
          ],
        },
        {
          header: "販売促進費",
          cells: [
            {
              name: "sales_promotion_expenses",
              binding: "sales_promotion_expenses",
              header: "販売促進費",
              isReadOnly: false,
              width: 120,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
              cssClass: "text-right",
            },
            {
              name: "deposits",
              binding: "deposits",
              header: "預り金",
              isReadOnly: false,
              width: 120,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
              cssClass: "text-right",
            },
          ],
        },
        {
          header: "買掛金相殺",
          cells: [
            {
              name: "accounts_payed",
              binding: "accounts_payed",
              header: "買掛金相殺",
              isReadOnly: false,
              width: 120,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
              cssClass: "text-right",
            },
            {
              name: "discount",
              binding: "discount",
              header: "値引き",
              isReadOnly: false,
              width: 120,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
              cssClass: "text-right",
            },
          ],
        },

        {
          header: "値引き申請",
          cells: [
            {
              name: "status",
              header: "値引き申請",
              isReadOnly: true,
              width: 110,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
          ],
        },
        /* 以降、非表示カラム */
        {
          header: "id",
          cells: [
            {
              name: "id",
              binding: "id",
              header: "id",
              width: 0,
              maxWidth: 0,
            },
          ],
        },
        {
          header: "status",
          cells: [
            {
              name: "status",
              binding: "status",
              header: "status",
              width: 0,
              maxWidth: 0,
            },
          ],
        },
        {
          header: "customer_id",
          cells: [
            {
              name: "customer_id",
              binding: "customer_id",
              header: "customer_id",
              width: 0,
              maxWidth: 0,
            },
          ],
        },
        {
          header: "collection_kbn_code",
          cells: [
            {
              name: "collection_kbn_code",
              binding: "collection_kbn_code",
              header: "collection_kbn_code",
              width: 0,
              maxWidth: 0,
            },
          ],
        },
      ];
    },
    //グリッドプルダウン用データマップ作成
    //会計フラグ
    financialsFlgDataMap() {
      var array = [];
      array.push({ financials_flg: "0", display: this.UNPAID });
      array.push({ financials_flg: "1", display: this.DONE });

      return new wjGrid.DataMap(array, "financials_flg", "display");
    },
    //部門
    departmentDataMap() {
      var array = [];
      array.push({ id: "0", department_name: "　" });
      this.departmentlist.forEach((element) => {
        array.push({
          id: element.id,
          department_name: element.department_name,
        });
      });

      return new wjGrid.DataMap(array, "id", "department_name");
    },
    //銀行
    bankDataMap() {
      var bankList = this.banklist.filter((value, index, array) => {
        return array.findIndex((v) => value.bank_name === v.bank_name) === index;
      });

      var array = [];
      array.push({ bank_code: "0", bank_name: "　" });
      bankList.forEach((element) => {
        array.push({
          bank_code: element.bank_code,
          bank_name: element.bank_name,
        });
      });

      return new wjGrid.DataMap(array, "bank_code", "bank_name");
    },
    //銀行支店
    branchDataMap() {
      // var bankList = this.banklist.filter((value, index, array) => {
      //   return value.bank_code === bank_code;
      // });

      var bankList = this.banklist;

      var array = [];
      array.push({ branch_code: "0", branch_name: "　" });
      bankList.forEach((element) => {
        array.push({
          bank_code: element.bank_code,
          branch_code: element.branch_code,
          branch_name: element.branch_name,
        });
      });

      var dataMap = new wjGrid.DataMap(array, "branch_code", "branch_name");
      dataMap.getDisplayValues = (dataItem) => {
        let validBranch = bankList.filter(
          (branch) => branch.bank_code == dataItem.bank_code
        );
        return validBranch.map((branch) => branch.branch_name);
      };

      return dataMap;
    },

    // グリッドに設定適用（itemsSource更新時に設定が消えるもののみ）
    applyGridSettings(grid) {
      // リサイジング設定
      grid.columns.forEach((element) => {
        if (this.gridSetting.deny_resizing_col.indexOf(element.index) >= 0) {
          element.allowResizing = false;
        }
      });
      // 非表示設定
      grid.columns.forEach((element) => {
        if (this.gridSetting.invisible_col.indexOf(element.index) >= 0) {
          element.visible = false;
        }
      });

      // 列ヘッダー結合
      grid.allowMerging = wjGrid.AllowMerging.All;
      for (var i = 0; i < grid.columns.length; i++) {
        if (
          i == 0 ||
          i == 13 ||
          i == 14 ||
          i == 15
          // i == 2 ||
          // i == 4 ||
          // i == 5 ||
          // i == 6 ||
          // i == 7
        ) {
          grid.columnHeaders.setCellData(0, i, grid.columnHeaders.getCellData(1, i));
          grid.columnHeaders.columns[i].allowMerging = true;
        }
      }

      return grid;
    },
    // グリッドに設定適用（itemsSource更新時に設定が消えるもののみ）
    applyDetailSettings(grid) {
      // リサイジング設定
      grid.columns.forEach((element) => {
        if (this.detailGridSetting.deny_resizing_col.indexOf(element.index) >= 0) {
          element.allowResizing = false;
        }
      });
      // 非表示設定
      grid.columns.forEach((element) => {
        if (this.detailGridSetting.invisible_col.indexOf(element.index) >= 0) {
          element.visible = false;
        }
      });

      return grid;
    },
    // グリッドレイアウト
    getLayout() {
      return [
        {
          header: "ステータス",
          cells: [
            // { binding: 'warehouse_name', header: '倉庫名', isReadOnly: true, width: 190, minWidth: GRID_COL_MIN_WIDTH },
            {
              name: "status",
              binding: "id",
              header: "ステータス：",
              isReadOnly: true,
              width: 100,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
          ],
        },
        {
          header: "得意先名",
          cells: [
            {
              name: "customer_name",
              binding: "customer_name",
              header: "得意先名",
              isReadOnly: true,
              width: 220,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
            {
              name: "sight",
              binding: "sight",
              header: "入金サイト・金種・手形サイト",
              isReadOnly: true,
              width: 220,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
          ],
        },
        {
          header: "部門",
          cells: [
            {
              name: "department_name",
              binding: "department_name",
              header: "部門：",
              isReadOnly: true,
              width: 160,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
            {
              name: "charge_staff_name",
              binding: "charge_staff_name",
              header: "担当者：",
              isReadOnly: true,
              width: 160,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
          ],
        },
        {
          header: "請求締め日",
          cells: [
            {
              name: "closing_day",
              binding: "closing_day",
              header: "請求締め日：",
              isReadOnly: true,
              width: 120,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
            {
              name: "expecteddeposit_at",
              binding: "expecteddeposit_at",
              header: "入金予定日：",
              isReadOnly: true,
              width: 120,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
          ],
        },

        {
          header: "売掛残高",
          cells: [
            {
              name: "carryforward_amount",
              binding: "carryforward_amount",
              header: "売掛残高",
              isReadOnly: true,
              width: 140,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
            {
              name: "request_amount",
              binding: "request_amount",
              header: "入金予定額",
              isReadOnly: true,
              width: 140,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
          ],
        },
        {
          header: "現金",
          cells: [
            {
              name: "cash_total",
              binding: "cash_total",
              header: "現金",
              isReadOnly: true,
              width: 140,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
            {
              binding: "",
              header: "　",
              isReadOnly: true,
              width: 140,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
          ],
        },
        {
          header: "小切手",
          cells: [
            {
              name: "cheque_total",
              binding: "cheque_total",
              header: "小切手",
              isReadOnly: true,
              width: 140,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
            {
              binding: "",
              header: "　",
              isReadOnly: true,
              width: 140,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
          ],
        },
        {
          header: "振込",
          cells: [
            {
              name: "transfer_total",
              binding: "transfer_total",
              header: "振込",
              isReadOnly: true,
              width: 140,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
            {
              binding: "",
              header: "　",
              isReadOnly: true,
              width: 140,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
          ],
        },
        {
          header: "手形",
          cells: [
            {
              name: "bills_total",
              binding: "bills_total",
              header: "手形",
              isReadOnly: true,
              width: 140,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
            {
              name: "credited_total",
              binding: "credited_total",
              header: "入金合計",
              isReadOnly: true,
              width: 140,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
          ],
        },
        {
          header: "相殺その他",
          cells: [
            {
              name: "offset_others",
              binding: "",
              header: "　",
              isReadOnly: true,
              width: 140,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
            {
              name: "offset_others",
              binding: "offset_others",
              header: "相殺その他",
              isReadOnly: true,
              width: 140,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
          ],
        },
        {
          header: "予定分",
          cells: [
            {
              name: "miscalculation_proc",
              binding: "miscalculation_proc",
              header: "予定分",
              isReadOnly: true,
              width: 140,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
            {
              name: "balance",
              binding: "balance",
              header: "差し引き残高",
              isReadOnly: true,
              width: 140,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
          ],
        },
        {
          header: "違算申請",
          cells: [
            {
              name: "miscalculation_status",
              header: "違算申請：",
              isReadOnly: true,
              width: 100,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
            {
              name: "discount_status",
              header: "値引申請：",
              isReadOnly: true,
              width: 100,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
          ],
        },
        {
          header: "入金件数",
          cells: [
            {
              name: "count",
              binding: "count",
              header: "入金件数",
              isReadOnly: true,
              width: 120,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
            {
              name: "new_date",
              binding: "new_date",
              header: "最新入金日",
              isReadOnly: true,
              width: 120,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
          ],
        },
        {
          header: "ステータス",
          cells: [
            {
              name: "back",
              binding: "id",
              header: "確定",
              width: 70,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
          ],
        },
        {
          header: "明細追加",
          cells: [
            {
              name: "add",
              binding: "id",
              header: "明細追加",
              width: 70,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
          ],
        },
        {
          header: "明細削除",
          cells: [
            {
              name: "delete",
              binding: "id",
              header: "明細削除",
              width: 70,
              minWidth: GRID_COL_MIN_WIDTH,
              isRequired: false,
            },
          ],
        },

        /* 以降、非表示カラム */
        {
          header: "id",
          cells: [
            {
              name: "id",
              binding: "id",
              header: "id",
              width: 0,
              maxWidth: 0,
            },
          ],
        },
        {
          header: "request_no",
          cells: [
            {
              name: "request_no",
              binding: "request_no",
              header: "request_no",
              width: 0,
              maxWidth: 0,
            },
          ],
        },
        {
          header: "customer_id",
          cells: [
            {
              name: "customer_id",
              binding: "customer_id",
              header: "customer_id",
              width: 0,
              maxWidth: 0,
            },
          ],
        },
        {
          header: "sales_category",
          cells: [
            {
              name: "sales_category",
              binding: "sales_category",
              header: "sales_category",
              width: 0,
              maxWidth: 0,
            },
          ],
        },
      ];
    },
    //一覧絞り込み
    filter(e) {
      this.wjCreditedGrid.collectionView.filter = (deposit) => {
        var showList = true;
        // 全部
        if (
          this.filterList.deposited == this.FLG_ON &&
          this.filterList.carried == this.FLG_ON &&
          this.filterList.miscalculation == this.FLG_ON &&
          this.filterList.notpayment == this.FLG_ON
        ) {
          showList = true;
        }
        // 印刷可＆発行済み＆未確定
        else if (
          this.filterList.deposited == this.FLG_ON &&
          this.filterList.carried == this.FLG_ON &&
          this.filterList.miscalculation == this.FLG_ON &&
          this.filterList.notpayment != this.FLG_ON
        ) {
          if (
            this.DEPOSITED.value != deposit.status &&
            this.CARRIED.value != deposit.status &&
            this.MISCALCULATION.value != deposit.status
          ) {
            showList = false;
          }
        }

        // 印刷可＆発行済み＆締め済
        else if (
          this.filterList.deposited == this.FLG_ON &&
          this.filterList.carried == this.FLG_ON &&
          this.filterList.miscalculation != this.FLG_ON &&
          this.filterList.notpayment == this.FLG_ON
        ) {
          if (
            this.DEPOSITED.value != deposit.status &&
            this.CARRIED.value != deposit.status &&
            this.NOTPAYMENT.value != deposit.status
          ) {
            showList = false;
          }
        }

        // 印刷可＆未確定＆締め済
        else if (
          this.filterList.deposited == this.FLG_ON &&
          this.filterList.carried != this.FLG_ON &&
          this.filterList.miscalculation == this.FLG_ON &&
          this.filterList.notpayment == this.FLG_ON
        ) {
          if (
            this.DEPOSITED.value != deposit.status &&
            this.NOTPAYMENT.value != deposit.status &&
            this.MISCALCULATION.value != deposit.status
          ) {
            showList = false;
          }
        }

        // 発行済み＆未確定＆締め済
        else if (
          this.filterList.deposited != this.FLG_ON &&
          this.filterList.carried == this.FLG_ON &&
          this.filterList.miscalculation == this.FLG_ON &&
          this.filterList.notpayment == this.FLG_ON
        ) {
          if (
            this.NOTPAYMENT.value != deposit.status &&
            this.CARRIED.value != deposit.status &&
            this.MISCALCULATION.value != deposit.status
          ) {
            showList = false;
          }
        }

        // 印刷可＆発行済み
        else if (
          this.filterList.deposited == this.FLG_ON &&
          this.filterList.carried == this.FLG_ON &&
          this.filterList.miscalculation != this.FLG_ON &&
          this.filterList.notpayment != this.FLG_ON
        ) {
          if (
            this.DEPOSITED.value != deposit.status &&
            this.CARRIED.value != deposit.status
          ) {
            showList = false;
          }
        }

        // 印刷可＆未確定
        else if (
          this.filterList.deposited == this.FLG_ON &&
          this.filterList.carried != this.FLG_ON &&
          this.filterList.miscalculation == this.FLG_ON &&
          this.filterList.notpayment != this.FLG_ON
        ) {
          if (
            this.DEPOSITED.value != deposit.status &&
            this.MISCALCULATION.value != deposit.status
          ) {
            showList = false;
          }
        }

        // 印刷可＆締め済
        else if (
          this.filterList.deposited == this.FLG_ON &&
          this.filterList.carried != this.FLG_ON &&
          this.filterList.miscalculation != this.FLG_ON &&
          this.filterList.notpayment == this.FLG_ON
        ) {
          if (
            this.DEPOSITED.value != deposit.status &&
            this.NOTPAYMENT.value != deposit.status
          ) {
            showList = false;
          }
        }

        // 発行済み＆未確定
        else if (
          this.filterList.deposited != this.FLG_ON &&
          this.filterList.carried == this.FLG_ON &&
          this.filterList.miscalculation == this.FLG_ON &&
          this.filterList.notpayment != this.FLG_ON
        ) {
          if (
            this.MISCALCULATION.value != deposit.status &&
            this.CARRIED.value != deposit.status
          ) {
            showList = false;
          }
        }

        // 発行済み＆締め済
        else if (
          this.filterList.deposited != this.FLG_ON &&
          this.filterList.carried == this.FLG_ON &&
          this.filterList.miscalculation != this.FLG_ON &&
          this.filterList.notpayment == this.FLG_ON
        ) {
          if (
            this.CARRIED.value != deposit.status &&
            this.NOTPAYMENT.value != deposit.status
          ) {
            showList = false;
          }
        }

        // 未確定＆締め済
        else if (
          this.filterList.deposited != this.FLG_ON &&
          this.filterList.carried != this.FLG_ON &&
          this.filterList.miscalculation == this.FLG_ON &&
          this.filterList.notpayment == this.FLG_ON
        ) {
          if (
            this.MISCALCULATION.value != deposit.status &&
            this.NOTPAYMENT.value != deposit.status
          ) {
            showList = false;
          }
        }

        //印刷可
        else if (
          this.filterList.deposited == this.FLG_ON &&
          this.DEPOSITED.value != deposit.status
        ) {
          showList = false;
        }

        //発行済み
        else if (
          this.filterList.carried == this.FLG_ON &&
          this.CARRIED.value != deposit.status
        ) {
          showList = false;
        }

        //未確定
        else if (
          this.filterList.miscalculation == this.FLG_ON &&
          this.MISCALCULATION.value != deposit.status
        ) {
          showList = false;
        }

        //締め済
        else if (
          this.filterList.notpayment == this.FLG_ON &&
          this.NOTPAYMENT.value != deposit.status
        ) {
          showList = false;
        }

        return showList;
      };
    },
    // 3桁ずつカンマ区切り
    comma_format: function (val) {
      if (val == undefined || val == "") {
        return 0;
      }
      if (typeof val !== "number") {
        val = parseInt(val);
      }
      return val.toLocaleString();
    },
    dateFormat: function (date) {
      return moment(date).format("YYYY/MM/DD");
    },
    // 以下オートコンプリートの値取得
    initCustomer(sender) {
      this.wjSearchObj.customer = sender;
    },
    initDepartment(sender) {
      this.wjSearchObj.department = sender;
    },
    initStaff(sender) {
      this.wjSearchObj.staff = sender;
    },
    initDepositMonth(sender) {
      this.wjSearchObj.deposit_month = sender;
    },
    initCustomernewInput(sender) {
      this.wjnewInputObj.customer = sender;
    },
    initDepartmentnewInput(sender) {
      this.wjnewInputObj.department = sender;
    },
    initStaffnewInput(sender) {
      this.wjnewInputObj.staff = sender;
    },

    //イベント処理***********************************
    // 新規入力
    newInput: function () {
      this.newInputDialog = true;
    },
    newInputProc: function () {
      var customerId = this.wjnewInputObj.customer.selectedValue;
      if (customerId != null && customerId != 0) {
        this.loading = true;
        axios
          .post("/deposit-list/new-input", { customer_id: customerId })

          .then(
            function (response) {
              this.loading = false;

              if (response.data && typeof response.data != "string") {
                // // 成功(追加行をグリッドに反映)
                // var data = response.data;
                // var creditedList = data.creditedList;
                // this.creditedDetailList = data.creditedList;
                // var gridData = this.wjCreditedGrid.collectionView.sourceCollection;
                // var newCredited = creditedList;
                // newCredited.forEach((element) => {
                //   //行追加
                //   var row = this.rowFormat(element);
                //   gridData.splice(gridData.length, 0, row);
                // });
                // //画面更新
                // this.filter();
                // this.wjCreditedGrid.collectionView.refresh();
                this.search();

                this.wjnewInputObj.customer.text = "";
                this.wjnewInputObj.department.text = "";
                this.wjnewInputObj.staff.text = "";
                this.newInputDialog = false;
              } else if (response.data && typeof response.data == "string") {
                alert(response.data);
              } else {
                // 失敗
                window.onbeforeunload = null;
                location.reload();
              }
            }.bind(this)
          )

          .catch(
            function (error) {
              this.loading = false;
              if (error.response.data.errors) {
                // エラーメッセージ表示
                this.showErrMsg(error.response.data.errors, this.errors);
              } else {
                alert(MSG_ERROR);

                window.onbeforeunload = null;
                location.reload();
              }
            }.bind(this)
          );
      } else {
        alert("得意先名は必須です。");
      }
    },
    //入金情報確定***********************************
    comfirmDeposit: function () {
      //確認メッセージ
      var result = window.confirm("入金情報を確定します。よろしいですか？ ");
      if (result) {
        this.loading = true;
        axios
          .post("/deposit-list/comfirm-deposit", {
            creditedList: this.creditedList,
            creditedDetailList: this.creditedDetailList,
          })

          .then(
            function (response) {
              this.loading = false;
              if (response.data.status === true) {
                // 成功
                this.search();
                // var newCredited = response.data;
                // newCredited.forEach(element => {
                //    this.creditedlist.push(element);
                // });

                this.newInputDialog = false;
              } else if (!response.data.status) {
                alert(response.data.message.replace(/\\n/g, '\n'));
              } else {
                // 失敗
                window.onbeforeunload = null;
                location.reload();
              }
            }.bind(this)
          )

          .catch(
            function (error) {
              if (error.response.data.errors) {
                // エラーメッセージ表示
                this.showErrMsg(error.response.data.errors, this.errors);
              } else {
                alert(MSG_ERROR);

                window.onbeforeunload = null;
                location.reload();
              }
              this.loading = false;
            }.bind(this)
          );
      }
    },
    //違算処理***********************************
    //違算処理ボタン
    onClickMiscalculation(mainRow) {
      //ステイタスが「違算有」の状態で、入金予定額と「入金の合計」が異なる場合、部門の上位担当者の承認処理を実施する。
      if (
        (mainRow.status == this.MISCALCULATION.value ||
          mainRow.status == this.CARRIED.value) &&
        Number(mainRow.credited_total) + Number(mainRow.offset_others) !=
          Number(mainRow.total_sales)
      ) {
        //子明細(子グリッド)に、値引申請中の子明細がないことを確認する。
        var isFeasible = true;
        var arr = this.creditedDetailList[mainRow.id];
        if (arr != null) {
          arr.forEach((detailRow) => {
            if (detailRow.status == 1) {
              alert(
                "未完了の値引申請があります。完了してから再度違算処理を実行してください。"
              );
              isFeasible = false;
            }
          });
        }

        //値引申請がなく続行可能の場合
        if (isFeasible) {
          //アクティブ行を保存してダイアログ表示
          this.activeRow = mainRow;

          //承認情報の改行処理
          if (this.activeRow.miscalculation_auth_info != null) {
            this.activeRow.miscalculation_auth_info = this.activeRow.miscalculation_auth_info.replaceAll(
              ",",
              "\n"
            );
          }
          this.miscalculationDialog = true;
        }
      }
    },

    confirmMiscalculationProc(btnKind) {
      
        var promise = axios
          .post("/deposit-list/confirm-miscalculation", {
            mainRow: this.activeRow,
            detailRows: this.creditedDetailList[this.activeRow.id],
            btnKind: btnKind,
          })

          .then(
            function (response) {
              if (response.data.status) {
                  return response.data.message;
              } else {
                // 失敗
              }
            }.bind(this)
          )
          .catch(
            function (error) {
              if (error.response.data.errors) {
                // エラーメッセージ表示
                this.showErrMsg(error.response.data.errors, this.errors);
              } else {
                alert(MSG_ERROR);
              }
              return false;
            }.bind(this)
          );
      return promise;
    },
    //違算処理ダイアログ内ボタン
    async miscalculationProc(btnKind) {
      var isIncompleteInput = false;
      switch (btnKind) {
        //申請*******
        case this.miscalculationBtnKind.APPLYING:
          //違算理由入力チェック
          if (
            this.activeRow.miscalculation_app_com == null ||
            this.activeRow.miscalculation_app_com == ""
          ) {
            alert("未入力項目を入力してください。");
            isIncompleteInput = true;
          } else {
          }
          break;

        //承認*******
        case this.miscalculationBtnKind.APPROVAL:
          //上長コメント入力チェック

          if (
            this.activeRow.miscalculation_auth_com == null ||
            this.activeRow.miscalculation_auth_com == ""
          ) {
            alert("未入力項目を入力してください。");
            isIncompleteInput = true;
          } else {
          }
          break;
        //否認*******
        case this.miscalculationBtnKind.DENIAL:
          //上長コメント入力チェック
          if (
            this.activeRow.miscalculation_auth_com == null ||
            this.activeRow.miscalculation_auth_com == ""
          ) {
            alert("未入力項目を入力してください。");
            isIncompleteInput = true;
          } else {
          }
          break;
        //取り消し*******
        case this.miscalculationBtnKind.CANCEL:
          break;
      }

      //入力に不備がない場合
      if (!isIncompleteInput) {
        this.loading = true;

        var result = undefined
        if (btnKind == this.miscalculationBtnKind.APPROVAL) {
          result = await this.confirmMiscalculationProc(btnKind)

          if (result == undefined) {
            this.loading = false;
            return
          } else {
            if (!confirm(result)) {
              this.loading = false;
              return
            }
          }
        }

        axios
          .post("/deposit-list/miscalculation", {
            mainRow: this.activeRow,
            detailRows: this.creditedDetailList[this.activeRow.id],
            btnKind: btnKind,
          })

          .then(
            function (response) {
              this.loading = false;
              if (response.data === true) {
                // 成功
                this.miscalculationDialog = false;
                this.search();
              } else if (response.data) {
                alert(response.data);
              } else {
                // 失敗
                window.onbeforeunload = null;
                location.reload();
              }
            }.bind(this)
          )

          .catch(
            function (error) {
              if (error.response.data.errors) {
                // エラーメッセージ表示
                this.showErrMsg(error.response.data.errors, this.errors);
              } else {
                alert(MSG_ERROR);

                window.onbeforeunload = null;
                location.reload();
              }
              this.loading = false;
            }.bind(this)
          );
      }
    },

    //値引承認***************
    onClickDiscountOK: function (detailRow) {
      //ステータスが申請中の場合のみ
      if (detailRow.status == this.APPLYING.value) {
        //確認メッセージ
        var result = window.confirm("値引き申請を承認します。よろしいですか？ ");
        if (result) {
          this.loading = true;
          var mainRow = null;
          this.creditedList.forEach(element => {
            if (mainRow == null) {
              if (element.id == detailRow.credited_id) {
                mainRow = element;
              }
            }
          });

          axios
            .post("/deposit-list/discount", {
              mainRow: mainRow,
              detailRow: detailRow,
              creditedDetailList: this.creditedDetailList,
            })

            .then(
              function (response) {
                this.loading = false;
                if (response.data === true) {
                  // 成功
                  this.search();
                } else if (response.data) {
                  alert(response.data);
                } else {
                  // 失敗
                  window.onbeforeunload = null;
                  location.reload();
                }
              }.bind(this)
            )

            .catch(
              function (error) {
                if (error.response.data.errors) {
                  // エラーメッセージ表示
                  this.showErrMsg(error.response.data.errors, this.errors);
                } else {
                  alert(MSG_ERROR);

                  window.onbeforeunload = null;
                  location.reload();
                }
                this.loading = false;
              }.bind(this)
            );
        }
      }
    },

    //値引否認***************
    onClickDiscountNG: function (detailRow) {
      //ステータスが申請中の場合のみ
      if (detailRow.status == this.APPLYING.value) {
        //確認メッセージ
        var result = window.confirm("値引き申請を否認します。よろしいですか？ ");
        if (result) {
          this.loading = true;
          axios
            .post("/deposit-list/discount-cancel", {
              detailRow: detailRow,
            })

            .then(
              function (response) {
                this.loading = false;
                if (response.data === true) {
                  // 成功
                  this.search();
                } else if (response.data) {
                  alert(response.data);
                } else {
                  // 失敗
                  window.onbeforeunload = null;
                  location.reload();
                }
              }.bind(this)
            )

            .catch(
              function (error) {
                if (error.response.data.errors) {
                  // エラーメッセージ表示
                  this.showErrMsg(error.response.data.errors, this.errors);
                } else {
                  alert(MSG_ERROR);

                  window.onbeforeunload = null;
                  location.reload();
                }
                this.loading = false;
              }.bind(this)
            );
        }
      }
    },

    //明細削除***************
    onClickDelete(mainRow) {
      //繰越済、入金済み、子明細が値引申請中、会計でデータ出力済はだめ
      if (
        mainRow.status == this.DEPOSITED.value ||
        mainRow.status == this.CARRIED.value
      ) {
        alert("繰越済又は入金済みのデータは削除できません。");
      } else {
        var arr = this.creditedDetailList[mainRow.id];
        var deleteIdList = [];
        var isDiscounted = false;
        var isFinished = false;
        if (arr != null) {
          arr.forEach((detailRow) => {
            if (detailRow.checked && detailRow.status == 1) {
              isDiscounted = true;
            } else if (detailRow.checked && detailRow.financials_flg == 1) {
              isFinished = true;
            } else if (detailRow.checked) {
              deleteIdList.push(detailRow.id);
            }
          });
          if (isDiscounted) {
            alert("値引申請中の明細は削除できません。");
          } else if (isFinished) {
            alert("会計データ出力済みの明細は削除できません。");
          } else if (mainRow.miscalculation_status == 1) {
            alert("違算申請中のデータは削除できません。");
          } else if (deleteIdList.length <= 0) {
            alert("削除する子明細を選択してください。");
          } else {
            var result = window.confirm("選択中の子明細を削除します。よろしいですか？ ");
            if (result) {
              this.loading = true;
              axios
                .post("/deposit-list/delete", {
                  mainRow: mainRow,
                  deleteIdList: deleteIdList,
                })
                .then(
                  function (response) {
                    this.loading = false;
                    if (response.data === true) {
                      // 成功
                      this.search();
                    } else if (response.data) {
                      alert(response.data);
                    } else {
                      // 失敗
                      window.onbeforeunload = null;
                      location.reload();
                    }
                  }.bind(this)
                )

                .catch(
                  function (error) {
                    if (error.response.data.errors) {
                      // エラーメッセージ表示
                      this.showErrMsg(error.response.data.errors, this.errors);
                    } else {
                      alert(MSG_ERROR);

                      window.onbeforeunload = null;
                      location.reload();
                    }
                    this.loading = false;
                  }.bind(this)
                );
            }
          }
        }
      }
    },
    //明細追加***************
    onClickAdd(mainRow) {
      //繰越済、入金済みの場合は不可
      if (
        mainRow.status == this.DEPOSITED.value ||
        mainRow.status == this.CARRIED.value
      ) {
        alert("繰越済又は入金済みのデータは追加できません。");
      } else if (mainRow.miscalculation_status == 1) {
        alert("違算申請中のデータは追加できません。");
      } else {
        var result = true;
        // var result = window.confirm("子明細を追加します。よろしいですか？ ");
        if (result) {
          this.loading = true;
          axios
            .post("/deposit-list/add", {
              mainRow: mainRow,
            })
            .then(
              function (response) {
                this.loading = false;
                if (response.data === true) {
                  // 成功
                  this.search(mainRow.id);
                } else if (response.data) {
                  alert(response.data);
                } else {
                  // 失敗
                  window.onbeforeunload = null;
                  location.reload();
                }
              }.bind(this)
            )

            .catch(
              function (error) {
                if (error.response.data.errors) {
                  // エラーメッセージ表示
                  this.showErrMsg(error.response.data.errors, this.errors);
                } else {
                  alert(MSG_ERROR);

                  window.onbeforeunload = null;
                  location.reload();
                }
                this.loading = false;
              }.bind(this)
            );
        }
      }
    },
    //戻す(解除)***************
    onClickBack(mainRow) {
      //繰越済、入金済みの場合は不可
      if (mainRow.status != this.DEPOSITED.value && (mainRow.status != this.CARRIED.value || this.rmUndefinedZero(mainRow.different_amount) == 0)) {
        alert("入金済もしくは繰越済で次期請求に違算が反映されていない明細のみステータスを変更できます。");
      } else if (mainRow.sales_category == 1) {
        alert("いきなり売上の明細は変更できません。");
      } else {
        {
          var confirmMessage = '';
          confirmMessage = mainRow.status == this.DEPOSITED.value ? "ステータスを未入金に戻します。よろしいですか？ " : "ステータスを違算有に戻します。よろしいですか？ " 
          // var result = window.confirm(confirmMessage);
          if (confirm(confirmMessage)) {
            this.loading = true;
            axios
              .post("/deposit-list/back", {
                mainRow: mainRow,
              })
              .then(
                function (response) {
                  this.loading = false;
                  if (response.data === true) {
                    // 成功
                    this.search();
                  } else if (response.data) {
                    alert(response.data);
                  } else {
                    // 失敗
                    window.onbeforeunload = null;
                    location.reload();
                  }
                }.bind(this)
              )

              .catch(
                function (error) {
                  if (error.response.data.errors) {
                    // エラーメッセージ表示
                    this.showErrMsg(error.response.data.errors, this.errors);
                  } else {
                    alert(MSG_ERROR);

                    window.onbeforeunload = null;
                    location.reload();
                  }
                  this.loading = false;
                }.bind(this)
              );
          }
        }
      }
    },
    //振込データ取込***************
    importTransfer: function () {
      this.inputData.list = [];
      this.inputData.fileName = null;
      this.inputData.fileDate = null;
      this.inputData.count = null;
      this.inputData.crc = null;
      this.inputData.csvList = null;
      this.inputData.creditedList = null;
      this.importTransferDialog = true;
      var obj = this.$refs.inputfile;
      if (obj != null) {
        obj.value = "";
      }
    },
    selectedFile: function (e) {
      var crypto = require("crypto");
      var algorithm = "md5";
      var encoding = "base64";
      var file = e.target.files[0];
      var array = [];
      var crc = null;
      var _this = this;

      if (file != null) {
        var r = new FileReader();
        r.onerror = function (err) {
          alert(err);
        };
        r.onload = function () {
          var result = r.result;
          var buf = new Buffer(result);
          var hash = crypto.createHash(algorithm);
          hash.update(buf);
          _this.inputData.crc = hash.digest(encoding);
          _this.loading = true;

          let lines = result.split("\n");
          lines.forEach((element) => {
            let workerData = element.split(",");
            array.push(workerData);
          });

          let formData = new FormData();

          let config = {
            headers: {
              "content-type": "multipart/form-data",
            },
          };

          axios
            .post("/deposit-list/import-file", {
              crc: _this.inputData.crc,
              file: array,
              fileName: file.name,
              fileDate: file.lastModifiedDate,
            })

            .then(
              function (response) {
                _this.loading = false;
                if (response.data && typeof response.data == "object") {
                  // 成功
                  _this.inputData.csvList = response.data.csvList;
                  _this.inputData.creditedList = response.data.creditedList;
                  _this.inputData.fileName = file.name;
                  _this.inputData.fileDate = file.lastModifiedDate.toLocaleDateString();
                  _this.inputData.count = _this.inputData.csvList.length;
                } else if (response.data) {
                  alert(response.data);
                } else {
                  // 失敗
                  window.onbeforeunload = null;
                  location.reload();
                }
              }.bind(this)
            )

            .catch(
              function (error) {
                if (error.response.data.errors) {
                  // エラーメッセージ表示
                  this.showErrMsg(error.response.data.errors, this.errors);
                } else {
                  alert(MSG_ERROR);

                  window.onbeforeunload = null;
                  location.reload();
                }
                this.loading = false;
              }.bind(this)
            );
        };
        r.readAsText(file);
      }
    },
    //←ボタン
    onClickLeft(index) {
      //ラジオボタン
      if (this.inputData.creditedList[this.radioData].radio_button == 0) {
        //同じ摘要の箇所すべてに反映させる
        var customer_name_kana = this.inputData.csvList[index].customer_name_kana;

        for (let i = 0; i < this.inputData.csvList.length; i++) {
          if (
            customer_name_kana == this.inputData.csvList[i].customer_name_kana &&
            this.inputData.csvList[i].select != 2
          ) {
            this.inputData.csvList[i].customer_id = this.inputData.creditedList[
              this.radioData
            ].customer_id;
            this.inputData.csvList[i].customer_name = this.inputData.creditedList[
              this.radioData
            ].customer_name;
            this.inputData.csvList[i].request_no = this.inputData.creditedList[
              this.radioData
            ].request_no;
            this.inputData.csvList[i].request_id = this.inputData.creditedList[
              this.radioData
            ].request_id;
            this.inputData.csvList[i].credited_id = this.inputData.creditedList[
              this.radioData
            ].credited_id;
            this.inputData.csvList[i].total_sales = this.inputData.creditedList[
              this.radioData
            ].total_sales;
            this.inputData.creditedList[this.radioData].radio_button = 1;

            if (
              1000 >=
                Number(this.inputData.csvList[i].total_sales) -
                  Number(this.inputData.csvList[i].amount) &&
              Number(this.inputData.csvList[i].total_sales) -
                Number(this.inputData.csvList[i].amount) >
                0
            ) {
              this.inputData.csvList[i].transfer_amount =
                Number(this.inputData.csvList[i].total_sales) -
                Number(this.inputData.csvList[i].amount);
            } else {
              this.inputData.csvList[i].transfer_amount = 0;
            }

            this.inputData.csvList[i].deposit_difference =
              Number(this.inputData.creditedList[this.radioData].total_sales) -
              Number(this.inputData.csvList[i].amount) -
              Number(this.inputData.csvList[i].transfer_amount);

            this.inputData.csvList[i].select = 0;
            this.inputData.csvList[i].out = 0;
          }
        }
      }
    },
    //→ボタン
    onClickRight(index) {
      this.inputData.creditedList.forEach((element) => {
        if (this.inputData.csvList[index].request_no == element.request_no) {
          element.radio_button = 0;
        }
      });

      //同じ摘要の箇所すべてに反映させる
      var customer_name_kana = this.inputData.csvList[index].customer_name_kana;

      for (let i = 0; i < this.inputData.csvList.length; i++) {
        if (
          customer_name_kana == this.inputData.csvList[i].customer_name_kana &&
          this.inputData.csvList[i].select != 2
        ) {
          this.inputData.csvList[i].customer_id = null;
          this.inputData.csvList[i].customer_name = null;
          this.inputData.csvList[i].request_no = null;
          this.inputData.csvList[i].request_id = null;
          this.inputData.csvList[i].credited_id = null;
          this.inputData.csvList[i].total_sales = null;

          // this.inputData.creditedList[this.radioData].radio_button = 0;

          this.inputData.csvList[i].transfer_amount = null;

          this.inputData.csvList[i].deposit_difference = null;

          this.inputData.csvList[i].select = 1;
          this.inputData.csvList[i].out = 1;
        }
      }
    },
    //除外ボタン
    onClickOut(index) {
      this.inputData.csvList[index].select = 2;
      this.inputData.csvList[index].out = 2;
      this.inputData.csvList[index].customer_name = "除外";
    },
    //戻すボタン
    onClickReturn(index) {
      this.inputData.csvList[index].select = 1;
      this.inputData.csvList[index].out = 1;
      this.inputData.csvList[index].customer_name = null;
    },
    //完了ボタン
    completeImport() {
      this.loading = true;
      axios
        .post("/deposit-list/complete-import", {
          data: this.inputData,
        })

        .then(
          function (response) {
            this.loading = false;
            if (response.data && Array.isArray(response.data)) {
              // 成功(追加行をグリッドに反映)
              var addRows = response.data;
              addRows.forEach((row) => {
                //子グリッド追加
                if (typeof this.creditedDetailList[row.credited_id] !== "undefined") {
                  this.creditedDetailList[row.credited_id].push(row);
                } else {
                  this.$set(this.creditedDetailList, row.credited_id, [row]);
                }
                //親明細に反映
                for (let i = 0; i < this.creditedList.length; i++) {
                  if (this.creditedList[i].id === row.credited_id) {
                    var tmp = this.creditedList[i];
                    tmp.count = Number(tmp.count) + 1;
                    tmp.transfer_total =
                      Number(tmp.transfer_total) +
                      Number(row.transfer) +
                      Number(row.transfer_charges);
                    tmp.credited_total =
                      Number(tmp.credited_total) +
                      Number(row.transfer) +
                      Number(row.transfer_charges);
                    tmp.balance =
                      Number(tmp.balance) -
                      Number(row.transfer) -
                      Number(row.transfer_charges);
                    this.creditedList.splice(i, 1, tmp)[i];
                  }
                }
              });

              //画面更新
              var itemsSource = [];
              var dataLength = 0;

              this.creditedList.forEach((element) => {
                var row = this.rowFormat(element);
                //値セット
                itemsSource.push(row);
                dataLength++;
              });
              this.tableData = dataLength;

              // データセット
              // グリッドのページング設定
              var view = new wjCore.CollectionView(itemsSource, {
                pageSize: 50,
              });

              this.credited = view;
              this.wjCreditedGrid.itemsSource = this.credited;
              this.filter();

              // 設定更新
              this.wjCreditedGrid = this.applyGridSettings(this.wjCreditedGrid);

              // 描画更新
              // this.wjCreditedGrid.collectionView.refresh();
              this.wjCreditedGrid.refresh();
              this.importTransferDialog = false;
              // this.search();
            } else if (response.data) {
              this.importTransferDialog = false;
            } else {
              // 失敗
              window.onbeforeunload = null;
              location.reload();
            }
          }.bind(this)
        )

        .catch(
          function (error) {
            if (error.response.data.errors) {
              // エラーメッセージ表示
              alert(error.response.data.errors);
              this.showErrMsg(error.response.data.errors, this.errors);
            } else {
              alert(MSG_ERROR);

              window.onbeforeunload = null;
              location.reload();
            }
            this.loading = false;
          }.bind(this)
        );
    },
    //入金情報出力
    outputCreditedList() {
      // this.outputDialog = true;
    },
    tableRowClassName({ row, rowIndex }) {
      if (row.select == 0) {
        return "color-row";
      } else {
        return "nomal-row";
      }
    },
    TableRowRadio({ row, rowIndex }) {
      if (row.radio_button != 0) {
        return "color-row";
      } else {
        return "nomal-row";
      }
    },
  },
};
</script>

<style>
/*********************************
    枠サイズ等
**********************************/
.search-body {
  width: 100%;
  background: #ffffff;
  padding: 15px;
  -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, 0.3);
  box-shadow: 0 0 1px 1px rgba(0, 0, 0, 0.3);
}
.result-body {
  width: 100%;
  background: #ffffff;
  margin-top: 30px;
  padding: 15px;
  -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, 0.3);
  box-shadow: 0 0 1px 1px rgba(0, 0, 0, 0.3);
}
.wj-multirow {
  height: 500px;
  margin: 6px 0;
}
.btn-search {
  height: 35px;
  width: 100px;
}

.print-btn {
  background-color: #6200ee;
  color: #fff;
  height: 80%;
  width: 100%;
}
.numeric-cell {
  text-align: right;
}
.input-group {
  width: 100%;
  padding: 10px;
  -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, 0.3);
  box-shadow: 0 0 1px 1px rgba(0, 0, 0, 0.3);
}
/*********************************
    以下wijmo系
**********************************/

.container-fluid .wj-multirow {
  height: 400px;
  margin: 6px 0;
}
.container-fluid .wj-detail {
  padding-left: 225px !important;
}

.wj-header {
  background-color: #43425d !important;
  color: #ffffff !important;
  text-align: center !important;
}
.wj-glyph-plus {
  margin-top: 10px;
}
.wj-glyph-minus {
  margin-top: 10px;
}

/* custom styling for a MultiRow */
.container-fluid .multirow-css .wj-row .wj-cell.wj-record-end:not(.wj-header) {
  border-bottom: 1px solid #000; /* thin black lines between records */
}
/* .container-fluid .multirow-css .wj-row .wj-cell.wj-group-end {
  border-right: 2px solid #00b68c; 
} */
.container-fluid .multirow-css .wj-cell.id {
  color: #c0c0c0;
}
.container-fluid .multirow-css .wj-cell.amount {
  color: #014701;
  font-weight: bold;
}
.container-fluid .multirow-css .wj-cell.email {
  color: #0010c0;
  text-decoration: underline;
}

/* ステータス */
.deposited {
  background: rgb(0, 0, 0);
  color: #fff;
  height: 25px;
  width: 90px;
  position: absolute;
  top: 0px;
  left: 0px;
  margin-top: 18px;
  margin-left: 5px;
  padding: 3px;
}
.carried {
  background: #3103af;
  color: #fff;
  height: 25px;
  width: 90px;
  position: absolute;
  top: 0px;
  left: 0px;
  margin-top: 18px;
  margin-left: 5px;
  padding: 3px;
}
.miscalculation {
  background: #f7c217;
  color: #fff;
  height: 25px;
  width: 90px;
  position: absolute;
  top: 0px;
  left: 0px;
  margin-top: 18px;
  margin-left: 5px;
  padding: 3px;
}
.notpayment {
  background: #f00000;
  color: #fff;
  height: 25px;
  width: 90px;
  position: absolute;
  top: 0px;
  left: 0px;
  margin-top: 18px;
  margin-left: 5px;
  padding: 3px;
}
.status {
  display: block !important;
  width: 100%;
  height: 20px;
  margin-top: 25px;
  margin-left: 10px;

  padding: 0px 0px !important;
}

.grid-btn {
  width: 90%;
  height: 23px !important;
  font-size: 12px;
  text-align: center !important;
  line-height: 12px;
  position: absolute;
  top: 0px;
  left: 0px;
  margin-top: 3px;
  margin-left: 7px;
  padding: 3px;
  background: #3103af;
}
.approval {
  width: 90%;
  height: 22px !important;
  font-size: 12px;
  text-align: center !important;
  position: absolute;
  top: 0px;
  left: 0px;
  margin-top: 5px;
  margin-left: 6px;
  padding-top: 3px;
  background: #3103af;
  color: #fff;
}
.applying {
  width: 90%;
  height: 22px !important;
  font-size: 12px;
  text-align: center !important;
  position: absolute;
  top: 0px;
  left: 0px;
  padding-top: 3px;
  margin-top: 5px;
  margin-left: 6px;
  background: #3103af;
  color: #fff;
}
.unapplied {
  width: 90%;
  height: 22px !important;
  font-size: 12px;
  text-align: center !important;
  position: absolute;
  top: 0px;
  left: 0px;
  margin-top: 5px;
  margin-left: 6px;
  padding-top: 3px;
  background: #3103af;
  color: #fff;
}
.grid-approval {
  width: 42%;
  height: 22px !important;
  font-size: 12px;
  text-align: center !important;
  line-height: 12px;
  position: absolute;
  top: 0px;
  left: 0px;
  margin-top: 32px;
  margin-left: 6px;
  padding: 3px;
  background: #15ff20;
}
.grid-denial {
  width: 42%;
  height: 22px !important;
  font-size: 12px;
  text-align: center !important;
  line-height: 12px;
  position: absolute;
  top: 0px;
  left: 0px;
  margin-top: 32px;
  margin-left: 57px;
  padding: 3px;
  background: #d10000;
}

.grid-delete {
  width: 85%;
  height: 35px !important;
  font-size: 12px;
  text-align: center !important;
  line-height: 12px;
  position: absolute;
  top: 0px;
  left: 0px;
  margin-top: 12px;
  margin-left: 5px;
  padding: 3px;
  background: #d10000;
}

.grid-add {
  width: 85%;
  height: 35px !important;
  font-size: 12px;
  text-align: center !important;
  line-height: 12px;
  position: absolute;
  top: 0px;
  left: 0px;
  margin-top: 12px;
  margin-left: 5px;
  padding: 3px;
  background: #3503e9;
}
.grid-back {
  width: 85%;
  height: 35px !important;
  font-size: 12px;
  text-align: center !important;
  line-height: 12px;
  position: absolute;
  top: 0px;
  left: 0px;
  margin-top: 12px;
  margin-left: 5px;
  padding: 3px;
  background: #3503e9;
}

.grid-date {
  height: 25px;
  width: 145px;
  position: absolute;
  top: 0px;
  left: 0px;
  margin-top: 2px;
  margin-left: 4px;
  padding: 3px;
}

.grid-cash {
  height: 25px;
  width: 130px;
  position: absolute;
  top: 0px;
  left: 0px;
  margin-top: 2px;
  margin-left: 5px;
  padding: 3px;
}

.grid-receivingdept {
  height: 25px;
  width: 130px;
  position: absolute;
  top: 0px;
  left: 0px;
  margin-top: 2px;
  margin-left: 5px;
  padding: 3px;
}

.miscalculation_status {
  width: 90%;
  height: 23px !important;
  font-size: 12px;
  text-align: center !important;
  line-height: 12px;
  position: absolute;
  top: 0px;
  left: 0px;
  margin-top: 3px;
  margin-left: 5px;
  padding-top: 6px;
  color: white;
  background: #3503e9;
}
.discount_approval {
  width: 90%;
  height: 22px !important;
  font-size: 12px;
  text-align: center !important;
  position: absolute;
  top: 0px;
  left: 0px;
  margin-top: 4px;
  margin-left: 5px;
  padding-top: 3px;
  background: #15ff20;
  color: #fff;
}
.discount_applying {
  width: 90%;
  height: 22px !important;
  font-size: 12px;
  text-align: center !important;
  position: absolute;
  top: 0px;
  left: 0px;
  margin-top: 4px;
  margin-left: 5px;
  padding-top: 3px;
  background: #3503e9;
  color: #fff;
}
.discount_unapplied {
  width: 90%;
  height: 22px !important;
  font-size: 12px;
  text-align: center !important;
  position: absolute;
  top: 0px;
  left: 0px;
  margin-top: 4px;
  margin-left: 5px;
  padding-top: 3px;
  background: #3503e9;
  color: #fff;
}

.grid-column {
  font-size: 80%;
}
.grid-column.text-right {
  text-align: right;
}
.btn-blue {
  background: #3503e9;
  color: white;
}
.btn-red {
  background: red;
  color: white;
}
.btn-gray {
  background: gray;
  color: white;
}
.bg-grey {
  background-color: slategrey;
}
.el-table .color-row {
  background: rgb(76, 217, 100);
}
.el-table .normal-row {
  background: rgb(255, 255, 255);
}
.wj-green-color {
  background: #40b5b1 !important;
  color: #43425d !important;
}
.wj-orange-color {
  background: #f1df69 !important;
  color: #43425d !important;
}
</style>
