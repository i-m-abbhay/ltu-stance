<template>
  <div>
    <loading-component :loading="loading" />
    <div class="search-form search-body col-sm-12 col-md-12" id="searchForm">
      <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
        <div class="col-md-12 col-sm-12">
          <p>検索条件</p>
          <div class="col-md-3 col-sm-3">
            <label class="control-label">商品番号</label>
            <input type="text" class="form-control" v-model="searchParams.product_code" />
            <!-- <wj-auto-complete
              class="form-control"
              id="acProductCode"
              search-member-path="product_code"
              display-member-path="product_code"
              selected-value-path="product_code"
              :selected-index="-1"
              :selected-value="searchParams.product_code"
              :is-required="false"
              :initialized="initProductCode"
              :max-items="productlist.length"
              :items-source="productlist"
            ></wj-auto-complete> -->
          </div>
          <div class="col-md-3 col-sm-3">
            <label class="control-label">商品名</label>
            <input type="text" class="form-control" v-model="searchParams.product_name" />
            <!-- <wj-auto-complete
              class="form-control"
              id="acProductName"
              search-member-path="product_name"
              display-member-path="product_name"
              selected-value-path="product_name"
              :selected-index="-1"
              :selected-value="searchParams.product_name"
              :is-required="false"
              :initialized="initProductName"
              :max-items="productlist.length"
              :items-source="productlist"
            ></wj-auto-complete> -->
          </div>
          <div class="col-md-3 col-sm-3">
            <label class="control-label">型式／規格</label>
            <input type="text" class="form-control" v-model="searchParams.model" />
            <!-- <wj-auto-complete
              class="form-control"
              id="acModel"
              search-member-path="model"
              display-member-path="model"
              selected-value-path="model"
              :selected-index="-1"
              :selected-value="searchParams.model"
              :is-required="false"
              :initialized="initModel"
              :max-items="productlist.length"
              :items-source="productlist"
            ></wj-auto-complete> -->
          </div>
          <div class="col-md-3 col-sm-3">
            <label class="control-label">拠点名</label>
            <wj-auto-complete
              class="form-control"
              id="acMaker"
              search-member-path="base_name"
              display-member-path="base_name"
              selected-value-path="base_name"
              :selected-index="-1"
              :selected-value="searchParams.base_name"
              :is-required="false"
              :initialized="initBaseName"
              :max-items="baselist.length"
              :items-source="baselist"
            ></wj-auto-complete>
          </div>
          <div class="col-md-12 col-sm-12 text-right">
            <button type="button" class="btn btn-clear" @click="clearParams">クリア</button>
            <button type="submit" class="btn btn-search">検索</button>
          </div>
        </div>
        <div class="clearfix"></div>
      </form>
    </div>
    <br />
    <!-- 検索結果グリッド -->
    <div class="col-sm-12 result-body">
      <div class="container-fluid">
        <div class="col-md-12 col-sm-12 col-xs-12">
          <div class="col-md-6 col-sm-6 col-xs-6">
            <div class="row">
              <u>絞り込み機能</u>
            </div>
            <div class="row">
              <el-checkbox true-label="1" false-label="0" v-model="should_order_only">要発注</el-checkbox>
            </div>
          </div>
          <div class="col-md-6 col-sm-6 col-xs-6 text-right">
            <button
              type="button"
              class="btn btn-search"
              v-on:click="process"
              style="margin-right:5px;"
            >登録</button>
            <p class="pull-right search-count" style="text-align:right;">検索結果：{{ tableData }}件</p>
          </div>
        </div>

        <wj-multi-row
          :itemsSource="base"
          :layoutDefinition="layoutDefinition"
          :initialized="initMultiRow"
          :itemFormatter="itemFormatter"
        >
          <wj-flex-grid-detail
            :maxHeight="250"
            :initialized="initFlexGridDetail"
            :rowHasDetail="rwhas"
          >
            <template slot-scope="row">
              <wj-multi-row
                :itemsSource="getDetails(row.item)"
                :layoutDefinition="layoutDetailDefinition"
                :is-read-only="true"
                :initialized="initDetailRow"
                :itemFormatter="itemDetailFormatter"
              ></wj-multi-row>
            </template>
          </wj-flex-grid-detail>
        </wj-multi-row>
        <wj-collection-view-navigator
          headerFormat="{currentPage: n0} / {pageCount: n0}"
          :byPage="true"
          :cv="base"
        />
      </div>
    </div>
  </div>
</template>

<script>
import * as wjCore from "@grapecity/wijmo";
import * as wjInput from "@grapecity/wijmo.input";
import * as wjGrid from "@grapecity/wijmo.grid";
import * as wjMultiRow from "@grapecity/wijmo.grid.multirow";
import * as wjDetail from "@grapecity/wijmo.grid.detail";

export default {
  data: () => ({
    loading: false,
    tableData: 0,
    urlparam: "",
    queryparam: "",
    FLG_ON: 1,
    FLG_OFF: 0,
    ERROR_MSG: MSG_ERROR_NOT_IMAGE,

    base: new wjCore.CollectionView(),
    warehouse: new wjCore.CollectionView(),

    should_order_only: 0,

    warehouseList: [],
    orderLimitList: [],

    layoutDefinition: null,
    layoutDetailDefinition: null,
    keepDOM: {},
    keepDetailDOM: {},
    // グリッド設定用
    gridSetting: {
      // リサイジング不可[ 写真データ ]
      deny_resizing_col: [],
      // 非表示[ ID ]
      invisible_col: [9],
    },
    // 子グリッド設定用
    detailGridSetting: {
      // リサイジング不可[ 印刷 ]
      deny_resizing_col: [],
      // 非表示[ ID ]
      invisible_col: [],
    },
    gridPKCol: 9,
    detailGridPKCol: 5,

    focusImage: null,

    searchParams: {
      product_code: "",
      product_name: "",
      model: "",
      base_name: "",
    },

    wjSearchObj: {
      base_name: {},
    },
    wjBaseGrid: null,
    wjWarehouseGrid: null,
  }),
  props: {
    baselist: Array,
  },
  created: function () {
    Vue.config.devtools = true;
    this.queryparam = window.location.search;
    if (this.queryparam.length > 1) {
      // 検索条件セット
      this.setSearchParams(this.queryparam, this.searchParams);
    } else {
      // 初回の検索条件をセット
      // this.setInitSearchParams(this.searchParams, this.initSearchParams);
    }
    this.layoutDefinition = this.getLayout();
    this.layoutDetailDefinition = this.detailLayout();
  },
  mounted: function () {
    if (this.queryparam.length > 1) {
      this.search();
    }
  },
  watch: {
    // 要発注フィルタ
    should_order_only: {
      immediate: false,
      handler: function () {
        this.checkShouldOrderOnly();
      },
    },
  },
  methods: {
    //表示中のテーブル取得
    dispTable: function checkTable() {
      if (this.should_order_only) {
        return this.orderLimitList.filter(function (row) {
          return row.should_order == 1;
        });
      } else {
        return this.orderLimitList;
      }
    },
    //登録
    process() {
      var table = this.dispTable();

      if (table.length <= 0) {
        return;
      }

      //発注点入力値確認
      for (var i = 0; i < table.length; i++) {
        if (
          document.getElementById("text_order_limit_" + table[i].id) == null
        ) {
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
    checkShouldOrderOnly() {
      this.wjBaseGrid.collectionView.filter = (base) => {
        if (this.should_order_only == 1) {
          return base.should_order == 1;
        } else {
          return base.should_order == 1 || base.should_order == 0;
        }
      };
    },
    // 詳細行データ取得
    getDetails(item) {
      var arr = [];
      var baseId = item.base_id;
      var productId = item.product_id;

      this.warehouseList.forEach(function (warehouse) {
        if (warehouse.product_id == productId && warehouse.base_id == baseId) {
          arr.push(warehouse);
        }
      });
      var itemDetailSource = [];
      arr.forEach((element) => {
        // DOM生成
        // itemFormatterでDOM要素が消えないようにする為、行ごとに生成するDOMを保存しておく必要がある
        this.keepDetailDOM[element.detail_id] = {
          print_btn: document.createElement("a"),
        };

        itemDetailSource.push({
          warehouse_name: element.warehouse_name,
          active_quantity: this.comma_format(element.active_quantity),
          actual_quantity: this.comma_format(element.actual_quantity),
          reserve_quantity: this.comma_format(element.reserve_quantity),
          order_quantity: this.comma_format(element.order_quantity),
        });
      });

      return itemDetailSource;
    },

    // 奇数行の展開ボタンを削除
    rwhas: function (r) {
      return r.recordIndex % 2 == 1;
    },
    initMultiRow: function (multirow) {
      // 行高さ
      multirow.rows.defaultSize = 30;
      // 設定更新
      multirow = this.applyGridSettings(multirow);
      // セルを押下してもカーソルがあたらないように変更
      // multirow.selectionMode = wjGrid.SelectionMode.None;

      multirow.addEventListener(multirow.hostElement, "click", (e) => {
        let ht = multirow.hitTest(e);
        if (ht.cellType === wjGrid.CellType.RowHeader) {
          if (this.wjFlexDetailSetting.isDetailVisible(ht.row)) {
            this.wjFlexDetailSetting.hideDetail(ht.row);
          } else {
            this.wjFlexDetailSetting.showDetail(ht.row);
          }
        }
      });

      this.wjBaseGrid = multirow;
    },
    initReserveGrid: function (multirow) {
      // 行高さ
      multirow.rows.defaultSize = 30;
      // 設定更新
      multirow = this.applyGridSettings(multirow);
      // セルを押下してもカーソルがあたらないように変更
      // multirow.selectionMode = wjGrid.SelectionMode.None;

      multirow.headersVisibility = wjGrid.HeadersVisibility.Column;

      this.wjReserveGrid = multirow;
    },
    initDetailRow: function (detailrow) {
      // 行高さ
      detailrow.rows.defaultSize = 30;
      // 行高さの自動調整
      // 行ヘッダ非表示
      detailrow.headersVisibility = wjGrid.HeadersVisibility.Column;
      // セル押下してもカーソルがあたらないように変更
      // detailrow.selectionMode = wjGrid.SelectionMode.None;

      detailrow = this.applyDetailSettings(detailrow);

      this.wjDetailGrid = detailrow;
    },
    initFlexGridDetail: function (detailSetting) {
      /** 詳細行設定 **************************
       * ExpandSingle => 1レコードのみ展開可能
       * ExpandMulti  => 複数の詳細行を展開可能 ★
       ****************************************/
      detailSetting.detailVisibilityMode =
        wjDetail.DetailVisibilityMode.ExpandMulti;

      this.wjFlexDetailSetting = detailSetting;
    },

    // 検索
    search() {
      this.loading = true;

      var params = new URLSearchParams();

      params.append(
        "product_code",
        this.rmUndefinedBlank(this.searchParams.product_code)
      );
      params.append(
        "product_name",
        this.rmUndefinedBlank(this.searchParams.product_name)
      );
      params.append(
        "model",
        this.rmUndefinedBlank(this.searchParams.model)
      );
      params.append(
        "base_name",
        this.rmUndefinedBlank(this.wjSearchObj.base_name.text)
      );

      axios
        .post("/order-point-list/search", params)

        .then(
          function (response) {
            if (response.data) {
              // URLパラメータ作成
              this.urlparam = "?";
              this.urlparam +=
                "product_code=" +
                encodeURIComponent(
                  this.rmUndefinedBlank(this.searchParams.product_code)
                );
              this.urlparam +=
                "&" +
                "product_name=" +
                encodeURIComponent(
                  this.rmUndefinedBlank(this.searchParams.product_name)
                );
              this.urlparam +=
                "&" +
                "model=" +
                encodeURIComponent(
                  this.rmUndefinedBlank(this.searchParams.model)
                );
              this.urlparam +=
                "&" +
                "base_name=" +
                encodeURIComponent(
                  this.rmUndefinedBlank(this.wjSearchObj.base_name.text)
                );

              var itemsSource = [];

              this.orderLimitList = response.data.orderLimitList;
              this.warehouseList = response.data.warehouseList;
              var dataLength = 0;

              response.data.orderLimitList.forEach((element) => {
                // DOM生成
                // itemFormatterでDOM要素が消えないようにする為、行ごとに生成するDOMを保存しておく必要がある
                this.keepDOM[element.id] = {
                  should_order_mark: document.createElement("div"),
                  should_order: document.createElement("span"),
                  base_name: document.createElement("span"),
                  product_code: document.createElement("span"),
                  unit: document.createElement("span"),
                  order_limit: document.createElement("span"),
                  active_quantity: document.createElement("span"),
                  actual_quantity: document.createElement("span"),
                };

                // レコード間でのセル結合を防ぐため、一旦値を避難
                // 発注用否マーク
                var _this = this;
                if (element.should_order == 1) {
                  this.keepDOM[element.id].should_order_mark.innerHTML =
                    '<div style="width:60px; background: #FF3B30;"><font align="center" color="white">要発注</font></div>';
                } else {
                  this.keepDOM[element.id].should_order_mark.innerHTML = "";
                }

                //発注要否
                this.keepDOM[element.id].should_order.innerHTML =
                  element.should_order;
                // this.keepDOM[element.id].should_order.classList.add(
                //   "string-cell"
                // );

                // 拠点
                this.keepDOM[element.id].base_name.innerHTML =
                  element.base_name;
                this.keepDOM[element.id].base_name.classList.add("string-cell");

                // 商品番号
                this.keepDOM[element.id].product_code.innerHTML =
                  element.product_code;
                this.keepDOM[element.id].product_code.classList.add(
                  "string-cell"
                );

                // 単位
                this.keepDOM[element.id].unit.innerHTML = element.unit;
                this.keepDOM[element.id].unit.classList.add("string-cell");

                // 発注点
                if (element.order_limit != null && element.order_limit > 0) {
                  var order_limit = this.comma_format(element.order_limit);
                  this.keepDOM[element.id].order_limit.innerHTML =
                    '<input type="number" id="text_order_limit_' +
                    element.id +
                    '" class="form-control" min="0" style="text-align:right" value="' +
                    this.orderLimitList[dataLength].order_limit +
                    '">';
                } else {
                  var order_limit = null;
                  this.keepDOM[element.id].order_limit.innerHTML =
                    '<input type="number" id="text_order_limit_' +
                    element.id +
                    '" class="form-control" min="0" style="text-align:right">';
                }
                this.keepDOM[element.id].order_limit.classList.add(
                  "numeric-cell"
                );

                // 有効在庫数
                var active_quantity = this.comma_format(
                  element.active_quantity
                );
                this.keepDOM[
                  element.id
                ].active_quantity.innerHTML = active_quantity;
                if (element.should_order == 1) {
                  this.keepDOM[element.id].active_quantity.classList.add(
                    "should-order-cell"
                  );
                } else {
                  this.keepDOM[element.id].active_quantity.classList.add(
                    "numeric-cell"
                  );
                }

                // 実在庫数
                var actual_quantity = this.comma_format(
                  element.actual_quantity
                );
                this.keepDOM[
                  element.id
                ].actual_quantity.innerHTML = actual_quantity;
                this.keepDOM[element.id].actual_quantity.classList.add(
                  "numeric-cell"
                );

                //値セット
                itemsSource.push({
                  // itemFormatterでDOM要素を書き換えてもフィルター機能でヒットするのはitemsSourceにセットした時の値
                  should_order: element.should_order,
                  base_id: element.base_id,
                  base_name: element.base_name,
                  product_id: element.product_id,
                  product_code: element.product_code,
                  product_name: element.product_name,
                  model: element.model,
                  unit: element.unit,
                  order_limit_id: element.order_limit_id,
                  order_limit: element.order_limit,
                  active_quantity: element.active_quantity,
                  actual_quantity: element.actual_quantity,
                  reserve_quantity: element.reserve_quantity,
                  order_quantity: element.order_quantity,
                  id: element.id,
                });

                dataLength++;
              });

              this.tableData = dataLength;

              // データセット
              // グリッドのページング設定
              var view = new wjCore.CollectionView(itemsSource, {
                pageSize: 5,
              });

              this.base = view;

              this.wjBaseGrid.itemsSource = this.base;

              // 設定更新
              this.wjBaseGrid = this.applyGridSettings(this.wjBaseGrid);

              // 描画更新
              this.wjBaseGrid.refresh();
            }
            //チェックボックス絞り込み
            this.checkShouldOrderOnly();
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

    // 検索条件のクリア
    clearParams: function () {
      // this.searchParams = this.initParams;
      var wjSearchObj = this.wjSearchObj;
      Object.keys(wjSearchObj).forEach(function (key) {
        wjSearchObj[key].selectedValue = null;
        wjSearchObj[key].value = null;
        wjSearchObj[key].text = null;
      });
      this.searchParams.product_code = null;
      this.searchParams.product_name = null;
      this.searchParams.model = null;
    },
    itemFormatter: function (panel, r, c, cell) {
      if (this.keepDOM === undefined || this.keepDOM === null) {
        return;
      }

      // 列ヘッダのセンタリング
      if (panel.cellType == wjGrid.CellType.ColumnHeader) {
        cell.style.textAlign = "center";
      }

      if (panel.cellType == wjGrid.CellType.Cell) {
        // c（0始まり）
        // 例1：1列目(c=0)を非表示にしている場合は『case 0:～』と書いたとしてその中に入ることはない。
        // 例2：1列目(c=0)が何らかの理由で隠れている場合(横スクロールして1列目が見えていない等)は『case 0:～』と書いたとしてその中に入ることはない。
        var item = this.wjBaseGrid.rows[r];
        if (this.rmUndefinedBlank(item.dataItem) != "") {
          switch (c) {
            case 0: // 発注要否
              cell.innerHTML = "";
              cell.appendChild(
                this.keepDOM[panel.getCellData(r, this.gridPKCol)]
                  .should_order_mark
              );
              cell.style.textAlign = "center";
              break;
            case 1: // 拠点
              cell.innerHTML = "";
              cell.appendChild(
                this.keepDOM[panel.getCellData(r, this.gridPKCol)].base_name
              );
              cell.style.textAlign = "left";
              break;
            case 2: // 商品番号
              cell.innerHTML = "";
              cell.appendChild(
                this.keepDOM[panel.getCellData(r, this.gridPKCol)].product_code
              );
              cell.style.textAlign = "left";
              break;
            case 3: // 商品名
              cell.style.textAlign = "left";
              break;
            case 4: // 単位
              cell.innerHTML = "";
              cell.appendChild(
                this.keepDOM[panel.getCellData(r, this.gridPKCol)].unit
              );
              cell.style.textAlign = "left";
              break;
            case 5: //発注点
              cell.innerHTML = "";
              cell.appendChild(
                this.keepDOM[panel.getCellData(r, this.gridPKCol)].order_limit
              );
              cell.style.textAlign = "right";
              break;
            case 6: //有効在庫数
              cell.innerHTML = "";
              cell.appendChild(
                this.keepDOM[panel.getCellData(r, this.gridPKCol)]
                  .active_quantity
              );
              cell.style.textAlign = "right";
              break;
            case 7: //実在庫数
              cell.innerHTML = "";
              cell.appendChild(
                this.keepDOM[panel.getCellData(r, this.gridPKCol)]
                  .actual_quantity
              );
              cell.style.textAlign = "right";
              break;
            case 8: //引当数
              cell.style.textAlign = "right";
              break;
          }
        }
      }
    },
    itemDetailFormatter: function (panel, r, c, cell) {
      if (this.keepDOM === undefined || this.keepDOM === null) {
        return;
      }

      // 列ヘッダのセンタリング
      if (panel.cellType == wjGrid.CellType.ColumnHeader) {
        cell.style.textAlign = "center";
      }

      if (panel.cellType == wjGrid.CellType.Cell) {
        // c（0始まり）
        // 例1：1列目(c=0)を非表示にしている場合は『case 0:～』と書いたとしてその中に入ることはない。
        // 例2：1列目(c=0)が何らかの理由で隠れている場合(横スクロールして1列目が見えていない等)は『case 0:～』と書いたとしてその中に入ることはない。
        switch (c) {
          case 0: // 倉庫名
            cell.style.textAlign = "left";
            break;
          case 1: // 有効在庫数
            cell.style.textAlign = "right";
            break;
          case 2: // 実在庫数
            cell.style.textAlign = "right";
            break;
          case 3: // 引当数
            cell.style.textAlign = "right";
            break;
          case 4: // 発注数
            cell.style.textAlign = "right";
            break;
        }
      }
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
          i == 1 ||
          i == 2 ||
          i == 4 ||
          i == 5 ||
          i == 6 ||
          i == 7
        ) {
          grid.columnHeaders.setCellData(
            0,
            i,
            grid.columnHeaders.getCellData(1, i)
          );
          grid.columnHeaders.columns[i].allowMerging = true;
        }
      }

      return grid;
    },
    // グリッドに設定適用（itemsSource更新時に設定が消えるもののみ）
    applyDetailSettings(grid) {
      // リサイジング設定
      grid.columns.forEach((element) => {
        if (
          this.detailGridSetting.deny_resizing_col.indexOf(element.index) >= 0
        ) {
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
          header: "発注要否",
          cells: [
            // { binding: 'warehouse_name', header: '倉庫名', isReadOnly: true, width: 190, minWidth: GRID_COL_MIN_WIDTH },
            {
              binding: "id",
              header: "発注要否",
              isReadOnly: true,
              width: 75,
              minWidth: GRID_COL_MIN_WIDTH,
            },
          ],
        },
        {
          header: "拠点",
          cells: [
            {
              binding: "id",
              header: "拠点",
              isReadOnly: true,
              width: 190,
              minWidth: GRID_COL_MIN_WIDTH,
            },
          ],
        },
        {
          header: "商品番号",
          cells: [
            // { binding: 'active_quantity', header: '有効在庫数', isReadOnly: true, width: 90, minWidth: GRID_COL_MIN_WIDTH },
            {
              binding: "id",
              header: "商品番号",
              isReadOnly: true,
              width: 190,
              minWidth: GRID_COL_MIN_WIDTH,
            },
          ],
        },
        {
          header: "商品名",
          cells: [
            {
              binding: "product_name",
              header: "商品名",
              isReadOnly: true,
              width: 220,
              minWidth: GRID_COL_MIN_WIDTH,
            },
            {
              binding: "model",
              header: "型式／規格",
              isReadOnly: true,
              width: 220,
              minWidth: GRID_COL_MIN_WIDTH,
            },
          ],
        },

        {
          header: "単位",
          cells: [
            // { binding: 'actual_quantity', header: '実在庫数', isReadOnly: true, wordWrap: true, width: 90, minWidth: GRID_COL_MIN_WIDTH },
            {
              binding: "id",
              header: "単位",
              isReadOnly: true,
              width: 80,
              minWidth: GRID_COL_MIN_WIDTH,
            },
          ],
        },
        {
          header: "発注点",
          cells: [
            // { binding: 'reserve_quantity', header: '引当数', wordWrap: true, width: 90, minWidth: GRID_COL_MIN_WIDTH },
            {
              binding: "id",
              header: "発注点",
              isReadOnly: false,
              width: 100,
              minWidth: GRID_COL_MIN_WIDTH,
            },
          ],
        },
        {
          header: "有効在庫数",
          cells: [
            // { binding: 'active_quantity', header: '有効在庫数', isReadOnly: true, width: 90, minWidth: GRID_COL_MIN_WIDTH },
            {
              binding: "id",
              header: "有効在庫数",
              isReadOnly: true,
              width: 100,
              minWidth: GRID_COL_MIN_WIDTH,
            },
          ],
        },
        {
          header: "実在庫数",
          cells: [
            // { binding: 'arrival_quantity', header: '入荷予定数', isReadOnly: true, width: 90, minWidth: GRID_COL_MIN_WIDTH },
            {
              binding: "id",
              header: "実在庫数",
              isReadOnly: true,
              width: 100,
              minWidth: GRID_COL_MIN_WIDTH,
            },
          ],
        },
        {
          header: "引当数",
          cells: [
            {
              binding: "reserve_quantity",
              header: "引当数",
              isReadOnly: true,
              width: 100,
              minWidth: GRID_COL_MIN_WIDTH,
            },
            {
              binding: "order_quantity",
              header: "発注数",
              isReadOnly: true,
              width: 100,
              minWidth: GRID_COL_MIN_WIDTH,
            },
          ],
        },

        /* 以降、非表示カラム */
        {
          header: "ID",
          cells: [{ binding: "id", header: "ID", width: 0, maxWidth: 0 }],
        },
        // {
        //   header: "ID",
        //   cells: [
        //     {
        //       binding: "warehouse_id",
        //       header: "warehouse_id",
        //       width: 0,
        //       maxWidth: 0,
        //     },
        //   ],
        // },
      ];
    },
    // グリッドレイアウト
    detailLayout() {
      return [
        {
          header: "倉庫名",
          cells: [
            {
              binding: "warehouse_name",
              header: "倉庫名",
              isReadOnly: true,
              width: 200,
              minWidth: GRID_COL_MIN_WIDTH,
            },
          ],
        },
        {
          header: "有効在庫数",
          cells: [
            {
              binding: "active_quantity",
              header: "有効在庫数",
              isReadOnly: true,
              width: 110,
              minWidth: GRID_COL_MIN_WIDTH,
            },
          ],
        },
        {
          header: "実在庫数",
          cells: [
            {
              binding: "actual_quantity",
              header: "実在庫数",
              isReadOnly: true,
              width: 110,
              minWidth: GRID_COL_MIN_WIDTH,
            },
          ],
        },
        {
          header: "引当数",
          cells: [
            {
              binding: "reserve_quantity",
              header: "引当数",
              isReadOnly: true,
              width: 110,
              minWidth: GRID_COL_MIN_WIDTH,
            },
          ],
        },
        {
          header: "発注数",
          cells: [
            {
              binding: "order_quantity",
              header: "発注数",
              isReadOnly: true,
              width: 110,
              minWidth: GRID_COL_MIN_WIDTH,
            },
          ],
        },
      ];
    },
    // グリッドレイアウト

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
    initBaseName: function (sender) {
      this.wjSearchObj.base_name = sender;
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
.should-order-cell {
  color: red;
  text-align: right;
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
</style>

