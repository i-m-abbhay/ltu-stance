<template>
    <div>
        <div class="sidemenu-switch-box" :class="{'sidemenu-switch-close': isCollapse, 'sidemenu-switch-open': !isCollapse}">
          <button class="sidemenu-switch" v-show="isCollapse" v-on:click="switchMenu"><i class="el-icon-arrow-right"></i></button>
          <button class="sidemenu-switch pull-right" v-show="!isCollapse" v-on:click="switchMenu"><i class="el-icon-arrow-left"></i></button>
        </div>
        <div class="sidemenu-switch-box clearfix"></div>

        <el-menu class="el-menu-vertical-demo" :collapse="isCollapse">
            <el-submenu index="21">
                <template slot="title">
                    <i class="el-icon-s-custom"></i>
                    <span slot="title">新規得意先</span>
                </template>
                <el-menu-item index="21-1"><a href="/new-customer-list">新規得意先一覧</a></el-menu-item>
                <el-menu-item index="21-2" v-show="hasAuth.master.edit"><a href="/new-customer-edit/new">新規得意先入力</a></el-menu-item>
                <el-menu-item index="21-3" v-show="hasAuth.master.edit"><a href="/customer-staff">得意先担当者設定</a></el-menu-item>
            </el-submenu>
            <!-- <el-submenu index="22">
                <template slot="title">
                    <i class="el-icon-location-outline"></i>
                    <span slot="title">住所</span>
                </template>
                <el-menu-item index="22-1" disabled><a href="/address-list">住所一覧</a></el-menu-item>
                <el-menu-item index="22-2" disabled><a href="/address-edit/new">住所入力</a></el-menu-item>
            </el-submenu> -->
            <el-submenu index="23">
                <template slot="title">
                    <i class="el-icon-apple"></i>
                    <span slot="title">案件</span>
                </template>
                <el-menu-item index="23-1"><a href="/matter-list">案件一覧</a></el-menu-item>
                <el-menu-item index="23-2"><a href="/matter-edit/new">新規案件情報入力</a></el-menu-item>
            </el-submenu>
            <el-submenu index="24">
                <template slot="title">
                    <i class="el-icon-document"></i>
                    <span slot="title">見積依頼</span>
                </template>
                <el-menu-item index="24-1"><a href="/quote-request-list">見積依頼一覧</a></el-menu-item>
                <el-menu-item index="24-2"><a href="/quote-request-edit/new">見積依頼入力</a></el-menu-item>
            </el-submenu>
            <el-submenu index="25">
                <template slot="title">
                    <i class="el-icon-document-copy"></i>
                    <span slot="title">見積</span>
                </template>
                <el-menu-item index="25-1"><a href="/quote-list">見積一覧</a></el-menu-item>
                <el-menu-item index="25-2"><a href="/quote-edit/new">見積入力</a></el-menu-item>
            </el-submenu>
            
            <el-submenu index="31">
                <template slot="title">
                    <i class="el-icon-data-analysis"></i>
                    <span slot="title">木材立米単価</span>
                </template>
                <el-menu-item index="31-1"><a href="/wood-unit-price-edit">木材立米単価入力</a></el-menu-item>
                <!-- <el-menu-item index="31-2"><a href="#">木材立米単価分析</a></el-menu-item> -->
            </el-submenu>

            <el-submenu index="41">
                <template slot="title">
                    <i class="el-icon-wallet"></i>
                    <span slot="title">受発注</span>
                </template>
                <el-menu-item index="41-1"><a href="/order-list">受発注一覧</a></el-menu-item>
                <el-menu-item index="41-2"><a href="/order-sudden">いきなり発注</a></el-menu-item>
                <el-menu-item index="41-3"><a href="/order-sudden-ownstock">当社在庫品発注</a></el-menu-item>
                <el-menu-item index="41-4"><a href="/stock-allocation">在庫引当</a></el-menu-item>
            </el-submenu>
            <el-submenu index="42">
                <template slot="title">
                    <i class="el-icon-sold-out"></i>
                    <span slot="title">入荷</span>
                </template>
                <el-menu-item index="42-1"><a href="/arrival-list">入荷一覧</a></el-menu-item>
                <!-- <el-menu-item index="42-2" disabled><a href="/arrival-search">入荷検索</a></el-menu-item> -->
            </el-submenu>
            <el-submenu index="43">
                <template slot="title">
                    <i class="el-icon-box"></i>
                    <span slot="title">出荷</span>
                </template>
                <!--<el-menu-item index="43-1" disabled><a href="/shipping-search">出荷検索</a></el-menu-item> -->
                <!-- <el-menu-item index="43-2" disabled><a href="/shipping-list">出荷一覧</a></el-menu-item> -->
                <el-menu-item index="43-3"><a href="/shipping-delivery-list">出荷納品一覧</a></el-menu-item>
                <!-- <el-menu-item index="43-4" disabled><a href="/delivery-list">納品先選択</a></el-menu-item> -->
                <el-menu-item index="43-5"><a href="/shipping-instruction/new">出荷指示</a></el-menu-item>
            </el-submenu>
            <el-submenu index="45">
                <template slot="title">
                    <i class="el-icon-receiving"></i>
                    <span slot="title">返品</span>
                </template>
                <el-menu-item index="45-1"><a href="/return-process">返品処理</a></el-menu-item>
            </el-submenu>
            <el-submenu index="44">
                <template slot="title">
                    <i class="el-icon-collection"></i>
                    <span slot="title">在庫</span>
                </template>
                <el-menu-item index="44-1"><a href="/stock-search">在庫照会</a></el-menu-item>
                <el-menu-item index="44-2"><a href="/stock-transfer-list">在庫移管一覧</a></el-menu-item>
                <!-- <el-menu-item index="44-3" disabled><a href="/stock-transfer">在庫振替</a></el-menu-item> -->
                <el-menu-item index="44-4"><a href="/stock-conversion">在庫転換入力</a></el-menu-item>
                <el-menu-item index="44-6"><a href="/order-point-list">倉庫別在庫管理</a></el-menu-item>
            </el-submenu>
            <el-submenu index="15" v-show="hasAuth.master.inquiry">
                <template slot="title">
                    <i class="el-icon-suitcase"></i>
                    <span slot="title">商品</span>
                </template>
                <el-menu-item index="15-1"><a href="/product-list">商品一覧</a></el-menu-item>
                <el-menu-item index="15-2" v-show="hasAuth.master.edit"><a href="/product-edit/new">商品編集</a></el-menu-item>
                <el-menu-item index="15-3" v-show="hasAuth.master.edit"><a href="/product-check">商品マスタチェック</a></el-menu-item>
                <el-menu-item index="15-4" v-show="hasAuth.master.edit" disabled><a href="/product-import">CSVデータ取込</a></el-menu-item>
            </el-submenu>
            <el-submenu index="16" v-show="hasAuth.master.inquiry">
                <template slot="title">
                    <i class="el-icon-school"></i>
                    <span slot="title">仕入先</span>
                </template>
                <el-menu-item index="16-1"><a href="/supplier-list">仕入先一覧</a></el-menu-item>
                <el-menu-item index="16-2" v-show="hasAuth.master.edit"><a href="/supplier-edit/new">仕入先編集</a></el-menu-item>
            </el-submenu>
            <el-submenu index="14" v-show="hasAuth.master.inquiry || hasAuth.auth.setting">
                <template slot="title">
                    <i class="el-icon-user"></i>
                    <span slot="title">担当者</span>
                </template>
                <el-menu-item index="14-1" v-show="hasAuth.master.inquiry"><a href="/staff-list">担当者一覧</a></el-menu-item>
                <el-menu-item index="14-2" v-show="hasAuth.master.edit"><a href="/staff-edit/new">担当者編集</a></el-menu-item>
                <el-menu-item index="14-3" v-show="hasAuth.auth.setting"><a href="/authority-edit">権限管理</a></el-menu-item>
            </el-submenu>
            <el-submenu index="11" v-show="hasAuth.master.inquiry">
                <template slot="title">
                    <i class="el-icon-office-building"></i>
                    <span slot="title">拠点</span>
                </template>
                <el-menu-item index="11-1"><a href="/base-list">拠点一覧</a></el-menu-item>
                <el-menu-item index="11-2" v-show="hasAuth.master.edit"><a href="/base-edit/new">拠点編集</a></el-menu-item>
            </el-submenu>
            <el-submenu index="12" v-show="hasAuth.master.inquiry">
                <template slot="title">
                    <i class="el-icon-suitcase"></i>
                    <span slot="title">部門</span>
                </template>
                <el-menu-item index="12-1"><a href="/department-list">部門一覧</a></el-menu-item>
                <el-menu-item index="12-2" v-show="hasAuth.master.edit"><a href="/department-edit/new">部門編集</a></el-menu-item>
            </el-submenu>
            <el-submenu index="13" v-show="hasAuth.master.inquiry">
                <template slot="title">
                    <i class="el-icon-house"></i>
                    <span slot="title">倉庫</span>
                </template>
                <el-menu-item index="13-1"><a href="/warehouse-list">倉庫一覧</a></el-menu-item>
                <el-menu-item index="13-2" v-show="hasAuth.master.edit"><a href="/warehouse-edit/new">倉庫編集</a></el-menu-item>
            </el-submenu>
            <el-submenu index="18" v-show="hasAuth.master.inquiry">
                <template slot="title">
                    <i class="el-icon-s-comment"></i>
                    <span slot="title">共通名称</span>
                </template>
                <el-menu-item index="18-1"><a href="/general-list">共通名称一覧</a></el-menu-item>
                <!-- <el-menu-item index="14-2" v-show="hasAuth.master.edit"><a href="/general-edit/new">共通名称編集</a></el-menu-item> -->
            </el-submenu>
            <el-submenu index="17" v-show="hasAuth.master.inquiry">
                <template slot="title">
                    <i class="el-icon-set-up"></i>
                    <span slot="title">見積依頼項目設定</span>
                </template>
                <el-menu-item index="17-1"><a href="/choice-list">選択肢一覧</a></el-menu-item>
                <el-menu-item index="17-2" v-show="hasAuth.master.edit"><a href="/choice-edit/new">選択肢登録</a></el-menu-item>
                <el-menu-item index="17-3"><a href="/item-list">項目一覧</a></el-menu-item>
                <el-menu-item index="17-4" v-show="hasAuth.master.edit"><a href="/item-edit/new">項目登録</a></el-menu-item>
                <el-menu-item index="17-5"><a href="/spec-item-list">仕様項目一覧</a></el-menu-item>
                <el-menu-item index="17-6" v-show="hasAuth.master.edit"><a href="/spec-item-edit/new">仕様項目登録</a></el-menu-item>
            </el-submenu>
            <el-submenu index="19" v-show="hasAuth.master.inquiry">
                <template slot="title">
                    <i class="el-icon-files"></i>
                    <span slot="title">分類</span>
                </template>
                <el-menu-item index="19-1"><a href="/class-middle-list">中分類一覧</a></el-menu-item>
                <el-menu-item index="19-2" v-show="hasAuth.master.edit"><a href="/class-middle-edit/new">中分類登録</a></el-menu-item>
                <el-menu-item index="19-3"><a href="/class-small-list">工程一覧</a></el-menu-item>
                <el-menu-item index="19-4" v-show="hasAuth.master.edit"><a href="/class-small-edit/new">工程登録</a></el-menu-item>
            </el-submenu>
            <el-submenu index="51">
                <template slot="title">
                    <i class="el-icon-document-checked"></i>
                    <span slot="title">請求</span>
                </template>
                <el-menu-item index="51-1"><a href="/request-list">請求一覧</a></el-menu-item>
                <el-menu-item index="51-2"><a href="/deposit-list">入金一覧</a></el-menu-item>
                <el-menu-item index="51-3"><a href="/sales-list">売上一覧</a></el-menu-item>
            </el-submenu>
            <el-submenu index="61">
                <template slot="title">
                    <i class="el-icon-tickets"></i>
                    <span slot="title">仕入</span>
                </template>
                <el-menu-item index="61-1"><a href="/purchase-detail">仕入明細</a></el-menu-item>
                <el-menu-item index="61-2"><a href="/payment-list">支払予定一覧</a></el-menu-item>
            </el-submenu>
            <el-submenu index="71">
                <template slot="title">
                    <i class="el-icon-date"></i>
                    <span slot="title">カレンダー</span>
                </template>
                <el-menu-item index="71-1"><a href="/calendar-data">カレンダーデータ</a></el-menu-item>
            </el-submenu>
        </el-menu>
    </div>
</template>

<style>
    a{
        color: #303133;
    }
    a:hover{
        color: #303133;
        text-decoration: none;
    }
    li a{
      display:block;
    }

    .el-menu-vertical-demo:not(.el-menu--collapse) {
        width: 200px;
    }

    .sidemenu-switch-box {
      display: block;
      background-color: #fff;
      border: none;/*solid 1px #e6e6e6;*/
      border-right: 1px solid #000;
    }
    .sidemenu-switch-box-close {
      width: 63px;
    }
    .sidemenu-switch-box-open {
      width: 200px;
    }
    .sidemenu-switch {
      display: block;
      height: 50px;
      width: 60px;
      margin: 0;
      padding: 0;
      border: none;
      background-color: #fff;
    }
</style>

<script>
  export default {
    data() {
      return {
        isCollapse: true
      };
    },
    props: {
        hasAuth: Object,
    },
    methods: {
      switchMenu() {
        this.isCollapse = !this.isCollapse
      }
    }
  }
</script>
