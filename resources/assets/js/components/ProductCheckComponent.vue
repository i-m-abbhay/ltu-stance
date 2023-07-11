<template>
    <div>
        <loading-component :loading="loading" />
        <div class="col-md-12 search-body bg-white">
            <!-- 検索条件 -->
            <div class="search-form" id="searchForm">
                <form id="searchForm" name="searchForm" class="form-horizontal">
                    <div class="col-md-12 search-body">
                        <div class="col-md-4">
                            <h5><u>商品選択</u></h5>
                            <label class="col-md-12 col-sm-12 col-xs-12"><span style="color:red;">*</span>商品番号</label>
                            <div class="col-md-10 col-sm-12 col-xs-12" v-bind:class="{'has-error': (searchErr.product_code != '')}">
                                <wj-auto-complete class="form-control" id="acPcode" 
                                    search-member-path="product_code"
                                    display-member-path="product_code"
                                    selected-value-path="id"
                                    :selected-index="-1"
                                    :is-required="false"
                                    :selected-value="searchParams.product_code"
                                    :selectedIndexChanged="selectProductCode"
                                    :initialized="initPcode"
                                    :max-items="productlist.length"
                                    :items-source="productlist">
                                </wj-auto-complete>
                                <p class="text-danger">{{ searchErr.product_code }}</p>
                            </div>
                            <label class="col-md-12 col-sm-12 col-xs-12"><span style="color:red;">*</span>商品名</label>
                            <div class="col-md-12 col-sm-10 col-sm-12 col-xs-12" v-bind:class="{'has-error': (searchErr.product_name != '')}">
                                <wj-auto-complete class="form-control" id="acPname"
                                    search-member-path="product_name"
                                    display-member-path="product_name"
                                    selected-value-path="id"
                                    :selected-index="-1"
                                    :is-required="false"
                                    :selected-value="searchParams.product_name"
                                    :selectedIndexChanged="selectProductName"
                                    :initialized="initPname"
                                    :max-items="productlist.length"
                                    :items-source="productlist">
                                </wj-auto-complete>
                                <p class="text-danger">{{ searchErr.product_name }}</p>
                            </div>
                        </div>
                        <div class="col-md-6 search-body">
                            <h5><u>商品絞り込み</u></h5>
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <label class="col-md-12 col-sm-12 col-xs-12">工事区分</label>
                                <wj-auto-complete class="form-control" id="acConstruction" 
                                    search-member-path="construction_name"
                                    display-member-path="construction_name"
                                    selected-value-path="id"
                                    :selected-index="-1"
                                    :is-required="false"
                                    :selected-value="searchParams.construction_name"
                                    :selectedIndexChanged="execProductFilter"
                                    :initialized="initConstruction"
                                    :max-items="constlist.length"
                                    :items-source="constlist">
                                </wj-auto-complete>
                            </div>                        
                            <div class="col-md-6 col-sm-6 col-sm-12 col-xs-12">
                                <label class="col-md-12 col-sm-12 col-xs-12">案件名</label>
                                <wj-auto-complete class="form-control" id="acMatter"
                                    search-member-path="matter_name"
                                    display-member-path="matter_name"
                                    selected-value-path="matter_no"
                                    :selected-index="-1"
                                    :is-required="false"
                                    :selected-value="searchParams.matter_name"
                                    :selectedIndexChanged="execProductFilter"
                                    :initialized="initMatterName"
                                    :max-items="matterlist.length"
                                    :items-source="matterlist">
                                </wj-auto-complete>
                            </div>                        
                            <div class="col-md-6 col-sm-6 col-sm-12 col-xs-12">
                                <label class="col-md-12 col-sm-12 col-xs-12">メーカー名</label>
                                <wj-auto-complete class="form-control" id="acMaker"
                                    search-member-path="supplier_name"
                                    display-member-path="supplier_name"
                                    selected-value-path="id"
                                    :selected-index="-1"
                                    :is-required="false"
                                    :selected-value="searchParams.supplier_name"
                                    :selectedIndexChanged="execProductFilter"
                                    :initialized="initMaker"
                                    :max-items="makerlist.length"
                                    :items-source="makerlist">
                                </wj-auto-complete>
                            </div>                        
                            <div class="col-md-6 col-sm-6 col-sm-12 col-xs-12">
                                <label class="col-md-12 col-sm-12 col-xs-12">発注番号</label>
                                <wj-auto-complete class="form-control" id="acOrder"
                                    search-member-path="order_no"
                                    display-member-path="order_no"
                                    selected-value-path="order_no"
                                    :selected-index="-1"
                                    :is-required="false"
                                    :selected-value="searchParams.order_no"
                                    :selectedIndexChanged="execProductFilter"
                                    :initialized="initOrderNo"
                                    :max-items="orderlist.length"
                                    :items-source="orderlist">
                                </wj-auto-complete>
                            </div>
                        </div>
                        <div class="col-md-12 text-right">
                            <button type="button" class="btn btn-primary btn-clear" @click="clear">クリア</button>
                            <button type="button" class="btn btn-search btn-submit" @click="select">決定</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="row">
                <!-- モード変更 -->
                <div class="col-md-12 text-right mode-btn-box">
                    <label class="form-control-static" v-show="rmUndefinedZero(lockdata.id) > 0">ロック日時：{{ rmUndefinedBlank(lockdata.lock_dt)|datetime_format }}&emsp;</label>
                    <label class="form-control-static" v-show="rmUndefinedZero(lockdata.id) > 0">ロック者：{{ rmUndefinedBlank(lockdata.lock_user_name) }}&emsp;</label>
                    <button type="button" class="btn btn-danger btn-unlock" @click="unlock" v-show="isLocked">ロック解除</button>
                    <button type="button" class="btn btn-primary btn-edit"  v-show="isShowEditBtn" @click="edit">編集</button>
                    <p class="btn btn-default btn-editing" v-show="(!isLocked && !isShowEditBtn && isEditable)">編集中</p>
                </div>

                <div class="col-md-4">
                    <div class="product-header col-sm-12 col-md-12 col-xs-12" style="height: 60px;">
                        <p class="text-left col-md-6" style="padding-top: 10px;">新規商品マスタ情報</p>
                        <div class="help-header col-md-4">
                            <p>残り</p>
                        </div>
                        <div class="help-body col-md-4">
                            <p>{{ productlist.length }}</p>
                        </div>
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">工事区分１</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.construction_id_1 != '')}">
                        <wj-auto-complete class="wj-product-complete form-control"
                            search-member-path="construction_name"
                            display-member-path="construction_name"
                            selected-value-path="id"
                            :selected-index="-1"
                            :is-required="false"
                            :isReadOnly="isReadOnly"
                            :selected-value="tmpPdt.construction_id_1"
                            :selectedIndexChanged="selectConstruction_1"
                            :initialized="initConst_1"
                            :max-items="constlist.length"
                            :items-source="constlist">
                        </wj-auto-complete>
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">工事区分２</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.construction_id_2 != '')}">
                        <wj-auto-complete class="wj-product-complete form-control"
                            search-member-path="construction_name"
                            display-member-path="construction_name"
                            selected-value-path="id"
                            :selected-index="-1"
                            :is-required="false"
                            :isReadOnly="isReadOnly"
                            :selected-value="tmpPdt.construction_id_2"
                            :selectedIndexChanged="selectConstruction_2"
                            :initialized="initConst_2"
                            :max-items="constlist.length"
                            :items-source="constlist">
                        </wj-auto-complete>
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">工事区分３</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.construction_id_3 != '')}">
                        <wj-auto-complete class="wj-product-complete form-control"
                            search-member-path="construction_name"
                            display-member-path="construction_name"
                            selected-value-path="id"
                            :selected-index="-1"
                            :is-required="false"
                            :isReadOnly="isReadOnly"
                            :selected-value="tmpPdt.construction_id_3"
                            :selectedIndexChanged="selectConstruction_3"
                            :initialized="initConst_3"
                            :max-items="constlist.length"
                            :items-source="constlist">
                        </wj-auto-complete>
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">工程１</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.class_small_id_1 != '')}">
                        <wj-auto-complete class="wj-product-complete form-control"
                            search-member-path="class_small_name"
                            display-member-path="class_small_name"
                            selected-value-path="id"
                            :selected-index="-1"
                            :is-required="false"
                            :isReadOnly="isReadOnly"
                            :selected-value="tmpPdt.class_small_id_1"
                            :initialized="initSmall_1"
                            :max-items="classsmalllist.length"
                            :items-source="classsmalllist">
                        </wj-auto-complete>
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">工程２</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.class_small_id_2 != '')}">
                        <wj-auto-complete class="wj-product-complete form-control"
                            search-member-path="class_small_name"
                            display-member-path="class_small_name"
                            selected-value-path="id"
                            :selected-index="-1"
                            :is-required="false"
                            :isReadOnly="isReadOnly"
                            :selected-value="tmpPdt.class_small_id_2"
                            :initialized="initSmall_2"
                            :max-items="classsmalllist.length"
                            :items-source="classsmalllist">
                        </wj-auto-complete>
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">工程３</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.class_small_id_3 != '')}">
                        <wj-auto-complete class="wj-product-complete form-control"
                            search-member-path="class_small_name"
                            display-member-path="class_small_name"
                            selected-value-path="id"
                            :selected-index="-1"
                            :is-required="false"
                            :isReadOnly="isReadOnly"
                            :selected-value="tmpPdt.class_small_id_3"
                            :initialized="initSmall_3"
                            :max-items="classsmalllist.length"
                            :items-source="classsmalllist">
                        </wj-auto-complete>
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">大分類</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.class_big_id != '')}">
                        <wj-auto-complete class="wj-product-complete form-control"
                            search-member-path="class_big_name"
                            display-member-path="class_big_name"
                            selected-value-path="id"
                            :selected-index="-1"
                            :is-required="false"
                            :isReadOnly="isReadOnly"
                            :selectedIndexChanged="changeIdxClassBig"
                            :selected-value="tmpPdt.class_big_id"
                            :initialized="initClassBig"
                            :max-items="classbiglist.length"
                            :items-source="classbiglist">
                        </wj-auto-complete>
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">中分類</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.class_middle_id != '')}">
                        <wj-auto-complete class="wj-product-complete form-control"
                            search-member-path="class_middle_name"
                            display-member-path="class_middle_name"
                            selected-value-path="id"
                            :selected-index="-1"
                            :is-required="false"
                            :isReadOnly="isReadOnly"
                            :selected-value="tmpPdt.class_middle_id"
                            :initialized="initClassMid"
                            :max-items="classmidlist.length"
                            :items-source="classmidlist">
                        </wj-auto-complete>
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">形品区分</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.intangible_flg != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.intangible_flg_txt" readonly>
                    </div>

                    <div class="product-header col-sm-4" style="margin-top: 5px;">
                        <p class="text-left">商品番号</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px; margin-top: 5px;" v-bind:class="{'has-error': (tmpErrors.product_code != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.product_code" v-bind:readonly="isReadOnly" v-bind:class="{'has-error': (tmpErrors.product_code != '')}">
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">商品名</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.product_name != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.product_name" v-bind:readonly="isReadOnly" v-bind:class="{'has-error': (tmpErrors.product_name != '')}">
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">商品略称</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.product_short_name != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.product_short_name" v-bind:readonly="isReadOnly" v-bind:class="{'has-error': (tmpErrors.product_short_name != '')}">
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">型式/規格</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.model != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.model" v-bind:readonly="isReadOnly">
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">メーカー名</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.maker_id != '')}">
                        <wj-auto-complete class="wj-product-complete form-control"
                            search-member-path="supplier_name"
                            display-member-path="supplier_name"
                            selected-value-path="id"
                            :selected-index="-1"
                            :is-required="false"
                            :selected-value="tmpPdt.maker_id"
                            :isReadOnly="(tmpPdt.maker_id != 0)"
                            :initialized="initMakerInp"
                            :selectedIndexChanged="changedTmpMaker"
                            :max-items="makerlist.length"
                            :items-source="makerlist">
                        </wj-auto-complete>
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">樹種</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.tree_species != '')}">
                        <wj-auto-complete class="wj-product-complete form-control"
                            search-member-path="value_text_1"
                            display-member-path="value_text_1"
                            selected-value-path="value_code"
                            :selected-index="-1"
                            :is-required="false"
                            :isReadOnly="isReadOnly"
                            :selected-value="tmpPdt.tree_species"
                            :initialized="initTreeSpec"
                            :max-items="woodlist.length"
                            :items-source="woodlist">
                        </wj-auto-complete>
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">等級</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.grade != '')}">
                        <wj-auto-complete class="wj-product-complete form-control"
                            search-member-path="value_text_1"
                            display-member-path="value_text_1"
                            selected-value-path="value_code"
                            :selected-index="-1"
                            :is-required="false"
                            :isReadOnly="isReadOnly"
                            :selected-value="tmpPdt.grade"
                            :initialized="initGrade"
                            :max-items="gradelist.length"
                            :items-source="gradelist">
                        </wj-auto-complete>
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">長さ(mm)</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.length != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.length" v-bind:readonly="isReadOnly">
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">高さ(mm)</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.thickness != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.thickness" v-bind:readonly="isReadOnly">
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">幅(mm)</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.width != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.width" v-bind:readonly="isReadOnly">
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">重量(kg)</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.weight != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.weight" v-bind:readonly="isReadOnly">
                    </div>

                    <div class="product-header col-sm-4" style="margin-top: 5px;">
                        <p class="text-left">定価</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px; margin-top: 5px;" v-bind:class="{'has-error': (tmpErrors.price != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.price" v-bind:readonly="isReadOnly" @change="changePrice">
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">オープン価格</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.open_price_flg != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.open_price_txt" readonly>
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">単位</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.unit != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.unit" v-bind:readonly="isReadOnly">
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">管理数単位</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.stock_unit != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.stock_unit" v-bind:readonly="isReadOnly">
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">最小単位数量</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.min_quantity != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.min_quantity" readonly>
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">入り数</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.quantity_per_case != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.quantity_per_case" v-bind:readonly="isReadOnly">
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">ロット数</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.order_lot_quantity != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.order_lot_quantity" v-bind:readonly="isReadOnly">
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">リードタイム</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.lead_time != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.lead_time" v-bind:readonly="isReadOnly">
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">標準仕入掛率</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.purchase_makeup_rate != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.purchase_makeup_rate" v-bind:readonly="isReadOnly">
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">標準仕入単価</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.normal_purchase_price != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.normal_purchase_price" v-bind:readonly="isReadOnly">
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">標準販売掛率</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.sales_makeup_rate != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.sales_makeup_rate" v-bind:readonly="isReadOnly">
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">標準販売単価</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.normal_sales_price != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.normal_sales_price" v-bind:readonly="isReadOnly">
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">標準粗利率</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.normal_gross_profit_rate != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.normal_gross_profit_rate" v-bind:readonly="isReadOnly">
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">税種別</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.tax_type != '')}">
                        <wj-auto-complete class="wj-product-complete form-control"
                            search-member-path="value_text_1"
                            display-member-path="value_text_1"
                            selected-value-path="value_code"
                            :selected-index="-1"
                            :is-required="false"
                            :isReadOnly="isReadOnly"
                            :selected-value="tmpPdt.tax_type"
                            :initialized="initTaxType"
                            :max-items="taxtypelist.length"
                            :items-source="taxtypelist">
                        </wj-auto-complete>
                    </div>

                    <div class="product-header col-sm-4" style="margin-top: 5px;">
                        <p class="text-left">適用開始日</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px; margin-top: 5px;" v-bind:class="{'has-error': (tmpErrors.start_date != '')}">
                        <wj-input-date class="wj-product-complete form-control"
                            :value="tmpPdt.start_date" 
                            placeholder=" "
                            :isReadOnly="isReadOnly"
                            :initialized="initStartDate"
                            :isRequired="false"
                        ></wj-input-date>
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">適用終了日</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.end_date != '')}">
                        <wj-input-date class="wj-product-complete form-control"
                            :value="tmpPdt.end_date" 
                            placeholder=" "
                            :isReadOnly="isReadOnly"
                            :initialized="initEndDate"
                            :isRequired="false"
                        ></wj-input-date>
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">保障期間</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.warranty_term != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.warranty_term" v-bind:readonly="isReadOnly">
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">住宅履歴</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.housing_history_transfer_kbn != '')}">
                        <wj-combo-box class="wj-product-complete form-control"
                            search-member-path="text"
                            display-member-path="text"
                            selected-value-path="value"
                            :selected-index="-1"
                            :is-required="false"
                            :isReadOnly="isReadOnly"
                            :selected-value="tmpPdt.housing_history_transfer_kbn"
                            :initialized="initHousingKbn"
                            :max-items="housingHistoryTransferKbn.length"
                            :items-source="housingHistoryTransferKbn">
                        </wj-combo-box>
                        <!-- <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.housing_history_transfer_kbn" v-bind:readonly="isReadOnly"> -->
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">後継商品コード</p>
                    </div>
                    <div class="product-body col-sm-8 btn-group" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.new_product_id != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.new_product_code" readonly>
                        <span class="searchclear glyphicon glyphicon-remove-circle" @click="delPdtNewProductId" v-bind:disabled="isReadOnly" v-show="tmpPdt.new_product_id > 0"></span>
                    </div>
                    <div class="product-header col-sm-4" style="height: 80px;">
                        <p class="text-left">備考</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px; height: 80px;" v-bind:class="{'has-error': (tmpErrors.memo != '')}">
                        <textarea class="wj-product-complete form-control" rows="10" style="height: 80px;" v-model="tmpPdt.memo" v-bind:readonly="isReadOnly"></textarea>
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">登録者</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.created_staff != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.created_staff" v-bind:readonly="true">
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">案件番号</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.matter_no != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.matter_no" v-bind:readonly="true">
                    </div>
                    <div class="product-header col-sm-4">
                        <p class="text-left">案件名</p>
                    </div>
                    <div class="product-body col-sm-8" style="padding:0px;" v-bind:class="{'has-error': (tmpErrors.matter_name != '')}">
                        <input type="text" class="wj-product-complete form-control" v-model="tmpPdt.matter_name" v-bind:readonly="true">
                    </div>

                    <div class="col-md-10 col-md-offset-2">
                        <div class="text-left col-md-4">
                            <button type="button" class="btn btn-search" @click="saveDisable" v-bind:class="{ 'active-btn': !isSaveDisp }">登録しない</button>
                        </div>
                        <div class="text-center col-md-4">
                            <button type="button" class="btn btn-search" @click="onceActive" v-bind:class="{ 'active-btn': isSaveDisp && isOnceDisp }">1回限り登録</button>
                        </div>
                        <div class="text-right col-md-4">
                            <button type="button" class="btn btn-search" @click="saveActive" v-bind:class="{ 'active-btn': isSaveDisp && !isOnceDisp }">&nbsp;登録&nbsp;</button>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="product-header col-sm-12 col-md-12 col-xs-12" style="padding-top:0px; height: 25px;">
                        <p class="col-md-6 text-right" style="padding-top: 0px;">類似商品マスタ情報</p>
                        <div class="text-left">
                            <button type="button" class="btn btn-search btn-add" @click="addProduct" v-bind:disabled="rmUndefinedZero(tmpPdt.id) == 0">追加</button>
                        </div>
                    </div>
                    <div class="scroll-area">
                        <div class="scroll-item" v-for="(data, index) in similarPdt" v-bind:key="index" v-bind:id="'pdt-' + index">
                            <div class="product-header" style="padding-top:5px; margin-top:5px; height: 30px;">
                                <p class="col-md-6 text-right" style="margin: 0px; padding-top: 0px;">類似商品{{ index+1 }}</p>
                                <div class="text-right">
                                    <button type="button" class="btn btn-search btn-header" @click="productSearch(index)" v-bind:disabled="rmUndefinedZero(tmpPdt.id) == 0">検索</button>
                                </div>
                            </div>
                            <!-- 工事区分1 -->
                            <div class="product-body col-sm-12" v-bind:class="{'has-error': (simErrors[index].construction_id_1 != ''), 'diff-form' :rmUndefinedZero(tmpInputObj.construction_id_1.selectedValue) != rmUndefinedZero(similarPdt[index].construction_id_1) }" style="padding:0px;">
                                <div v-bind:id="'acCon1-' + index" class="wj-product-complete form-control"></div>
                                
                                <!-- <wj-auto-complete class="wj-product-complete" v-bind:id="index" 
                                    search-member-path="construction_name"
                                    display-member-path="construction_name"
                                    selected-value-path="id"
                                    :selected-index="-1"
                                    :is-required="false"
                                    :selected-value="data.construction_id_1"
                                    :selectedIndexChanged="selectConstructionByIndex_1"
                                    :initialized="initConstByIndex_1"
                                    :max-items="constlist.length"
                                    :items-source="constlist">
                                </wj-auto-complete> -->
                            </div>
                            <!-- 工事区分2 -->
                            <div class="product-body col-sm-12" v-bind:class="{'has-error': (simErrors[index].construction_id_2 != ''), 'diff-form' :rmUndefinedZero(tmpInputObj.construction_id_2.selectedValue) != rmUndefinedZero(similarPdt[index].construction_id_2) }" style="padding:0px;">
                                <div v-bind:id="'acCon2-' + index" class="wj-product-complete form-control"></div>
                                <!-- <wj-auto-complete class="wj-product-complete" v-bind:id="index"
                                    search-member-path="construction_name"
                                    display-member-path="construction_name"
                                    selected-value-path="id"
                                    :selected-index="-1"
                                    :is-required="false"
                                    :selected-value="data.construction_id_2"
                                    :selectedIndexChanged="selectConstructionByIndex_2"
                                    :initialized="initConstByIndex_2"
                                    :max-items="constlist.length"
                                    :items-source="constlist">
                                </wj-auto-complete> -->
                            </div>
                            <!-- 工事区分3 -->
                            <div class="product-body col-sm-12" v-bind:class="{ 'has-error': (simErrors[index].construction_id_3 != ''), 'diff-form' :rmUndefinedZero(tmpInputObj.construction_id_3.selectedValue) != rmUndefinedZero(similarPdt[index].construction_id_3) }" style="padding:0px;">
                                <div v-bind:id="'acCon3-' + index" class="wj-product-complete form-control"></div>
                                <!-- <wj-auto-complete class="wj-product-complete" v-bind:id="index"
                                    search-member-path="construction_name"
                                    display-member-path="construction_name"
                                    selected-value-path="id"
                                    :selected-index="-1"
                                    :is-required="false"
                                    :selected-value="data.construction_id_3"
                                    :selectedIndexChanged="selectConstructionByIndex_3"
                                    :initialized="initConstByIndex_3"
                                    :max-items="constlist.length"
                                    :items-source="constlist">
                                </wj-auto-complete> -->
                            </div>
                            <!-- 工程1 -->
                            <div class="product-body col-sm-12" v-bind:class="{'has-error': (simErrors[index].class_small_id_1 != ''),  'diff-form' :rmUndefinedZero(tmpInputObj.class_small_id_1.selectedValue) != rmUndefinedZero(similarPdt[index].class_small_id_1) }"  style="padding:0px;">
                                <div v-bind:id="'acSm1-' + index" class="wj-product-complete form-control"></div>
                                <!-- <wj-auto-complete class="wj-product-complete" v-bind:id="index"
                                    search-member-path="class_small_name"
                                    display-member-path="class_small_name"
                                    selected-value-path="id"
                                    :selected-index="-1"
                                    :is-required="false"
                                    :selected-value="data.class_small_id_1"
                                    :initialized="initSmallByIndex_1"
                                    :max-items="classsmalllist.length"
                                    :items-source="classsmalllist">
                                </wj-auto-complete> -->
                            </div>
                            <!-- 工程2 -->
                            <div class="product-body col-sm-12" v-bind:class="{'has-error': (simErrors[index].class_small_id_2 != ''),  'diff-form' :rmUndefinedZero(tmpInputObj.class_small_id_2.selectedValue) != rmUndefinedZero(similarPdt[index].class_small_id_2) }" style="padding:0px;">
                                <div v-bind:id="'acSm2-' + index" class="wj-product-complete form-control"></div>
                                <!-- <wj-auto-complete class="wj-product-complete" v-bind:id="index"
                                    search-member-path="class_small_name"
                                    display-member-path="class_small_name"
                                    selected-value-path="id"
                                    :selected-index="-1"
                                    :is-required="false"
                                    :selected-value="data.class_small_id_2"
                                    :initialized="initSmallByIndex_2"
                                    :max-items="classsmalllist.length"
                                    :items-source="classsmalllist">
                                </wj-auto-complete> -->
                            </div>
                            <!-- 工程3 -->
                            <div class="product-body col-sm-12" v-bind:class="{'has-error': (simErrors[index].class_small_id_3 != ''),  'diff-form' :rmUndefinedZero(tmpInputObj.class_small_id_3.selectedValue) != rmUndefinedZero(similarPdt[index].class_small_id_3) }" style="padding:0px;">
                                <div v-bind:id="'acSm3-' + index" class="wj-product-complete form-control"></div>
                                <!-- <wj-auto-complete class="wj-product-complete" v-bind:id="index"
                                    search-member-path="class_small_name"
                                    display-member-path="class_small_name"
                                    selected-value-path="id"
                                    :selected-index="-1"
                                    :is-required="false"
                                    :selected-value="data.class_small_id_3"
                                    :initialized="initSmallByIndex_3"
                                    :max-items="classsmalllist.length"
                                    :items-source="classsmalllist">
                                </wj-auto-complete> -->
                            </div>
                            <!-- 大分類 -->
                            <div class="product-body col-sm-12" v-bind:class="{'has-error': (simErrors[index].class_big_id != ''),  'diff-form' :rmUndefinedZero(tmpInputObj.class_big_id.selectedValue) != rmUndefinedZero(similarPdt[index].class_big_id) }" style="padding:0px;">
                                <div v-bind:id="'acBg-' + index" class="wj-product-complete form-control"></div>
                                <!-- <wj-auto-complete class="wj-product-complete" v-bind:id="index"
                                    search-member-path="class_big_name"
                                    display-member-path="class_big_name"
                                    selected-value-path="id"
                                    :selected-index="-1"
                                    :is-required="false"
                                    :selected-value="data.class_big_id"
                                    :initialized="initBigByIndex"
                                    :max-items="classbiglist.length"
                                    :items-source="classbiglist">
                                </wj-auto-complete> -->
                            </div>
                            <!-- 中分類 -->
                            <div class="product-body col-sm-12" v-bind:class="{'has-error': (simErrors[index].class_middle_id != ''),  'diff-form' :rmUndefinedZero(tmpInputObj.class_middle_id.selectedValue) != rmUndefinedZero(similarPdt[index].class_middle_id) }" style="padding:0px;">
                                <div v-bind:id="'acMd-' + index" class="wj-product-complete form-control"></div>
                                <!-- <wj-auto-complete class="wj-product-complete" v-bind:id="index"
                                    search-member-path="class_middle_name"
                                    display-member-path="class_middle_name"
                                    selected-value-path="id"
                                    :selected-index="-1"
                                    :is-required="false"
                                    :selected-value="data.class_middle_id"
                                    :initialized="initMidByIndex"
                                    :max-items="classmidlist.length"
                                    :items-source="classmidlist">
                                </wj-auto-complete> -->
                            </div>
                            <!-- 形品区分 -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].intangible_flg != '') }">
                                <input type="text" class="wj-product-complete form-control"  v-bind:class="{ 'diff-form' :tmpPdt.intangible_flg != data.intangible_flg }" v-model="data.intangible_flg_txt" readonly>
                            </div>

                            <!-- 商品番号 -->
                            <div class="product-body col-sm-12" style="padding:0px; margin-top: 5px;" v-bind:class="{ 'has-error': (simErrors[index].product_code != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.product_code"  v-bind:class="{ 'diff-form' :tmpPdt.product_code != data.product_code }" v-bind:readonly="isReadOnly">
                            </div>
                            <!-- 商品名 -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].product_name != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.product_name" v-bind:class="{ 'diff-form' :tmpPdt.product_name != data.product_name }" v-bind:readonly="isReadOnly">
                            </div>
                            <!-- 商品名略称 -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].product_short_name != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.product_short_name" v-bind:class="{ 'diff-form' :tmpPdt.product_short_name != data.product_short_name }" v-bind:readonly="isReadOnly">
                            </div>
                            <!-- 型式／規格 -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].model != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.model" v-bind:class="{ 'diff-form' :tmpPdt.model != data.model }" v-bind:readonly="isReadOnly">
                            </div>
                            <!-- メーカー -->
                            <div class="product-body col-sm-12" v-bind:class="{'has-error': (simErrors[index].maker_id != ''), 'diff-form' :rmUndefinedZero(tmpInputObj.maker_id.selectedValue) != rmUndefinedZero(similarPdt[index].maker_id) }" style="padding:0px;">
                                <div v-bind:id="'acMkId-' + index" class="wj-product-complete form-control"></div>
                                <!-- <wj-auto-complete class="wj-product-complete" v-bind:id="index"
                                    search-member-path="supplier_name"
                                    display-member-path="supplier_name"
                                    selected-value-path="id"
                                    :selected-index="-1"
                                    :is-required="false"
                                    :selected-value="data.maker_id"
                                    :isReadOnly="true"
                                    :initialized="initMakerByIndex"
                                    :max-items="supplierlist.length"
                                    :items-source="supplierlist">
                                </wj-auto-complete> -->
                            </div>
                            <!-- 樹種 -->
                            <div class="product-body col-sm-12" v-bind:class="{'has-error': (simErrors[index].tree_species != ''), 'diff-form' :rmUndefinedZero(tmpInputObj.tree_species.selectedValue) != rmUndefinedZero(similarPdt[index].tree_species) }" style="padding:0px;">
                                <div v-bind:id="'acTS-' + index" class="wj-product-complete form-control"></div>
                                <!-- <wj-auto-complete class="wj-product-complete" v-bind:id="index"
                                    search-member-path="value_text_1"
                                    display-member-path="value_text_1"
                                    selected-value-path="value_code"
                                    :selected-index="-1"
                                    :is-required="false"
                                    :isReadOnly="true"
                                    :selected-value="data.tree_species"
                                    :initialized="initWoodByIndex"
                                    :max-items="woodlist.length"
                                    :items-source="woodlist">
                                </wj-auto-complete> -->
                            </div>
                            <!-- 等級 -->
                            <div class="product-body col-sm-12" v-bind:class="{'has-error': (simErrors[index].grade != ''), 'diff-form' :rmUndefinedZero(tmpInputObj.grade.selectedValue) != rmUndefinedZero(similarPdt[index].grade) }" style="padding:0px;">
                                <div v-bind:id="'acGr-' + index" class="wj-product-complete form-control"></div>
                                <!-- <wj-auto-complete class="wj-product-complete" v-bind:id="index"
                                    search-member-path="value_text_1"
                                    display-member-path="value_text_1"
                                    selected-value-path="value_code"
                                    :selected-index="-1"
                                    :is-required="false"
                                    :isReadOnly="true"
                                    :selected-value="data.grade"
                                    :initialized="initGradeByIndex"
                                    :max-items="gradelist.length"
                                    :items-source="gradelist">
                                </wj-auto-complete> -->
                            </div>
                            <!-- 長さ -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].length != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.length" readonly v-bind:class="{ 'diff-form' :tmpPdt.length != data.length }">
                            </div>
                            <!-- 高さ -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].thickness != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.thickness" readonly v-bind:class="{ 'diff-form' :tmpPdt.thickness != data.thickness }">
                            </div>
                            <!-- 幅 -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].width != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.width" readonly v-bind:class="{ 'diff-form' :tmpPdt.width != data.width }">
                            </div>
                            <!-- 重量 -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].weight != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.weight" v-bind:class="{ 'diff-form' :tmpPdt.weight != data.weight }" v-bind:readonly="isReadOnly">
                            </div>

                            <!-- 定価 -->
                            <div class="product-body col-sm-12" style="padding:0px; margin-top: 5px;" v-bind:class="{ 'has-error': (simErrors[index].price != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.price" v-bind:class="{ 'diff-form' :tmpPdt.price != data.price }" v-bind:readonly="isReadOnly" @change="changeSimPrice(index)">
                            </div>
                            <!-- オープン価格 -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].open_price_flg != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.open_price_txt" readonly v-bind:class="{ 'diff-form' :tmpPdt.open_price_flg != data.open_price_flg }">
                            </div>
                            <!-- 単位 -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].unit != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.unit" v-bind:class="{ 'diff-form' :tmpPdt.unit != data.unit }" v-bind:readonly="isReadOnly">
                            </div>
                            <!-- 管理数単位 -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].stock_unit != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.stock_unit" v-bind:class="{ 'diff-form' :tmpPdt.stock_unit != data.stock_unit }" v-bind:readonly="isReadOnly">
                            </div>
                            <!-- 最小単位数 -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].min_quantity != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.min_quantity" readonly v-bind:class="{ 'diff-form' :tmpPdt.min_quantity != data.min_quantity }">
                            </div>
                            <!-- 入り数 -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].quantity_per_case != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.quantity_per_case" v-bind:class="{ 'diff-form' :tmpPdt.quantity_per_case != data.quantity_per_case }" v-bind:readonly="isReadOnly">
                            </div>
                            <!-- ロット数 -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].order_lot_quantity != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.order_lot_quantity" readonly v-bind:class="{ 'diff-form' :tmpPdt.order_lot_quantity != data.order_lot_quantity }">
                            </div>
                            <!-- リードタイム -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].lead_time != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.lead_time" v-bind:class="{ 'diff-form' :tmpPdt.lead_time != data.lead_time }" v-bind:readonly="isReadOnly">
                            </div>
                            <!-- 標準仕入掛率 -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].purchase_makeup_rate != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.purchase_makeup_rate" v-bind:class="{ 'diff-form' :tmpPdt.purchase_makeup_rate != data.purchase_makeup_rate }" v-bind:readonly="isReadOnly">
                            </div>
                            <!-- 標準仕入単価 -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].normal_purchase_price != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.normal_purchase_price" v-bind:class="{ 'diff-form' :tmpPdt.normal_purchase_price != data.normal_purchase_price }" v-bind:readonly="isReadOnly">
                            </div>
                            <!-- 標準販売掛率 -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].sales_makeup_rate != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.sales_makeup_rate" v-bind:class="{ 'diff-form' :tmpPdt.sales_makeup_rate != data.sales_makeup_rate }" v-bind:readonly="isReadOnly">
                            </div>
                            <!-- 標準販売単価 -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].normal_sales_price != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.normal_sales_price" v-bind:class="{ 'diff-form' :tmpPdt.normal_sales_price != data.normal_sales_price }" v-bind:readonly="isReadOnly">
                            </div>
                            <!-- 標準粗利率 -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].normal_gross_profit_rate != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.normal_gross_profit_rate" v-bind:class="{ 'diff-form' :tmpPdt.normal_gross_profit_rate != data.normal_gross_profit_rate }" v-bind:readonly="isReadOnly">
                            </div>
                            <!-- 税種別 -->
                            <div class="product-body col-sm-12" v-bind:class="{  'has-error': (simErrors[index].tax_type != ''), 'diff-form' :rmUndefinedZero(tmpInputObj.tax_type.selectedValue) != rmUndefinedZero(similarPdt[index].tax_type) }" style="padding:0px;">
                                <div v-bind:id="'acTtype-' + index" class="wj-product-complete form-control"></div>
                                <!-- <wj-auto-complete class="wj-product-complete" v-bind:id="index"
                                    search-member-path="value_text_1"
                                    display-member-path="value_text_1"
                                    selected-value-path="value_code"
                                    :selected-index="-1"
                                    :is-required="false"
                                    :selected-value="data.tax_type"
                                    :initialized="initTaxTypeByIndex"
                                    :max-items="taxtypelist.length"
                                    :items-source="taxtypelist">
                                </wj-auto-complete> -->
                            </div>

                            <!-- 適用開始日 -->
                            <div class="product-body col-sm-12" v-bind:class="{  'has-error': (simErrors[index].start_date != ''), 'diff-form' :rmUndefinedBlank(tmpInputObj.start_date.text) != rmUndefinedBlank(similarPdt[index].start_date) }" style="padding:0px; margin-top: 5px;">
                                <div v-bind:id="'acSdate-' + index" class="wj-product-complete form-control"></div>
                                <!-- <wj-input-date class="wj-product-complete" v-bind:id="index"
                                    :value="data.start_date"
                                    :isRequired=false
                                    :initialized="initStartDateByIndex"
                                ></wj-input-date> -->
                            </div>
                            <!-- 適用終了日 -->
                            <div class="product-body col-sm-12" v-bind:class="{ 'has-error': (simErrors[index].end_date != ''), 'diff-form' :rmUndefinedBlank(tmpInputObj.end_date.text) != rmUndefinedBlank(similarPdt[index].end_date) }" style="padding:0px;">
                                <div v-bind:id="'acEdate-' + index" class="wj-product-complete form-control"></div>
                                <!-- <wj-input-date class="wj-product-complete" v-bind:id="index"
                                    :value="data.end_date"
                                    :isRequired=false
                                    :initialized="initEndDateByIndex"
                                ></wj-input-date> -->
                            </div>
                            <!-- 保障期間 -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].warranty_term != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.warranty_term" v-bind:class="{ 'diff-form' :tmpPdt.warranty_term != data.warranty_term }" v-bind:readonly="isReadOnly">
                            </div>
                            <!-- 住宅履歴 -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].housing_history_transfer_kbn != '') }">
                                <div v-bind:id="'acHouse-' + index" class="wj-product-complete form-control"></div>
                                <!-- <input type="text" class="wj-product-complete form-control" v-model="data.housing_history_transfer_kbn" v-bind:class="{ 'diff-form' :tmpPdt.housing_history_transfer_kbn != data.housing_history_transfer_kbn }"> -->
                            </div>
                            <!-- 後継商品コード -->
                            <div class="product-body col-sm-12" v-bind:class="{ 'diff-form' :tmpPdt.new_product_id != data.new_product_id }" style="padding:0px;">
                                <!-- <div v-show="rmUndefinedZero(data.new_product_id) == 0"> -->
                                    <div class="col-md-2">
                                        <button type="button" v-bind:disabled="isReadOnly" class="btn btn-search" @click="simToDraftProductCode(index)">←</button>
                                    </div>
                                    <div class="col-md-8 btn-group">
                                        <!-- <el-button type="danger" icon="el-icon-close" circle size="mini" @click="delNewProductId(index)" v-bind:disabled="isReadOnly" v-show="data.new_product_id > 0"></el-button> -->
                                        <input type="text" class="wj-product-complete form-control" style="border: #FFCC00 !important" v-bind:class="{ 'diff-form' :tmpPdt.new_product_id != data.new_product_id }" v-model="data.new_product_code"  readonly>
                                        <span class="searchclear glyphicon glyphicon-remove-circle" @click="delNewProductId(index)" v-bind:disabled="isReadOnly" v-show="data.new_product_id > 0"></span>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" v-bind:disabled="isReadOnly" class="btn btn-search" @click="draftToSimProductCode(index)">→</button>
                                    </div>
                                <!-- </div> -->
                                <!-- <div v-show="rmUndefinedZero(data.new_product_id) != 0" v-bind:class="{ 'has-error': (simErrors[index].new_product_id != '') }"> -->
                                <!-- </div> -->
                            </div>
                            <!-- 備考 -->
                            <div class="product-body col-sm-12" style="padding:0px; height: 80px;" v-bind:class="{ 'has-error': (simErrors[index].memo != '') }">
                                <textarea class="wj-product-complete form-control" rows="10" style="height: 80px;" v-model="data.memo" v-bind:class="{ 'diff-form' :tmpPdt.memo != data.memo }" v-bind:readonly="isReadOnly"></textarea>
                            </div>
                            <!-- 登録者 -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].created_staff != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.created_staff" v-bind:readonly="true">
                            </div>
                            <!-- 案件番号 -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].matter_no != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.matter_no" v-bind:readonly="true">
                            </div>
                            <!-- 案件名 -->
                            <div class="product-body col-sm-12" style="padding:0px;" v-bind:class="{ 'has-error': (simErrors[index].matter_name != '') }">
                                <input type="text" class="wj-product-complete form-control" v-model="data.matter_name" v-bind:readonly="true">
                            </div>

                            <div class="col-md-12">
                                <div class="text-left col-md-6">
                                    <button type="button" class="btn btn-search" v-bind:class="{ 'active-btn': data.isUpdate }" @click="onUpdate(index)" v-bind:disabled="isReadOnly">マスタ修正</button>
                                </div>
                                <div class="text-right col-md-6">
                                    <button type="button" class="btn btn-search" v-bind:class="{ 'active-btn': data.isIntegrate }" @click="onIntegrate(index)" v-bind:disabled="isReadOnly">統合</button>
                                </div>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                    </div>  
                </div>
            </div>
            <div class="text-right">
                <button type="button" class="btn btn-primary btn-back" v-on:click="backProduct">&nbsp;戻る&nbsp;</button>
                <button type="button" class="btn btn-primary btn-save" v-bind:disabled="(!isEditable || isReadOnly)" v-on:click="nextProduct">&nbsp;次&nbsp;</button>
            </div>
        </div>

        <!-- 検索ポップアップ -->
        <el-dialog title="検索ポップアップ" :visible.sync="showDlgSearch" :closeOnClickModal=false>
            <div class="row">
                <div class="col-md-4">
                    <label class="col-md-12 col-sm-12 col-xs-12">工事区分</label>
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <wj-auto-complete class="form-control"
                            search-member-path="construction_name"
                            display-member-path="construction_name"
                            selected-value-path="id"
                            :selected-index="-1"
                            :is-required="false"
                            :selected-value="searchDlgParams.construction_name"
                            :initialized="initDlgConst"
                            :max-items="constlist.length"
                            :items-source="constlist">
                        </wj-auto-complete>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="col-md-12 col-sm-12 col-xs-12">大分類</label>
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <wj-auto-complete class="form-control"
                            search-member-path="class_big_name"
                            display-member-path="class_big_name"
                            selected-value-path="id"
                            :selected-index="-1"
                            :is-required="false"
                            :selected-value="searchDlgParams.class_big_name"
                            :initialized="initDlgBig"
                            :max-items="classbiglist.length"
                            :items-source="classbiglist">
                        </wj-auto-complete>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="col-md-12 col-sm-12 col-xs-12">メーカー</label>
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <wj-auto-complete class="form-control"
                            search-member-path="supplier_name"
                            display-member-path="supplier_name"
                            selected-value-path="id"
                            :selected-index="-1"
                            :is-required="false"
                            :selected-value="searchDlgParams.supplier_name"
                            :initialized="initDlgMaker"
                            :max-items="makerlist.length"
                            :items-source="makerlist">
                        </wj-auto-complete>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <label class="col-md-12 col-sm-12 col-xs-12">商品番号</label>
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <input type="text" class="form-control" v-model="searchDlgParams.product_code">
                        <!-- <wj-auto-complete class="form-control"
                            search-member-path="product_code"
                            display-member-path="product_code"
                            selected-value-path="product_code"
                            :selected-index="-1"
                            :is-required="false"
                            :selected-value="searchDlgParams.product_code"
                            :initialized="initDlgPCode"
                            :max-items="dlgproductlist.length"
                            :items-source="dlgproductlist">
                        </wj-auto-complete> -->
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="col-md-12 col-sm-12 col-xs-12">商品名</label>
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <input type="text" class="form-control" v-model="searchDlgParams.product_name">
                        <!-- <wj-auto-complete class="form-control"
                            search-member-path="product_name"
                            display-member-path="product_name"
                            selected-value-path="product_name"
                            :selected-index="-1"
                            :is-required="false"
                            :selected-value="searchDlgParams.product_name"
                            :initialized="initDlgPName"
                            :max-items="dlgproductlist.length"
                            :items-source="dlgproductlist">
                        </wj-auto-complete> -->
                    </div>
                </div>
                <div class="text-right col-md-12 col-sm-12 col-xs-12">
                    <el-button @click="searchDlg" class="btn btn-search" style="margin-top: 30px;">検索</el-button>
                </div>
            </div>

            <div class="row">
                <!-- グリッド -->
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <!-- <div id="wjProductGrid"></div> -->
                    <wj-multi-row
                        :itemsSource="product"
                        :layoutDefinition="layoutDefinition"
                        :initialized="initProductGrid"
                        :itemFormatter="itemFormatter"
                    ></wj-multi-row>
                </div>
            </div>

            <div class="row">
                <div class="text-right col-md-12 col-sm-12 col-xs-12">
                    <span slot="footer" class="dialog-footer">
                        <el-button @click="closeDlgSearch" class="btn-create">キャンセル</el-button>
                    </span>
                </div>
            </div>
        </el-dialog>
    </div>
</template>

<script>
import * as wjCore from '@grapecity/wijmo';
import * as wjcInput from '@grapecity/wijmo.input';
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjMultiRow from '@grapecity/wijmo.grid.multirow';

export default {
    data: () => ({
        loading: false,
        isLocked: false,
        isShowEditBtn: true,
        // isEditable: true,
        isReadOnly: true,
        isOwnLock: 0,
        lockInfo: {
            lock_type: '',
            key_ids: [],
        },

        FLG_ON: 1,
        FLG_OFF: 0,
        OPEN_PRICE_TEXT: {
            ON: 'はい',
            OFF: 'いいえ',
        },
        INIT_MATTER_ID: 0,
        INIT_MATTER_NO_TEXT: '(本登録)',
        INIT_MATTER_NAME_TEXT: '',

        isSaveDisp: false,
        isOnceDisp: false,

        showDlgSearch: false,

        layoutDefinition: null,
        keepDOM: {},
        product: new wjCore.CollectionView(),
        wjProductGrid: null,
        gridSetting: {
            deny_resizing_col: [],

            invisible_col: [8],
        },
        gridPKCol: 8,

        acColumns: {
            colName: [
                'class_small_id_1',
                'class_small_id_2',
                'class_small_id_3',
                'construction_id_1',
                'construction_id_2',
                'construction_id_3',
                'class_big_id',
                'class_middle_id',
                'maker_id',
                'tree_species',
                'tax_type',
                'start_date',
                'end_date',
            ],
            colDivId: [
                '#acSm1-',
                '#acSm2-',
                '#acSm3-',
                '#acCon1-',
                '#acCon2-',
                '#acCon3-',
                '#acBg-',
                '#acMd-',
                '#acMkId-',
                '#acTS-',
                '#acTtype-',
                '#acSdate-',
                '#acEdate-',
            ],
        },

        searchParams: {
            product_code: '',
            product_name: '',
            construction_name: '',
            matter_no: '',
            order_no: '',
            supplier_name: '',
        },
        wjSearchObj: {
            product_code: {},
            product_name: {},
            construction_name: {},
            matter_no: {},
            order_no: {},
            supplier_name: {},

        },

        searchDlgParams: {
            construction_name: '',
            class_big_name: '',
            supplier_name: '',
            product_code: '',
            product_name: '',
        },
        wjDlgSearchObj: {
            construction_name: {},
            class_big_name: {},
            supplier_name: {},
            product_code: {},
            product_name: {},
        },
        
        selectedProductId: 0,

        // focusTarget: 0,
        simTarget: 0,

        // 仮商品（新規商品）
        tmpPdt: {
            id: '',
            class_big_id: '',
            class_middle_id: '',
            class_small_id_1: '',
            class_small_id_2: '',
            class_small_id_3: '',
            construction_id_1: '',
            construction_id_2: '',
            construction_id_3: '',
            draft_flg: '',
            end_date: null,
            grade: 0,
            housing_history_transfer_kbn: '',
            intangible_flg: '',
            lead_time: '',
            length: '',
            maker_id: '',
            memo: '',
            min_quantity: '',
            model: '',
            new_product_id: 0,
            normal_gross_profit_rate: '',
            normal_purchase_price: '',
            normal_sales_price: '',
            open_price_flg: '',
            order_lot_quantity: '',
            price: '',
            product_code: '',
            product_name: '',
            product_short_name: '',
            purchase_makeup_rate: '',
            quantity_per_case: '',
            sales_makeup_rate: '',
            set_kbn: '',
            start_date: null,
            stock_unit: '',
            tax_type: '',
            thickness: '',
            tree_species: 0,
            unit: '',
            warranty_term: '',
            weight: '',
            width: '',

            matter_id: '',
            matter_no: '',
            matter_name: '',
            created_staff: '',

            isSave: false,
            isOnce: false,
        },
        tmpInputObj: {
            construction_id_1: {},
            construction_id_2: {},
            construction_id_3: {},
            class_small_id_1: {},
            class_small_id_2: {},
            class_small_id_3: {},
            class_big_id: {},
            class_middle_id: {},
            intangible_flg: {},
            maker_id: {},
            tree_species: {},
            grade: {},
            tax_type: {},
            start_date: {},
            end_date: {},
            housing_history_transfer_kbn: {},
        },

        // 類似商品
        similarPdt: [],
        similarInputObj: [],

        tmpErrors: {
            id: '',
            class_big_id: '',
            class_middle_id: '',
            class_small_id_1: '',
            class_small_id_2: '',
            class_small_id_3: '',
            construction_id_1: '',
            construction_id_2: '',
            construction_id_3: '',
            draft_flg: '',
            end_date: '',
            grade: '',
            housing_history_transfer_kbn: '',
            intangible_flg: '',
            lead_time: '',
            length: '',
            maker_id: '',
            memo: '',
            min_quantity: '',
            model: '',
            new_product_id: '',
            normal_gross_profit_rate: '',
            normal_purchase_price: '',
            normal_sales_price: '',
            open_price_flg: '',
            order_lot_quantity: '',
            price: '',
            product_code: '',
            product_name: '',
            product_short_name: '',
            purchase_makeup_rate: '',
            quantity_per_case: '',
            sales_makeup_rate: '',
            set_kbn: '',
            start_date: '',
            stock_unit: '',
            tax_type: '',
            thickness: '',
            tree_species: '',
            unit: '',
            warranty_term: '',
            weight: '',
            width: '',
            matter_id: '',
            matter_no: '',
            matter_name: '',
            created_staff: '',
        },
        simErrors: [],
        simErrorObj: {
            id: '',
            class_big_id: '',
            class_middle_id: '',
            class_small_id_1: '',
            class_small_id_2: '',
            class_small_id_3: '',
            construction_id_1: '',
            construction_id_2: '',
            construction_id_3: '',
            draft_flg: '',
            end_date: '',
            grade: '',
            housing_history_transfer_kbn: '',
            intangible_flg: '',
            lead_time: '',
            length: '',
            maker_id: '',
            memo: '',
            min_quantity: '',
            model: '',
            new_product_id: '',
            normal_gross_profit_rate: '',
            normal_purchase_price: '',
            normal_sales_price: '',
            open_price_flg: '',
            order_lot_quantity: '',
            price: '',
            product_code: '',
            product_name: '',
            product_short_name: '',
            purchase_makeup_rate: '',
            quantity_per_case: '',
            sales_makeup_rate: '',
            set_kbn: '',
            start_date: '',
            stock_unit: '',
            tax_type: '',
            thickness: '',
            tree_species: '',
            unit: '',
            warranty_term: '',
            weight: '',
            width: '',
            matter_id: '',
            matter_no: '',
            matter_name: '',
            created_staff: '',

            product_locked: '',
        },

        searchErr: {
            product_code: '',
            product_name: '',
        },
    }),
    props: {
        isEditable: Number,
        // isOwnLock: Number,
        lockdata: {},
        // productdata: Array,
        productlist: Array,
        // simlist: Array,
        constlist: Array,
        matterlist: Array,
        orderlist: Array,
        orderdetaillist: Array,
        // supplierlist: Array,
        classbiglist: Array,
        classmidlist: Array,
        classsmalllist: Array,
        // tangiblelist: Array,
        taxtypelist: Array,
        woodlist: Array,
        gradelist: Array,
        // dlgproductlist: Array,
        makerlist: Array,
        housingHistoryTransferKbn: Array,
        noProductCode: {},
    },
    created() {
        // グリッドレイアウトセット
        this.layoutDefinition = this.getLayout();
    },
    mounted() {
        // 仮商品1件目を検索
        // var searchId = 0;
        if (this.productlist.length > 0) {
            this.wjSearchObj.product_code.selectedIndex = 0;
            // searchId = this.wjSearchObj.product_code.selectedValue;
        }

        this.productlist.forEach((element, i) => {
            this.productlist[i].grade = this.rmUndefinedBlank(parseInt(this.productlist[i].grade));
            this.productlist[i].tree_species = this.rmUndefinedBlank(parseInt(this.productlist[i].tree_species));
        });
        this.select(0);
    },
    methods: {       
        changeSimPrice(i) {
            if(this.similarPdt[i].price == 0){
                this.similarPdt[i].open_price_flg = this.FLG_ON;
                this.similarPdt[i].open_price_txt = this.OPEN_PRICE_TEXT.ON;
            } else {
                this.similarPdt[i].open_price_flg = this.FLG_OFF;
                this.similarPdt[i].open_price_txt = this.OPEN_PRICE_TEXT.OFF;
            }
        },
        changePrice() {
            if(this.tmpPdt.price == 0){
                this.tmpPdt.open_price_flg = this.FLG_ON;
                this.tmpPdt.open_price_txt = this.OPEN_PRICE_TEXT.ON;
            } else {
                this.tmpPdt.open_price_flg = this.FLG_OFF;
                this.tmpPdt.open_price_txt = this.OPEN_PRICE_TEXT.OFF;
            }
        },
        changeIdxClassBig(sender) {
            var tmpMid = this.classmidlist;
            if (sender.selectedValue) {
                tmpMid = [];
                for(var key in this.classmidlist) {
                    if (sender.selectedValue == this.classmidlist[key].class_big_id) {
                        tmpMid.push(this.classmidlist[key]);
                    }
                }             
            }

            this.tmpInputObj.class_middle_id.itemsSource = tmpMid;
            this.tmpInputObj.class_middle_id.selectedIndex = -1;

            // var item = sender.selectedItem;
            // var list = [];
            // if(item !== null) {
            //     this.classmidlist.forEach(record => {
            //         if (record.class_big_id == item.id) {
            //             list.push(record);
            //         }
            //     });

            //     this.tmpInputObj.class_middle_id.itemsSource = list;
            //     this.tmpInputObj.class_middle_id.selectedIndex = -1;
            // }else{
            //     this.tmpInputObj.class_middle_id.itemsSource = this.classmidlist;
            //     this.tmpInputObj.class_middle_id.selectedIndex = -1;
            // }
        },
        delPdtNewProductId() {
            var row = this.tmpPdt;

            this.$set(row, 'new_product_id', 0);
            this.$set(row, 'new_product_code', '');
        },
        delNewProductId(index) {
            var row = this.similarPdt[index];

            this.$set(row, 'new_product_id', 0);
            this.$set(row, 'new_product_code', '');
        },
        // 仮商品のメーカーを変更した場合、全統合ボタンを非活性にする
        changedTmpMaker(sender) {
            var item = sender.selectedItem;
            if (item != null) {
                this.similarPdt.forEach((element, i) => {
                    this.similarPdt[i].isIntegrate = false;
                });
            }
        },
        // 類似商品のオートコンプリート生成
        createAutoComplete(divIndex, itemsSrc) {
            var acControl = {};

            var targetId = '#acSm1-'+divIndex;
            acControl.class_small_id_1 = new wjcInput.AutoComplete(targetId, {
                searchMemberPath: 'class_small_name',
                displayMemberPath: 'class_small_name',
                selectedValuePath: 'id',
                selectedIndex: -1,
                isRequired: false,
                isReadOnly: this.isReadOnly,
                // selectedValue: itemsSrc.construction_id_1,
                // selectedIndexChanged: this.selectConstructionByIndex_1,
                maxItems: this.classsmalllist.length,
                itemsSource: this.classsmalllist,
            });
            // selectedValueで指定できなかったので、後からセット
            acControl.class_small_id_1.selectedIndexChanged.addHandler(function (sender) {
                var item = sender.selectedItem;
                var list = [];
                if(item !== null) {
                    this.similarPdt[divIndex].class_small_id_1 = item.id;
                }else{
                    this.similarPdt[divIndex].class_small_id_1 = '';
                }
            }.bind(this));
            
            var targetId = '#acSm2-'+divIndex;
            acControl.class_small_id_2 = new wjcInput.AutoComplete(targetId, {
                searchMemberPath: 'class_small_name',
                displayMemberPath: 'class_small_name',
                selectedValuePath: 'id',
                selectedIndex: -1,
                isRequired: false,
                isReadOnly: this.isReadOnly,
                // selectedValue: itemsSrc.construction_id_1,
                // selectedIndexChanged: this.selectConstructionByIndex_1,
                maxItems: this.classsmalllist.length,
                itemsSource: this.classsmalllist,
            });
            // selectedValueで指定できなかったので、後からセット
            acControl.class_small_id_2.selectedIndexChanged.addHandler(function (sender) {
                var item = sender.selectedItem;
                var list = [];
                if(item !== null) {
                    this.similarPdt[divIndex].class_small_id_2 = item.id;
                }else{
                    this.similarPdt[divIndex].class_small_id_2 = '';
                }
            }.bind(this));
            acControl.class_small_id_2.selectedValue = itemsSrc.class_small_id_2;

            var targetId = '#acSm3-'+divIndex;
            acControl.class_small_id_3 = new wjcInput.AutoComplete(targetId, {
                searchMemberPath: 'class_small_name',
                displayMemberPath: 'class_small_name',
                selectedValuePath: 'id',
                selectedIndex: -1,
                isRequired: false,
                isReadOnly: this.isReadOnly,
                // selectedValue: itemsSrc.construction_id_1,
                // selectedIndexChanged: this.selectConstructionByIndex_1,
                maxItems: this.classsmalllist.length,
                itemsSource: this.classsmalllist,
            });
            // selectedValueで指定できなかったので、後からセット
            acControl.class_small_id_3.selectedIndexChanged.addHandler(function (sender) {
                var item = sender.selectedItem;
                var list = [];
                if(item !== null) {
                    this.similarPdt[divIndex].class_small_id_3 = item.id;
                }else{
                    this.similarPdt[divIndex].class_small_id_3 = '';
                }
            }.bind(this));
            acControl.class_small_id_3.selectedValue = itemsSrc.class_small_id_3;

            
            var targetId = '#acMd-'+divIndex;
            acControl.class_middle_id = new wjcInput.AutoComplete(targetId, {
                searchMemberPath: 'class_middle_name',
                displayMemberPath: 'class_middle_name',
                selectedValuePath: 'id',
                selectedIndex: -1,
                isRequired: false,
                isReadOnly: this.isReadOnly,
                // selectedValue: itemsSrc.construction_id_1,
                // selectedIndexChanged: this.selectConstructionByIndex_1,
                maxItems: this.classmidlist.length,
                itemsSource: this.classmidlist,
            });
            // selectedValueで指定できなかったので、後からセット
            acControl.class_middle_id.selectedIndexChanged.addHandler(function (sender) {
                var item = sender.selectedItem;
                var list = [];
                if(item !== null) {
                    this.similarPdt[divIndex].class_middle_id = item.id;
                }else{
                    this.similarPdt[divIndex].class_middle_id = '';
                }
            }.bind(this));

            var targetId = '#acBg-'+divIndex;
            acControl.class_big_id = new wjcInput.AutoComplete(targetId, {
                searchMemberPath: 'class_big_name',
                displayMemberPath: 'class_big_name',
                selectedValuePath: 'id',
                selectedIndex: -1,
                isRequired: false,
                isReadOnly: this.isReadOnly,
                // selectedValue: itemsSrc.construction_id_1,
                // selectedIndexChanged: this.selectConstructionByIndex_1,
                maxItems: this.classbiglist.length,
                itemsSource: this.classbiglist,
            });
            // selectedValueで指定できなかったので、後からセット
            acControl.class_big_id.selectedIndexChanged.addHandler(function (sender) {
                var tmpMid = this.classmidlist;
                if (sender.selectedValue) {
                    tmpMid = [];
                    for(var key in this.classmidlist) {
                        if (sender.selectedValue == this.classmidlist[key].class_big_id) {
                            tmpMid.push(this.classmidlist[key]);
                        }
                    }             
                }

                acControl.class_middle_id.itemsSource = tmpMid;
                acControl.class_middle_id.selectedIndex = -1;

                // var item = sender.selectedItem;
                // var list = [];
                // if(item !== null) {
                //     this.similarPdt[divIndex].class_big_id = item.id;

                //     this.classmidlist.forEach(record => {
                //         if (record.class_big_id == item.id) {
                //             list.push(record);
                //         }
                //     });
                //     acControl.class_middle_id.itemsSource = list;
                //     acControl.class_middle_id.selectedIndex = -1;
                // }else{
                //     this.similarPdt[divIndex].class_big_id = '';
                //     acControl.class_middle_id.itemsSource = this.classmidlist;
                //     acControl.class_middle_id.selectedIndex = -1;
                // }
            }.bind(this));
            acControl.class_big_id.selectedValue = itemsSrc.class_big_id;
            acControl.class_middle_id.selectedValue = itemsSrc.class_middle_id;

            var targetId = '#acCon1-'+divIndex;
            acControl.construction_id_1 = new wjcInput.AutoComplete(targetId, {
                searchMemberPath: 'construction_name',
                displayMemberPath: 'construction_name',
                selectedValuePath: 'id',
                selectedIndex: -1,
                isRequired: false,
                isReadOnly: this.isReadOnly,
                // selectedValue: itemsSrc.construction_id_1,
                // selectedIndexChanged: this.selectConstructionByIndex_1,
                maxItems: this.constlist.length,
                itemsSource: this.constlist,
            });
            // selectedValueで指定できなかったので、後からセット
            acControl.construction_id_1.selectedIndexChanged.addHandler(function (sender) {
                // var index = sender.hostElement.id;
                // 工事区分を選択したら工程を絞り込む
                var item = sender.selectedItem;
                var list = [];
                if(item !== null) {
                    this.similarPdt[divIndex].construction_id_1 = item.id;
                    this.classsmalllist.forEach(record => {
                        if (record.construction_id == item.id) {
                            list.push(record);
                        }
                    });
                    acControl.class_small_id_1.itemsSource = list;
                    acControl.class_small_id_1.selectedIndex = -1;
                }else{
                    this.similarPdt[divIndex].construction_id_1 = '';
                    acControl.class_small_id_1.itemsSource = this.classsmalllist;
                    acControl.class_small_id_1.selectedIndex = -1;
                }
            }.bind(this));
            acControl.construction_id_1.selectedValue = itemsSrc.construction_id_1;
            acControl.class_small_id_1.selectedValue = itemsSrc.class_small_id_1;
            // acControl.construction_id_1.onSelectedIndexChanged();

            var targetId = '#acCon2-'+divIndex;
            acControl.construction_id_2 = new wjcInput.AutoComplete(targetId, {
                searchMemberPath: 'construction_name',
                displayMemberPath: 'construction_name',
                selectedValuePath: 'id',
                selectedIndex: -1,
                isRequired: false,
                isReadOnly: this.isReadOnly,
                // selectedValue: itemsSrc.construction_id_1,
                // selectedIndexChanged: this.selectConstructionByIndex_1,
                maxItems: this.constlist.length,
                itemsSource: this.constlist,
            });
            // selectedValueで指定できなかったので、後からセット
            acControl.construction_id_2.selectedIndexChanged.addHandler(function (sender) {
                // var index = sender.hostElement.id;
                // 工事区分を選択したら工程を絞り込む
                var item = sender.selectedItem;
                var list = [];
                if(item !== null) {
                    this.similarPdt[divIndex].construction_id_2 = item.id;
                    this.classsmalllist.forEach(record => {
                        if (record.construction_id == item.id) {
                            list.push(record);
                        }
                    });
                    acControl.class_small_id_2.itemsSource = list;
                    acControl.class_small_id_2.selectedIndex = -1;
                }else{
                    this.similarPdt[divIndex].construction_id_2 = '';
                    acControl.class_small_id_2.itemsSource = this.classsmalllist;
                    acControl.class_small_id_2.selectedIndex = -1;
                }
            }.bind(this));
            acControl.construction_id_2.selectedValue = itemsSrc.construction_id_2;
            acControl.class_small_id_2.selectedValue = itemsSrc.class_small_id_2;
            // acControl.construction_id_2.onSelectedIndexChanged();

            var targetId = '#acCon3-'+divIndex;
            acControl.construction_id_3 = new wjcInput.AutoComplete(targetId, {
                searchMemberPath: 'construction_name',
                displayMemberPath: 'construction_name',
                selectedValuePath: 'id',
                selectedIndex: -1,
                isRequired: false,
                isReadOnly: this.isReadOnly,
                // selectedValue: itemsSrc.construction_id_1,
                // selectedIndexChanged: this.selectConstructionByIndex_1,
                maxItems: this.constlist.length,
                itemsSource: this.constlist,
            });
            // selectedValueで指定できなかったので、後からセット
            acControl.construction_id_3.selectedIndexChanged.addHandler(function (sender) {
                // var index = sender.hostElement.id;
                // 工事区分を選択したら工程を絞り込む
                var item = sender.selectedItem;
                var list = [];
                if(item !== null) {
                    this.similarPdt[divIndex].construction_id_3 = item.id;
                    this.classsmalllist.forEach(record => {
                        if (record.construction_id == item.id) {
                            list.push(record);
                        }
                    });
                    acControl.class_small_id_3.itemsSource = list;
                    acControl.class_small_id_3.selectedIndex = -1;
                }else{
                    this.similarPdt[divIndex].construction_id_3 = '';
                    acControl.class_small_id_3.itemsSource = this.classsmalllist;
                    acControl.class_small_id_3.selectedIndex = -1;
                }
            }.bind(this));
            acControl.construction_id_3.selectedValue = itemsSrc.construction_id_3;
            acControl.class_small_id_3.selectedValue = itemsSrc.class_small_id_3;
            // acControl.construction_id_3.onSelectedIndexChanged();

            var targetId = '#acMkId-'+divIndex;
            acControl.maker_id = new wjcInput.AutoComplete(targetId, {
                searchMemberPath: 'supplier_name',
                displayMemberPath: 'supplier_name',
                selectedValuePath: 'id',
                selectedIndex: -1,
                isRequired: false,
                isReadOnly: this.isReadOnly,
                // selectedValue: itemsSrc.construction_id_1,
                // selectedIndexChanged: this.selectConstructionByIndex_1,
                maxItems: this.makerlist.length,
                itemsSource: this.makerlist,
            });
            // selectedValueで指定できなかったので、後からセット
            acControl.maker_id.selectedIndexChanged.addHandler(function (sender) {
                var item = sender.selectedItem;
                var list = [];
                if(item !== null) {
                    this.similarPdt[divIndex].maker_id = item.id;
                }else{
                    this.similarPdt[divIndex].maker_id = '';
                }
            }.bind(this));
            acControl.maker_id.selectedValue = itemsSrc.maker_id;

            var targetId = '#acHouse-'+divIndex;
            acControl.housing_history_transfer_kbn = new wjcInput.AutoComplete(targetId, {
                searchMemberPath: 'text',
                displayMemberPath: 'text',
                selectedValuePath: 'value',
                selectedIndex: -1,
                isRequired: false,
                isReadOnly: this.isReadOnly,
                // selectedValue: itemsSrc.construction_id_1,
                // selectedIndexChanged: this.selectConstructionByIndex_1,
                maxItems: this.housingHistoryTransferKbn.length,
                itemsSource: this.housingHistoryTransferKbn,
            });
            // selectedValueで指定できなかったので、後からセット
            acControl.housing_history_transfer_kbn.selectedIndexChanged.addHandler(function (sender) {
                var item = sender.selectedItem;
                var list = [];
                if(item !== null) {
                    this.similarPdt[divIndex].housing_history_transfer_kbn = item.value;
                }else{
                    this.similarPdt[divIndex].housing_history_transfer_kbn = '';
                }
            }.bind(this));
            acControl.housing_history_transfer_kbn.selectedValue = this.rmUndefinedBlank(parseInt(itemsSrc.housing_history_transfer_kbn));

            var targetId = '#acTS-'+divIndex;
            acControl.tree_species = new wjcInput.AutoComplete(targetId, {
                searchMemberPath: 'value_text_1',
                displayMemberPath: 'value_text_1',
                selectedValuePath: 'value_code',
                selectedIndex: -1,
                isRequired: false,
                isReadOnly: this.isReadOnly,
                // selectedValue: itemsSrc.construction_id_1,
                // selectedIndexChanged: this.selectConstructionByIndex_1,
                maxItems: this.woodlist.length,
                itemsSource: this.woodlist,
            });
            // selectedValueで指定できなかったので、後からセット
            acControl.tree_species.selectedIndexChanged.addHandler(function (sender) {
                var item = sender.selectedItem;
                var list = [];
                if(item !== null) {
                    this.similarPdt[divIndex].tree_species = item.value_code;
                }else{
                    this.similarPdt[divIndex].tree_species = '';
                }
            }.bind(this));
            acControl.tree_species.selectedValue = this.rmUndefinedBlank(parseInt(itemsSrc.tree_species));

            var targetId = '#acGr-'+divIndex;
            acControl.grade = new wjcInput.AutoComplete(targetId, {
                searchMemberPath: 'value_text_1',
                displayMemberPath: 'value_text_1',
                selectedValuePath: 'value_code',
                selectedIndex: -1,
                isRequired: false,
                isReadOnly: this.isReadOnly,
                // selectedValue: itemsSrc.construction_id_1,
                // selectedIndexChanged: this.selectConstructionByIndex_1,
                maxItems: this.gradelist.length,
                itemsSource: this.gradelist,
            });
            // selectedValueで指定できなかったので、後からセット
            acControl.grade.selectedIndexChanged.addHandler(function (sender) {
                var item = sender.selectedItem;
                var list = [];
                if(item !== null) {
                    this.similarPdt[divIndex].grade = item.value_code;
                }else{
                    this.similarPdt[divIndex].grade = '';
                }
            }.bind(this));
            acControl.grade.selectedValue = this.rmUndefinedBlank(parseInt(itemsSrc.grade));

            var targetId = '#acTtype-'+divIndex;
            acControl.tax_type = new wjcInput.AutoComplete(targetId, {
                searchMemberPath: 'value_text_1',
                displayMemberPath: 'value_text_1',
                selectedValuePath: 'value_code',
                selectedIndex: -1,
                isRequired: false,
                isReadOnly: this.isReadOnly,
                // selectedValue: itemsSrc.construction_id_1,
                // selectedIndexChanged: this.selectConstructionByIndex_1,
                maxItems: this.taxtypelist.length,
                itemsSource: this.taxtypelist,
            });
            // selectedValueで指定できなかったので、後からセット
            acControl.tax_type.selectedIndexChanged.addHandler(function (sender) {
                var item = sender.selectedItem;
                var list = [];
                if(item !== null) {
                    this.similarPdt[divIndex].tax_type = item.id;
                }else{
                    this.similarPdt[divIndex].tax_type = '';
                }
            }.bind(this));
            acControl.tax_type.selectedValue = this.rmUndefinedBlank(parseInt(itemsSrc.tax_type));

            var targetId = '#acSdate-'+divIndex;
            acControl.start_date = new wjcInput.InputDate(targetId, {
                // value: null,
                isReadOnly: this.isReadOnly,
                isRequired: false,
            });
            acControl.start_date.valueChanged.addHandler(function (sender) {
                var item = sender.text;
                if(item !== null) {
                    this.similarPdt[divIndex].start_date = item;
                }else{
                    this.similarPdt[divIndex].start_date = '';
                }
            }.bind(this));
            // selectedValueで指定できなかったので、後からセット
            if (itemsSrc.start_date != undefined || itemsSrc.start_date != null) {
                acControl.start_date.value = itemsSrc.start_date;
            } else {
                acControl.start_date.value = null;
            }

            var targetId = '#acEdate-'+divIndex;
            acControl.end_date = new wjcInput.InputDate(targetId, {
                // value: null,
                isReadOnly: this.isReadOnly,
                isRequired: false,
            });
            acControl.end_date.valueChanged.addHandler(function (sender) {
                var item = sender.text;
                if(item !== null) {
                    this.similarPdt[divIndex].end_date = item;
                }else{
                    this.similarPdt[divIndex].end_date = '';
                }
            }.bind(this));
            // selectedValueで指定できなかったので、後からセット
            if (itemsSrc.end_date != undefined || itemsSrc.end_date != null) {
                acControl.end_date.value = itemsSrc.end_date;
            } else {
                acControl.end_date.value = null;
            }

            return acControl;
        },
        // 画面の編集モードを判定
        changeEditMode(){
            if (this.isEditable == FLG_EDITABLE) {
                // 編集可
                this.isShowEditBtn = true;
                if (this.tmpPdt.id == null) {
                    this.isShowEditBtn = false;
                    this.isReadOnly = false;
                }
                // ロック中判定
                if (this.rmUndefinedZero(this.lockdata.id) != 0 && this.isOwnLock != 1) {
                    this.isLocked = true;
                    this.isShowEditBtn = false;
                    this.isReadOnly = true;
                } else {
                    this.isLocked = false;
                    this.isShowEditBtn = false;
                }
            }

            if(this.isLocked) {
                // ロック中
                this.isShowEditBtn = false;
                this.isReadOnly = true;
            }else {
                // 編集モードで開くか判定
                var query = window.location.search;
                if (this.isOwnLock == 1 || this.isEditMode(query, this.isReadOnly, this.isEditable)) {
                    this.isReadOnly = false
                    this.isShowEditBtn = false
                }
            }
            
            if (this.isReadOnly) {
                // 照会モードの場合
                window.onbeforeunload = null;
            } else {
                window.onbeforeunload = function(e) {
                    return MSG_CONFIRM_LEAVE;
                };
            }
        },
        itemFormatter(panel, r, c, cell) {
            var dataItem = panel.rows[r].dataItem;
            if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                // 列ヘッダ中央寄せ
                cell.style.textAlign = 'center';
            } else if (panel.cellType == wjGrid.CellType.Cell) {
                // this.setGridCellReadOnlyColor(gridCtrl, r, c, cell);
                var col = panel.columns[c];
                switch(col.name) {
                    case 'select':
                        cell.appendChild(this.keepDOM[this.wjProductGrid.getCellData(r, this.gridPKCol)].select_btn);
                        break;
                }
            }
        },
        initProductGrid: function(multirow) {
            // 行高さ
            multirow.rows.defaultSize = 30;
            // 設定更新
            multirow = this.applyGridSettings(multirow);
            // セルを押下してもカーソルがあたらないように変更
            // multirow.selectionMode = wjGrid.SelectionMode.None;

            multirow.headersVisibility = wjGrid.HeadersVisibility.Column

            this.wjProductGrid = multirow;
        },
        searchDlg() {
            this.loading = true;

            var params = new URLSearchParams();

            params.append('construction_id', this.rmUndefinedBlank(this.wjDlgSearchObj.construction_name.selectedValue));
            params.append('class_big_id', this.rmUndefinedBlank(this.wjDlgSearchObj.class_big_name.selectedValue));
            params.append('maker_id', this.rmUndefinedBlank(this.wjDlgSearchObj.supplier_name.selectedValue));
            params.append('product_code', this.rmUndefinedBlank(this.searchDlgParams.product_code));
            params.append('product_name', this.rmUndefinedBlank(this.searchDlgParams.product_name));

            axios.post('/product-check/search', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    var itemsSource = [];
                    var rowIdx = 0;
                    var dataLength = 0;
                    response.data.forEach(element => {
                        // DOM生成
                        // itemFormatterでDOM要素が消えないようにする為、行ごとに生成するDOMを保存しておく必要がある
                        this.keepDOM[element.id] = {
                            select_btn: document.createElement('div'),
                        }
                        var _this = this;
                        // 決定ボタン
                        this.keepDOM[element.id].select_btn.innerHTML = '<button data-id=' + JSON.stringify(element.id) + ' class="btn btn-search btn-select">決定</button>';
                        this.keepDOM[element.id].select_btn.addEventListener('click', function(e) {
                            if (e.target.dataset.id)
                            var data = JSON.parse(e.target.dataset.id);
                            _this.selectProduct(data);
                        }) 

                        itemsSource.push({
                            // itemFormatterでDOM要素を書き換えてもフィルター機能でヒットするのはitemsSourceにセットした時の値
                            class_big_name: element.class_big_name,
                            construction_name_1: element.construction_name_1,
                            construction_name_2: element.construction_name_2,
                            construction_name_3: element.construction_name_3,
                            product_code: element.product_code,
                            product_name: element.product_name,
                            supplier_name: element.supplier_name,
                            id: element.id,
                        })

                        dataLength++;
                    });
                    // データセット
                    this.wjProductGrid.itemsSource = itemsSource;
                    // this.tableData = dataLength;

                    // 設定更新
                    this.wjProductGrid = this.applyGridSettings(this.wjProductGrid);

                    // 描画更新
                    this.wjProductGrid.refresh();

                } else {
                    // 失敗
                    // window.onbeforeunload = null;
                    // location.reload()
                }
            }.bind(this))

            .catch(function (error) {
                 if (error.response.data.errors) {
                    this.loading = false
                    // エラーメッセージ表示
                    this.showErrMsg(error.response.data.errors, this.errors)
                } else {
                    if (error.response.data.message) {
                        alert(error.response.data.message)
                    } else {
                        alert(MSG_ERROR)
                    }
                    window.onbeforeunload = null;
                    // location.reload()
                }
                // this.loading = false
            }.bind(this))
        },
        // ポップアップ　決定ボタン
        selectProduct(id) {
            this.loading = true;

            var isSame = false;
            this.similarPdt.forEach(element => {
                if (element.id == id) {
                    isSame = true;
                }
            });
            if (isSame) {
                alert(MSG_ERROR_SAME_CHECK_PRODUCT)
                this.loading = false;
                return;
            }

            var params = new URLSearchParams();
            params.append('id', this.rmUndefinedZero(id));

            axios.post('/product-check/getInfo', params)
            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    var index = this.simTarget;
                    var data = response.data;
                    data.isUpdate = false;
                    data.isIntegrate = false;

                    var tmpObj = {
                        construction_id_1: {},
                        construction_id_2: {},
                        construction_id_3: {},
                        class_small_id_1: {},
                        class_small_id_2: {},
                        class_small_id_3: {},
                        class_big_id: {},
                        class_middle_id: {},
                        intangible_flg: {},
                        maker_id: {},
                        tree_species: {},
                        grade: {},
                        tax_type: {},
                        start_date: {},
                        end_date: {},
                        housing_history_transfer_kbn: {},
                    };

                    if (data.price == 0) {
                        data.open_price_flg = this.FLG_ON;
                        data.open_price_txt = this.OPEN_PRICE_TEXT.ON;
                    } else {
                        data.open_price_flg = this.FLG_OFF;
                        data.open_price_txt = this.OPEN_PRICE_TEXT.OFF;
                    }
                    if (data.draft_flg == this.FLG_OFF && data.auto_flg == this.FLG_OFF) {
                        // 本登録商品の場合は案件番号、案件名を書き換える
                        data.matter_id = this.INIT_MATTER_ID;
                        data.matter_no = this.INIT_MATTER_NO_TEXT;
                        data.matter_name = this.INIT_MATTER_NAME_TEXT;
                    }
                    var pdt = {
                        id: data.id,
                        isUpdate: false,
                        isIntegrate: false,
                        class_big_id: data.class_big_id,
                        class_middle_id: data.class_middle_id,
                        class_small_id_1: data.class_small_id_1,
                        class_small_id_2: data.class_small_id_2,
                        class_small_id_3: data.class_small_id_3,
                        construction_id_1: data.construction_id_1,
                        construction_id_2: data.construction_id_2,
                        construction_id_3: data.construction_id_3,
                        draft_flg: data.draft_flg,
                        auto_flg: data.auto_flg,
                        end_date: data.end_date,
                        grade: data.grade,
                        housing_history_transfer_kbn: data.housing_history_transfer_kbn,
                        intangible_flg: data.intangible_flg,
                        intangible_flg_txt: data.intangible_flg_txt,
                        lead_time: data.lead_time,
                        length: data.length,
                        maker_id: data.maker_id,
                        memo: data.memo,
                        min_quantity: data.min_quantity,
                        model: data.model,
                        new_product_id: data.new_product_id,
                        new_product_code: data.new_product_code,
                        normal_gross_profit_rate: data.normal_gross_profit_rate,
                        normal_purchase_price: data.normal_purchase_price,
                        normal_sales_price: data.normal_sales_price,
                        open_price_flg: data.open_price_flg,
                        open_price_txt: data.open_price_txt,
                        order_lot_quantity: data.order_lot_quantity,
                        price: data.price,
                        product_code: data.product_code,
                        product_name: data.product_name,
                        product_short_name: data.product_short_name,
                        purchase_makeup_rate: data.purchase_makeup_rate,
                        quantity_per_case: data.quantity_per_case,
                        sales_makeup_rate: data.sales_makeup_rate,
                        set_kbn: data.set_kbn,
                        start_date: data.start_date,
                        stock_unit: data.stock_unit,
                        tax_type: data.tax_type,
                        thickness: data.thickness,
                        tree_species: data.tree_species,
                        unit: data.unit,
                        warranty_term: data.warranty_term,
                        weight: data.weight,
                        width: data.width,
                        update_at: data.update_at,
                        update_user: data.update_user,

                        matter_id: data.matter_id,
                        matter_no: data.matter_no,
                        matter_name: data.matter_name,
                        created_staff: data.created_staff,
                    };
                    var err = Object.assign({}, this.simErrorObj);

                    if (index < this.similarPdt.length) {
                        // 検索
                        this.similarPdt[index] = pdt;
                        this.simErrors[index] = err;
                        
                        this.similarInputObj[index].construction_id_1.dispose();                        
                        this.similarInputObj[index].construction_id_2.dispose();                        
                        this.similarInputObj[index].construction_id_3.dispose();                
                        this.similarInputObj[index].class_small_id_1.dispose();                        
                        this.similarInputObj[index].class_small_id_2.dispose();                        
                        this.similarInputObj[index].class_small_id_3.dispose();                        
                        this.similarInputObj[index].class_big_id.dispose();                        
                        this.similarInputObj[index].class_middle_id.dispose();                        
                        // this.similarInputObj[i].intangible_flg.dispose();                        
                        this.similarInputObj[index].maker_id.dispose();                        
                        this.similarInputObj[index].tree_species.dispose();                        
                        this.similarInputObj[index].grade.dispose();                        
                        this.similarInputObj[index].tax_type.dispose();                        
                        this.similarInputObj[index].start_date.dispose();                        
                        this.similarInputObj[index].end_date.dispose();                        
                        this.similarInputObj[index].housing_history_transfer_kbn.dispose();

                        this.similarInputObj[index] = tmpObj;
                    } else {
                        // 追加
                        this.similarInputObj.push(tmpObj);
                        this.similarPdt.push(pdt);
                        this.simErrors.push(err);
                    }

                    // this.similarPdt[index] = data;

                    setTimeout(function() {
                        this.similarInputObj[index] = this.createAutoComplete(index, data);
                                
                    }.bind(this), 800);

                    this.showDlgSearch = false;

                } else {
                    // 失敗
                    // window.onbeforeunload = null;
                    // location.reload()
                }
            }.bind(this))

            .catch(function (error) {
                 if (error.response.data.errors) {
                    this.loading = false
                    // エラーメッセージ表示
                    this.showErrMsg(error.response.data.errors, this.errors)
                } else {
                    if (error.response.data.message) {
                        alert(error.response.data.message)
                    } else {
                        alert(MSG_ERROR)
                    }
                    window.onbeforeunload = null;
                    // location.reload()
                }
                // this.loading = false
            }.bind(this))
        },
        // 類似商品検索 
        productSearch(index){
            this.simTarget = index;
            this.showDlgSearch = true;

        },
        closeDlgSearch() {
            var len = this.similarPdt.length -1;
            if (this.similarPdt.length > 0 && this.rmUndefinedZero(this.similarPdt[len].id) == 0) {
                this.similarInputObj.pop();
                this.similarPdt.pop();
                this.simErrors.pop();
            }
            this.showDlgSearch = false;
        },
        // 類似商品追加
        addProduct() {
            var index = this.similarPdt.length;
            this.productSearch(index);
        },
        // 仮商品選択
        select(id) {
            this.loading = true;
            this.searchErr.product_code = '';
            this.searchErr.product_name = '';

            var params = new URLSearchParams();
            var okFlg = true;
            if (id > 0) {
                params.append('id', id);
            } else {
                var item = this.wjSearchObj.product_code.selectedItem;
                if (item == null) {
                    okFlg = false;
                    if (this.productlist.length != 0) {
                        this.searchErr.product_code = MSG_ERROR_NO_SELECT;
                        this.searchErr.product_name = MSG_ERROR_NO_SELECT;
                    }
                } else {
                    var id = item.id;
                    params.append('id', this.rmUndefinedZero(id));
                }
            }

            if (!okFlg) {
                this.loading = false;
                return;
            }

            params.append('lock_info', JSON.stringify(this.lockInfo));

            axios.post('/product-check/select', params)

            .then( function (response) {
                this.loading = false

                if (response.data.resultStatus) {
                    // 成功
                    this.productlist.forEach((element) => {
                        if (element.draft_flg == this.FLG_OFF && element.auto_flg == this.FLG_OFF) {
                            // 本登録商品の場合は案件番号、案件名を書き換える
                            element.matter_id = this.INIT_MATTER_ID;
                            element.matter_no = this.INIT_MATTER_NO_TEXT;
                            element.matter_name = this.INIT_MATTER_NAME_TEXT;
                        }
                        if (element.id == id) {
                            var data = element;
                            data.isSave = false;
                            data.isOnce = false;

                            this.selectedProductId = id;
                            // this.focusTarget = i;
                            this.tmpPdt = data;
                            this.isSaveDisp = false;
                            this.isOnceDisp = false;
                        }
                    });
                    this.lockdata = response.data.lockData;
                    this.isOwnLock = response.data.isOwnLock;
                    this.lockInfo.lock_type = response.data.lockInfo.lockType;
                    this.lockInfo.key_ids = response.data.lockInfo.keyIds;
                    this.changeEditMode();

                    // 類似商品をローカル変数へ
                    var tmpObj = {
                        construction_id_1: {},
                        construction_id_2: {},
                        construction_id_3: {},
                        class_small_id_1: {},
                        class_small_id_2: {},
                        class_small_id_3: {},
                        class_big_id: {},
                        class_middle_id: {},
                        intangible_flg: {},
                        maker_id: {},
                        tree_species: {},
                        grade: {},
                        tax_type: {},
                        start_date: {},
                        end_date: {},
                        housing_history_transfer_kbn: {},
                    };
                    var err = Object.assign({}, this.simErrorObj);

                    for(var i = 0 ; i < this.similarInputObj.length ; i ++){
                        this.similarInputObj[i].construction_id_1.dispose();                        
                        this.similarInputObj[i].construction_id_2.dispose();                        
                        this.similarInputObj[i].construction_id_3.dispose();                
                        this.similarInputObj[i].class_small_id_1.dispose();                        
                        this.similarInputObj[i].class_small_id_2.dispose();                        
                        this.similarInputObj[i].class_small_id_3.dispose();                        
                        this.similarInputObj[i].class_big_id.dispose();                        
                        this.similarInputObj[i].class_middle_id.dispose();                        
                        // this.similarInputObj[i].intangible_flg.dispose();                        
                        this.similarInputObj[i].maker_id.dispose();                        
                        this.similarInputObj[i].tree_species.dispose();                        
                        this.similarInputObj[i].grade.dispose();                        
                        this.similarInputObj[i].tax_type.dispose();                        
                        this.similarInputObj[i].start_date.dispose();                        
                        this.similarInputObj[i].end_date.dispose();                        
                        this.similarInputObj[i].housing_history_transfer_kbn.dispose();
                        
                    }
                    this.similarPdt = [];
                    this.similarInputObj = [];
                    var list = [];
                    
                    response.data.simList.forEach((rec, i) => {
                        if (rec.price == 0) {
                            rec.open_price_flg = this.FLG_ON;
                            rec.open_price_txt = this.OPEN_PRICE_TEXT.ON;
                        } else {
                            rec.open_price_flg = this.FLG_OFF;
                            rec.open_price_txt = this.OPEN_PRICE_TEXT.OFF;
                        }
                        if (rec.draft_flg == this.FLG_OFF && rec.auto_flg == this.FLG_OFF) {
                            // 本登録商品の場合は案件番号、案件名を書き換える
                            rec.matter_id = this.INIT_MATTER_ID;
                            rec.matter_no = this.INIT_MATTER_NO_TEXT;
                            rec.matter_name = this.INIT_MATTER_NAME_TEXT;
                        }

                        var data = {
                            isUpdate: false,
                            isIntegrate: false,
                            class_big_id: rec.class_big_id,
                            class_middle_id: rec.class_middle_id,
                            class_small_id_1: rec.class_small_id_1,
                            class_small_id_2: rec.class_small_id_2,
                            class_small_id_3: rec.class_small_id_3,
                            construction_id_1: rec.construction_id_1,
                            construction_id_2: rec.construction_id_2,
                            construction_id_3: rec.construction_id_3,
                            created_at: rec.created_at,
                            created_user: rec.created_user,
                            del_flg: rec.del_flg,
                            draft_flg: rec.draft_flg,
                            auto_flg: rec.auto_flg,
                            end_date: rec.end_date,
                            grade: rec.grade,
                            housing_history_transfer_kbn: rec.housing_history_transfer_kbn,
                            id: rec.id,
                            intangible_flg: rec.intangible_flg,
                            intangible_flg_txt: rec.intangible_flg_txt,
                            lead_time: rec.lead_time,
                            length: rec.length,
                            maker_id: rec.maker_id,
                            maker_name: rec.maker_name,
                            memo: rec.memo,
                            min_quantity: rec.min_quantity,
                            model: rec.model,
                            new_product_id: rec.new_product_id,
                            new_product_code: rec.new_product_code,
                            normal_gross_profit_rate: rec.normal_gross_profit_rate,
                            normal_purchase_price: rec.normal_purchase_price,
                            normal_sales_price: rec.normal_sales_price,
                            open_price_flg: rec.open_price_flg,
                            open_price_txt: rec.open_price_txt,
                            order_lot_quantity: rec.order_lot_quantity,
                            price: rec.price,
                            product_code: rec.product_code,
                            product_name: rec.product_name,
                            product_short_name: rec.product_short_name,
                            purchase_makeup_rate: rec.purchase_makeup_rate,
                            quantity_per_case: rec.quantity_per_case,
                            sales_makeup_rate: rec.sales_makeup_rate,
                            set_kbn: rec.set_kbn,
                            start_date: rec.start_date,
                            stock_unit: rec.stock_unit,
                            tax_type: rec.tax_type,
                            thickness: rec.thickness,
                            tree_species: rec.tree_species,
                            unit: rec.unit,
                            warranty_term: rec.warranty_term, 
                            weight: rec.weight,
                            width: rec.width,
                            update_at: rec.update_at,
                            update_user: rec.update_user,

                            matter_id: rec.matter_id,
                            matter_no: rec.matter_no,
                            matter_name: rec.matter_name,
                            created_staff: rec.created_staff,
                        };

                        list.push(data);
                        
                        if (response.data.simList.length > i) {
                            this.similarInputObj.push(tmpObj);
                            this.simErrors.push(err);
                        }
                    });
                    this.similarPdt = list;

                    setTimeout(function() {
                        list.forEach((rec, i) => {
                            this.similarInputObj[i] = this.createAutoComplete(i, rec);    
                        });
                    }.bind(this), 800);
                    this.changeEditMode();
                } else {
                    // 失敗
                    // window.onbeforeunload = null;
                    // location.reload()
                    
                    if (response.data.resultMessage && response.data.resultMessage !== '') {
                        alert(response.data.resultMessage);
                        window.onbeforeunload = null;
                        location.reload()
                    } else {
                        alert(MSG_ERROR);
                    }
                }
            }.bind(this))

            .catch(function (error) {
                 if (error.response.data.errors) {
                    this.loading = false
                    // エラーメッセージ表示
                    this.showErrMsg(error.response.data.errors, this.searchErr)
                } else {
                    if (error.response.data.message) {
                        alert(error.response.data.message)
                    } else {
                        alert(MSG_ERROR)
                    }
                    window.onbeforeunload = null;
                    // location.reload()
                }
                // this.loading = false
            }.bind(this))
        },
        saveDisable() {
            // this.tmpPdt.isSave = false;
            this.$set(this.tmpPdt, 'isSave',  false);
            this.$set(this.tmpPdt, 'isOnce',  false);
            this.isSaveDisp = false;
            this.isOnceDisp = false;
        },
        saveActive() {
            
            var integrateFlg = false;
            this.similarPdt.forEach(rec => {
                if (rec.isIntegrate) {
                    rec.isIntegrate = false;
                }
            });
            // this.tmpPdt.isSave = true;
            this.$set(this.tmpPdt, 'isSave',  true);
            this.$set(this.tmpPdt, 'isOnce',  false);
            this.isSaveDisp = true;
            this.isOnceDisp = false;
        },
        onceActive() {
            
            var integrateFlg = false;
            this.similarPdt.forEach(rec => {
                if (rec.isIntegrate) {
                    rec.isIntegrate = false;
                }
            });
            // this.tmpPdt.isSave = true;
            this.$set(this.tmpPdt, 'isSave',  true);
            this.$set(this.tmpPdt, 'isOnce',  true);
            this.isSaveDisp = true;
            this.isOnceDisp = true;
        },
        // 類似商品の商品番号を仮商品の後継商品コードへ
        simToDraftProductCode(index) {
            var data = this.similarPdt[index];

            this.$set(this.tmpPdt, 'new_product_id',  data.id);
            this.$set(this.tmpPdt, 'new_product_code',  data.product_code);
            // this.tmpPdt.new_product_id = data.id;
            // this.tmpPdt.new_product_code = data.product_code;
        },
        // 仮商品の後継商品コードを類似商品の商品番号へ
        draftToSimProductCode(index) {
            var data = this.tmpPdt;

            this.$set(this.similarPdt[index], 'new_product_id',  data.id);
            this.$set(this.similarPdt[index], 'new_product_code',  data.product_code);
            // this.similarPdt[index].new_product_id = data.id;
            // this.similarPdt[index].new_product_code = data.product_code;
        },
        // マスタ修正ボタン
        onUpdate(index) {
            this.similarPdt[index].isUpdate = !this.similarPdt[index].isUpdate;

            this.similarPdt.splice();
        },
        // 統合ボタン
        onIntegrate(index) {
            var errCnt = 0;
            var errMsg = '';
            // メーカーIDが不一致ならエラー
            if (this.rmUndefinedZero(this.tmpInputObj.maker_id.selectedValue) != 0) {
                if (this.similarPdt[index].maker_id != this.tmpInputObj.maker_id.selectedValue) {
                    errCnt++;
                    errMsg += MSG_ERROR_MAKERID_DIFFERENT;
                }
            }
            // 最小単位数が不一致ならエラー
            if (this.similarPdt[index].min_quantity != this.tmpPdt.min_quantity) {
                errCnt++;
                if (errMsg != '') {
                    errMsg += '\n'
                }
                errMsg += MSG_ERROR_MIN_QUANTITY_DIFFERENT;
            }
            // 無形品が不一致ならエラー
            if (this.similarPdt[index].intangible_flg != this.tmpPdt.intangible_flg) {
                errCnt++;
                if (errMsg != '') {
                    errMsg += '\n'
                }
                if (this.tmpPdt.intangible_flg == this.FLG_ON) {
                    errMsg += MSG_ERROR_FROM_INTANGIBLE_TO_TANGIBLE;
                } else {
                    errMsg += MSG_ERROR_FROM_TANGIBLE_TO_INTANGIBLE;
                }
            }

            if (errCnt > 0) {
                alert(errMsg);
                return;
            }

            // 統合フラグ切り替え
            this.similarPdt[index].isIntegrate = !this.similarPdt[index].isIntegrate;
            var pdt = this.similarPdt[index];

            // 統合がONなら登録しないボタンをアクティブにする
            if (this.similarPdt[index].isIntegrate == this.FLG_ON) {
                // this.tmpPdt.isSave = false;
                this.$set(this.tmpPdt, 'isSave',  false);
                this.$set(this.tmpPdt, 'isOnce',  false);
                this.isSaveDisp = false;
                this.isOnceDisp = false;

                // 他レコードの統合ボタンをオフにする(統合は0 or 1のみ)
                this.similarPdt.forEach(rec => {
                    if (rec.id != pdt.id) {
                        rec.isIntegrate = false;
                    }
                });
            }  
            this.similarPdt.splice();
        },
        // 戻るボタン
        backProduct() {
            if (this.productlist.length > 0 && this.wjSearchObj.product_code.selectedIndex > 0) {
                var searchIndex = this.wjSearchObj.product_code.selectedIndex - 1;
                this.wjSearchObj.product_code.selectedIndex = searchIndex;
                // var searchId = this.wjSearchObj.product_code.selectedValue;
                this.select(0);
            } else {
                alert(MSG_ERROR_BACK_TO_PRODUCT);
            }
        },
        // 次ボタン
        nextProduct() {
            this.loading = true

            // エラーの初期化
            this.initErr(this.tmpErrors);
            this.simErrors.forEach((data, i) => {
                this.initErr(this.simErrors[i]);
            });
            var params = new URLSearchParams();

            // 類似商品
            var saveSimList = []; 
            var isInteg = false;
            this.similarPdt.forEach((row, i) => {
                // オートコンプリートの値を変数に格納
                var inpObj = this.similarInputObj[i];
                if (row.isIntegrate) {
                    isInteg = true;
                }

                row.construction_id_1 = this.rmUndefinedZero(inpObj.construction_id_1.selectedValue);
                row.construction_id_2 = this.rmUndefinedZero(inpObj.construction_id_2.selectedValue);
                row.construction_id_3 = this.rmUndefinedZero(inpObj.construction_id_3.selectedValue);

                row.class_small_id_1 = this.rmUndefinedZero(inpObj.class_small_id_1.selectedValue);
                row.class_small_id_2 = this.rmUndefinedZero(inpObj.class_small_id_2.selectedValue);
                row.class_small_id_3 = this.rmUndefinedZero(inpObj.class_small_id_3.selectedValue);

                row.class_big_id = this.rmUndefinedZero(inpObj.class_big_id.selectedValue);
                row.class_middle_id = this.rmUndefinedZero(inpObj.class_middle_id.selectedValue);

                row.housing_history_transfer_kbn = this.rmUndefinedZero(inpObj.housing_history_transfer_kbn.selectedValue);
                
                row.maker_id = this.rmUndefinedZero(inpObj.maker_id.selectedValue);
                row.tree_species = this.rmUndefinedZero(inpObj.tree_species.selectedValue);
                row.grade = this.rmUndefinedZero(inpObj.grade.selectedValue);
                row.tax_type = this.rmUndefinedZero(inpObj.tax_type.selectedValue);

                if (this.rmUndefinedBlank(inpObj.start_date.text) == '') {
                    row.start_date = null;
                }else {
                    row.start_date = inpObj.start_date.text;
                }
                if (this.rmUndefinedBlank(inpObj.end_date.text) == '') {
                    row.end_date = null;
                }else {
                    row.end_date = inpObj.end_date.text;
                }

                saveSimList.push(row);
            });

            var tmpData = this.tmpPdt;
            var isErr = false;
            var message = '';
            if (!tmpData.isSave && !isInteg) {
                // 登録しない(削除)の場合、使用されている見積があれば確認メッセージ表示
                if (this.rmUndefinedBlank(tmpData.matter_no) != '' || this.rmUndefinedBlank(tmpData.matter_name) != '') {
                    if (!confirm(MSG_CONFIRM_PRODUCT_USED_BY_QUOTE)) {
                        isErr = true;
                    }
                }
            }
            if (tmpData.isSave && !tmpData.isOnce) {
                // 本登録の場合入力チェック
                // 仮の商品番号は不可
                if (tmpData.product_code == this.noProductCode.value_text_1) {
                    message = MSG_ERROR_NO_INPUT_PRODUCT_CODE;
                    isErr = true;
                }
                // 商品番号が3文字未満の場合は不可
                if (tmpData.product_code.length < PRODUCT_AUTO_COMPLETE_SETTING.SEARCH_PRODUCT_CODE_LENGTH) {
                    message = MSG_ERROR_NO_INPUT_PRODUCT_CODE;
                    isErr = true;
                }
            }
            if (isErr) {
                if (message !== '') {
                    alert(message);
                }
                this.loading = false;
                return;
            }

            tmpData.construction_id_1 = this.rmUndefinedZero(this.tmpInputObj.construction_id_1.selectedValue);
            tmpData.construction_id_2 = this.rmUndefinedZero(this.tmpInputObj.construction_id_2.selectedValue);
            tmpData.construction_id_3 = this.rmUndefinedZero(this.tmpInputObj.construction_id_3.selectedValue);

            tmpData.class_small_id_1 = this.rmUndefinedZero(this.tmpInputObj.class_small_id_1.selectedValue);
            tmpData.class_small_id_2 = this.rmUndefinedZero(this.tmpInputObj.class_small_id_2.selectedValue);
            tmpData.class_small_id_3 = this.rmUndefinedZero(this.tmpInputObj.class_small_id_3.selectedValue);
            tmpData.class_big_id = this.rmUndefinedZero(this.tmpInputObj.class_big_id.selectedValue);
            tmpData.class_middle_id = this.rmUndefinedZero(this.tmpInputObj.class_middle_id.selectedValue);

            tmpData.housing_history_transfer_kbn = this.rmUndefinedZero(this.tmpInputObj.housing_history_transfer_kbn.selectedValue);

            tmpData.tree_species = this.rmUndefinedZero(this.tmpInputObj.tree_species.selectedValue);
            tmpData.grade = this.rmUndefinedZero(this.tmpInputObj.grade.selectedValue);
            tmpData.tax_type = this.rmUndefinedZero(this.tmpInputObj.tax_type.selectedValue);
            if (this.rmUndefinedBlank(this.tmpInputObj.start_date.text) == '') {
                tmpData.start_date = null;
            }else {
                tmpData.start_date = this.tmpInputObj.start_date.text;
            }
            if (this.rmUndefinedBlank(this.tmpInputObj.end_date.text) == '') {
                tmpData.end_date = null;
            }else {
                tmpData.end_date = this.tmpInputObj.end_date.text;
            }

            params.append('tmpPdt', JSON.stringify(tmpData));
            params.append('lock_info', JSON.stringify(this.lockInfo));
            params.append('simList', JSON.stringify(saveSimList));
            
            axios.post('/product-check/save', params)

            .then( function (response) {
                this.loading = false

                if (response.data.result) {
                    // 成功
                    window.onbeforeunload = null;
                    location.reload()
                } else {
                    // 失敗
                    var alertMsg = '';
                    this.showErrMsg(response.data.tmpErrors, this.tmpErrors);
                    for(let errItem in response.data.tmpErrors) {
                        if (alertMsg != '') {
                            alertMsg += "\n";
                        }
                        alertMsg += response.data.tmpErrors[errItem];
                    }
                    for (var i = 0; this.similarPdt.length > i; i++) {
                        var err = Object.assign({}, this.simErrorObj);
                        for(let errItem in response.data.simErrors[i]) {
                            err[errItem] = response.data.simErrors[i][errItem];

                            if (alertMsg != '') {
                                alertMsg += "\n";
                            }
                            alertMsg += response.data.simErrors[i][errItem];
                        }
                        this.simErrors[i] = err;
                    }
                    if (response.data.resultMsg != '') {
                        alertMsg = response.data.resultMsg;
                    }
                    if (alertMsg == '') {
                        alert(MSG_ERROR);
                    } else {
                        alert(alertMsg);
                    }

                    // window.onbeforeunload = null;
                    // location.reload()
                }
            }.bind(this))

            .catch(function (error) {
                this.loading = false
                if (error && error.response && error.response.data) {
                    if (error.response.data.errors) {
                        // this.loading = false
                        // エラーメッセージ表示
                        this.showErrMsg(error.response.data.errors, this.errors)
                    } else {
                        if (error.response.data.message) {
                            alert(error.response.data.message)
                        } else {
                            alert(MSG_ERROR)
                        }
                        window.onbeforeunload = null;
                        // location.reload()
                    }
                } else {
                    alert(MSG_ERROR)
                }
                // this.loading = false
            }.bind(this))
        },
        selectProductCode: function(sender) {
            var item = sender.selectedItem;
            if(item !== null){
                this.searchParams.product_name = item.id;
            }else{
                this.searchParams.product_name = '';
            }
        },
        selectProductName: function(sender) {
            var item = sender.selectedItem;
            if(item !== null){
                this.searchParams.product_code = item.id;
            }else{
                this.searchParams.product_code = '';
            }
        },
        // 仮商品コンボの絞り込み
        execProductFilter() {
            // 仮商品リストをディープコピー
            var draftList = JSON.parse(JSON.stringify(this.productlist));

            // 工事区分　仮商品の場合、商品マスタの工事区分1しか見る必要がない
            if (this.wjSearchObj.construction_name.selectedIndex >= 0) {
                var selectedConstructionId = this.wjSearchObj.construction_name.selectedValue;
                draftList = draftList.filter(
                    record => (record.construction_id_1 === selectedConstructionId)
                );
            }

            // 案件名
            var tmpList = [];
            if (this.wjSearchObj.matter_name.selectedIndex >= 0) {
                var selectedMatterNo = this.wjSearchObj.matter_name.selectedValue;
                // 案件に該当する商品IDを絞る
                var productIdList = [];
                this.orderdetaillist.forEach(od => {
                    if (od.matter_no === selectedMatterNo) {
                        if (productIdList.indexOf(od.product_id) < 0) {
                            productIdList.push(od.product_id);
                        }
                    }
                });
                // 商品選択肢を再生成
                if (productIdList.length > 0) {
                    var tmpList = JSON.parse(JSON.stringify(draftList));
                    draftList = [];
                    tmpList.forEach(record => {
                        for(var i = 0; i < productIdList.length; i++) {
                            if (record.id === productIdList[i]) {
                                draftList.push(record);
                            }
                        }
                    });
                } else {
                    draftList = [];
                }
            }

            // メーカー名
            if (this.wjSearchObj.supplier_name.selectedIndex >= 0) {
                var selectedMakerId = this.wjSearchObj.supplier_name.selectedValue;
                draftList = draftList.filter(
                    record => (record.maker_id === selectedMakerId)
                );
            }

            // 発注番号
            if (this.wjSearchObj.order_no.selectedIndex >= 0) {
                var selectedOrderNo = this.wjSearchObj.order_no.selectedValue;
                // 発注番号に該当する商品IDを絞る
                var productIdList = [];
                this.orderdetaillist.forEach(od => {
                    if (od.order_no === selectedOrderNo) {
                        if (productIdList.indexOf(od.product_id) < 0) {
                            productIdList.push(od.product_id);
                        }
                    }
                });
                // 商品選択肢を再生成
                if (productIdList.length > 0) {
                    var tmpList = JSON.parse(JSON.stringify(draftList));
                    draftList = [];
                    tmpList.forEach(record => {
                        for(var i = 0; i < productIdList.length; i++) {
                            if (record.id === productIdList[i]) {
                                draftList.push(record);
                            }
                        }
                    });
                } else {
                    draftList = [];
                }
            }
                
            this.wjSearchObj.product_code.itemsSource = draftList;
            this.wjSearchObj.product_code.selectedIndex = -1;

            this.wjSearchObj.product_name.itemsSource = draftList;
            this.wjSearchObj.product_name.selectedIndex = -1;
        },
        // 編集モード
        edit() {
            if (this.productlist.length === 0) {
                alert(MSG_ERROR_BACK_TO_PRODUCT);
                return;
            }

            var params = new URLSearchParams();

            params.append('id', this.selectedProductId);
            
            axios.post('/product-check/lock', params)

            .then( function (response) {
                this.loading = false
                if (response.data.status) {
                    if (response.data.isLocked) {
                        alert(MSG_EDITING);
                        // window.onbeforeunload = null;
                        // location.reload()
                        this.select(0);
                    } else {
                        this.isReadOnly = false
                        this.isShowEditBtn = false
                        this.lockdata = response.data.lockdata;
                        this.lockInfo.lock_type = response.data.lockInfo.lockType;
                        this.lockInfo.key_ids = response.data.lockInfo.keyIds;
                        window.onbeforeunload = null;
                        location.reload()
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
        // ロック解除（強制ロック解除）
        unlock() {
            if (!confirm(MSG_CONFIRM_UNLOCK)) {
                return;
            }
            var params = new URLSearchParams();
            
            params.append('id', this.selectedProductId);
            axios.post('/product-check/gainLock', params)

            .then( function (response) {
                this.loading = false
                if (response.data.status) {
                    this.select(0);
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
        clear: function() {
            // this.searchParams = this.initParams;
            var wjSearchObj = this.wjSearchObj;
            Object.keys(wjSearchObj).forEach(function (key) {
                wjSearchObj[key].selectedValue = null;
                wjSearchObj[key].value = null;
                wjSearchObj[key].text = null;
            });
        },
        getLayout() {
            return [
                {
                    header: '選択', cells: [
                        { name: 'select', header: '選択', minWidth: GRID_COL_MIN_WIDTH, width: 60, isReadOnly: true },  
                    ]
                },
                {
                    header: '工事区分１', cells: [
                        { binding: 'construction_name_1', name: 'construction_name_1', header: '工事区分１', minWidth: GRID_COL_MIN_WIDTH, width: 120, isReadOnly: true},  
                    ]
                },
                {
                    header: '工事区分２', cells: [
                        { binding: 'construction_name_2', name: 'construction_name_2', header: '工事区分２', minWidth: GRID_COL_MIN_WIDTH, width: 120, isReadOnly: true },  
                    ]
                },
                {
                    header: '工事区分３', cells: [
                        { binding: 'construction_name_3', name: 'construction_name_3', header: '工事区分３', minWidth: GRID_COL_MIN_WIDTH, width: 120, isReadOnly: true },  
                    ]
                },
                {
                    header: '大分類', cells: [
                        { binding: 'class_big_name', name: 'class_big_name', header: '大分類', minWidth: GRID_COL_MIN_WIDTH, width: 120, isReadOnly: true },  
                    ]
                },
                {
                    header: 'メーカー', cells: [
                        { binding: 'supplier_name', name: 'supplier_name', header: 'メーカー', minWidth: GRID_COL_MIN_WIDTH, width: 150, isReadOnly: true },  
                    ]
                },
                {
                    header: '商品番号', cells: [
                        { binding: 'product_code', name: 'product_code', header: '商品番号', minWidth: GRID_COL_MIN_WIDTH, width: 150, isReadOnly: true },  
                    ]
                },
                {
                    header: '商品名', cells: [
                        { binding: 'product_name', name: 'product_name', header: '商品名', minWidth: GRID_COL_MIN_WIDTH, width: 300, isReadOnly: true },  
                    ]
                },
                /* 非表示 */
                {
                    header: 'ID', cells: [
                        { binding: 'id', name: 'id', header: 'ID', maxWidth: 0, width: 0, isReadOnly: true, isRequired: false },
                    ]
                },
            ]
        },
        applyGridSettings(grid) {
            // リサイジング設定
            grid.columns.forEach(element => {
                if (this.gridSetting.deny_resizing_col.indexOf(element.index) >= 0) {
                    element.allowResizing = false;
                }
            });
            // 非表示設定
            grid.columns.forEach(element => {
                if (this.gridSetting.invisible_col.indexOf(element.index) >= 0) {
                    element.visible = false;
                }
            });
            // grid.allowMerging = wjGrid.AllowMerging.All;
            
            return grid;
        },
        initPcode(sender) {
            this.wjSearchObj.product_code = sender;
        },
        initPname(sender) {
            this.wjSearchObj.product_name = sender;
        },
        initConstruction(sender) {
            this.wjSearchObj.construction_name = sender;
        },
        initMatterName(sender) {
            this.wjSearchObj.matter_name = sender;
        },
        initOrderNo(sender) {
            this.wjSearchObj.order_no = sender;
        },
        initMaker(sender) {
            this.wjSearchObj.supplier_name = sender;
        },
        initStartDate(sender) {
            this.tmpInputObj.start_date = sender;
        },
        initEndDate(sender) {
            this.tmpInputObj.end_date = sender;
        },
        initTaxType(sender) {
            this.tmpInputObj.tax_type = sender;
        },
        initGrade(sender) {
            this.tmpInputObj.grade = sender;
        },
        initTreeSpec(sender) {
            this.tmpInputObj.tree_species = sender;
        },
        initMakerInp(sender) {
            this.tmpInputObj.maker_id = sender;
        },
        initClassMid(sender) {
            this.tmpInputObj.class_middle_id = sender;
        },
        initClassBig(sender) {
            this.tmpInputObj.class_big_id = sender;
        },
        initSmall_1(sender) {
            this.tmpInputObj.class_small_id_1 = sender;
        },
        initSmall_2(sender) {
            this.tmpInputObj.class_small_id_2 = sender;
        },
        initSmall_3(sender) {
            this.tmpInputObj.class_small_id_3 = sender;
        },
        initConst_1(sender) {
            this.tmpInputObj.construction_id_1 = sender;
        },
        initConst_2(sender) {
            this.tmpInputObj.construction_id_2 = sender;
        },
        initConst_3(sender) {
            this.tmpInputObj.construction_id_3 = sender;
        },
        initHousingKbn(sender) {
            this.tmpInputObj.housing_history_transfer_kbn = sender;
        },
        initDlgConst(sender) {
            this.wjDlgSearchObj.construction_name = sender;
        },
        initDlgBig(sender) {
            this.wjDlgSearchObj.class_big_name = sender;
        },
        initDlgMaker(sender) {
            this.wjDlgSearchObj.supplier_name = sender;
        },
        initDlgPCode(sender) {
            this.wjDlgSearchObj.product_code = sender;
        },
        initDlgPName(sender) {
            this.wjDlgSearchObj.product_name = sender;
        },
        selectConstruction_1(s) {
            // 工事区分を選択したら工程を絞り込む
            var item = s.selectedItem;
            var list = [];
            if(item !== null) {
                this.classsmalllist.forEach(record => {
                    if (record.construction_id == item.id) {
                        list.push(record);
                    }
                });
                this.tmpInputObj.class_small_id_1.itemsSource = list;
                this.tmpInputObj.class_small_id_1.selectedIndex = -1;
            }else{
                this.tmpInputObj.class_small_id_1.itemsSource = this.classsmalllist;
                this.tmpInputObj.class_small_id_1.selectedIndex = -1;
            }
        },
        selectConstruction_2(s) {
            // 工事区分を選択したら工程を絞り込む
            var item = s.selectedItem;
            var list = [];
            if(item !== null) {
                this.classsmalllist.forEach(record => {
                    if (record.construction_id == item.id) {
                        list.push(record);
                    }
                });
                this.tmpInputObj.class_small_id_2.itemsSource = list;
                this.tmpInputObj.class_small_id_2.selectedIndex = -1;
            }else{
                this.tmpInputObj.class_small_id_2.itemsSource = this.classsmalllist;
                this.tmpInputObj.class_small_id_2.selectedIndex = -1;
            }
        },
        selectConstruction_3(s) {
            // 工事区分を選択したら工程を絞り込む
            var item = s.selectedItem;
            var list = [];
            if(item !== null) {
                this.classsmalllist.forEach(record => {
                    if (record.construction_id == item.id) {
                        list.push(record);
                    }
                });
                this.tmpInputObj.class_small_id_3.itemsSource = list;
                this.tmpInputObj.class_small_id_3.selectedIndex = -1;
            }else{
                this.tmpInputObj.class_small_id_3.itemsSource = this.classsmalllist;
                this.tmpInputObj.class_small_id_3.selectedIndex = -1;
            }
        },
        // オートコンプリート紐付け
        initTaxTypeByIndex(s) {
            var index = s.hostElement.id;
            this.similarInputObj[index].tax_type = s;
        },
        initConstByIndex_1(s) {
            var index = s.hostElement.id;
            this.similarInputObj[index].construction_id_1 = s;
        },
        initConstByIndex_2(s) {
            var index = s.hostElement.id;
            this.similarInputObj[index].construction_id_2 = s;
        },
        initConstByIndex_3(s) {
            var index = s.hostElement.id;
            this.similarInputObj[index].construction_id_3 = s;
        },
        initSmallByIndex_1(s) {
            var index = s.hostElement.id;
            this.similarInputObj[index].class_small_id_1 = s;
        },
        initSmallByIndex_2(s) {
            var index = s.hostElement.id;
            this.similarInputObj[index].class_small_id_2 = s;
        },
        initSmallByIndex_3(s) {
            var index = s.hostElement.id;
            this.similarInputObj[index].class_small_id_3 = s;
        },
        initBigByIndex(s) {
            var index = s.hostElement.id;
            this.similarInputObj[index].class_big_id = s;
        },
        initMidByIndex(s) {
            var index = s.hostElement.id;
            this.similarInputObj[index].class_middle_id = s;
        },
        initMakerByIndex(s) {
            var index = s.hostElement.id;
            this.similarInputObj[index].maker_id = s;
        },
        initWoodByIndex(s) {
            var index = s.hostElement.id;
            this.similarInputObj[index].tree_species = s;
        },
        initGradeByIndex(s) {
            var index = s.hostElement.id;
            this.similarInputObj[index].grade = s;
        },
        initStartDateByIndex(s) {
            var index = s.hostElement.id;
            this.similarInputObj[index].start_date = s;
        },
        initEndDateByIndex(s) {
            var index = s.hostElement.id;
            this.similarInputObj[index].end_date = s;
        },
    },
    
}
</script>

<style>
.bg-white {
    background: #ffffff;
}
.search-body {
    /* width: 100%; */
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.result-body {
    /* width: 100%; */
    background: #ffffff;
    margin-top: 30px;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.btn-clear{
    padding-top: 5px;
}
.btn-submit {
    width: 80px;
}
.btn-add {
    height: 20px;
    font-size: 12px;
    margin-top: 2px;
    padding-top: 0px;
}
.btn-header {
    margin: 0px;
    padding-top: 2px;
    height: 20px;
    font-size: 12px;
}
.product-header{
    background-color: #43425D;
    color: #FFFFFF;
    text-align: center;
    font-size: 1.5rem;
    padding-top: 10px;
    height: 42px;
    font-weight: bold;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.product-body {
    background: #ffffff;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    text-align: center;
    font-size: 1.5rem;
    padding: 0px;
    height: 42px;
}
input[type="text"] {
  border: 1px solid #ccd0d2;
}
.wj-product-complete {
    width: 100%;
    height: 42px;
    border-radius: 0;
}
.diff-form > .wj-product-complete {
    background-color: #FFCC00 !important;
}
.diff-form {
    background-color: #FFCC00 !important;
}
input:read-only {
  background-color: #ccc;
}
.help-header {
    background-color: #a9a9a9;
    color: #FFFFFF;
    text-align: center;
    font-size: 1.5rem;
    height: 20px;
    font-weight: bold;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.help-body {
    background-color: #FFFFFF;
    color: #000000;
    text-align: center;
    font-size: 1.5rem;
    height: 25px;
    font-weight: bold;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.scroll-area {
    width: 100%;
    height: 1960px;
    overflow-x: scroll;
    white-space: nowrap;
}
.main-area {
    width: 95%;
    padding: 5px;
    margin: 15px 10px;
    -webkit-box-shadow: 0 0 3px 3px rgba(0, 0, 0, .3);
    box-shadow: 0 0 3px 3px rgba(0, 0, 0, .3);
}
.scroll-item {
    font-size: 12px;
    vertical-align: top;    
    margin-right: 10px;
    width: 400px;
    display: inline-block;
}
.active-btn {
    background-color: #FFCC00 !important;
}
.active-btn:hover {
    background-color: #FFCC00 !important;
}
.active-btn:focus {
    background-color: #FFCC00 !important;
}
.btn-select {
    margin: -2px;
    padding-top: 0px;
    height: 26px;
}
.el-dialog {
    width: 60%;
    height: 70%;
}
/* グリッド */
.wj-multirow {
    height: 200px;
    margin: 6px 0;
}
.searchclear {
    position: absolute;
    right: 5px;
    top: 0;
    bottom: 0;
    height: 14px;
    margin: auto;
    font-size: 14px;
    cursor: pointer;
    color: #a94442;
}
.mode-btn-box {
    margin-top: 5px;
}

</style>