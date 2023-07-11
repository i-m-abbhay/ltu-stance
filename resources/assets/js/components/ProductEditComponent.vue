<template>
    <div>
        <loading-component :loading="loading" />
        <!-- モード変更 -->
        <div class="col-md-12 text-right">
            <button type="button" class="btn btn-danger btn-unlock" @click="unlock" v-show="isLocked">ロック解除</button>
            <button type="button" class="btn btn-primary btn-edit"  v-show="isShowEditBtn" @click="edit">編集</button>
            <p class="btn btn-default btn-editing" v-show="(!isLocked && !isShowEditBtn && isEditable)">編集中</p>
            <button type="button" class="btn btn-primary btn-save" v-show="(!isLocked && !isShowEditBtn && isEditable && !isReadOnly)" v-on:click="save">登録</button>
            <button type="button" class="btn btn-danger btn-delete" v-show="(!isReadOnly && this.productdata.id)" v-on:click="del">削除</button>      
        </div>
        <div class="form-horizontal save-form">
            <form id="saveForm" name="saveForm" class="form-horizontal" method="post" action="/product-edit/save">
                <div class="col-md-12 col-sm-12 col-xs-12 main-body">
                    <!-- <div class="col-md-12 col-sm-12 col-xs-12 text-right">
                        <button type="button" class="btn btn-primary btn-save">ＣＳＶデータ取り込み</button>
                    </div> -->      
                    <div class="col-md-12 pull-right" v-show="(rmUndefinedZero(productdata.id) != 0 && isAutoProduct)">
                        <label class="control-label">&nbsp;</label>
                        <div class="pull-right">
                            <el-checkbox v-model="inputData.auto_flg" :true-label="FLG_OFF" :false-label="FLG_ON" @change="changeChkAutoFlg">本登録する</el-checkbox>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-12 col-xs-12 text-left">
                        <div class="form-group">
                            <label class="col-sm-12">商品ID</label>
                            <div class="col-sm-10">
                                <p class="form-control-static box">{{ productdata.id }}</p>
                            </div>
                        </div>
                    </div>          
                    <div class="col-md-12 col-sm-12 col-xs-12 main-body">
                        <h4><u>使用分類</u></h4>
                        <div class="col-md-4 col-sm-12 col-xs-12">
                            <div v-bind:class="{'has-error': (errors.class_big_id != '') }">
                                <label class="col-md-12 col-sm-12 col-xs-12">大分類</label>
                                <div class="col-md-10 col-sm-12 col-xs-12">
                                    <wj-auto-complete class="form-control" id="acClassBig" v-bind:readonly="isReadOnly"
                                        search-member-path="class_big_name"
                                        display-member-path="class_big_name"
                                        selected-value-path="class_big_id"
                                        :selected-index="-1"
                                        :selected-value="inputData.class_big_id"
                                        :is-required="false"
                                        :isReadOnly="isReadOnly"
                                        :selectedIndexChanged="changeIdxClassBig"
                                        :initialized="selectBig"
                                        :max-items="classbigdata.length"
                                        :items-source="classbigdata">
                                    </wj-auto-complete>
                                    <p class="text-danger">{{ errors.class_big_id }}</p>
                                </div>
                            </div>
                            <label class="col-md-11 col-md-offset-1 col-sm-12 col-xs-12">中分類</label>
                            <div class="col-md-10 col-sm-12 col-xs-12 col-md-offset-1">
                                <wj-auto-complete class="form-control" id="acClassMiddle" v-bind:readonly="isReadOnly"
                                    search-member-path="class_middle_name"
                                    display-member-path="class_middle_name"
                                    selected-value-path="id"
                                    :selected-index="-1"
                                    :selected-value="inputData.class_middle_id"
                                    :is-required="false"
                                    :isReadOnly="isReadOnly"
                                    :max-items="classmiddata.length"
                                    :initialized="selectMiddle"
                                    :items-source="classmiddata">
                                </wj-auto-complete>
                            </div>                            
                            <label class="col-md-12 col-sm-12 col-xs-12">形品区分</label>
                            <div class="col-md-12 col-sm-12 col-xs-12">
                                <el-radio-group v-model="inputData.intangible_flg" v-bind:disabled="(isReadOnly || rmUndefinedZero(productdata.id) != 0)" @input="changeIntangibleFlg">
                                    <div class="radio">
                                        <el-radio :label="FLG_OFF">有形品</el-radio>
                                        <el-radio :label="FLG_ON">無形品</el-radio>
                                    </div>
                                </el-radio-group>
                            </div>
                        </div>

                        <div class="col-md-4 col-sm-12 col-xs-12">
                            <div v-bind:class="{'has-error': (errors.construction_id_1 != '') }">
                                <label class="col-md-11 col-md-offset-1 col-sm-12 col-xs-12">工事区分１</label>
                                <div class="col-md-10 col-sm-12 col-xs-12 col-md-offset-1">
                                    <wj-auto-complete class="form-control" id="acConst1" v-bind:readonly="isReadOnly"
                                        search-member-path="construction_name"
                                        display-member-path="construction_name"
                                        selected-value-path="id"
                                        :selected-value="inputData.construction_id_1"
                                        :selectedIndexChanged="changeIdxConst1"
                                        :isReadOnly="isReadOnly"
                                        :initialized="selectConstruction1"
                                        :max-items="constdata.length"
                                        :items-source="constdata">
                                    </wj-auto-complete>
                                    <p class="text-danger">{{ errors.construction_id_1 }}</p>
                                </div>
                            </div>
                            <label class="col-md-11 col-md-offset-1 col-sm-12 col-xs-12">工事区分２</label>
                            <div class="col-md-10 col-sm-12 col-xs-12 col-md-offset-1">
                                <wj-auto-complete class="form-control" id="acConst2" v-bind:readonly="isReadOnly"
                                    search-member-path="construction_name"
                                    display-member-path="construction_name"
                                    selected-value-path="id"
                                    :selected-value="inputData.construction_id_2"
                                    :selectedIndexChanged="changeIdxConst2"
                                    :isReadOnly="isReadOnly"
                                    :initialized="selectConstruction2"
                                    :max-items="constdata.length"
                                    :items-source="constdata">
                                </wj-auto-complete>
                            </div>   
                            <label class="col-md-11 col-md-offset-1 col-sm-12 col-xs-12">工事区分３</label>
                            <div class="col-md-10 col-sm-12 col-xs-12 col-md-offset-1">
                                <wj-auto-complete class="form-control" id="acConst3" v-bind:readonly="isReadOnly"
                                    search-member-path="construction_name"
                                    display-member-path="construction_name"
                                    selected-value-path="id"
                                    :selected-value="inputData.construction_id_3"
                                    :selectedIndexChanged="changeIdxConst3"
                                    :isReadOnly="isReadOnly"
                                    :initialized="selectConstruction3"
                                    :max-items="constdata.length"
                                    :items-source="constdata">
                                </wj-auto-complete>
                            </div>      
                        </div>

                        <div class="col-md-4 col-sm-12 col-xs-12">
                            <label class="col-md-11 col-md-offset-1 col-sm-12 col-xs-12">工程１</label>
                            <div class="col-md-10 col-sm-12 col-xs-12 col-md-offset-1">
                                <wj-auto-complete class="form-control" id="acUsage1" v-bind:readonly="isReadOnly"
                                    search-member-path="class_small_name"
                                    display-member-path="class_small_name"
                                    selected-value-path="id"
                                    :selected-value="inputData.class_small_id_1"
                                    :isReadOnly="isReadOnly"
                                    :initialized="selectSmall1"
                                    :max-items="classsmalldata.length"
                                    :items-source="classsmalldata">
                                </wj-auto-complete>
                            </div>    
                            <label class="col-md-11 col-md-offset-1 col-sm-12 col-xs-12">工程２</label>
                            <div class="col-md-10 col-sm-12 col-xs-12 col-md-offset-1">
                                <wj-auto-complete class="form-control" id="acUsage2" v-bind:readonly="isReadOnly"
                                    search-member-path="class_small_name"
                                    display-member-path="class_small_name"
                                    selected-value-path="id"
                                    :selected-value="inputData.class_small_id_2"
                                    :isReadOnly="isReadOnly"
                                    :initialized="selectSmall2"
                                    :max-items="classsmalldata.length"
                                    :items-source="classsmalldata">
                                </wj-auto-complete>
                            </div>   
                            <label class="col-md-11 col-md-offset-1 col-sm-12 col-xs-12">工程３</label>
                            <div class="col-md-10 col-sm-12 col-xs-12 col-md-offset-1">
                                <wj-auto-complete class="form-control" id="acUsage3" v-bind:readonly="isReadOnly"
                                    search-member-path="class_small_name"
                                    display-member-path="class_small_name"
                                    selected-value-path="id"
                                    :selected-value="inputData.class_small_id_3"
                                    :isReadOnly="isReadOnly"
                                    :initialized="selectSmall3"
                                    :max-items="classsmalldata.length"
                                    :items-source="classsmalldata">
                                </wj-auto-complete>
                            </div>      
                        </div>

                    </div>
                     <div class="col-md-12 col-sm-12 col-xs-12 main-body">
                        <h4><u>商品名等</u></h4>
                        <div class="col-md-8 col-sm-12 col-xs-12" style="margin-top:20px;">
                            <div class="form-group">
                                <div class="col-sm-12 col-xs-12 col-md-5" v-bind:class="{'has-error': (errors.product_code != '') }">
                                    <label for="proNo" class="inp">
                                        <input type="text" id="proNo" v-model="inputData.product_code" class="form-control input_alphanumeric" placeholder=" " v-bind:readonly="(isReadOnly || (rmUndefinedZero(productdata.id) != 0))">
                                        <span for="proNo" class="label"><span style="color:red;">*</span>商品番号</span>
                                        <span class="border"></span>
                                    </label>
                                    <p class="text-danger">{{ errors.product_code }}</p>
                                </div>
                                <div class="col-sm-12 col-xs-12 col-md-7" v-bind:class="{'has-error': (errors.product_name != '') }">
                                    <label for="proName" class="inp">
                                        <input type="text" id="proName" v-model="inputData.product_name" class="form-control" placeholder=" " v-bind:readonly="isReadOnly">
                                        <span for="proName" class="label"><span style="color:red;">*</span>商品名</span>
                                        <span class="border"></span>
                                    </label>
                                    <p class="text-danger">{{ errors.product_name }}</p>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-sm-12 col-xs-12 col-md-4" v-bind:class="{'has-error': (errors.product_short_name != '') }">
                                    <label for="proSName" class="inp">
                                        <input type="text" id="proSName" v-model="inputData.product_short_name" class="form-control" placeholder=" " v-bind:readonly="isReadOnly">
                                        <span for="proSName" class="label">商品略称</span>
                                        <span class="border"></span>
                                    </label>
                                    <p class="text-danger">{{ errors.product_short_name }}</p>
                                </div>
                                <div class="col-sm-12 col-xs-12 col-md-4" v-show="isFormat" v-bind:class="{'has-error': (errors.tree_species != '') }">
                                    <label class="col-md-11 col-md-offset-1 col-sm-12 col-xs-12"><span style="color:red;">*</span>樹種</label>
                                    <div class="col-md-10 col-sm-12 col-xs-12 col-md-offset-1">
                                        <wj-auto-complete class="form-control" id="acUsage2" v-bind:readonly="isReadOnly"
                                            search-member-path="value_text_1"
                                            display-member-path="value_text_1"
                                            selected-value-path="value_code"
                                            :selected-value="inputData.tree_species"
                                            :selectedIndexChanged="Naming"
                                            :isReadOnly="isReadOnly"
                                            :initialized="initTreeSpecies"
                                            :max-items="wooddata.length"
                                            :items-source="wooddata">
                                        </wj-auto-complete>
                                        <p class="text-danger">{{ errors.tree_species }}</p>
                                    </div>   
                                </div>
                                <!-- <div class="col-sm-12 col-xs-12 col-md-3" v-show="isFormat" v-bind:class="{'has-error': (errors.tree_species != '') }">
                                    <label for="proTree" class="inp">
                                        <input type="text" id="proTree" v-model="inputData.tree_species" class="form-control" placeholder=" " v-bind:readonly="isReadOnly" @change="Naming">
                                        <span for="proTree" class="label">樹種</span>
                                        <span class="border"></span>
                                    </label>
                                    <p class="text-danger">{{ errors.tree_species }}</p>
                                </div> -->
                                <div class="col-sm-12 col-xs-12 col-md-4" v-show="isFormat" v-bind:class="{'has-error': (errors.grade != '') }">
                                    <label class="col-md-11 col-md-offset-1 col-sm-12 col-xs-12"><span style="color:red;">*</span>等級</label>
                                    <div class="col-md-10 col-sm-12 col-xs-12 col-md-offset-1">
                                        <wj-auto-complete class="form-control" id="acUsage2" v-bind:readonly="isReadOnly"
                                            search-member-path="value_text_1"
                                            display-member-path="value_text_1"
                                            selected-value-path="value_code"
                                            :selected-value="inputData.grade"
                                            :selectedIndexChanged="Naming"
                                            :isReadOnly="isReadOnly"
                                            :initialized="initGrade"
                                            :max-items="gradedata.length"
                                            :items-source="gradedata">
                                        </wj-auto-complete>
                                        <p class="text-danger">{{ errors.grade }}</p>
                                    </div>   
                                </div>
                                <!-- <div class="col-sm-12 col-xs-12 col-md-3" v-show="isFormat" v-bind:class="{'has-error': (errors.grade != '') }">
                                    <label for="proGrade" class="inp">
                                        <input type="text" id="proGrade" v-model="inputData.grade" class="form-control" placeholder=" " v-bind:readonly="isReadOnly" @change="Naming">
                                        <span for="proGrade" class="label">等級</span>
                                        <span class="border"></span>
                                    </label>
                                    <p class="text-danger">{{ errors.grade }}</p>
                                </div> -->
                            </div>
                            <div class="form-group">
                                <div class="col-sm-12 col-xs-12 col-md-5" v-bind:class="{'has-error': (errors.model != '') }">
                                    <label for="proModel" class="inp">
                                        <input type="text" id="proModel" v-model="inputData.model" class="form-control" placeholder=" " v-bind:readonly="isReadOnly" @change="Naming">
                                        <span for="proModel" class="label">型式／規格</span>
                                        <span class="border"></span>
                                    </label>
                                    <p class="text-danger">{{ errors.model }}</p>
                                </div>
                                <div class="col-sm-12 col-xs-12 col-md-2" v-show="isFormat" v-bind:class="{'has-error': (errors.tree_length != '') }">
                                    <label for="proLen" class="inp">
                                        <input type="text" id="proLen" v-model="inputData.length" class="form-control" placeholder=" " v-bind:readonly="isReadOnly"  @change="Naming">
                                        <span for="proLen" class="label"><span style="color:red;">*</span>長さ</span>
                                        <span class="border"></span>
                                    </label>
                                    <p class="text-danger">{{ errors.tree_length }}</p>
                                </div>                                
                                <div class="col-sm-12 col-xs-12 col-md-2" v-show="isFormat" v-bind:class="{'has-error': (errors.thickness != '') }">
                                    <label for="proThick" class="inp">
                                        <input type="text" id="proThick" v-model="inputData.thickness" class="form-control" placeholder=" " v-bind:readonly="isReadOnly"  @change="Naming">
                                        <span for="proThick" class="label"><span style="color:red;">*</span>厚さ</span>
                                        <span class="border"></span>
                                    </label>
                                    <p class="text-danger">{{ errors.thickness }}</p>
                                </div>
                                <div class="col-sm-12 col-xs-12 col-md-2" v-show="isFormat" v-bind:class="{'has-error': (errors.width != '') }">
                                    <label for="proWidth" class="inp">
                                        <input type="text" id="proWidth" v-model="inputData.width" class="form-control" placeholder=" " v-bind:readonly="isReadOnly"  @change="Naming">
                                        <span for="proWidth" class="label"><span style="color:red;">*</span>幅</span>
                                        <span class="border"></span>
                                    </label>
                                    <p class="text-danger">{{ errors.width }}</p>
                                </div>
                            </div>
                            <div class="form-group">                                
                                <div class="col-sm-12 col-xs-12 col-md-5" v-bind:class="{'has-error': (errors.weight != '') }">
                                    <label for="proWeight" class="inp">
                                        <input type="text" id="proWeight" v-model="inputData.weight" class="form-control" placeholder=" " v-bind:readonly="isReadOnly">
                                        <span for="proWeight" class="label">重量(kg)</span>
                                        <span class="border">kg</span>
                                    </label>
                                    <p style="padding-top:8px;" class="text-danger">{{ errors.weight }}</p>
                                </div>
                                <div class="col-md-5 col-sm-12 col-xs-12" v-bind:class="{'has-error': (errors.maker_id != '') }">
                                    <label class="col-md-12 col-sm-12 col-xs-12"><span style="color:red;">*</span>メーカー名</label>
                                    <wj-auto-complete class="form-control" id="acMaker" v-bind:readonly="(isReadOnly || rmUndefinedZero(productdata.id) != 0)"
                                    search-member-path="supplier_name"
                                    display-member-path="supplier_name"
                                    selected-value-path="id"
                                    :selected-value="inputData.maker_id"
                                    :isReadOnly="(isReadOnly || rmUndefinedZero(productdata.id) != 0)"
                                    :lost-focus="selectMaker"
                                    :items-source="supplierdata">
                                    </wj-auto-complete>
                                    <p class="text-danger">{{ errors.maker_id }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-12 col-xs-12">
                            <label>商品画像</label>
                            <label class="image-preview">
                                <img class="image-preview" v-show="viewImage" :src="viewImage">
                                <span>
                                    <input type="file" class="uploadfile" accept="image/png, image/jpeg" style="display:none" @change="Preview" v-bind:disabled="isReadOnly">
                                </span>
                            </label>  
                        </div>
                    </div>

                    <div class="col-md-12 col-sm-12 col-xs-12 main-body">
                        <h4><u>価格等詳細</u></h4>                         
                        <div class="col-md-10 col-sm-12 col-xs-12" style="margin-top:20px;">
                            <div class="form-group">
                                <div class="col-sm-12 col-xs-12 col-md-2" v-bind:class="{'has-error': (errors.price != '') }">
                                    <label for="proPrice" class="inp">
                                        <input type="text" id="proPrice" v-model="inputData.price" class="form-control" placeholder=" " v-bind:readonly="(isReadOnly || inputData.open_price_flg == FLG_ON)" @change="salesRate(); salesPrice(); purchasePrice(); purchaseRate()">
                                        <span for="proPrice" class="label">定価</span>
                                        <span class="border"></span>
                                    </label>
                                    <p class="text-danger">{{ errors.price }}</p>
                                </div>
                                <div class="col-md-3 col-sm-12 col-xs-12" style="padding-top:15px;">
                                    <label class="col-md-12 col-sm-12 col-xs-12">オープン価格</label>
                                    <el-radio-group v-model="inputData.open_price_flg" v-bind:disabled="isReadOnly" @input="changeOpenPriceFlg">
                                        <div class="radio">
                                            <el-radio :label="FLG_ON">あり</el-radio>
                                            <el-radio :label="FLG_OFF">なし</el-radio>
                                        </div>
                                    </el-radio-group>
                                </div>
                                <div class="col-md-2 col-sm-12 col-xs-12" v-bind:class="{'has-error': (errors.unit != '') }">
                                    <label for="salesUnit" class="inp">
                                        <input type="text" id="salesUnit" v-model="inputData.unit" class="form-control" placeholder=" " v-bind:readonly="isReadOnly">
                                        <span for="salesUnit" class="label">単位</span>
                                        <span class="border"></span>
                                    </label>
                                    <p class="text-danger">{{ errors.unit }}</p>
                                </div>
                                <div class="col-md-2 col-sm-12 col-xs-12" v-bind:class="{'has-error': (errors.stock_unit != '') }">
                                    <label for="stockUnit" class="inp">
                                        <input type="text" id="stockUnit" v-model="inputData.stock_unit" class="form-control" placeholder=" " v-bind:readonly="isReadOnly">
                                        <span for="salesUnit" class="label">管理数単位</span>
                                        <span class="border"></span>
                                    </label>
                                    <p class="text-danger">{{ errors.stock_unit }}</p>
                                </div>                                
                            </div>                            
                            <div class="form-group">
                                <div class="col-md-3 col-sm-12 col-xs-12" v-bind:class="{'has-error': (errors.min_quantity != '') }">
                                    <label for="minQuantity" class="inp">
                                        <input type="text" id="minQuantity" v-model="inputData.min_quantity" class="form-control" placeholder=" " v-bind:readonly="(isReadOnly || inputData.intangible_flg == FLG_ON)">
                                        <span for="proQuantity" class="label">最小単位数量</span>
                                        <span class="border"></span>
                                    </label>
                                    <p class="text-danger">{{ errors.min_quantity }}</p>
                                </div>
                                <div class="col-md-3 col-sm-12 col-xs-12" v-bind:class="{'has-error': (errors.quantity_per_case != '') }">
                                    <label for="proQuantity" class="inp">
                                        <input type="text" id="proQuantity" v-model="inputData.quantity_per_case" class="form-control" placeholder=" " v-bind:readonly="isReadOnly">
                                        <span for="proQuantity" class="label">入り数</span>
                                        <span class="border"></span>
                                    </label>
                                    <p class="text-danger">{{ errors.quantity_per_case }}</p>
                                </div>
                                <div class="col-md-3 col-sm-12 col-xs-12" v-bind:class="{'has-error': (errors.order_lot_quantity != '') }">
                                    <label for="proLot" class="inp">
                                        <input type="text" id="proLot" v-model="inputData.order_lot_quantity" class="form-control" placeholder=" " v-bind:readonly="isReadOnly">
                                        <span for="proLot" class="label">ロット数</span>
                                        <span class="border"></span>
                                    </label>
                                    <p class="text-danger">{{ errors.order_lot_quantity }}</p>
                                </div>
                                <div class="col-sm-12 col-xs-12 col-md-3" v-bind:class="{'has-error': (errors.lead_time != '') }">
                                    <label for="leadtime" class="inp">
                                        <input type="text" id="leadtime" v-model="inputData.lead_time" class="form-control" placeholder=" " v-bind:readonly="isReadOnly">
                                        <span for="leadtime" class="label">リードタイム</span>
                                        <span class="border"></span>
                                    </label>
                                    <p class="text-danger">{{ errors.lead_time }}</p>
                                </div>
                                <!-- <div class="col-md-2 col-sm-12 col-xs-12" v-bind:class="{'has-error': (errors.purchase_unit != '') }">
                                    <label for="purUnit" class="inp">
                                        <input type="text" id="purUnit" v-model="inputData.purchase_unit" class="form-control" placeholder=" " v-bind:readonly="isReadOnly">
                                        <span for="purUnit" class="label">仕入単位</span>
                                        <span class="border"></span>
                                    </label>
                                    <p class="text-danger">{{ errors.purchase_unit }}</p>
                                </div> -->
                            </div>
                            <div class="form-group">
                                <div class="col-sm-12 col-xs-12 col-md-3" v-bind:class="{'has-error': (errors.purchase_makeup_rate != '') }">
                                    <label for="purchaseRate" class="inp">
                                        <input type="text" id="purchaseRate" v-model="inputData.purchase_makeup_rate" class="form-control" placeholder=" " v-bind:readonly="isReadOnly" @change="purchasePrice(); calcPrice();">
                                        <span for="purchaseRate" class="label">仕入掛率 (%)</span>
                                        <span class="border">%</span>
                                    </label>
                                    <p class="text-danger">{{ errors.purchase_makeup_rate }}</p>
                                </div>
                                <div class="col-sm-12 col-xs-12 col-md-3" v-bind:class="{'has-error': (errors.normal_purchase_price != '') }">
                                    <label for="standardPurchase" class="inp">
                                        <input type="text" id="standardPurchase" v-model="inputData.normal_purchase_price" class="form-control" placeholder=" " v-bind:readonly="isReadOnly" @change="purchaseRate(); calcPrice(); grossProfit();">
                                        <span for="standardPurchase" class="label">標準仕入単価</span>
                                        <span class="border"></span>
                                    </label>
                                    <p class="text-danger">{{ errors.normal_purchase_price }}</p>
                                </div>
                            </div>
                            <div class="form-group">                                
                                <div class="col-sm-12 col-xs-12 col-md-3" v-bind:class="{'has-error': (errors.sales_makeup_rate != '') }">
                                    <label for="salesRate" class="inp">
                                        <input type="text" id="salesRate" v-model="inputData.sales_makeup_rate" class="form-control" placeholder=" " v-bind:readonly="isReadOnly" @change="salesPrice">
                                        <span for="salesRate" class="label">販売掛率 (%)</span>
                                        <span class="border">%</span>
                                    </label>
                                    <p class="text-danger">{{ errors.sales_makeup_rate }}</p>
                                </div>
                                <div class="col-sm-12 col-xs-12 col-md-3" v-bind:class="{'has-error': (errors.normal_sales_price != '') }">
                                    <label for="standardSales" class="inp">
                                        <input type="text" id="standardSales" v-model="inputData.normal_sales_price" class="form-control" placeholder=" " v-bind:readonly="isReadOnly" @change="salesRate(); grossProfit();">
                                        <span for="standardSales" class="label">標準販売単価</span>
                                        <span class="border"></span>
                                    </label>
                                    <p class="text-danger">{{ errors.normal_sales_price }}</p>
                                </div>
                                <div class="col-sm-12 col-xs-12 col-md-3" v-bind:class="{'has-error': (errors.normal_gross_profit_rate != '') }">
                                    <label for="profitRate" class="inp">
                                        <input type="text" id="profitRate" v-model="inputData.normal_gross_profit_rate" class="form-control" placeholder=" " v-bind:readonly="isReadOnly" @change="calcSalesPrice(); salesRate();">
                                        <span for="profitRate" class="label">標準粗利率 (%)</span>
                                        <span class="border">%</span>
                                    </label>
                                    <p class="text-danger">{{ errors.normal_gross_profit_rate }}</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-3 col-sm-12 col-xs-12">
                                    <label class="col-md-12 col-sm-12 col-xs-12">税種別</label>
                                    <wj-auto-complete class="form-control" id="acTaxType" v-bind:readonly="isReadOnly"
                                    search-member-path="value_text_1"
                                    display-member-path="value_text_1"
                                    selected-value-path="value_code"
                                    :selected-value="inputData.tax_type"
                                    :isReadOnly="isReadOnly"
                                    :lost-focus="selectTaxType"
                                    :items-source="taxtypedata">
                                    </wj-auto-complete>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12 col-sm-12 col-xs-12 main-body">
                        <h4><u>商品履歴等</u></h4>
                        <div class="col-md-12 col-sm-12 col-xs-12" style="margin-top:20px;">
                            <div class="form-group">
                                <div class="col-md-3 col-sm-12 col-xs-12" style="padding-top:25px;" v-bind:class="{'has-error': (errors.start_date != '') }">
                                    <label class="control-label">適用開始日</label>
                                    <wj-input-date
                                        :value="inputData.start_date"
                                        placeholder=" "
                                        :isReadOnly="isReadOnly"
                                        :lost-focus="selectStartDate"
                                        :isRequired="false"
                                    ></wj-input-date>
                                    <p class="text-danger">{{ errors.start_date }}</p>
                                </div>
                                <div class="col-md-1">
                                    <div class="col-md-12 col-sm-12 col-xs-12" style="padding-top:30px;">
                                        <span>～</span>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-12 col-xs-12" style="padding-top:25px;" v-bind:class="{'has-error': (errors.end_date != '') }">
                                    <label class="control-label">適用終了日</label>
                                    <wj-input-date
                                        :value="inputData.end_date" 
                                        placeholder=" "
                                        :isReadOnly="isReadOnly"
                                        :lost-focus="selectEndDate"
                                        :isRequired="false"
                                    ></wj-input-date>
                                    <p class="text-danger">{{ errors.end_date }}</p>
                                </div>
                                <div class="col-sm-12 col-xs-12 col-md-3" v-bind:class="{'has-error': (errors.warranty_term != '') }">
                                    <label for="period" class="inp">
                                        <input type="text" id="period" v-model="inputData.warranty_term" class="form-control" placeholder=" " v-bind:readonly="isReadOnly">
                                        <span for="period" class="label">保証期間</span>
                                        <span class="border"></span>
                                    </label>
                                    <p class="text-danger">{{ errors.warranty_term }}</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-3 col-sm-12 col-xs-12">
                                    <label class="col-md-12 col-sm-12 col-xs-12">住宅履歴への転送</label>
                                    <el-radio-group v-model="inputData.housing_history_transfer_kbn" v-bind:disabled="isReadOnly">
                                        <div class="radio">
                                            <el-radio :label="FLG_OFF">しない</el-radio>
                                            <el-radio :label="FLG_ON">する</el-radio>
                                        </div>
                                    </el-radio-group>
                                </div>                                

                                <div class="col-md-4 col-sm-12 col-xs-12">
                                    <div class="col-md-12 col-sm-12 col-xs-12" v-bind:class="{'has-error': (errors.new_product_id != '') }">
                                        <label class="col-md-12 ">後継商品コード</label>
                                        <wj-auto-complete class="form-control" id="acClassBig" v-bind:readonly="isReadOnly"
                                        search-member-path="product_code"
                                        display-member-path="product_code"
                                        selected-value-path="product_id"
                                        :selected-value="inputData.new_product_id"
                                        :isReadOnly="isReadOnly"
                                        :initialized="initNewProduct"
                                        :selectedIndexChanged="selectProduct"
                                        :min-length=1
                                        :itemsSourceFunction="filterProductItemsSouceFunction"
                                        :text="productdata.new_product_text"
                                        ></wj-auto-complete> 
                                        <!-- :selectedIndexChanged="selectFilterProduct" -->
                                    </div>
                                    <p class="text-danger">{{ errors.new_product_id }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="col-md-12 col-sm-12 col-xs-12" v-bind:class="{'has-error': (errors.memo != '') }">
                                <label>備考</label>
                                <el-input v-bind:readonly="isReadOnly"
                                    type="textarea"
                                    :rows="3"
                                    v-model="inputData.memo">
                                </el-input>
                                <p class="text-danger">{{ errors.memo }}</p>
                            </div>
                        </div>
                    </div>
                </div>         
                <div class="col-md-12 col-sm-12 col-xs-12 text-right">
                    <button type="button" class="btn btn-primary btn-back" @click="back">戻る</button>
                </div>        
            </form>
        </div>
    </div>
</template>

<script>
export default {
    data: () => ({
        loading: false,
        isLocked: false,
        isShowEditBtn: true,
        // isEditable: true,
        isReadOnly: true,
        // 木材フォーマット
        isFormat: false,

        FLG_ON: 1,
        FLG_OFF: 0,
        wjObj: {
            class_big_id: {},
            class_middle_id: {},
            construction_id_1: {},
            construction_id_2: {},
            construction_id_3: [],
            class_small_id_1: {},
            class_small_id_2: {},
            class_small_id_3: {},
            tree_species: {},
            grade: {},
            new_product_id: {},
        },

        viewImage: '',
        // 未使用登録商品判定フラグ
        isAutoProduct: false,
        BEFORE_PRODUCT_CODE: '',

        inputData: {
            product_code: '',
            product_name: '',
            product_short_name: '',
            class_big_id: {},
            class_middle_id: {},
            construction_id_1: {},
            construction_id_2: {},
            construction_id_3: [],
            class_small_id_1: {},
            class_small_id_2: {},
            class_small_id_3: {},
            model: '',
            selectedStockKbn: '',
            // stock_border: '',
            tree_species: '',
            grade: '',
            tree_length: '',
            thickness: '',
            width: '',
            weight: '',
            maker_id: '',
            price: '',
            open_price_flg: '',
            min_quantity: '1.00',
            stock_unit: '',
            quantity_per_case: '',
            lead_time: '',
            order_lot_quantity: '',
            // purchase_unit: '',
            purchase_makeup_rate: '',
            normal_purchase_price: '',
            unit: '',
            sales_makeup_rate: '',
            normal_sales_price: '',
            normal_gross_profit_rate: '',
            tax_type: '',
            // tax_kbn: '',
            start_date: null,
            end_date: null,
            warranty_term: '',
            housing_history_transfer_kbn: '',
            new_product_id: '',
            memo: '',
            product_image: '',
            intangible_flg: 0,
            draft_flg: 0,
        },

        errors: {
            product_code: '',
            product_name: '',
            product_short_name: '',
            class_big_id: '',
            class_middle_id: '',
            construction_id_1: '',
            construction_id_2: '',
            construction_id_3: '',
            class_small_id_1: '',
            class_small_id_2: '',
            class_small_id_3: '',
            model: '',
            price: '',
            thickness: '',
            tree_species: '',
            grade: '',
            width: '',
            weight: '',
            tree_length: '',
            quantity_per_case: '',
            maker_id: '',
            open_price_flg: '',
            min_quantity: '',
            stock_unit: '',
            lead_time: '',
            order_lot_quantity: '',
            // purchase_unit: '',
            purchase_makeup_rate: '',
            normal_purchase_price: '',
            unit: '',
            sales_makeup_rate: '',
            normal_sales_price: '',
            normal_gross_profit_rate: '',
            tax_type: '',
            // tax_kbn: '',
            start_date: '',
            end_date: '',
            warranty_term: '',
            housing_history_transfer_kbn: '',
            new_product_id: '',
            memo: '',
            product_image: '',
            intangible_flg: '',
        },

    }),
    props: {
        productdata: Object,
        // productlist: {},
        taxkbndata: {},
        taxtypedata: {},
        supplierdata: {},
        constbigdata: Array,
        constdata: {},
        classbigdata: {},
        classmiddata: {},
        classsmalldata: {},
        isEditable: Number,
        isOwnLock: Number,
        lockdata: {},
        wooddata: Array,
        gradedata: Array,
        noProductCode: {},
    },
    created() {
        // TODO:ロック中の場合は編集ボタンを押せないようにして、ロック中の表示を出す
        if (this.isEditable == FLG_EDITABLE) {
            // 編集可
            this.isShowEditBtn = true;
            if (this.productdata.id == null) {
                this.isShowEditBtn = false;
                this.isReadOnly = false;
            }

            // ロック中判定
            if (this.rmUndefinedBlank(this.lockdata.id) != '' && this.isOwnLock != 1) {
                this.isLocked = true;
                this.isShowEditBtn = false;
                this.isReadOnly = true;
            }
        }
        
    },
    mounted() {
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
        // 新規の場合
        var currentUrl = location.href
        var separatorDir = currentUrl.split('/');
        if (separatorDir.pop() === 'new') {
            window.onbeforeunload = null;
        }
        // 照会モードの場合
        if (this.isReadOnly) {
            window.onbeforeunload = null;
        }

        this.wjObj.tree_species.loadingFlg = true;
        this.wjObj.grade.loadingFlg = true;

        // データ初期値
        if (this.productdata.id != null){
            // 編集
            this.inputData = this.productdata;
            this.inputData.tree_species = parseInt(this.rmUndefinedZero(this.inputData.tree_species));
            this.inputData.grade = parseInt(this.rmUndefinedZero(this.inputData.grade));
            this.inputData.tree_length = this.productdata.length;
            var ClassBigId = this.inputData.class_big_id;
            this.viewImage = this.productdata.product_image;
            // 木材だった場合フォーマット変更
            if (this.classmiddata[ClassBigId].format_kbn) {
                this.isFormat = true;
            }

            if (this.rmUndefinedZero(this.productdata.id) != 0 && this.rmUndefinedZero(this.productdata.auto_flg) == this.FLG_ON) {
                this.isAutoProduct = true;
            }

            // this.productlist.forEach((element, idx) => {
            //     if (this.productdata.id == element.id) {
            //         this.productlist.splice(idx, 1);
            //     }
            // });
            this.BEFORE_PRODUCT_CODE = this.productdata.product_code;
        }else {
            // 新規
            this.inputData.housing_history_transfer_kbn = this.FLG_ON;
            this.inputData.open_price_flg = this.FLG_OFF;
        }

        this.$nextTick(function () {
            this.wjObj.tree_species.loadingFlg = false;
            this.wjObj.grade.loadingFlg = false;
        })
    },
    methods: {
        changeChkAutoFlg() {
            // 本登録チェックを戻した場合、商品番号も戻す
            if (this.isAutoProduct && this.inputData.auto_flg == this.FLG_ON) {
                this.inputData.product_code = this.BEFORE_PRODUCT_CODE;
            }
        },
        selectProduct: function(sender) {
            var item = sender.selectedItem;
            if (item != null) {
                this.inputData.new_product_id = this.rmUndefinedZero(item.product_id);
            }
        },
        filterProductItemsSouceFunction(text, maxItems, callback){
            if (!text) {
                callback([]);
                return;
            }

            // サーバ通信中の場合 グリッド以外のオートコンプリートには存在しないプロパティのため初回はundefined
            if(this.wjObj.new_product_id.loadingFlg){
                return;
            }

            if(text.length < PRODUCT_AUTO_COMPLETE_SETTING.SEARCH_PRODUCT_CODE_LENGTH){
                return;
            }

            this.setASyncAutoCompleteList(this.wjObj.new_product_id, PRODUCT_AUTO_COMPLETE_SETTING.PRODUCT_CODE_URL, text, PRODUCT_AUTO_COMPLETE_SETTING.MAX_LIST_COUNT, PRODUCT_AUTO_COMPLETE_SETTING.OVER_PRODUCT_CODE_LIST, callback, this.getProductCodeAutoCompleteFilterData);
        },
        changeIntangibleFlg() {
            if (this.inputData.intangible_flg == this.FLG_ON) {
                this.inputData.min_quantity = 0.01;
                this.inputData.order_lot_quantity = 0.01;
            }
        },  
        changeOpenPriceFlg() {
            if (this.inputData.open_price_flg == this.FLG_ON) {
                this.inputData.price = 0;
            }
        },
        changeIdxConst1(sender) {
            // 工事区分1から工程1を絞り込む
            var tmpSmall = this.classsmalldata;
            if (sender.selectedValue) {
                tmpSmall = [];
                for(var key in this.classsmalldata) {
                    if (sender.selectedValue == this.classsmalldata[key].construction_id) {
                        tmpSmall.push(this.classsmalldata[key]);
                    }
                }             
            }
            this.wjObj.class_small_id_1.itemsSource = tmpSmall;
            this.wjObj.class_small_id_1.selectedIndex = -1;
        },
        changeIdxConst2(sender) {
             // 工事区分1から工程1を絞り込む
            var tmpSmall = this.classsmalldata;
            if (sender.selectedValue) {
                tmpSmall = [];
                for(var key in this.classsmalldata) {
                    if (sender.selectedValue == this.classsmalldata[key].construction_id) {
                        tmpSmall.push(this.classsmalldata[key]);
                    }
                }             
            }
            this.wjObj.class_small_id_2.itemsSource = tmpSmall;
            this.wjObj.class_small_id_2.selectedIndex = -1;
        },
        changeIdxConst3(sender) {
            // 工事区分1から工程1を絞り込む
            var tmpSmall = this.classsmalldata;
            if (sender.selectedValue) {
                tmpSmall = [];
                for(var key in this.classsmalldata) {
                    if (sender.selectedValue == this.classsmalldata[key].construction_id) {
                        tmpSmall.push(this.classsmalldata[key]);
                    }
                }             
            }
            this.wjObj.class_small_id_3.itemsSource = tmpSmall;
            this.wjObj.class_small_id_3.selectedIndex = -1;
        },
        changeIdxClassBig: function(sender){
            // 大分類選択で中分類を絞り込む
            // TODO: 大分類を選択したら工事区分絞り込み？
            //       大分類⇒中分類
            //       (大分類⇒)工事区分⇒小分類
            // 工事区分大分類マスタがないので一旦保留、　小分類と工事区分の紐付きが難しい。
            var tmpMid = this.classmiddata;
            if (sender.selectedValue) {
                tmpMid = [];
                for(var key in this.classmiddata) {
                    if (sender.selectedValue == this.classmiddata[key].class_big_id) {
                        tmpMid.push(this.classmiddata[key]);
                    }
                }             
            }

            this.wjObj.class_middle_id.itemsSource = tmpMid;
            this.wjObj.class_middle_id.selectedIndex = -1;

            // 大分類から工事区分を絞り込む
            var tmpArr = this.constbigdata;
            var tmpConst = this.constdata;
            if (sender.selectedItem) {
                tmpArr = [];
                for(var key in this.constbigdata) {
                    if (sender.selectedItem.class_big_id == this.constbigdata[key].class_big_id) {
                        tmpArr.push(this.constbigdata[key]);
                    }
                }
                tmpConst = [];
                for(var key in this.constdata) {
                    for(var i = 0; i < tmpArr.length; i++) {
                        if (tmpArr[i].construction_id == this.constdata[key].id) {
                            tmpConst.push(this.constdata[key]);
                            break;
                        }
                    }
                }      
            }
            this.wjObj.construction_id_1.itemsSource = tmpConst;
            this.wjObj.construction_id_1.selectedIndex = -1;
            this.wjObj.construction_id_2.itemsSource = tmpConst;
            this.wjObj.construction_id_2.selectedIndex = -1;
            this.wjObj.construction_id_3.itemsSource = tmpConst;
            this.wjObj.construction_id_3.selectedIndex = -1;

            // 木材だった場合は入力フォーマット変更
            if (sender.selectedItem.format_kbn) {
                this.isFormat = true;
            } else {
                this.isFormat = false;
            }
        },
        // 仕入掛率
        purchaseRate() {
            if (!this.inputData.open_price_flg && this.inputData.price != 0 && this.inputData.price != '' && this.inputData.normal_purchase_price != 0 && this.inputData.normal_purchase_price != '' &&  this.inputData.purchase_makeup_rate == ''){
                // var value = Math.floor((this.inputData.normal_purchase_price / this.inputData.price) * 10000) / 100;
                // var value = (this.inputData.normal_purchase_price / this.inputData.price) * 100;
                var value = this.bigNumberDiv(this.inputData.normal_purchase_price, this.inputData.price);
                value = this.bigNumberTimes(value, 100);
                value = this.roundDecimalRate(value);
                if (!isNaN(value)){
                    this.inputData.purchase_makeup_rate = value;

                    if (this.rmUndefinedZero(this.inputData.price) > 0 && this.rmUndefinedZero(this.inputData.normal_purchase_price) > 0) {
                        this.grossProfit();
                    }
                }
            }
        },
        // 標準仕入価格
        purchasePrice() {
            if (!this.inputData.open_price_flg && this.inputData.price != 0 && this.inputData.price != '' &&  this.inputData.purchase_makeup_rate != 0 && this.inputData.purchase_makeup_rate != '' && this.inputData.normal_purchase_price == ''){
                // var value = Math.ceil(this.inputData.price * (this.inputData.purchase_makeup_rate / 100));
                // var value = this.inputData.price * (this.inputData.purchase_makeup_rate / 100);
                var value = this.bigNumberDiv(this.inputData.purchase_makeup_rate, 100);
                value = this.bigNumberTimes(this.inputData.price, value);
                value = this.roundDecimalStandardPrice(value);
                if (!isNaN(value)){
                    this.inputData.normal_purchase_price = value;

                    if (this.rmUndefinedZero(this.inputData.price) > 0 && this.rmUndefinedZero(this.inputData.normal_sales_price) > 0 && this.rmUndefinedZero(this.inputData.normal_purchase_price) > 0) {
                        this.grossProfit();
                    }
                }
            }
        },
        // 販売掛率
        salesRate() {
            if (!this.inputData.open_price_flg && this.inputData.price != 0 && this.inputData.price != '' &&  this.inputData.normal_sales_price != 0 && this.inputData.normal_sales_price != '' && this.inputData.sales_makeup_rate == ''){
                //  var value = Math.floor((this.inputData.normal_sales_price / this.inputData.price) * 10000) / 100;
                // var value = (this.inputData.normal_sales_price / this.inputData.price) * 100;
                var value = this.bigNumberDiv(this.inputData.normal_sales_price, this.inputData.price);
                value = this.bigNumberTimes(value, 100);
                value = this.roundDecimalRate(value);
                if (!isNaN(value)){
                    this.inputData.sales_makeup_rate = value;

                    if (this.rmUndefinedZero(this.inputData.price) > 0 && this.rmUndefinedZero(this.inputData.normal_sales_price) > 0) {
                        this.grossProfit();
                    }
                }
            }   
        },
        // 標準販売価格
        salesPrice(){
            if (!this.inputData.open_price_flg && this.inputData.price != 0 && this.inputData.price != '' &&  this.inputData.sales_makeup_rate != 0 && this.inputData.sales_makeup_rate != '' && this.inputData.normal_sales_price == ''){
                //  var value = Math.ceil(this.inputData.price * (this.inputData.sales_makeup_rate / 100));
                //  var value = this.inputData.price * (this.inputData.sales_makeup_rate / 100);
                 var value = this.bigNumberDiv(this.inputData.sales_makeup_rate, 100);
                 value = this.bigNumberTimes(this.inputData.price, value);
                 value = this.roundDecimalSalesPrice(value);
                 if (!isNaN(value)) {
                     this.inputData.normal_sales_price = value;

                     if (this.rmUndefinedZero(this.inputData.price) > 0 && this.rmUndefinedZero(this.inputData.normal_sales_price) > 0 && this.rmUndefinedZero(this.inputData.normal_purchase_price) > 0) {
                        this.grossProfit();
                    }
                 }
            }  
        },
        // 仕入単価/仕入掛率　= 定価
        calcPrice() {
            if (!this.inputData.open_price_flg && this.inputData.purchase_makeup_rate != 0 && this.inputData.purchase_makeup_rate != '' &&  this.inputData.normal_purchase_price != 0 && this.inputData.normal_purchase_price != '' && this.inputData.price == ''){
                //  var value = Math.ceil(this.inputData.normal_purchase_price / (this.inputData.purchase_makeup_rate / 100));
                //  var value = this.inputData.normal_purchase_price / (this.inputData.purchase_makeup_rate / 100);
                 var value = this.bigNumberDiv(this.inputData.purchase_makeup_rate, 100);
                 value = this.bigNumberDiv(this.inputData.normal_purchase_price, value);
                 value = this.roundDecimalStandardPrice(value);
                 if (!isNaN(value)){
                    this.inputData.price = value;

                    if (this.rmUndefinedZero(this.inputData.price) > 0 && this.rmUndefinedZero(this.inputData.normal_sales_price) > 0 && this.rmUndefinedZero(this.inputData.normal_purchase_price) > 0) {
                        this.grossProfit();
                    }
                 }
            }
        },
        // 粗利率
        grossProfit() {
            if(!this.inputData.open_price_flg && this.inputData.normal_purchase_price != 0 && this.inputData.normal_purchase_price != '' && this.inputData.normal_sales_price != 0 && this.inputData.normal_sales_price != '' && this.inputData.normal_gross_profit_rate == ''){
                // var value = ((this.inputData.normal_sales_price - this.inputData.normal_purchase_price) / this.inputData.normal_sales_price) * 100;
                var value = this.bigNumberMinus(this.inputData.normal_sales_price, this.inputData.normal_purchase_price);
                value = this.bigNumberDiv(value, this.inputData.normal_sales_price);
                value = this.bigNumberTimes(value, 100);
                value = this.roundDecimalRate(value)
                if (!isNaN(value)) {
                    this.inputData.normal_gross_profit_rate = value;
                }
            }
        },
        // 仕入値 / (1 - 粗利率) = 販売価格
        calcSalesPrice() {
            if (!this.inputData.open_price_flg && this.inputData.normal_purchase_price != 0 && this.inputData.normal_purchase_price != '' &&  this.inputData.normal_gross_profit_rate != 0 && this.inputData.normal_gross_profit_rate != '' && this.inputData.normal_sales_price == ''){
                // var value = this.inputData.normal_purchase_price / (1 - (this.inputData.normal_gross_profit_rate / 100));
                var value = this.bigNumberDiv(this.inputData.normal_gross_profit_rate, 100);
                value = this.bigNumberMinus(1, value);
                value = this.bigNumberDiv(this.inputData.normal_purchase_price, value);
                value = this.roundDecimalSalesPrice(value)
                if (!isNaN(value)) {
                    this.inputData.normal_sales_price = value;
                }
            }
        },

        Naming() {
            console.log(this.wjObj.tree_species)
            console.log(this.wjObj.tree_species.loadingFlg)
            if (this.wjObj.tree_species.loadingFlg || this.wjObj.grade.loadingFlg) {
                return;
            }
            var productCode = '';
            var productName = '';
            if (this.isFormat) {
                if (this.wjObj.tree_species.selectedItem != null) {
                    this.inputData.tree_species = this.wjObj.tree_species.selectedValue;
                    productCode += this.wjObj.tree_species.selectedItem.value_code;
                    productName += this.wjObj.tree_species.selectedItem.value_text_1 + ' ';
                }
                if (this.wjObj.grade.selectedItem != null) {
                    this.inputData.grade = this.wjObj.grade.selectedValue;
                    var gradeId = this.wjObj.grade.selectedItem.value_code + '';
                    // 等級IDが1桁の場合はゼロ埋めして2桁にする
                    if (gradeId.length == 1) {
                        gradeId = ('00' + gradeId).slice(-2);   
                    }
                    productCode += gradeId;
                    productName += this.wjObj.grade.selectedItem.value_text_1 + ' ';
                }
                // if (this.inputData.model != ''&& this.inputData.model != null) {
                //     productCode += '_' + this.inputData.model;
                // }
                if (this.inputData.length != ''&& this.inputData.length != null) {
                    // 長さを1000で割った数値にする
                    var leng = parseInt(this.rmUndefinedZero(this.inputData.length)) / 1000;
                    if (leng >= 1) {
                        leng = leng.toString().replace('.', '');
                    }
                    productCode += leng;
                    productName += this.inputData.length + '×';
                }
                if (this.inputData.thickness != '' && this.inputData.thickness != null) {
                    productCode += this.inputData.thickness;
                    productName += this.inputData.thickness;
                }
                if (this.inputData.width != ''&& this.inputData.width != null) {
                    if (this.inputData.width != this.inputData.thickness) {
                        productCode += this.inputData.width;
                    }
                    productName += '×' + this.inputData.width;
                }                

                this.inputData.product_code = productCode;
                this.inputData.product_name = productName;
            }
            
        },
        save() {
            this.loading = true
            var isErr = false;

            // エラーの初期化
            this.initErr(this.errors);

            var checkFlg = true;
            if (this.rmUndefinedBlank(this.inputData.order_lot_quantity) == '') {
                this.inputData.order_lot_quantity = this.inputData.min_quantity;
            }
            if (this.rmUndefinedBlank(this.inputData.min_quantity) == '') {
                this.errors.min_quantity = MSG_ERROR_NO_INPUT;

                // エラー位置（最小単位数量）へリンク
                // var n = window.location.href.slice(window.location.href.indexOf('?') + 4);
                // var pos = $("#minQuantity").offset().top;
                // $('html,body').animate({ scrollTop: pos }, 'slow');
                isErr = true;
            }
            // 最小単位数量がロット数の倍数かどうかチェック
            if (!this.bigNumberEq(this.bigNumberMod(this.inputData.order_lot_quantity, this.inputData.min_quantity), 0)) {
                // this.errors.order_lot_quantity = MSG_ERROR_QUANTITY_NOT_DIVIDED;
                this.errors.min_quantity = MSG_ERROR_QUANTITY_NOT_DIVIDED;

                // エラー位置（最小単位数量）へリンク
                // var n = window.location.href.slice(window.location.href.indexOf('?') + 4);
                // var pos = $("#minQuantity").offset().top;
                // $('html,body').animate({ scrollTop: pos }, 'slow');
                isErr = true;
            }
            // 後継商品コードが存在しないIDの場合エラー
            if (this.rmUndefinedZero(this.inputData.new_product_id) == PRODUCT_AUTO_COMPLETE_SETTING.DEFAULT_PRODTCT_ID) {
                this.errors.new_product_id = MSG_ERROR_BACK_TO_PRODUCT;
                isErr = true;
            }
            if (!this.isMatchPattern(PRODUCT_CODE_REGEX, this.inputData.product_code)) {
                this.errors.product_code = MSG_ERROR_PRODUCT_CODE_REGEX;
                isErr = true;
            }   
            // 本登録する場合、仮の商品番号は不可
            if (this.isAutoProduct && this.inputData.auto_flg == this.FLG_OFF && this.inputData.product_code == this.noProductCode.value_text_1) {
                this.errors.product_code = '・'+MSG_ERROR_NO_INPUT_PRODUCT_CODE;
                isErr = true;
            }
            // 商品番号が3文字未満の場合は不可
            if (this.isAutoProduct && this.inputData.auto_flg == this.FLG_OFF && this.inputData.product_code.length < PRODUCT_AUTO_COMPLETE_SETTING.SEARCH_PRODUCT_CODE_LENGTH) {
                this.errors.product_code = '・'+MSG_ERROR_NO_INPUT_PRODUCT_CODE;
                isErr = true;
            }

            if (!isErr) {
                // 入力値の取得
                var params = new FormData();
                params.append('id', (this.productdata.id !== undefined) ? this.productdata.id : '');
                params.append('product_code', this.rmUndefinedBlank(this.inputData.product_code));
                params.append('product_name', this.rmUndefinedBlank(this.inputData.product_name));
                params.append('product_short_name', this.rmUndefinedBlank(this.inputData.product_short_name));
                params.append('class_big_id', this.rmUndefinedBlank(this.wjObj.class_big_id.selectedValue));
                params.append('class_middle_id', this.rmUndefinedZero(this.wjObj.class_middle_id.selectedValue));
                params.append('construction_id_1', this.rmUndefinedBlank(this.wjObj.construction_id_1.selectedValue));
                params.append('construction_id_2', this.rmUndefinedZero(this.wjObj.construction_id_2.selectedValue));
                params.append('construction_id_3', this.rmUndefinedZero(this.wjObj.construction_id_3.selectedValue));
                params.append('class_small_id_1', this.rmUndefinedZero(this.wjObj.class_small_id_1.selectedValue));
                params.append('class_small_id_2', this.rmUndefinedZero(this.wjObj.class_small_id_2.selectedValue));
                params.append('class_small_id_3', this.rmUndefinedZero(this.wjObj.class_small_id_3.selectedValue));
                // params.append('unit', this.rmUndefinedBlank(this.inputData.unit));
                params.append('model', this.rmUndefinedBlank(this.inputData.model));
                params.append('tree_species', this.rmUndefinedBlank(this.inputData.tree_species));
                params.append('grade', this.rmUndefinedBlank(this.inputData.grade));
                params.append('tree_length', this.rmUndefinedBlank(this.inputData.length));
                params.append('thickness', this.rmUndefinedBlank(this.inputData.thickness));
                params.append('width', this.rmUndefinedBlank(this.inputData.width));
                params.append('weight', this.rmUndefinedBlank(this.inputData.weight));
                params.append('maker_id', this.rmUndefinedBlank(this.inputData.maker_id));
                params.append('price', this.rmUndefinedZero(this.inputData.price));
                params.append('open_price_flg', this.rmUndefinedZero(this.inputData.open_price_flg));
                params.append('min_quantity', this.rmUndefinedZero(this.inputData.min_quantity));
                params.append('stock_unit', this.rmUndefinedBlank(this.inputData.stock_unit));
                params.append('quantity_per_case', this.rmUndefinedBlank(this.inputData.quantity_per_case));
                params.append('lead_time', this.rmUndefinedBlank(this.inputData.lead_time));
                params.append('order_lot_quantity', this.rmUndefinedBlank(this.inputData.order_lot_quantity));
                // params.append('purchase_unit', this.rmUndefinedBlank(this.inputData.purchase_unit));
                params.append('purchase_makeup_rate', this.rmUndefinedZero(this.inputData.purchase_makeup_rate));
                params.append('normal_purchase_price', this.rmUndefinedZero(this.inputData.normal_purchase_price));
                params.append('unit', this.rmUndefinedBlank(this.inputData.unit));
                params.append('sales_makeup_rate', this.rmUndefinedZero(this.inputData.sales_makeup_rate));
                params.append('normal_sales_price', this.rmUndefinedZero(this.inputData.normal_sales_price));
                params.append('normal_gross_profit_rate', this.rmUndefinedZero(this.inputData.normal_gross_profit_rate));
                params.append('tax_type', this.rmUndefinedZero(this.inputData.tax_type));
                // params.append('tax_kbn', this.rmUndefinedZero(this.inputData.tax_kbn));
                params.append('start_date', this.rmUndefinedBlank(this.inputData.start_date));
                params.append('end_date', this.rmUndefinedBlank(this.inputData.end_date));
                params.append('warranty_term', this.rmUndefinedBlank(this.inputData.warranty_term));
                params.append('housing_history_transfer_kbn', this.rmUndefinedZero(this.inputData.housing_history_transfer_kbn));
                params.append('new_product_id', this.rmUndefinedZero(this.inputData.new_product_id));
                params.append('memo', this.rmUndefinedBlank(this.inputData.memo));
                params.append('intangible_flg', this.rmUndefinedZero(this.inputData.intangible_flg));
                params.append('draft_flg', this.rmUndefinedZero(this.inputData.draft_flg));
                params.append('auto_flg', this.rmUndefinedZero(this.inputData.auto_flg));
                
                params.append('product_image', this.inputData.product_image);

                axios.post('/product-edit/save', params, {headers: {'Content-Type': 'multipart/form-data'}})

                .then( function (response) {
                    this.loading = false

                    if (response.data.status) {
                        // 成功
                        window.onbeforeunload = null;
                        var listUrl = '/product-list' + window.location.search
                        location.href = listUrl
                    } else {
                        // 失敗
                        alert(response.data.message)
                        if (response.data.message.indexOf('既に使われています。') > -1) {
                            this.errors.product_code = response.data.message;
                        }
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
            } else {
                var errText = '';                
                errText += MSG_ERROR_ILLEGAL_VALUE + "\n";
                var errList = this.errors;
                Object.keys(errList).forEach(function (key) {
                    // errText += this.errors[key] + "\n";
                    if (errList[key] != '') {
                        errText += "\n";
                    }
                    errText += errList[key];
                });
                alert(errText)
                this.loading = false;
                return false;
            }
        },
        del() {
            var confirmMessage = '';
            // 見積で使われている場合
            if (this.inputData.is_exist_quote) {
                confirmMessage += '・' + MSG_CONFIRM_PRODUCT_USED_BY_QUOTE;
            }
            // 在庫が存在する場合
            if (this.inputData.is_exist_stock) {
                confirmMessage += this.rmUndefinedBlank(confirmMessage) == '' ? '・' + MSG_CONFIRM_PRODUCT_USED_BY_STOCK : '\n' + '・' + MSG_CONFIRM_PRODUCT_USED_BY_STOCK;
                // confirmMessage += MSG_CONFIRM_PRODUCT_USED_STOCK;
            }
            if (this.rmUndefinedBlank(confirmMessage) == '') {
                confirmMessage = MSG_CONFIRM_DELETE;
            }
            if (!confirm(confirmMessage)) {
                return;
            }
            
            this.loading = true

            var params = new URLSearchParams();
            params.append('id', (this.productdata.id !== undefined) ? this.productdata.id : '');
            axios.post('/product-edit/delete', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/product-list' + window.location.search
                    location.href = listUrl
                } else {
                    // 失敗
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
                window.onbeforeunload = null;
                location.reload()
            }.bind(this))
        },
        //戻る
        back() {
            var listUrl = '/product-list' + window.location.search

            if (!this.isReadOnly && this.productdata.id) {
                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'product-edit');
                params.append('keys', this.rmUndefinedBlank(this.productdata.id));
                axios.post('/common/release-lock', params)

                .then( function (response) {
                    this.loading = false
                    if (response.data) {
                        window.onbeforeunload = null;
                        location.href = listUrl
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
        // 編集モード
        edit() {
            var params = new URLSearchParams();
            params.append('screen', 'product-edit');
            params.append('keys', this.rmUndefinedBlank(this.productdata.id));
            axios.post('/common/lock', params)

            .then( function (response) {
                this.loading = false
                if (response.data.status) {
                    if (response.data.isLocked) {
                        alert(MSG_EDITING)
                        location.reload()
                    } else {
                        this.isReadOnly = false
                        this.isShowEditBtn = false
                        this.lockdata = response.data.lockdata
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
            var params = new URLSearchParams();
            params.append('screen', 'product-edit');
            params.append('keys', this.rmUndefinedBlank(this.productdata.id));
            axios.post('/common/gain-lock', params)

            .then( function (response) {
                this.loading = false
                if (response.data.status) {
                    // 編集中状態へ
                    this.isLocked = false
                    this.isReadOnly = false
                    this.isShowEditBtn = false
                    this.lockdata = response.data.lockdata
                    window.onbeforeunload = function(e) {
                        return MSG_CONFIRM_LEAVE;
                    };
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

        // 画像プレビュー
        Preview(e) {
            let files = e.target.files[0];
            let reader = new FileReader();
            reader.onload = (e) => {
                this.viewImage = e.target.result;
            };
            this.inputData.product_image = e.target.files[0];
            reader.readAsDataURL(files);
        },

        selectMaker: function(sender) {
            // LostFocus時に選択したメーカーIDを取得
            this.inputData.maker_id = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
        },
        selectStartDate: function(sender) {
            // LostFocus時に選択した適用開始日を取得。
            this.inputData.start_date = this.rmUndefinedBlank(sender.text)
        },
        selectEndDate: function(sender) {
            // LostFocus時に選択した適用終了日を取得。
            this.inputData.end_date = this.rmUndefinedBlank(sender.text)
        },
        selectTaxType: function(sender) {
            // LostFocus時に選択した税種別を取得
            this.inputData.tax_type = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
        },
        selectTaxKbn: function(sender) {
            // LostFocus時に選択した税区分を取得
            this.inputData.tax_kbn = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
        },
        selectProductCode: function(sender) {
            // LostFocus時に選択した商品番号を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.inputData.product_code = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
        },
        selectProductName: function(sender) {
            // LostFocus時に選択した商品名を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.inputData.product_name = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
        },
        selectModel: function(sender) {
            // LostFocus時に選択した型式規格を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.inputData.model = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
        },
        selectMakerId: function(sender) {
            // LostFocus時に選択したメーカーを取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.inputData.maker_id = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
        },
        selectConstruction1: function(sender) {
            // LostFocus時に選択した工事区分を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.wjObj.construction_id_1 = sender;
        },
        selectConstruction2: function(sender) {
            // LostFocus時に選択した工事区分を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.wjObj.construction_id_2 = sender;
        },
        selectConstruction3: function(sender) {
            // LostFocus時に選択した工事区分を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.wjObj.construction_id_3 = sender;
        },
        selectBig: function(sender) {
            // LostFocus時に選択した大分類を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.wjObj.class_big_id = sender;
        },
        selectMiddle: function(sender) {
            // LostFocus時に選択した中分類を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.wjObj.class_middle_id = sender;
        },
        selectSmall1: function(sender) {
            // LostFocus時に選択した用途を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.wjObj.class_small_id_1 = sender;
        },
        selectSmall2: function(sender) {
            // LostFocus時に選択した用途を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.wjObj.class_small_id_2 = sender;
        },
        selectSmall3: function(sender) {
            // LostFocus時に選択した用途を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.wjObj.class_small_id_3 = sender;
        },
        initNewProduct: function(sender) {
            this.wjObj.new_product_id = sender;
        },
        initTreeSpecies(sender) {
            this.wjObj.tree_species = sender;
        },
        initGrade(sender) {
            this.wjObj.grade = sender;
        },
    },
    
}
</script>




<style>
.main-body {
    width: 100%;
    background: #ffffff;
    padding: 15px;
    margin-bottom:20px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.image-preview{
    width: 100%;
    height: 370px;
    background-position: center center;
    background-size: cover;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    display: inline-block;
    cursor: pointer;
}
.box {
    border-bottom: 1px solid black;
    padding-left: 2em;
}
.inp {
    margin-top: 25px;
}
.input_alphanumeric {
    ime-mode: disabled;
}
</style>