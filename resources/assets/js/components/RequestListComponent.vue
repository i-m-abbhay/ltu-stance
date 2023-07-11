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

          <div class="col-md-2 col-sm-2">
            <label>請求月</label>
            <wj-input-date
              class="form-control"
              :value="searchParams.request_month"
              :selected-value="searchParams.request_month"
              :initialized="initRequestMonth"
              :isRequired="false"
              selectionMode="Month"
              format="yyyy/MM"
            ></wj-input-date>
          </div>
        </div>
        <div class="row">
          <div class="col-md-2 col-sm-2">
            <label class="control-label">締め日</label>
            <wj-auto-complete
              class="form-control"
              id="acClassBig"
              search-member-path="closingDate"
              display-member-path="closingDate"
              selected-value-path="value"
              :selected-index="-1"
              :initialized="initClosingDate"
              :is-required="false"
              :max-items="closingDateList.length"
              :items-source="closingDateList"
            ></wj-auto-complete>
          </div>

          <div class="col-md-3 col-sm-3">
            <label class="control-label">請求番号</label>
            <div id="textarea">
              <input type="text" class="form-control" v-model="searchParams.request_no" />
            </div>
          </div>
        </div>
        <div class="col-md-12 col-sm-12 text-right">
          <button type="button" class="btn btn-clear" @click="clear()">クリア</button>
          <button type="submit" class="btn btn-search">検索</button>
        </div>
        <!-- <div class="row">
          <div class="form-group">
            <div class="col-sm-offset-5 col-sm-1">
              <button type="submit" class="btn btn-search btn-sm form-control">
                検索
              </button>
            </div>
            <div class="col-sm-1">
              <button
                type="button"
                class="btn btn-clear btn-sm form-control"
                v-on:click="clear()"
              >
                クリア
              </button>
            </div>
          </div>
        </div> -->
        <div class="clearfix"></div>
      </form>
    </div>
    <br />
    <!-- 検索結果グリッド -->
    <div class="col-sm-12 result-body" v-show="isSearched">
      <div class="container-fluid">
        <div class="row">
          <div class="col-sm-4">
            <div class="input-group">
              <el-checkbox
                class="col-md-2 col-xs-12"
                v-model="filterList.printable"
                :true-label="FLG_ON"
                :false-label="FLG_OFF"
                @input="filter($event)"
                >印刷可</el-checkbox
              >
              <el-checkbox
                class="col-md-2 col-xs-12"
                v-model="filterList.issued"
                :true-label="FLG_ON"
                :false-label="FLG_OFF"
                @input="filter($event)"
                >発行済</el-checkbox
              >
              <el-checkbox
                class="col-md-2 col-xs-12"
                v-model="filterList.undecided"
                :true-label="FLG_ON"
                :false-label="FLG_OFF"
                @input="filter($event)"
                >未確定</el-checkbox
              >
              <el-checkbox
                class="col-md-2 col-xs-12"
                v-model="filterList.closed"
                :true-label="FLG_ON"
                :false-label="FLG_OFF"
                @input="filter($event)"
                >締め済</el-checkbox
              >
            </div>
          </div>
          <div class="col-md-8 col-sm-12">
            <div class="box-group">
              <div class="col-sm-2"></div>
              <div class="col-sm-2">未確定数</div>
              <div class="col-sm-2">印刷可数</div>
              <div class="col-sm-2">発行済数</div>
              <div class="col-sm-2">締め済数</div>
              <div class="col-sm-2">合計</div>
            </div>

            <div class="box-group">
              <div class="col-sm-2">締日経過件数</div>
              <div class="col-sm-2">{{ UNDECIDED.count }}</div>
              <div class="col-sm-2">{{ PRINTABLE.count }}</div>
              <div class="col-sm-2">{{ ISSUED.count }}</div>
              <div class="col-sm-2">{{ CLOSED.count }}</div>
              <div class="col-sm-2">
                {{ PRINTABLE.count + UNDECIDED.count + ISSUED.count + CLOSED.count }}
              </div>
            </div>
          </div>

          <div class="col-sm-12">
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
                :cv="requests"
              />
            </div>
            <div v-bind:id="'requestGrid'"></div>
          </div>
        </div>
      </div>
    </div>
    <!-- フッタ -->
    <div class="col-md-12 col-sm-12">
      <br />
    </div>
    <div class="col-md-12 col-sm-12" v-show="isSearched">
      <div class="col-md-3">
        <div class="row">
          <br />
        </div>
        <div class="row" v-show="authclosing == 1">
          <div class="col-md-4 text-right">
            締め処理
            <br />（管理者）
          </div>
          <div class="col-md-4 text-left">
            <button
              type="button"
              class="btn btn-search btn-sm form-control"
              @click="btnConfirmClosing()"
            >
              締め確定
            </button>
          </div>
          <div class="col-md-4 text-left">
            <button
              type="button"
              class="btn btn-search btn-sm form-control"
              style="background-color: red"
              @click="closingCancel()"
            >
              締め解除
            </button>
          </div>
        </div>
      </div>

      <div class="col-md-4 text-left">
        <div class="row">
          <br />
        </div>
        <div class="row">
          <div class="col-md-4 text-right">
            <div style="padding-top: 5px">データ出力</div>
          </div>
          <div class="col-md-4 text-left">
            <button
              type="button"
              class="btn btn-search btn-sm form-control"
              @click="outputSalesDetail()"
            >
              売上明細出力
            </button>
          </div>
          <div class="col-md-4 text-left">
            <button
              type="button"
              class="btn btn-search btn-sm form-control"
              @click="outputRequestList()"
            >
              請求一覧出力
            </button>
          </div>
        </div>
      </div>

      <div class="col-md-4 text-left" v-show="authinvoice == 1">
        <div class="row">
          <div v-bind:class="{ 'has-error': errors.request_day != '' }">
            <div class="col-md-7 text-right">
              <span>請求発行日</span>
            </div>
            <div class="col-md-5 text-left">
              <wj-input-date
                :value="searchParams.request_day"
                :selected-value="searchParams.request_day"
                :initialized="initBillingDate"
                :isRequired="false"
              ></wj-input-date>
              <p class="text-danger">{{ errors.request_day }}</p>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4 text-right">
            <div style="padding-top: 5px">請求書発行</div>
          </div>
          <div class="col-md-3 text-left">
            <button
              type="button"
              class="btn btn-search btn-sm form-control"
              @click="downloadInvoice()"
            >
              印刷
            </button>
          </div>
          <div class="col-md-5 text-left">
            <button
              type="button"
              class="btn btn-search btn-sm form-control"
              @click="print()"
            >
              請求書ダウンロード
            </button>
          </div>
        </div>
      </div>

      <div class="col-md-1 text-right">
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
  </div>
</template>

<script>
/* TODO:app.jsで読み込んでいるので、出来るなら二重インポートしたくない */
import * as wjGrid from "@grapecity/wijmo.grid";
import * as wjCore from "@grapecity/wijmo";
import * as wjPdf from "@grapecity/wijmo.pdf";
import * as wjGridPdf from "@grapecity/wijmo.grid.pdf";
import * as wjMultiRow from "@grapecity/wijmo.grid.multirow";
import { forEach, isSet } from "lodash";

export default {
  data: () => ({
    loading: false,
    isSearched: false,
    FLG_ON: 1,
    FLG_OFF: 0,

    PRINTABLE: { value: "printable", display: "印刷可", count: 0 },
    ISSUED: { value: "issued", display: "発行済", count: 0 },
    UNDECIDED: { value: "undecided", display: "未確定", count: 0 },
    CLOSED: { value: "closed", display: "締め済", count: 0 },

    filterList: {
      printable: 1, //印刷可能
      issued: 0, //発行済み
      undecided: 1, //未確定
      closed: 0, //締め済
    },

    requestList: [],
    tableData: 0,
    urlparam: "",
    queryParam: "",
    lastSearchParam: null,

    report: null,
    pageDocument: null,
    fonts: [
      {
        name: "ＭＳ Ｐゴシック",
        source: "/fonts/ms_gothic/msgothic.ttc",
        postscriptName: "MS-PGothic",
      },
    ],

    keepDOM: {},
    requests: new wjCore.CollectionView(),
    gridLayout: null,
    closingDateList: [
      { closingDate: "全て", value: null },
      { closingDate: "随時", value: 0 },
      { closingDate: "5日", value: 5 },
      { closingDate: "10日", value: 10 },
      { closingDate: "15日", value: 15 },
      { closingDate: "20日", value: 20 },
      { closingDate: "25日", value: 25 },
      { closingDate: "末日", value: 99 },
      { closingDate: "1日", value: 1 },
      { closingDate: "2日", value: 2 },
      { closingDate: "3日", value: 3 },
      { closingDate: "4日", value: 4 },
      { closingDate: "6日", value: 6 },
      { closingDate: "7日", value: 7 },
      { closingDate: "8日", value: 8 },
      { closingDate: "9日", value: 9 },
      { closingDate: "11日", value: 11 },
      { closingDate: "12日", value: 12 },
      { closingDate: "13日", value: 13 },
      { closingDate: "14日", value: 14 },
      { closingDate: "16日", value: 16 },
      { closingDate: "17日", value: 17 },
      { closingDate: "18日", value: 18 },
      { closingDate: "19日", value: 19 },
      { closingDate: "21日", value: 21 },
      { closingDate: "22日", value: 22 },
      { closingDate: "23日", value: 23 },
      { closingDate: "24日", value: 24 },
      { closingDate: "26日", value: 26 },
      { closingDate: "27日", value: 27 },
      { closingDate: "28日", value: 28 },
      { closingDate: "29日", value: 29 },
      { closingDate: "30日", value: 30 },
    ],
    display_btn_args: {},
    // クエリパラメータ復帰時,初回表示にしか使うな（created⇒initialized以降値を変更してもwijmoに反映されたりされなかったり）
    searchParams: {
      customer_id: null,
      department_id: null,
      staff_id: null,
      request_month: null,
      closing_date: null,
      request_no: null,
      request_day: null,
    },
    errors: {
      request_day: "",
    },

    // グリッド設定等
    gridSetting: {
      // リサイジング不可[ チェックボックス　 ]
      deny_resizing_col: [0],
      // 非表示[ ID ]
      invisible_col: [16, 17, 18, 19, 20, 21, 22, 23],
    },
    gridPKCol: 16,
    // 以下,initializedで紐づける変数
    wjRequestGrid: null,
    wjSearchObj: {
      customer: {},
      department: {},
      staff: {},
      request_month: {},
      closing_date: {},
      request_day: {},
    },
  }),
  props: {
    customerlist: Array,
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
    // created(vue)⇒initialized(wijmo)⇒mouted(vue)の順で実行される
    // 検索条件復帰はcreatedで行う。又はinitializedでsender.selectdValueにsearchParamsの値を直接指定する
    // 再検索はmountedでやる必要がある。 ※createdやmountedで値のセットと検索の両方を行うとwijmoのオブジェクトが正しく動かない
    this.queryParam = window.location.search;

    this.gridLayout = this.getGridLayout();
  },
  mounted: function () {
    this.wjRequestGrid = this.createGridCtrl("#requestGrid", []);

    this.changeIdxDepartment(this.wjSearchObj.department);
    this.changeIdxStaff(this.wjSearchObj.staff);
    if (this.queryParam.length > 1) {
      // 検索条件セット
      this.setSearchParams(this.queryParam, this.searchParams);
    } else {
      // 初回の検索条件をセット
      this.setInitSearchParams(this.searchParams, this.initsearchparams);
    }

    this.applyGridSettings(this.wjRequestGrid);

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
    initMultiRow: function (multirow) {
      // 行高さ
      multirow.rows.defaultSize = 30;
      // 行ヘッダ非表示
      multirow.headersVisibility = wjGrid.HeadersVisibility.Column;
      // 設定更新
      multirow = this.applyGridSettings(multirow);
      // セルを押下してもカーソルがあたらないように変更
      // multirow.selectionMode = wjGrid.SelectionMode.None;

      this.wjRequestGrid = multirow;
    },
    createGridCtrl(targetGridDivId, gridItemSource) {
      var gridCtrl = new wjMultiRow.MultiRow(targetGridDivId, {
        itemsSource: gridItemSource,
        layoutDefinition: this.gridLayout,
        headersVisibility: wjGrid.HeadersVisibility.Column,
        showSort: true,
        allowSorting: true,
        keyActionEnter: wjGrid.KeyAction.None,
      });

      (gridCtrl.itemFormatter = function (panel, r, c, cell) {
        var col = panel.columns[c];
        //ヘッダごとの設定
        if (panel.cellType == wjGrid.CellType.ColumnHeader) {
          cell.style.textAlign = "center";
          var _this = this;
          // チェックボックス生成
          if (panel.columns[c].name == "chk") {
            var checkedCount = 0;
            for (var i = 0; i < gridCtrl.rows.length; i++) {
              if (this.rmUndefinedBlank(gridCtrl.rows[i].dataItem) != "") {
                if (gridCtrl.rows[i].dataItem.chk == true) {
                  checkedCount++;
                }
              }
            }
            var checkBox = '<input type="checkbox">';
            if (this.isReadOnly || this.isEdit) {
              checkBox = '<input type="checkbox" disabled="true">';
            }
            cell.innerHTML = checkBox;
            var checkBox = cell.firstChild;
            checkBox.checked = checkedCount > 0;
            checkBox.indeterminate =
              checkedCount > 0 && checkedCount < gridCtrl.rows.length;

            checkBox.addEventListener("click", function (e) {
              gridCtrl.beginUpdate();
              for (var i = 0; i < gridCtrl.rows.length; i++) {
                if (_this.rmUndefinedBlank(gridCtrl.rows[i].dataItem) != "") {
                  var chk = checkBox.checked;
                  gridCtrl.rows[i].dataItem.chk = chk;
                  gridCtrl.setCellData(i, c, checkBox.checked);
                }
              }
              gridCtrl.endUpdate();
            });
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
            case "chk":
              // データ取得
              cell.style.textAlign = "center";
              if (this.rmUndefinedBlank(dataItem) != "") {
                if (dataItem.chk == true) {
                  var box = '<input type="checkbox" checked>';
                } else {
                  var box = '<input type="checkbox">';
                }

                if (this.isReadOnly || this.isEdit) {
                  box = '<input type="checkbox" disabled="true">';
                }
                // var box = '<input type="checkbox">';
                cell.innerHTML = box;
                var checkBox = cell.firstChild;
                checkBox.addEventListener("click", function (e) {
                  dataItem.chk = !dataItem.chk;
                  gridCtrl.collectionView.commitEdit();
                  gridCtrl.refresh();
                });
              }
              // var checkBox = cell.firstChild;
              // checkBox.disabled = false;
              // // チェック時にすぐに編集を確定
              // checkBox.addEventListener('mousedown', function (e) {
              //     // dataItem.chk = !dataItem.chk;
              //     gridCtrl.collectionView.commitEdit();
              // });
              break;
            case "status":
              if (item.recordIndex == 0) {
                cell.appendChild(
                  this.keepDOM[panel.getCellData(r, this.gridPKCol)].status
                );

                cell.style.textAlign = "center";
              } else if (item.recordIndex == 1) {
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

            case "request_no":
              if (item.recordIndex == 0) {
                cell.style.textAlign = "left";
              } else {
                cell.style.textAlign = "left";
              }
              break;

            case "matter_count":
              cell.style.textAlign = "right";
              break;

            case "closing_day":
              if (item.recordIndex == 0) {
                cell.style.textAlign = "left";
              } else {
                cell.style.textAlign = "left";
              }
              break;

            case "lastinvoice_amount":
              cell.style.textAlign = "right";
              break;

            case "offset_amount":
              if (item.recordIndex == 0) {
                cell.style.textAlign = "right";
              } else {
                cell.style.textAlign = "right";
              }
              break;

            case "carryforward_amount":
              cell.style.textAlign = "right";
              break;

            case "current_month_sales":
              if (item.recordIndex == 0) {
                cell.style.textAlign = "right";
              } else {
                cell.style.textAlign = "right";
              }
              break;

            case "display_consumption_tax":
              if (item.recordIndex == 0) {
                cell.style.textAlign = "right";
              } else {
                cell.style.textAlign = "right";
              }
              break;

            case "display_total_sales":
              cell.style.textAlign = "right";
              break;

            case "billing_amount":
              cell.style.textAlign = "right";
              break;

            case "request_day":
              if (item.recordIndex == 0) {
                cell.style.textAlign = "left";
              } else {
                cell.style.textAlign = "left";
              }
              break;

            case "display":
              if (item.recordIndex == 0) {
                cell.appendChild(
                  this.keepDOM[panel.getCellData(r, this.gridPKCol)].display_btn
                );
              } else {
                cell.appendChild(
                  this.keepDOM[panel.getCellData(r, this.gridPKCol)].cancellation_btn
                );
              }
              break;

            case "delete_btn":  // 請求削除
              cell.style.padding = '0px';
              if(dataItem !== undefined){
                var rId1 = 'delete-' + dataItem.request_id;

                var isDisable = true;
                // いきなり売上の場合、ボタンを活性化
                if(dataItem.sales_category == this.FLG_ON){
                  isDisable = false;
                }
                
                var div = document.createElement('div');
                div.classList.add('btn-group', 'status-btn-group');
                div.setAttribute("data-toggle","buttons");

                var btnDelete = document.createElement('button');
                btnDelete.type = 'button';
                btnDelete.id = rId1;
                btnDelete.classList.add('btn', 'btn-delete', 'btn-request-delete');
                btnDelete.innerHTML = '削除';
                btnDelete.disabled = isDisable;

                btnDelete.addEventListener('click', function (e) {
                  this.btnDeleteRequest(dataItem.request_id);
                }.bind(this));

                div.appendChild(btnDelete);

                cell.appendChild(div);
                  
              }

              break;
          }
        }
      }.bind(this)),
        gridCtrl.cellEditEnded.addHandler(
          function (s, e) {
            var currentItem = s.collectionView.currentItem;
            var col = e.panel.columns[e.col];
            switch (col.binding) {
              case "chk":
                for (const key in currentItem) {
                  if (key.indexOf(this.authorityBindingPrefix) === 0) {
                    currentItem[key] = currentItem["chk"];
                  }
                }
                break;
            }
            gridCtrl.collectionView.commitEdit();
          }.bind(this)
        );
      return gridCtrl;
    },
    // いきなり売上削除
    btnDeleteRequest(requestId) {
      if (requestId !== null) {
        if (!confirm('店頭販売データを削除します。よろしいですか？')) {
          return; 
        }
        this.loading = true;

        var params = new URLSearchParams();
        params.append('request_id', this.rmUndefinedZero(requestId));
            
        axios.post('/request-list/delete-request', params)
        .then( function (response) {
          this.loading = false;
          if (response.data) {
            // 成功
            // URLパラメータ作成
              this.urlparam = "?";
              this.urlparam +=
                "customer_id=" +
                encodeURIComponent(
                  this.rmUndefinedBlank(this.wjSearchObj.customer.selectedValue)
                );
              this.urlparam +=
                "&" +
                "staff_id=" +
                encodeURIComponent(
                  this.rmUndefinedBlank(this.wjSearchObj.staff.selectedValue)
                );
              this.urlparam +=
                "&" +
                "department_id=" +
                encodeURIComponent(
                  this.rmUndefinedBlank(this.wjSearchObj.department.selectedValue)
                );
              this.urlparam +=
                "&" +
                "request_month=" +
                encodeURIComponent(
                  this.rmUndefinedBlank(
                    this.wjSearchObj.request_month.text.replace("/", "")
                  )
                );
              this.urlparam +=
                "&" +
                "closing_date=" +
                encodeURIComponent(
                  this.rmUndefinedBlank(this.wjSearchObj.closing_date.selectedValue)
                );
              this.urlparam +=
                "&" +
                "request_no=" +
                encodeURIComponent(this.rmUndefinedBlank(this.searchParams.request_no));
              // 検索条件保持
              this.lastSearchParam = params;

              this.search();
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
      }
    },
    // 検索
    search() {
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
        "request_month",
        this.rmUndefinedBlank(this.wjSearchObj.request_month.text.replace("/", ""))
      );
      params.append(
        "closing_date",
        this.rmUndefinedBlank(this.wjSearchObj.closing_date.selectedValue)
      );
      params.append("request_no", this.rmUndefinedBlank(this.searchParams.request_no));

      axios
        .post("/request-list/search", params)

        .then(
          function (response) {
            if (response.data) {
              // URLパラメータ作成
              this.urlparam = "?";
              this.urlparam +=
                "customer_id=" +
                encodeURIComponent(
                  this.rmUndefinedBlank(this.wjSearchObj.customer.selectedValue)
                );
              this.urlparam +=
                "&" +
                "staff_id=" +
                encodeURIComponent(
                  this.rmUndefinedBlank(this.wjSearchObj.staff.selectedValue)
                );
              this.urlparam +=
                "&" +
                "department_id=" +
                encodeURIComponent(
                  this.rmUndefinedBlank(this.wjSearchObj.department.selectedValue)
                );
              this.urlparam +=
                "&" +
                "request_month=" +
                encodeURIComponent(
                  this.rmUndefinedBlank(
                    this.wjSearchObj.request_month.text.replace("/", "")
                  )
                );
              this.urlparam +=
                "&" +
                "closing_date=" +
                encodeURIComponent(
                  this.rmUndefinedBlank(this.wjSearchObj.closing_date.selectedValue)
                );
              this.urlparam +=
                "&" +
                "request_no=" +
                encodeURIComponent(this.rmUndefinedBlank(this.searchParams.request_no));
              // 検索条件保持
              this.lastSearchParam = params;

              var itemsSource = [];
              var dataCount = 0;

              this.requestList = response.data;

              this.PRINTABLE.count = 0;
              this.ISSUED.count = 0;
              this.UNDECIDED.count = 0;
              this.CLOSED.count = 0;

              this.requestList.forEach((element) => {
                switch (element.status) {
                  case this.PRINTABLE.display:
                    this.PRINTABLE.count += 1;
                    break;
                  case this.ISSUED.display:
                    this.ISSUED.count += 1;
                    break;
                  case this.UNDECIDED.display:
                    this.UNDECIDED.count += 1;
                    break;
                  case this.CLOSED.display:
                    this.CLOSED.count += 1;
                    break;
                }

                // DOM生成
                // itemFormatterでDOM要素が消えないようにする為、行ごとに生成するDOMを保存しておく必要がある
                this.keepDOM[element.id] = {
                  status: document.createElement("div"),
                  display_btn: document.createElement("div"),
                  cancellation_btn: document.createElement("div"),
                  matter_count: document.createElement("span"),
                  lastinvoice_amount: document.createElement("span"),
                  offset_amount: document.createElement("span"),
                  deposit_amount: document.createElement("span"),
                  carryforward_amount: document.createElement("span"),
                  current_month_sales: document.createElement("span"),
                  discount: document.createElement("span"),
                  display_consumption_tax: document.createElement("span"),
                  discount_amount: document.createElement("span"),
                  display_total_sales: document.createElement("span"),
                  billing_amount: document.createElement("span"),
                };

                var _this = this;
                this.display_btn_args[element.id] = {
                  status: element.status,
                  customer_id: element.customer_id,
                  request_id: element.request_id,
                  status_code: element.status_code,
                  request_mon: element.request_mon,
                  sales_category: element.sales_category,
                };

                // 請求状態
                this.keepDOM[element.id].status.innerHTML = element.status;
                var status = "";
                switch (element.status) {
                  case this.PRINTABLE.display:
                    status = this.PRINTABLE.value;
                    break;
                  case this.ISSUED.display:
                    status = this.ISSUED.value;
                    break;
                  case this.UNDECIDED.display:
                    status = this.UNDECIDED.value;
                    break;
                  case this.CLOSED.display:
                    status = this.CLOSED.value;
                    break;
                }

                this.keepDOM[element.id].status.classList.add("printable", status);

                // 表示ボタン
                if (element.sales_category != 1) {
                  this.keepDOM[element.id].display_btn.innerHTML =
                    "<button data-id=" +
                    JSON.stringify(element.id) +
                    ' class="btn btn-primary grid-btn">表示</button>';
                  this.keepDOM[element.id].display_btn.addEventListener(
                    "click",
                    function (e) {
                      if (e.target.dataset.id) {
                        var id = e.target.dataset.id;
                        var args = _this.display_btn_args[id];
                        _this.showInvoice(
                          args.customer_id,
                          args.request_id,
                          args.status_code,
                          args.sales_category,
                          args.request_mon
                        );
                      }
                    }
                  );
                } else {
                  this.keepDOM[element.id].display_btn.innerHTML =
                    '<button class="btn btn-primary grid-btn btn-disabled" disabled>表示</button>';
                }

                //権限がある場合
                if (this.authinvoice == 1) {
                  // 解除ボタン
                  if (status == this.ISSUED.value) {
                    this.keepDOM[element.id].cancellation_btn.innerHTML =
                      "<button data-id=" +
                      JSON.stringify(element.id) +
                      ' class="btn btn-primary grid-btn">解除</button>';
                    this.keepDOM[element.id].cancellation_btn.addEventListener(
                      "click",
                      function (e) {
                        if (e.target.dataset.id) var id = e.target.dataset.id;
                        _this.onClickCancellation(
                          _this.display_btn_args[id].request_id,
                          _this.display_btn_args[id].customer_id
                        );
                      }
                    );
                  } else {
                    this.keepDOM[element.id].cancellation_btn.innerHTML =
                      '<button class="btn btn-primary grid-btn btn-disabled" disabled>解除</button>';
                  }
                } else {
                  // this.keepDOM[element.id].display_btn.innerHTML = "";
                  this.keepDOM[element.id].cancellation_btn.innerHTML = "";
                }

                // 案件数
                var matter_count = this.comma_format(element.matter_count);

                this.keepDOM[element.id].matter_count.innerHTML = matter_count;

                this.keepDOM[element.id].matter_count.classList.add("numeric-cell");

                // 前回請求額
                var lastinvoice_amount = this.comma_format(element.lastinvoice_amount);
                this.keepDOM[
                  element.id
                ].lastinvoice_amount.innerHTML = lastinvoice_amount;
                this.keepDOM[element.id].lastinvoice_amount.classList.add("numeric-cell");

                // 相殺その他
                var offset_amount = this.comma_format(element.offset_amount);
                this.keepDOM[element.id].offset_amount.innerHTML = offset_amount;
                this.keepDOM[element.id].offset_amount.classList.add("numeric-cell");

                // 入金額
                var deposit_amount = this.comma_format(element.deposit_amount);
                this.keepDOM[element.id].deposit_amount.innerHTML = deposit_amount;
                this.keepDOM[element.id].deposit_amount.classList.add("numeric-cell");

                // 繰越額
                var carryforward_amount = this.comma_format(element.carryforward_amount);
                this.keepDOM[
                  element.id
                ].carryforward_amount.innerHTML = carryforward_amount;
                this.keepDOM[element.id].carryforward_amount.classList.add(
                  "numeric-cell"
                );

                // 当月売上高
                var current_month_sales = this.comma_format(element.current_month_sales);
                this.keepDOM[
                  element.id
                ].current_month_sales.innerHTML = current_month_sales;
                this.keepDOM[element.id].current_month_sales.classList.add(
                  "numeric-cell"
                );

                // 内 値引き額
                var discount = this.comma_format(element.discount);
                this.keepDOM[element.id].discount.innerHTML = discount;
                this.keepDOM[element.id].discount.classList.add("numeric-cell");

                // 消費税額
                var display_consumption_tax = this.comma_format(element.display_consumption_tax);
                this.keepDOM[element.id].display_consumption_tax.innerHTML = display_consumption_tax;
                this.keepDOM[element.id].display_consumption_tax.classList.add("numeric-cell");

                // 内 税調整額
                var discount_amount = this.comma_format(element.discount_amount);
                this.keepDOM[element.id].discount_amount.innerHTML = discount_amount;
                this.keepDOM[element.id].discount_amount.classList.add("numeric-cell");

                // 当月売上合計
                var display_total_sales = this.comma_format(
                  element.display_total_sales
                );
                this.keepDOM[
                  element.id
                ].display_total_sales.innerHTML = display_total_sales;
                this.keepDOM[element.id].display_total_sales.classList.add(
                  "numeric-cell"
                );

                // 当月請求金額
                var billing_amount = this.comma_format(element.billing_amount);
                this.keepDOM[element.id].billing_amount.innerHTML = billing_amount;
                this.keepDOM[element.id].billing_amount.classList.add("numeric-cell");

                dataCount++;
                itemsSource.push({
                  // フィルター機能で参照される為quote_request,quote,orderにはtext(未実施等...)をセット
                  // itemFormatterでDOM要素を書き換えてもフィルター機能でヒットするのはitemsSourceにセットした時の値
                  id: element.id,
                  customer_id: element.customer_id,
                  request_id: element.request_id,
                  status_code: element.status_code,
                  status: element.status,
                  request_mon: element.request_mon,
                  department_name: element.department_name,
                  department_id: element.department_id,
                  staff_name: element.staff_name,
                  staff_id: element.staff_id,
                  request_no: element.request_no,
                  customer_name: element.customer_name,
                  matter_count: element.matter_count,
                  closing_day: element.closing_day,
                  closing_code: element.closing_code,
                  sales_category: element.sales_category,
                  shipment_at: element.shipment_at,
                  lastinvoice_amount: element.lastinvoice_amount,
                  offset_amount: element.offset_amount,
                  deposit_amount: element.deposit_amount,
                  carryforward_amount: element.carryforward_amount,
                  current_month_sales: element.current_month_sales,
                  discount: element.discount,
                  consumption_tax: element.consumption_tax,
                  display_consumption_tax: element.display_consumption_tax,
                  discount_amount: element.discount_amount,
                  current_month_sales_total: element.current_month_sales_total,
                  display_total_sales: element.display_total_sales,
                  billing_amount: element.billing_amount,
                  request_day: element.request_day,
                  request_user: element.request_user,
                  request_user_name: element.request_user_name,
                  request_s_day: element.request_s_day,
                  request_e_day: element.request_e_day,
                });
              });
              // データセット
              // グリッドのページング設定
              var view = new wjCore.CollectionView(itemsSource, {
                pageSize: 50,
              });
              this.requests = view;
              this.wjRequestGrid.itemsSource = this.requests;

              this.filter();
              this.tableData = dataCount;

              // 設定更新
              this.wjRequestGrid = this.applyGridSettings(this.wjRequestGrid);
              // 描画更新
              this.wjRequestGrid.refresh();
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

    // 検索条件クリア(searchParamsの値を変更しても1回目しかリセットが反応しない為wijmoの値を変更する)
    clear() {
      // this.searchParams = this.initParams;
      var wjSearchObj = this.wjSearchObj;
      Object.keys(wjSearchObj).forEach(function (key) {
        wjSearchObj[key].selectedValue = null;
        wjSearchObj[key].value = null;
        wjSearchObj[key].text = null;
      });
      this.searchParams.request_no = null;
    },
    //グリッドレイアウト
    getGridLayout() {
      return [
        {
          cells: [
            {
              name: "chk",
              binding: "chk",
              header: " ",
              cssClass: "chkColumn",
              width: 40,
              isReadOnly: true,
              allowMerging: false,
            },
          ],
        },
        {
          header: "請求状態／計上月",
          cells: [
            {
              name: "status",
              binding: "status",
              header: "請求状態",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 90,
              isReadOnly: true,
              visible: false,
            },
            {
              name: "request_mon",
              binding: "request_mon",
              header: "計上月",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 90,
              isReadOnly: true,
            },
          ],
        },
        {
          header: "部門名／担当者",
          cells: [
            {
              name: "department_name",
              binding: "department_name",
              header: "部門名",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 140,
              isReadOnly: true,
            },
            {
              name: "staff_name",
              binding: "staff_name",
              header: "担当者",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 140,
              isReadOnly: true,
            },
          ],
        },
        {
          header: "請求書番号/得意先名",
          cells: [
            {
              name: "request_no",
              binding: "request_no",
              header: "請求書番号",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 170,
              isReadOnly: true,
            },
            {
              name: "customer_name",
              binding: "customer_name",
              header: "得意先名",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 170,
              isReadOnly: true,
            },
          ],
        },
        {
          header: "案件数",
          cells: [
            {
              name: "matter_count",
              binding: "matter_count",
              header: "案件数",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 60,
              isReadOnly: true,
            },
          ],
        },
        {
          header: "得意先締め日/請求発送予定",
          cells: [
            {
              name: "closing_day",
              binding: "closing_day",
              header: "得意先締め日",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 100,
              isReadOnly: true,
            },
            {
              name: "shipment_at",
              binding: "shipment_at",
              header: "請求発送予定",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 100,
              isReadOnly: true,
            },
          ],
        },
        {
          header: "前回請求額",
          cells: [
            {
              name: "lastinvoice_amount",
              binding: "lastinvoice_amount",
              header: "前回請求額",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 100,
              isReadOnly: true,
            },
          ],
        },
        {
          header: "相殺その他/入金額",
          cells: [
            {
              name: "offset_amount",
              binding: "offset_amount",
              header: "相殺その他",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 100,
              isReadOnly: true,
            },
            {
              name: "deposit_amount",
              binding: "deposit_amount",
              header: "入金額",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 100,
              isReadOnly: true,
            },
          ],
        },
        {
          header: "繰越額",
          cells: [
            {
              name: "carryforward_amount",
              binding: "carryforward_amount",
              header: "繰越額",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 110,
              isReadOnly: true,
            },
          ],
        },
        {
          header: "当月売上高/（内 値引き額）",
          cells: [
            {
              name: "current_month_sales",
              binding: "current_month_sales",
              header: "当月売上高",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 110,
              isReadOnly: true,
            },
            {
              name: "discount",
              binding: "discount",
              header: "（内 値引き額）",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 110,
              isReadOnly: true,
            },
          ],
        },
        {
          header: "消費税額/（内 税調整額）",
          cells: [
            {
              name: "display_consumption_tax",
              binding: "display_consumption_tax",
              header: "消費税額",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 110,
              isReadOnly: true,
            },
            {
              name: "discount_amount",
              binding: "discount_amount",
              header: "（内 税調整額）",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 110,
              isReadOnly: true,
            },
          ],
        },
        {
          header: "当月売上合計",
          cells: [
            {
              name: "display_total_sales",
              binding: "display_total_sales",
              header: "当月売上合計",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 110,
              isReadOnly: true,
            },
          ],
        },
        {
          header: "当月請求金額",
          cells: [
            {
              name: "billing_amount",
              binding: "billing_amount",
              header: "当月請求金額",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 110,
              isReadOnly: true,
            },
          ],
        },
        {
          header: "明細表示/発行解除",
          cells: [
            {
              name: "display",
              header: "明細表示",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 90,
              isReadOnly: true,
            },
            {
              name: "cancellation",
              header: "発行解除",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 90,
              isReadOnly: true,
            },
          ],
        },
        {
          header: "請求発行日/請求発行者",
          cells: [
            {
              name: "request_day",
              binding: "request_day",
              header: "請求発行日",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 115,
              isReadOnly: true,
            },
            {
              name: "request_user_name",
              binding: "request_user_name",
              header: "請求発行者",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 115,
              isReadOnly: true,
            },
          ],
        },
        {
          header: "削除",
          cells: [
            {
              name: "delete_btn",
              // binding: "request_day",
              header: "削除",
              minWidth: GRID_COL_MIN_WIDTH,
              width: 70,
              isReadOnly: true,
            },
          ],
        },
        // 以下非表示
        {
          header: "ID",
          cells: [{ binding: "id", header: "ID" }],
        },
        {
          header: "customer_id",
          cells: [{ binding: "customer_id", header: "customer_id" }],
        },
        {
          header: "request_id",
          cells: [{ binding: "request_id", header: "request_id" }],
        },
        {
          header: "status_code",
          cells: [{ binding: "status_code", header: "status_code" }],
        },
        {
          header: "department_id",
          cells: [{ binding: "department_id", header: "department_id" }],
        },
        {
          header: "staff_id",
          cells: [{ binding: "staff_id", header: "staff_id" }],
        },
        {
          header: "closing_code",
          cells: [{ binding: "closing_code", header: "closing_code" }],
        },
        {
          header: "sales_category",
          cells: [{ binding: "sales_category", header: "sales_category" }],
        },
      ];
    },
    applyGridSettings(grid) {
      // // リサイジング設定
      // grid.columns.forEach((element) => {
      //   if (this.gridSetting.deny_resizing_col.indexOf(element.index) >= 0) {
      //     element.allowResizing = false;
      //   }
      // });
      // 非表示設定
      grid.columns.forEach((element) => {
        if (this.gridSetting.invisible_col.indexOf(element.index) >= 0) {
          element.visible = false;
        }
      });

      return grid;
    },

    filter(e) {
      this.wjRequestGrid.collectionView.filter = (request) => {
        var showList = true;
        // 全部
        if (
          this.filterList.printable == this.FLG_ON &&
          this.filterList.issued == this.FLG_ON &&
          this.filterList.undecided == this.FLG_ON &&
          this.filterList.closed == this.FLG_ON
        ) {
          showList = true;
        }
        // 印刷可＆発行済み＆未確定
        else if (
          this.filterList.printable == this.FLG_ON &&
          this.filterList.issued == this.FLG_ON &&
          this.filterList.undecided == this.FLG_ON &&
          this.filterList.closed != this.FLG_ON
        ) {
          if (
            this.PRINTABLE.display != request.status &&
            this.ISSUED.display != request.status &&
            this.UNDECIDED.display != request.status
          ) {
            showList = false;
          }
        }

        // 印刷可＆発行済み＆締め済
        else if (
          this.filterList.printable == this.FLG_ON &&
          this.filterList.issued == this.FLG_ON &&
          this.filterList.undecided != this.FLG_ON &&
          this.filterList.closed == this.FLG_ON
        ) {
          if (
            this.PRINTABLE.display != request.status &&
            this.ISSUED.display != request.status &&
            this.CLOSED.display != request.status
          ) {
            showList = false;
          }
        }

        // 印刷可＆未確定＆締め済
        else if (
          this.filterList.printable == this.FLG_ON &&
          this.filterList.issued != this.FLG_ON &&
          this.filterList.undecided == this.FLG_ON &&
          this.filterList.closed == this.FLG_ON
        ) {
          if (
            this.PRINTABLE.display != request.status &&
            this.CLOSED.display != request.status &&
            this.UNDECIDED.display != request.status
          ) {
            showList = false;
          }
        }

        // 発行済み＆未確定＆締め済
        else if (
          this.filterList.printable != this.FLG_ON &&
          this.filterList.issued == this.FLG_ON &&
          this.filterList.undecided == this.FLG_ON &&
          this.filterList.closed == this.FLG_ON
        ) {
          if (
            this.CLOSED.display != request.status &&
            this.ISSUED.display != request.status &&
            this.UNDECIDED.display != request.status
          ) {
            showList = false;
          }
        }

        // 印刷可＆発行済み
        else if (
          this.filterList.printable == this.FLG_ON &&
          this.filterList.issued == this.FLG_ON &&
          this.filterList.undecided != this.FLG_ON &&
          this.filterList.closed != this.FLG_ON
        ) {
          if (
            this.PRINTABLE.display != request.status &&
            this.ISSUED.display != request.status
          ) {
            showList = false;
          }
        }

        // 印刷可＆未確定
        else if (
          this.filterList.printable == this.FLG_ON &&
          this.filterList.issued != this.FLG_ON &&
          this.filterList.undecided == this.FLG_ON &&
          this.filterList.closed != this.FLG_ON
        ) {
          if (
            this.PRINTABLE.display != request.status &&
            this.UNDECIDED.display != request.status
          ) {
            showList = false;
          }
        }

        // 印刷可＆締め済
        else if (
          this.filterList.printable == this.FLG_ON &&
          this.filterList.issued != this.FLG_ON &&
          this.filterList.undecided != this.FLG_ON &&
          this.filterList.closed == this.FLG_ON
        ) {
          if (
            this.PRINTABLE.display != request.status &&
            this.CLOSED.display != request.status
          ) {
            showList = false;
          }
        }

        // 発行済み＆未確定
        else if (
          this.filterList.printable != this.FLG_ON &&
          this.filterList.issued == this.FLG_ON &&
          this.filterList.undecided == this.FLG_ON &&
          this.filterList.closed != this.FLG_ON
        ) {
          if (
            this.UNDECIDED.display != request.status &&
            this.ISSUED.display != request.status
          ) {
            showList = false;
          }
        }

        // 発行済み＆締め済
        else if (
          this.filterList.printable != this.FLG_ON &&
          this.filterList.issued == this.FLG_ON &&
          this.filterList.undecided != this.FLG_ON &&
          this.filterList.closed == this.FLG_ON
        ) {
          if (
            this.ISSUED.display != request.status &&
            this.CLOSED.display != request.status
          ) {
            showList = false;
          }
        }

        // 未確定＆締め済
        else if (
          this.filterList.printable != this.FLG_ON &&
          this.filterList.issued != this.FLG_ON &&
          this.filterList.undecided == this.FLG_ON &&
          this.filterList.closed == this.FLG_ON
        ) {
          if (
            this.UNDECIDED.display != request.status &&
            this.CLOSED.display != request.status
          ) {
            showList = false;
          }
        }

        //印刷可
        else if (
          this.filterList.printable == this.FLG_ON &&
          this.PRINTABLE.display != request.status
        ) {
          showList = false;
        }

        //発行済み
        else if (
          this.filterList.issued == this.FLG_ON &&
          this.ISSUED.display != request.status
        ) {
          showList = false;
        }

        //未確定
        else if (
          this.filterList.undecided == this.FLG_ON &&
          this.UNDECIDED.display != request.status
        ) {
          showList = false;
        }

        //締め済
        else if (
          this.filterList.closed == this.FLG_ON &&
          this.CLOSED.display != request.status
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
      val = parseInt(val);
      return val.toLocaleString();
    },
    /* 以下オートコンプリード設定 */
    initCustomer(sender) {
      this.wjSearchObj.customer = sender;
    },
    initDepartment(sender) {
      this.wjSearchObj.department = sender;
    },
    initStaff(sender) {
      this.wjSearchObj.staff = sender;
    },
    initRequestMonth(sender) {
      this.wjSearchObj.request_month = sender;
    },
    initClosingDate(sender) {
      this.wjSearchObj.closing_date = sender;
    },
    initBillingDate(sender) {
      this.wjSearchObj.request_day = sender;
    },

    //グリッドの解除ボタン
    onClickCancellation(request_id, customer_id) {
      this.loading = true;
      axios
        .post("/request-list/cancellation", {
          request_id: request_id,
          customer_id: customer_id,
        })

        .then(
          function (response) {
            if (response.data === true) {
              this.search();
            } else if (response.data != false) {
              alert(response.data);
            }
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
    /**
     * 保存ボタンクリック
     */
    async btnConfirmClosing(){
        var message = await this.confirmClosing();
        if(message !== undefined){
            this.closing(message);
        }else{
            alert(MSG_ERROR)
        }
    },

    // 締め確定チェック
    confirmClosing() {
      this.loading = true;
      //パラメータ作成
      var items = [];
      var chkCount = 0;
      for (var i = 0; i < this.wjRequestGrid.rows.length; i++) {
        //2行で1レコードなので奇数行スキップ
        if (i % 2 == 1) {
          continue;
        }

        //チェック行
        var chk = this.wjRequestGrid.rows[i].dataItem.chk;
        if (
          typeof chk !== "undefined" &&
          chk &&
          this.wjRequestGrid.rows[i].dataItem.status == this.ISSUED.display
        ) {
          var customer_id = this.wjRequestGrid.rows[i].dataItem.customer_id;
          var customer_name = this.wjRequestGrid.rows[i].dataItem.customer_name;
          var request_id = this.wjRequestGrid.rows[i].dataItem.request_id;
          var department_id = this.wjRequestGrid.rows[i].dataItem.department_id;
          var department_name = this.wjRequestGrid.rows[i].dataItem.department_name;
          var staff_id = this.wjRequestGrid.rows[i].dataItem.staff_id;
          var staff_name = this.wjRequestGrid.rows[i].dataItem.staff_name;
          var closing_day = this.wjRequestGrid.rows[i].dataItem.closing_day;
          var closing_code = this.wjRequestGrid.rows[i].dataItem.closing_code;
          var sales_category = this.wjRequestGrid.rows[i].dataItem.sales_category;
          var request_mon = this.wjRequestGrid.rows[i].dataItem.request_mon;

          items.push({
            customer_id: customer_id,
            request_id: request_id,
            customer_name: customer_name,
            department_id: department_id,
            department_name: department_name,
            staff_id: staff_id,
            staff_name: staff_name,
            closing_day: closing_day,
            closing_code: closing_code,
            sales_category: sales_category,
            request_mon: request_mon,
          });
          chkCount++;
        }
      }

      //チェックなし
      if (chkCount <= 0) {
        this.loading = false;
        return MSG_ERROR_NO_SELECT;
      }

      var params = new URLSearchParams();

      params.append('requestList', JSON.stringify(items));

      var promise = axios
        .post("/request-list/confirm-closing", params)
          .then( function (response) {
              if (response.data) {
                  return response.data.message;
              }
          }.bind(this))
          .catch(function (error) {
          }.bind(this))
          .finally(function () {
              this.loading = false;
          }.bind(this));
      return promise;
    },
    //締め確定
    closing(message) {
      //パラメータ作成
      var items = [];
      var chkCount = 0;
      for (var i = 0; i < this.wjRequestGrid.rows.length; i++) {
        //2行で1レコードなので奇数行スキップ
        if (i % 2 == 1) {
          continue;
        }

        //チェック行
        var chk = this.wjRequestGrid.rows[i].dataItem.chk;
        if (
          typeof chk !== "undefined" &&
          chk &&
          this.wjRequestGrid.rows[i].dataItem.status == this.ISSUED.display
        ) {
          var customer_id = this.wjRequestGrid.rows[i].dataItem.customer_id;
          var customer_name = this.wjRequestGrid.rows[i].dataItem.customer_name;
          var request_id = this.wjRequestGrid.rows[i].dataItem.request_id;
          var department_id = this.wjRequestGrid.rows[i].dataItem.department_id;
          var department_name = this.wjRequestGrid.rows[i].dataItem.department_name;
          var staff_id = this.wjRequestGrid.rows[i].dataItem.staff_id;
          var staff_name = this.wjRequestGrid.rows[i].dataItem.staff_name;
          var closing_day = this.wjRequestGrid.rows[i].dataItem.closing_day;
          var closing_code = this.wjRequestGrid.rows[i].dataItem.closing_code;
          var sales_category = this.wjRequestGrid.rows[i].dataItem.sales_category;
          var request_mon = this.wjRequestGrid.rows[i].dataItem.request_mon;

          items.push({
            customer_id: customer_id,
            request_id: request_id,
            customer_name: customer_name,
            department_id: department_id,
            department_name: department_name,
            staff_id: staff_id,
            staff_name: staff_name,
            closing_day: closing_day,
            closing_code: closing_code,
            sales_category: sales_category,
            request_mon: request_mon,
          });
          chkCount++;
        }
      }

      //チェックなし
      if (chkCount <= 0) {
        return;
      }
      
      if (this.rmUndefinedBlank(message) != '') {
        alert(message.replace(/\\n/g, '\n'));
        return;
      } else {
        //確認メッセージ
        var result = window.confirm("締め確定処理を行います。よろしいですか？");
        if (!result) {
          return;
        }
      }

      

      this.loading = true;
      axios
        .post("/request-list/closing", { data: items })

        .then(
          function (response) {
            if (response.data === true) {
              this.search();
            } else if (response.data) {
              alert(response.data);
            }
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

    //締め解除
    closingCancel() {
      //パラメータ作成
      var items = [];
      var chkCount = 0;
      for (var i = 0; i < this.wjRequestGrid.rows.length; i++) {
        //2行で1レコードなので奇数行スキップ
        if (i % 2 == 1) {
          continue;
        }

        //チェック行
        var chk = this.wjRequestGrid.rows[i].dataItem.chk;
        if (typeof chk !== "undefined" && chk) {
          if (this.wjRequestGrid.rows[i].dataItem.status != this.CLOSED.display) {
            alert("選択したデータは締め済ではありません。");
            return;
          }

          var customer_id = this.wjRequestGrid.rows[i].dataItem.customer_id;
          var customer_name = this.wjRequestGrid.rows[i].dataItem.customer_name;
          var request_mon = this.wjRequestGrid.rows[i].dataItem.request_mon;
          var request_id = this.wjRequestGrid.rows[i].dataItem.request_id;
          var sales_category = this.wjRequestGrid.rows[i].dataItem.sales_category;
          items.push({
            customer_id: customer_id,
            customer_name: customer_name,
            request_mon: request_mon,
            request_id: request_id,
            sales_category: sales_category,
          });
          chkCount++;
        }
      }

      //チェックなし
      if (chkCount <= 0) {
        return;
      }

      //締め解除は1件ずつ
      if (chkCount > 1) {
        alert("締め解除は、1件ずつ実施してください。");
        return;
      }

      //確認メッセージ
      var result = window.confirm("締め解除処理を行います。よろしいですか？");
      if (!result) {
        return;
      }

      this.loading = true;
      axios
        .post("/request-list/closing-cancel", { data: items })

        .then(
          function (response) {
            var result = response.data;
            if (result === true) {
              this.search();
            } else if (result !== false) {
              if(this.rmUndefinedBlank(result) !== ''){
                alert(result.replace(/\\n/g, '\n'));
              } else {
                alert(MSG_ERROR);
              }
            }
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

    //請求一覧出力
    outputRequestList: function () {
      this.loading = true;
      var grid = this.wjRequestGrid;
      grid.columns[0].visible = false;
      var doc = new wjPdf.PdfDocument({
        compress: true,
        ended: function (sender, args) {
          wjPdf.saveBlob(args.blob, "request_list.pdf");
        },
      });

      //pdf設定
      doc.registerFont({
        source: "https://demo.grapecity.com/wijmo/sample/fonts/ipaexg.ttf",
        name: "ipaexg",
        style: "normal",
        weight: "normal",
      });
      doc.setFont(new wjPdf.PdfFont("ipaexg", 10, "normal", "normal"));
      doc.drawText("請求一覧");

      // グリッドを追加
      wjGridPdf.FlexGridPdfConverter.draw(grid, doc, null, null, {
        embeddedFonts: [
          {
            source: "https://demo.grapecity.com/wijmo/sample/fonts/ipaexg.ttf",
            name: "ipaexg",
          },
        ],
        maxPages: 100,
        exportMode: wjGridPdf.ExportMode.All,
        scaleMode: wjGridPdf.ScaleMode.PageWidth,
        documentOptions: {
          pageSettings: {
            layout: wjPdf.PdfPageOrientation.Landscape,
          },
          // header: {
          //   declarative: {
          //     text: "請求一覧",
          //   },
          // },
          // footer: {
          //   declarative: {
          //     text: "\t&[Page] / &[Pages]",
          //   },
          // },
        },
        styles: {
          cellStyle: {
            font: { family: "ipaexg" },
            backgroundColor: "#ffffff",
            borderColor: "#c6c6c6",
          },
          altCellStyle: {
            backgroundColor: "#f9f9f9",
          },
          groupCellStyle: {
            backgroundColor: "#dddddd",
          },
          headerCellStyle: {
            backgroundColor: "#eaeaea",
          },
        },
      });

      doc.end();
      grid.columns[0].visible = true;
      this.loading = false;
    },

    //売上明細出力
    outputSalesDetail() {
      //パラメータ作成
      var items = [];
      var chkCount = 0;
      for (var i = 0; i < this.wjRequestGrid.rows.length; i++) {
        //2行で1レコードなので奇数行スキップ
        if (i % 2 == 1) {
          continue;
        }

        //チェック行
        var chk = this.wjRequestGrid.rows[i].dataItem.chk;
        if (typeof chk !== "undefined" && chk) {
          var customer_id = this.wjRequestGrid.rows[i].dataItem.customer_id;
          var request_id = this.wjRequestGrid.rows[i].dataItem.request_id;
          var request_s_day = this.wjRequestGrid.rows[i].dataItem.request_s_day;
          var request_e_day = this.wjRequestGrid.rows[i].dataItem.request_e_day;
          items.push({ customer_id: customer_id, request_id: request_id, request_s_day: request_s_day, request_e_day: request_e_day });
          chkCount++;
        }
      }

      //チェックなし
      if (chkCount <= 0) {
        return;
      }

      this.loading = true;
      axios
        .post(
          "/request-list/output-sales-detail",
          { data: items },
          { responseType: "blob" }
        )

        .then(
          function (response) {
            // ContentDispositionからファイル名取得
            const contentDisposition = response.headers["content-disposition"];
            const regex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
            const matches = regex.exec(contentDisposition);
            var filename = "";
            if (matches != null && matches[1]) {
              const name = matches[1].replace(/['"]/g, "");
              filename = decodeURI(name);
            } else {
              filename = null;
            }

            const url = URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement("a");
            link.href = url;
            link.setAttribute("download", filename);
            document.body.appendChild(link);
            link.click();
            URL.revokeObjectURL(link);

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
    back() {
      var listUrl = "/";
      window.onbeforeunload = null;
      location.href = listUrl;
    },

    //請求書関連--------------------------------------------------

    //グリッドの表示ボタン
    showInvoice(customer_id, request_id, status, sales_category, request_mon) {
      //請求書表示処理
      this.loading = true;
      axios
        .post("/request-list/show-pdf", {
          data: {
            customer_id: customer_id,
            request_id: request_id,
            status: status,
            request_mon: request_mon,
          },
          request_day: this.wjSearchObj.request_day.text,
        })

        .then(
          function (response) {
            var result = response.data;
            this.loading = true;
            this.report = new GC.ActiveReports.Core.PageReport();
            this.report
              .load("/template/reports/Invoice-v2.rdlx-json")
              .then(
                function () {
                  var url = (this.report._instance.definition.DataSources[0].ConnectionProperties.ConnectString =
                    "jsondata=" + JSON.stringify(result));
                  if (this.rmUndefinedZero(result.sales) == 0) {
                    this.report._instance.definition.FixedPage.Pages.splice(1, this.report._instance.definition.FixedPage.Pages.length-1);
                  }
                  return this.report.load(this.report._instance.definition);
                }.bind(this)
              )
              .then(
                function () {
                  this.report
                    .run()
                    .then(
                      function (pageDocument) {
                        var settings = {
                          pdfVersion: "1.7",
                          fonts: this.fonts,
                        };
                        this.pageDocument = pageDocument;
                        return GC.ActiveReports.PdfExport.exportDocument(
                          pageDocument,
                          settings
                        );
                      }.bind(this)
                    )
                    .then(
                      function (result) {
                        // this.pageDocument.print();
                        var blob = result.data;
                        var fileURL = window.URL.createObjectURL(blob);
                        window.open(fileURL);
                        this.loading = false;
                      }.bind(this)
                    );
                }.bind(this)
              );
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

    confirmPrint(items) {
      this.loading = true

      var promise = axios.post('/request-list/confirm-print', {data: items})
        .then( function (response) {
            if (response.data && !response.data.status) {
                return response.data.message;
            } else {
              return '';
            }
        }.bind(this))
        .catch(function (error) {
        }.bind(this))
        .finally(function () {
            this.loading = false;
        }.bind(this));
        return promise;
    },
    //ダウンロードボタン
    async print() {
      if (
        this.wjSearchObj.request_day.text == null ||
        this.wjSearchObj.request_day.text == ""
      ) {
        this.errors.request_day = "必須です。";
        return;
      } else {
        this.errors.request_day = "";
      }

      //チェックボックスから引数取得
      var items = [];
      var chkCount = 0;
      for (var i = 0; i < this.wjRequestGrid.rows.length; i++) {
        //2行で1レコードなので奇数行スキップ
        if (i % 2 == 1) {
          continue;
        }
        //チェック行
        var chk = this.wjRequestGrid.rows[i].dataItem.chk;
        if (chk) {
          var customer_id = this.wjRequestGrid.rows[i].dataItem.customer_id;
          var request_id = this.wjRequestGrid.rows[i].dataItem.request_id;
          var status = this.wjRequestGrid.rows[i].dataItem.status_code;
          var request_mon = this.wjRequestGrid.rows[i].dataItem.request_mon;
          var sales_category = this.wjRequestGrid.rows[i].dataItem.sales_category;

          //未確定データは不可
          if (status === 0) {
            alert("未確定データが選択されています。");
            return false;
          } else if (sales_category == 1) {
            alert("いきなり売上のデータが選択されています。");
            return false;
          }

          items.push({
            customer_id: customer_id,
            request_id: request_id,
            status: status,
            request_mon: request_mon,
          });
          chkCount++;
        }
      }
      // 入金チェック処理
      if (chkCount > 0) {
        var message = await this.confirmPrint(items);

        if (this.rmUndefinedBlank(message.replace(/\\n/g, '\n')) !== '') {
          if (!confirm(this.rmUndefinedBlank(message.replace(/\\n/g, '\n')))) {
            return;
          }
        }
      }

      //チェックなし
      if (
        chkCount > 0 &&
        window.confirm(
          "請求書を印刷します。よろしいですか？\n請求書番号が未発行の請求書には請求書番号が発行されます。"
        )
      ) {
        this.loading = true;
        axios
          .post("/request-list/print", {
            data: items,
            request_day: this.wjSearchObj.request_day.text,
          })

          .then(
            function (response) {
              if (response.data) {
                if (this.rmUndefinedBlank(response.data.message) != '') {
                  alert(response.data.message.replace(/\\n/g, '\n'));
                }
                var dataList = response.data.invoiceData;
                var index = 0;
                // PDFアップロード
                var params = new FormData();
                dataList.forEach((value) => {
                  var report = new GC.ActiveReports.Core.PageReport();
                  report
                    .load("/template/reports/Invoice-v2.rdlx-json")
                    .then(
                      function () {
                        var url = (report._instance.definition.DataSources[0].ConnectionProperties.ConnectString =
                          "jsondata=" + JSON.stringify(value));
                        if (this.rmUndefinedZero(value.sales) == 0) {
                          report._instance.definition.FixedPage.Pages.splice(1, report._instance.definition.FixedPage.Pages.length-1);
                        }
                        return report.load(report._instance.definition);
                      }.bind(this)
                    )
                    .then(
                      function () {
                        report
                          .run()
                          .then(
                            function (pageDocument) {
                              var settings = {
                                pdfVersion: "1.7",
                                fonts: this.fonts,
                              };
                              this.pageDocument = pageDocument;
                              return GC.ActiveReports.PdfExport.exportDocument(
                                pageDocument,
                                settings
                              );
                            }.bind(this)
                          )
                          .then(
                            function (result) {
                              // this.pageDocument.print();

                              params.append("file[" + index + "]", result.data);
                              params.append(
                                "request_id[" + index + "]",
                                value.request_id
                              );

                              //チェックしたすべてのPDFを作成したらアップロード
                              if (index == dataList.length - 1) {
                                axios
                                  .post("/request-list/upload-pdf", params, {
                                    responseType: "arraybuffer",
                                    headers: {
                                      "Content-Type": "multipart/form-data",
                                      Accept: "application/zip",
                                    },
                                  })
                                  .then(
                                    function (zipResult) {
                                      if (zipResult) {
                                        this.search();
                                        this.loading = false;

                                        var dt = new Date();
                                        var y = dt.getFullYear();
                                        var m = ("00" + (dt.getMonth() + 1)).slice(-2);
                                        var d = ("00" + dt.getDate()).slice(-2);
                                        var today = y + m + d;
                                        this.$http;

                                        let blob = new Blob([zipResult.data], {
                                          type: "application/zip",
                                        });
                                        let link = document.createElement("a");
                                        link.href = window.URL.createObjectURL(blob);
                                        link.download = "請求書_" + today + ".zip";
                                        link.click();
                                      }
                                    }.bind(this)
                                  );
                              } else {
                                index++;
                              }
                            }.bind(this)
                          );
                      }.bind(this)
                    );
                });
              } else {
                this.loading = false;
              }
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
      }
    },

    //印刷ボタン
    async downloadInvoice() {
      if (
        this.wjSearchObj.request_day.text == null ||
        this.wjSearchObj.request_day.text == ""
      ) {
        this.errors.request_day = "必須です。";
        return;
      } else {
        this.errors.request_day = "";
      }

      //チェックボックスから引数取得
      var items = [];
      var chkCount = 0;
      for (var i = 0; i < this.wjRequestGrid.rows.length; i++) {
        //2行で1レコードなので奇数行スキップ
        if (i % 2 == 1) {
          continue;
        }
        //チェック行
        var chk = this.wjRequestGrid.rows[i].dataItem.chk;
        if (chk) {
          var customer_id = this.wjRequestGrid.rows[i].dataItem.customer_id;
          var request_id = this.wjRequestGrid.rows[i].dataItem.request_id;
          var status = this.wjRequestGrid.rows[i].dataItem.status_code;
          var request_mon = this.wjRequestGrid.rows[i].dataItem.request_mon;
          var sales_category = this.wjRequestGrid.rows[i].dataItem.sales_category;

          //未確定データは不可
          if (status === 0) {
            alert("未確定のデータが選択されています。");
            return false;
          } else if (sales_category == 1) {
            alert("いきなり売上のデータが選択されています。");
            return false;
          }
          items.push({
            customer_id: customer_id,
            request_id: request_id,
            status: status,
            request_mon: request_mon,
          });
          chkCount++;
        }
      }
      // 入金チェック処理
      if (chkCount > 0) {
        var message = await this.confirmPrint(items);
        
        console.log(message)

        if (this.rmUndefinedBlank(message.replace(/\\n/g, '\n')) !== '') {
          if (!confirm(this.rmUndefinedBlank(message.replace(/\\n/g, '\n')))) {
            return;
          }
        }
      }

      //チェックなし
      if (
        chkCount > 0 &&
        window.confirm(
          "請求書を印刷します。よろしいですか？\n請求書番号が未発行の請求書には請求書番号が発行されます。"
        )
      ) {
        this.loading = true;
        axios
          .post("/request-list/print", {
            data: items,
            request_day: this.wjSearchObj.request_day.text,
          })

          .then(
            function (response) {
              if (response.data) {
                if (this.rmUndefinedBlank(response.data.message) != '') {
                  alert(response.data.message.replace(/\\n/g, '\n'));
                }
                var dataList = response.data.invoiceData;
                var index = 0;
                // PDFアップロード
                var blobList = [];
                dataList.forEach((value) => {
                  var report = new GC.ActiveReports.Core.PageReport();
                  report
                    .load("/template/reports/Invoice-v2.rdlx-json")
                    .then(
                      function () {
                        var url = (report._instance.definition.DataSources[0].ConnectionProperties.ConnectString =
                          "jsondata=" + JSON.stringify(value));
                        if (this.rmUndefinedZero(value.sales) == 0) {
                          report._instance.definition.FixedPage.Pages.splice(1, report._instance.definition.FixedPage.Pages.length-1);
                        }
                        return report.load(report._instance.definition);
                      }.bind(this)
                    )
                    .then(
                      function () {
                        report
                          .run()
                          .then(
                            function (pageDocument) {
                              var settings = {
                                pdfVersion: "1.7",
                                fonts: this.fonts,
                              };
                              this.pageDocument = pageDocument;
                              return GC.ActiveReports.PdfExport.exportDocument(
                                pageDocument,
                                settings
                              );
                            }.bind(this)
                          )
                          .then(
                            function (result) {
                              blobList.push(result.data);

                              //チェックしたすべてのPDFを作成したら表示
                              if (index == dataList.length - 1) {
                                this.search();
                                blobList.forEach((blob) => {
                                  var fileURL = window.URL.createObjectURL(blob);
                                  window.open(fileURL, "_blank");
                                });
                                this.loading = false;
                              } else {
                                index++;
                              }
                            }.bind(this)
                          );
                      }.bind(this)
                    );
                });
              } else {
                this.loading = false;
              }
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
      }
    },
  },
};
</script>

<style>
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
.input-group {
  width: 100%;
  padding: 15px;
  -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, 0.3);
  box-shadow: 0 0 1px 1px rgba(0, 0, 0, 0.3);
}
.box-group {
  width: 100%;
  padding: 20px;
  -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, 0.3);
  box-shadow: 0 0 1px 1px rgba(0, 0, 0, 0.3);
}
.btn-print {
  background: #6200ee;
  color: #fff;
}

/* 請求状態 */
.printable {
  background: rgb(115, 226, 4);
  color: #fff;
  height: 23px;
  width: 82px;
  position: absolute;
  top: 0px;
  left: 0px;
  margin: 3px;
  padding: 3px;
}
.issued {
  background: #3103af;
  color: #fff;
  height: 23px;
  width: 82px;
  position: absolute;
  top: 0px;
  left: 0px;
  margin: 3px;
  padding: 3px;
}
.undecided {
  background: #f7c217;
  color: #fff;
  height: 23px;
  width: 82px;
  position: absolute;
  top: 0px;
  left: 0px;
  margin: 3px;
  padding: 3px;
}
.closed {
  background: #1f2629;
  color: #fff;
  height: 23px;
  width: 82px;
  position: absolute;
  top: 0px;
  left: 0px;
  margin: 3px;
  padding: 3px;
}
.status {
  display: block !important;
  width: 100%;
  height: 20px;
  text-align: center;
  padding: 0px 0px !important;
}
.btn-request-delete {
  height: 58px;
  width: 70px;
  border-radius: 0px;
}

.container-fluid .wj-multirow {
  height: 380px;
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

.grid-btn {
  width: 100%;
  height: 22px !important;
  font-size: 12px;
  text-align: center !important;
  line-height: 12px;
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
</style>
