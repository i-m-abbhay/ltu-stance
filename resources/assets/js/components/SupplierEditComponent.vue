<template>
    <div>
        <loading-component :loading="loading" />
        <!-- モード変更 -->
        <div class="col-md-12 col-sm-12 text-right">
            <button type="button" class="btn btn-save"  v-show="(!isLocked && !isShowEditBtn && isEditable)" v-on:click="save">保存</button>
            <button type="button" class="btn btn-danger btn-unlock" v-on:click="unlock" v-show="isLocked">ロック解除</button>
            <button type="button" class="btn btn-primary btn-edit" v-on:click="edit" v-show="isShowEditBtn">編集</button>
            <p class="btn btn-default btn-editing" v-show="(!isLocked && !isShowEditBtn && isEditable)">編集中</p>
        </div>
        <div class="form-horizontal save-form">
            <form id="saveForm" name="saveForm" class="form-horizontal" enctype="multipart/form-data" method="post" action="/supplier-edit/save">
            <!-- 基本情報 -->
                <div class="panel-group col-md-12 col-sm-12" id="Accordion" role="tablist">
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingOne">
                            <h4 class="panel-title collapse-height">
                                <a class="collapsed col-md-12" data-toggle="collapse" data-parent="#Accordion" href="#AccordionCollapse1" role="button" aria-expanded="true" aria-controls="AccordionCollapse1">
                                    基本情報
                                </a>
                            </h4>
                        </div>
                        <div id="AccordionCollapse1" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">
                            <div class="panel-body">
                                <!-- メイン -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="col-md-8 col-sm-8 col-xs-12">
                                            <label class="control-label">法人格</label>
                                            <wj-auto-complete class="form-control"  v-bind:readonly="isReadOnly"
                                                search-member-path="value_text_1"
                                                display-member-path="value_text_1"
                                                selected-value-path="value_code"
                                                :isReadOnly="isReadOnly"
                                                :selected-index="-1"                                                
                                                :selected-value="parseInt(supplier.juridical_code)"
                                                :is-required="false"
                                                :initialized="initJuridical"
                                                :max-items="juridlist.length"
                                                :items-source="juridlist">
                                            </wj-auto-complete>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-10 col-md-10" v-bind:class="{'has-error': (errors.supplier_name != '') }">
                                            <label for="supName" class="inp">
                                                <input type="text" id="supName" class="form-control" v-model="supplier.supplier_name" placeholder=" " v-bind:readonly="isReadOnly">
                                                <span for="supName" class="label"><span style="color:red;">＊</span>仕入先／メーカー名</span>
                                                <span class="border"></span>
                                            </label>
                                            <p class="text-danger">{{ errors.supplier_name }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-10 col-md-10" v-bind:class="{'has-error': (errors.supplier_kana != '') }">
                                            <label for="supKana" class="inp">
                                                <input type="text" id="supKana" class="form-control" placeholder=" " v-model="supplier.supplier_kana" v-bind:readonly="isReadOnly">
                                                <span for="supKana" class="label">カタカナ</span>
                                                <span class="border"></span>
                                            </label>
                                            <p class="text-danger">{{ errors.supplier_kana }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-6 col-md-6" v-bind:class="{'has-error': (errors.corporate_number != '') }">
                                            <label for="supNum" class="inp">
                                                <input type="text" id="supNum" class="form-control" placeholder=" " v-model="supplier.corporate_number" v-bind:readonly="isReadOnly">
                                                <span for="supNum" class="label">法人番号</span>
                                                <span class="border"></span>
                                            </label>
                                            <p class="text-danger">{{ errors.corporate_number }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-8 col-md-8" v-bind:class="{'has-error': (errors.supplier_short_name != '') }">
                                            <label for="supSName" class="inp">
                                                <input type="text" id="supSName" class="form-control" placeholder=" " v-model="supplier.supplier_short_name" v-bind:readonly="isReadOnly">
                                                <span for="supSName" class="label">略称名／表示名</span>
                                                <span class="border"></span>
                                            </label>
                                            <p class="text-danger">{{ errors.supplier_short_name }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-8 col-md-8" v-bind:class="{'has-error': (errors.honorific != '') }">
                                            <label for="supHonorific" class="inp">
                                                <input type="text" id="supHonorific" class="form-control" placeholder=" " v-model="supplier.honorific" v-bind:readonly="isReadOnly">
                                                <span for="supHonorific" class="label">敬称</span>
                                                <span class="border"></span>
                                            </label>
                                            <p class="text-danger">{{ errors.honorific }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-10 col-md-10" v-bind:class="{'has-error': (errors.email != '') }">
                                            <label for="supEmail" class="inp">
                                                <input type="text" id="supEmail" class="form-control" placeholder=" " v-model="supplier.email" v-bind:readonly="isReadOnly">
                                                <span for="supEmail" class="label">メールアドレス</span>
                                                <span class="border"></span>
                                            </label>
                                            <p class="text-danger">{{ errors.email }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-12 col-md-12" v-bind:class="{'has-error': (errors.url != '') }">
                                            <label for="supUrl" class="inp">
                                            <input type="text" id="supUrl" class="form-control" placeholder=" " v-model="supplier.url" v-bind:readonly="isReadOnly">
                                            <span for="supUrl" class="label">ＵＲＬ</span>
                                            <span class="border"></span>
                                        </label>
                                            <p class="text-danger">{{ errors.url }}</p>
                                        </div>
                                    </div>   
                                </div>                        
                                <!-- サブ -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="col-sm-5 col-md-5" v-bind:class="{'has-error': (errors.zipcode != '') }">
                                            <label for="supzip" class="inp">
                                                <input type="text" id="supzip" class="form-control" v-model="supplier.zipcode" placeholder=" " maxlength="7" @change="getAddress" v-bind:readonly="isReadOnly">
                                                <span for="supzip" class="label">郵便番号</span>
                                                <span class="border"></span>
                                            </label>
                                            <p class="text-danger">{{ errors.zipcode }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-10 col-md-10" v-bind:class="{'has-error': (errors.address1 != '') }">
                                            <label for="supadr1" class="inp">
                                                <input type="text" id="supadr1" class="form-control" v-model="supplier.address1" placeholder=" " v-bind:readonly="isReadOnly">
                                                <span for="supadr1" class="label">住所1</span>
                                                <span class="border"></span>
                                            </label>
                                            <p class="text-danger">{{ errors.address1 }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group" v-bind:class="{'has-error': (errors.address2 != '') }">
                                        <div class="col-sm-10 col-md-10" v-bind:class="{'has-error': (errors.address1 != '') }">
                                            <label for="supadr2" class="inp">
                                                <input type="text" id="supadr2" class="form-control" v-model="supplier.address2" placeholder=" " v-bind:readonly="isReadOnly">
                                                <span for="supadr2" class="label">住所2</span>
                                                <span class="border"></span>
                                            </label>
                                            <p class="text-danger">{{ errors.address2 }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-8 col-md-8" v-bind:class="{'has-error': (errors.tel != '') }">
                                            <label for="supTel" class="inp">
                                                <input type="text" id="supTel" class="form-control" placeholder=" " v-model="supplier.tel" v-bind:readonly="isReadOnly">
                                                <span for="supTel" class="label">ＴＥＬ</span>
                                                <span class="border"></span>
                                            </label>
                                            <p class="text-danger">{{ errors.tel }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-8 col-md-8" v-bind:class="{'has-error': (errors.fax != '') }">
                                            <label for="supFax" class="inp">
                                                <input type="text" id="supFax" class="form-control" placeholder=" " v-model="supplier.fax" v-bind:readonly="isReadOnly">
                                                <span for="supFax" class="label">ＦＡＸ</span>
                                                <span class="border"></span>
                                            </label>
                                            <p class="text-danger">{{ errors.fax }}</p>
                                        </div>
                                    </div>
                                </div>   
                                <div class="col-md-12 col-sm-12 col-xs-12">
                                    <div class="form-group">
                                        <label class="col-md-12"><span style="color:red;">＊</span>仕入先メーカー区分</label>
                                        <el-radio-group v-model="supplier.supplier_maker_kbn" v-bind:disabled="(isReadOnly || iskbnlock)">
                                            <div class="radio col-md-4">
                                                <el-radio :label="FLG_DIRECT">メーカー直接取引</el-radio>
                                            </div>
                                            <div class="radio col-md-4">
                                                <el-radio :label="FLG_SUPPLIER">仕入先</el-radio>
                                            </div>
                                            <div class="radio col-md-4">
                                                <el-radio :label="FLG_MAKER">メーカー</el-radio>
                                            </div>
                                        </el-radio-group>
                                    </div> 
                                </div>
                                <div class="col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-12">取扱品目</label>
                                    <div class="form-group">
                                        <el-checkbox-group v-model="cBigList" v-bind:disabled="isReadOnly">
                                            <div class="checkbox col-md-2 col-sm-2" v-for="big in classbiglist" :key="big.id">
                                                <el-checkbox class="checkboxStyle col-md-3 col-sm-3" :label="big.id">{{ big.class_big_name }}</el-checkbox>
                                            </div>
                                        </el-checkbox-group>
                                    </div>
                                </div>      
                                <div class="col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-12">施工業者区分</label>
                                    <div class="form-group">
                                        <el-checkbox-group v-model="cConstList" v-bind:disabled="isReadOnly">
                                            <div class="checkbox col-md-2 col-sm-2" v-for="construct in constlist" :key="construct.id">
                                                <el-checkbox class="checkboxStyle col-md-3 col-sm-3" :label="construct.id">{{ construct.construction_name }}</el-checkbox>
                                            </div>
                                        </el-checkbox-group>
                                    </div>
                                </div>       
                                <div class="col-md-12 col-sm-12 col-xs-12">   
                                    <div class="form-group">
                                        <label class="col-md-12">帳票印字</label>
                                        <el-radio-group v-model="supplier.print_exclusion_flg" v-bind:disabled="isReadOnly">
                                            <div class="radio col-md-6">
                                                <el-radio :label="FLG_OFF">印字する</el-radio>
                                            </div>
                                            <div class="radio col-md-6">
                                                <el-radio :label="FLG_ON">印字除外する</el-radio>
                                            </div>
                                        </el-radio-group>
                                    </div> 
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- パーソン情報 -->
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingTwo">
                            <h4 class="panel-title collapse-height">
                                <a class="collapsed col-md-12" data-toggle="collapse" data-parent="#Accordion" href="#AccordionCollapse2" role="button" aria-expanded="false" aria-controls="AccordionCollapse2">
                                    パーソン情報
                                </a>
                            </h4>
                        </div>
                        <div id="AccordionCollapse2" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
                            <div class="panel-body">
                                <div v-for="(person, index) in activePersons" v-bind:key="index" class="col-md-12 col-sm-12 col-xs-12 main-body"  style="margin-top:10px;">
                                    <div class="col-md-5 col-sm-12 col-xs-12 input-margin">
                                        <div class="form-group">
                                            <div class="col-sm-10 col-md-10" v-bind:class="{'has-error': (person.errors.name != '') }">
                                                <label v-bind:for="person.index" class="inp">
                                                    <input type="text" v-bind:key="person.index" class="form-control" placeholder=" " v-model="person.name" v-bind:readonly="isReadOnly">
                                                    <span class="label"><span style="color:red;">＊</span>氏名</span>
                                                    <span class="border"></span>
                                                </label>
                                                <p class="text-danger">{{ person.errors.name }}</p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-10 col-md-10" v-bind:class="{'has-error': (person.errors.kana != '') }">
                                                <label v-bind:for="person.index" class="inp">
                                                    <input type="text" v-bind:key="person.index" class="form-control" placeholder=" " v-model="person.kana" v-bind:readonly="isReadOnly">
                                                    <span class="label">カタカナ</span>
                                                    <span class="border"></span>
                                                </label>
                                                <p class="text-danger">{{ person.errors.kana }}</p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-10 col-md-10" v-bind:class="{'has-error': (person.errors.belong_name != '') }">
                                                <label v-bind:for="person.index" class="inp">
                                                    <input type="text" v-bind:key="person.index" class="form-control" placeholder=" " v-model="person.belong_name" v-bind:readonly="isReadOnly">
                                                    <span class="label">所属名</span>
                                                    <span class="border"></span>
                                                </label>
                                                <p class="text-danger">{{ person.errors.belong_name }}</p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-8 col-md-8" v-bind:class="{'has-error': (person.errors.position != '') }">
                                                <label v-bind:for="person.index" class="inp">
                                                    <input type="text" v-bind:key="person.index" class="form-control" placeholder=" " v-model="person.position" v-bind:readonly="isReadOnly">
                                                    <span class="label">役職</span>
                                                    <span class="border"></span>
                                                </label>
                                                <p class="text-danger">{{ person.errors.position }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-12 col-xs-12 input-margin">
                                        <div class="form-group">
                                            <div class="col-sm-10 col-md-10" v-bind:class="{'has-error': (person.errors.tel1 != '') }">
                                                <label v-bind:for="person.index" class="inp">
                                                <input type="text" v-bind:key="person.index" class="form-control" placeholder=" " v-model="person.tel1" v-bind:readonly="isReadOnly">
                                                <span class="label">ＴＥＬ１</span>
                                                <span class="border"></span>
                                            </label>
                                            <p class="text-danger">{{ person.errors.tel1 }}</p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-10 col-md-10" v-bind:class="{'has-error': (person.errors.tel2 != '') }">
                                                <label v-bind:for="person.index" class="inp">
                                                    <input type="text" v-bind:key="person.index" class="form-control" placeholder=" " v-model="person.tel2" v-bind:readonly="isReadOnly">
                                                    <span class="label">ＴＥＬ２</span>
                                                    <span class="border"></span>
                                                </label>
                                                <p class="text-danger">{{ person.errors.tel2 }}</p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-12 col-md-12" v-bind:class="{'has-error': (person.errors.email1 != '') }">
                                                <label v-bind:for="person.index" class="inp">
                                                    <input type="text" v-bind:key="person.index" class="form-control" placeholder=" " v-model="person.email1" v-bind:readonly="isReadOnly">
                                                    <span class="label">メールアドレス１</span>
                                                    <span class="border"></span>
                                                </label>
                                                <p class="text-danger">{{ person.errors.email1 }}</p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-12 col-md-12" v-bind:class="{'has-error': (person.errors.email2 != '') }">
                                                <label v-bind:for="person.index" class="inp">
                                                    <input type="text" v-bind:key="person.index" class="form-control" placeholder=" " v-model="person.email2" v-bind:readonly="isReadOnly">
                                                    <span class="label">メールアドレス２</span>
                                                    <span class="border"></span>
                                                </label>
                                            <p class="text-danger">{{ person.errors.email2 }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-3">
                                        <label class="perPreview pull-right">
                                            <img class="perPreview" v-show="photoList[index].imagePreview" v-bind:src="photoList[index].imagePreview">
                                            <span>
                                                <input type="file" class="uploadfile" accept="image/png, image/jpeg" style="display:none" @change="onPersonChange(index, $event)" v-bind:disabled="isReadOnly">
                                            </span>
                                        </label>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-3">
                                        <button type="button" class="btn btn-danger pull-right" @click="deleteForm(index)" v-bind:disabled="isReadOnly">パーソンの削除</button>
                                    </div>
                                </div>
                                <div class="col-md-12 col-sm-12 col-xs-12 list-group-item input-margin">
                                    <p class="text-center" style="font-size:2em;">パーソンの追加 <button type="button" class="btn btn-primary" style="width:200px;" @click="addForm" v-bind:disabled="isReadOnly">＋</button></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- 倉庫入力フォーム -->
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingThree">
                            <h4 class="panel-title collapse-height">
                                <a class="collapsed col-md-12" data-toggle="collapse" data-parent="#Accordion" href="#AccordionCollapse3" role="button" aria-expanded="false" aria-controls="AccordionCollapse3">
                                    倉庫情報
                                </a>
                            </h4>
                        </div>
                        <div id="AccordionCollapse3" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
                            <div class="panel-body">
                                <div v-for="(warehouse, index) in activeWarehouse" v-bind:key="index" class="col-md-12 list-group-item card input-margin">
                                    <div class="col-md-3 input-margin">
                                        <div class="form-group">
                                            <div class="col-sm-12 col-md-12" v-bind:class="{'has-error': (warehouse.errors.warehouse_name != '') }">
                                                <label v-bind:for="warehouse.index" class="inp">
                                                    <input type="text" v-bind:key="warehouse.index" class="form-control" placeholder=" " v-model="warehouse.warehouse_name" v-bind:readonly="isReadOnly">
                                                    <span class="label"><span style="color:red;">＊</span>倉庫名</span>
                                                    <span class="border"></span>
                                                </label>
                                                <p class="text-danger">{{ warehouse.errors.warehouse_name }}</p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-12 col-md-12" v-bind:class="{'has-error': (warehouse.errors.warehouse_short_name != '') }">
                                                <label v-bind:for="warehouse.index" class="inp">
                                                    <input type="text" v-bind:key="warehouse.index" class="form-control" placeholder=" " v-model="warehouse.warehouse_short_name" v-bind:readonly="isReadOnly">
                                                    <span class="label">倉庫名略称</span>
                                                    <span class="border"></span>
                                                </label>
                                                <p class="text-danger">{{ warehouse.errors.warehouse_short_name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-md-6">
                                        <p>地図</p>
                                        <div class="mapPreview">
                                            <googlemaps-component v-bind:readonly="isReadOnly"
                                                :propsLatLng="activeWarehouse[index]"
                                                :index="(index + 1)"
                                                :propsListIndex="index"
                                                :propsaddress="warehouseAddress"
                                                @setLatLng="getWarehouseLatLng"
                                            ><slot></slot></googlemaps-component>
                                        </div>  
                                    </div>
                                    <!-- サブ -->
                                    <div class="col-md-3 input-margin">
                                        <div class="form-group" v-bind:class="{'has-error': (warehouse.errors.zipcode != '') }">
                                            <label class="col-sm-12 text-left">郵便番号</label>
                                            <div class="col-sm-6">
                                                <input type="text" class="form-control" v-model="warehouse.zipcode" maxlength="7" @change="getWarehouseAddress(index)" v-bind:readonly="isReadOnly">
                                                <span class="text-danger">{{ warehouse.errors.zipcode }}</span>
                                            </div>
                                        </div>
                                        <div class="form-group" v-bind:class="{'has-error': (warehouse.errors.address1 != '') }">
                                            <label class="col-sm-3 text-left">住所1</label>
                                            <div class="col-sm-12">
                                                <input type="text" class="form-control" name="warehouse_address1" v-model="warehouse.address1" v-bind:readonly="isReadOnly">
                                                <span class="text-danger">{{ warehouse.errors.address1 }}</span>
                                            </div>
                                        </div>
                                        <div class="form-group" v-bind:class="{'has-error': (warehouse.errors.address2 != '') }">
                                            <label class="col-sm-3 text-left">住所2</label>
                                            <div class="col-sm-12">
                                                <input type="text" class="form-control" name="warehouse_address2" v-model="warehouse.address2" v-bind:readonly="isReadOnly">
                                                <span class="text-danger">{{ warehouse.errors.address2 }}</span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-12 text-left">日本測地計</label>
                                            <div class="col-sm-12">
                                                <div v-bind:class="{'has-error': (warehouse.errors.latitude_jp != '') }">
                                                    <label class="col-sm-6 text-left">緯度</label>
                                                    <label class="col-sm-6 text-left">経度</label>
                                                    <div class="col-sm-6">
                                                        <input type="text" class="form-control" v-model="warehouse.latitude_jp" v-bind:readonly="isReadOnly">
                                                        <span class="text-danger">{{ warehouse.errors.latitude_jp }}</span>
                                                    </div>
                                                </div>
                                                <div v-bind:class="{'has-error': (warehouse.errors.longitude_jp != '') }">
                                                    <div class="col-sm-6">
                                                        <input type="text" class="form-control" v-model="warehouse.longitude_jp" v-bind:readonly="isReadOnly">
                                                        <span class="text-danger">{{ warehouse.errors.longitude_jp }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-12 text-left">世界測地計</label>
                                            <div class="col-sm-12">
                                                <div v-bind:class="{'has-error': (warehouse.errors.latitude_world != '') }">
                                                    <label class="col-sm-6 text-left">緯度</label>
                                                    <label class="col-sm-6 text-left">経度</label>
                                                    <div class="col-sm-6">
                                                        <input type="text" class="form-control" v-model="warehouse.latitude_world" v-bind:readonly="isReadOnly">
                                                        <span class="text-danger">{{ warehouse.errors.latitude_world }}</span>
                                                    </div>
                                                </div>
                                                <div v-bind:class="{'has-error': (warehouse.errors.longitude_world != '') }">
                                                    <div class="col-sm-6">
                                                        <input type="text" class="form-control" v-model="warehouse.longitude_world" v-bind:readonly="isReadOnly">
                                                        <span class="text-danger">{{ warehouse.errors.longitude_world }}</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-12 col-sm-12 col-xs-12 text-right">
                                                    <button type="button" class="btn btn-primary btn-search pull-right" @click="setWarehouseAddress(index)" v-bind:disabled="isReadOnly">住所から緯度経度を検索</button>
                                                    <div class="col-md-12 col-sm-12 col-xs-12" style="padding-top:5px;"></div>
                                                    <button type="button" class="btn btn-danger col-md-6 col-sm-6 col-xs-6 pull-right" @click="deleteWarehouse(index)" v-bind:disabled="isReadOnly">倉庫の削除</button>
                                                </div>
                                            </div>
                                            <div class="col-md-12 col-sm-12 col-xs-12 text-right">
                                                
                                            </div>
                                        </div>
                                       
                                    </div>
                                </div>
                                <div class="col-md-12 col-sm-12 list-group-item input-margin">
                                    <p class="text-center" style="font-size:2em;">倉庫の追加 <button type="button" class="btn btn-primary" style="width:200px;" @click="addWarehouse" v-bind:disabled="isReadOnly">＋</button></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- 与信情報 -->
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingFour">
                            <h4 class="panel-title collapse-height">
                                <a class="collapsed col-md-12" data-toggle="collapse" data-parent="#Accordion" href="#AccordionCollapse4" role="button" aria-expanded="false" aria-controls="AccordionCollapse4">
                                    支払情報
                                </a>
                            </h4>
                        </div>
                        <div id="AccordionCollapse4" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingFour">
                            <div class="panel-body">
                                <div class="col-md-12 card">
                                    <div class="col-md-12 col-sm-12 col-xs-12">
                                        <div class="form-group">
                                            <div class="col-md-4 col-sm-4">
                                                <label class="control-label">支払先</label>
                                                <wj-auto-complete class="form-control"  v-bind:readonly="isReadOnly"
                                                    search-member-path="supplier_name"
                                                    display-member-path="supplier_name"
                                                    selected-value-path="id"
                                                    :isReadOnly="isReadOnly"
                                                    :selected-index="-1"                                                
                                                    :selected-value="supplier.payee_id"
                                                    :is-required="false"
                                                    :initialized="initPayee"
                                                    :max-items="supplierlist.length"
                                                    :items-source="supplierlist">
                                                </wj-auto-complete>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12 col-sm-12 col-xs-12">
                                        <div class="form-group">
                                            <div class="col-md-3 col-sm-3 col-xs-12" v-bind:class="{'has-error': (errors.bank_code != '') }">
                                                <label class="control-label">銀行名</label>
                                                <wj-auto-complete class="form-control"  v-bind:readonly="isReadOnly"
                                                    search-member-path="bank_name"
                                                    display-member-path="bank_name"
                                                    selected-value-path="bank_code"
                                                    :isReadOnly="isReadOnly"
                                                    :selected-index="-1"                                                
                                                    :selected-value="supplier.bank_code"
                                                    :selectedIndexChanged="changeIdxBank"
                                                    :is-required="false"
                                                    :initialized="initBank"
                                                    :max-items="banklist.length"
                                                    :items-source="banklist">
                                                </wj-auto-complete>
                                                <span class="text-danger">{{ errors.bank_code }}</span>
                                            </div>
                                            <div class="col-md-3 col-sm-3 col-xs-12" v-bind:class="{'has-error': (errors.branch_code != '') }">
                                                <label class="control-label">支店名</label>
                                                <wj-auto-complete class="form-control"  v-bind:readonly="isReadOnly"
                                                    search-member-path="branch_name"
                                                    display-member-path="branch_name"
                                                    selected-value-path="branch_code"
                                                    :isReadOnly="isReadOnly"
                                                    :selected-index="-1"                                               
                                                    :selected-value="supplier.branch_code"
                                                    :is-required="false"
                                                    :initialized="initBBranch"
                                                    :max-items="branchlist.length"
                                                    :items-source="branchlist">
                                                </wj-auto-complete>
                                                <span class="text-danger">{{ errors.branch_code }}</span>
                                            </div>
                                            <div class="col-md-3 col-sm-3 col-xs-12" v-bind:class="{'has-error': (errors.account_type != '') }">
                                                <label class="text-left col-md-12 col-sm-12 col-xs-12" style="padding-top:7px;">口座種別</label>
                                                <el-radio-group v-model="supplier.account_type" v-for="acc in acckbn" :key="acc.id" v-bind:disabled="isReadOnly">
                                                    <div class="radio col-md-12 col-sm-12 col-xs-12">
                                                        <el-radio :label="acc.value_code">{{ acc.value_text_1 }}</el-radio>
                                                    </div>
                                                </el-radio-group>
                                                <span class="text-danger">{{ errors.account_type }}</span>
                                            </div> 
                                        </div>
                                    </div>
                                    <div class="col-md-12 col-sm-12 col-xs-12">
                                        <div class="form-group">
                                            <div class="col-sm-3 col-md-3">
                                                <div v-bind:class="{'has-error': (errors.account_number != '') }">
                                                    <label class="text-left">口座番号</label>
                                                    <input type="text" class="form-control" v-model="supplier.account_number" v-bind:readonly="isReadOnly">
                                                    <span class="text-danger">{{ errors.account_number }}</span>
                                                </div>
                                            </div>
                                            <div class="col-sm-3 col-md-3">
                                                <div v-bind:class="{'has-error': (errors.account_name != '') }">
                                                    <label class="text-left">口座名義</label>
                                                    <input type="text" class="form-control" v-model="supplier.account_name" v-bind:readonly="isReadOnly">
                                                    <span class="text-danger">{{ errors.account_name }}</span>
                                                </div>
                                            </div>
                                        </div>  
                                    </div>
                                    <div class="col-md-12 col-sm-12 col-xs-12">
                                        <div class="form-group">
                                            <div class="col-sm-2 col-md-2">
                                                <div v-bind:class="{'has-error': (errors.closing_day != '') }">
                                                    <label class="control-label text-left"><span class="required-mark">＊</span>締日</label>
                                                    <input type="number" class="form-control" min="0" max="99"  v-model="supplier.closing_day" v-bind:readonly="isReadOnly">
                                                    <span class="text-danger">{{ errors.closing_day }}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-3 col-sm-3" v-bind:class="{'has-error': (errors.payment_day != '') }">
                                                <label class="control-label"><span class="required-mark">＊</span>支払サイト</label>
                                                <wj-auto-complete class="form-control col-md-8 col-sm-8 col-xs-12"  v-bind:readonly="isReadOnly"
                                                    search-member-path="value_text_1"
                                                    display-member-path="value_text_1"
                                                    selected-value-path="value_code"
                                                    :isReadOnly="isReadOnly"
                                                    :selected-index="-1"
                                                    :selected-value="supplier.payment_sight"
                                                    :is-required="false"
                                                    :initialized="initPaySight"
                                                    :max-items="paysight.length"
                                                    :items-source="paysight"
                                                ></wj-auto-complete>
                                                <span class="text-danger">{{ errors.payment_sight }}</span>
                                            </div>
                                            <div class="col-sm-2 col-md-2">
                                                <div v-bind:class="{'has-error': (errors.payment_day != '') }">
                                                    <label class="text-left"><span class="required-mark">＊</span>支払日</label>
                                                    <input type="number" class="form-control" min="0" max="99"  v-model="supplier.payment_day" v-bind:readonly="isReadOnly">
                                                    <span class="text-danger">{{ errors.payment_day }}</span>
                                                </div>
                                            </div>
                                            <div class="col-sm-2 col-md-2">
                                                <div v-bind:class="{'has-error': (errors.electricitydebtno != '') }">
                                                    <label class="text-left">電債利用者番号</label>
                                                    <input type="text" class="form-control" v-model="supplier.electricitydebtno" v-bind:readonly="isReadOnly">
                                                    <span class="text-danger">{{ errors.electricitydebtno }}</span>
                                                </div>
                                            </div>  
                                        </div>  
                                    </div>
                                    <div class="col-md-12 col-sm-12 col-xs-12">
                                        <div class="form-group">
                                            <div class="col-sm-2 col-md-2 col-xs-10">
                                                <div v-bind:class="{'has-error': (errors.cash_rate != '') }">
                                                    <label class="text-left"><span class="required-mark">＊</span>現金割合</label>
                                                    <input type="number" class="form-control" v-model="supplier.cash_rate" v-bind:readonly="isReadOnly">
                                                    <span class="text-danger">{{ errors.cash_rate }}</span>
                                                </div>
                                            </div>
                                            <div class="col-xs-1 col-sm-1 col-md-1 percent-text">
                                                <span class="text-left">％</span>
                                            </div>
                                            <div class="col-sm-2 col-md-2 col-xs-10">
                                                <div v-bind:class="{'has-error': (errors.check_rate != '') }">
                                                    <label class="text-left"><span class="required-mark">＊</span>小切手割合</label>
                                                    <input type="number" class="form-control" v-model="supplier.check_rate" v-bind:readonly="isReadOnly">
                                                    <span class="text-danger">{{ errors.check_rate }}</span>
                                                </div>
                                            </div>
                                            <div class="col-xs-1 col-sm-1 col-md-1 percent-text">
                                                <span class="text-left">％</span>
                                            </div>
                                            <div class="col-sm-2 col-md-2 col-xs-10">
                                                <div v-bind:class="{'has-error': (errors.bill_rate != '') }">
                                                    <label class="text-left"><span class="required-mark">＊</span>手形割合</label>
                                                    <input type="number" class="form-control" v-model="supplier.bill_rate" v-bind:readonly="isReadOnly">
                                                    <span class="text-danger">{{ errors.bill_rate }}</span>
                                                </div>
                                            </div>
                                            <div class="col-xs-1 col-sm-1 col-md-1 percent-text">
                                                <span class="text-left">％</span>
                                            </div>
                                            <div class="col-sm-2 col-md-2 col-xs-10">
                                                <div v-bind:class="{'has-error': (errors.transfer_rate != '') }">
                                                    <label class="text-left"><span class="required-mark">＊</span>振込割合</label>
                                                    <input type="number" class="form-control" v-model="supplier.transfer_rate" v-bind:readonly="isReadOnly">
                                                    <span class="text-danger">{{ errors.transfer_rate }}</span>
                                                </div>
                                            </div>
                                            <div class="col-xs-1 col-sm-1 col-md-1 percent-text">
                                                <span class="text-left">％</span>
                                            </div>
                                        </div>  
                                    </div>
                                    <div class="col-md-12 col-sm-12 col-xs-12">
                                        <div class="form-group">
                                            <div class="col-sm-3 col-md-3">
                                                <div v-bind:class="{'has-error': (errors.bill_min_price != '') }">
                                                    <label class="text-left">最低手形支払額</label>
                                                    <input type="text" class="form-control" v-model="supplier.bill_min_price" v-bind:readonly="isReadOnly">
                                                    <span class="text-danger">{{ errors.bill_min_price }}</span>
                                                </div>
                                            </div>
                                            <div class="col-sm-2 col-md-2">
                                                <div v-bind:class="{'has-error': (errors.bill_deadline != '') }">
                                                    <label class="text-left">手形期日</label>
                                                    <input type="number" class="form-control" min="0" max="999"  v-model="supplier.bill_deadline" v-bind:readonly="isReadOnly">
                                                    <span class="text-danger">{{ errors.bill_deadline }}</span>
                                                </div>
                                            </div>
                                            <div class="col-sm-3 col-md-3">
                                                <div v-bind:class="{'has-error': (errors.bill_fee != '') }">
                                                    <label class="text-left">手形手数料</label>
                                                    <input type="text" class="form-control" v-model="supplier.bill_fee" v-bind:readonly="isReadOnly">
                                                    <span class="text-danger">{{ errors.bill_fee }}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-sm-4 col-xs-12">
                                                <label class="control-label">手形発行銀行</label>
                                                <wj-auto-complete class="form-control"  v-bind:readonly="isReadOnly"
                                                    search-member-path="bank_name"
                                                    display-member-path="bank_name"
                                                    selected-value-path="id"
                                                    :isReadOnly="isReadOnly"
                                                    :selected-index="-1"                                                
                                                    :selected-value="parseInt(supplier.bill_issuing_bank_id)"
                                                    :is-required="false"
                                                    :initialized="initBillBank"
                                                    :max-items="banklist.length"
                                                    :items-source="banklist">
                                                </wj-auto-complete>
                                            </div>
                                        </div>  
                                    </div>
                                    <div class="col-md-12 col-sm-12 col-xs-12">
                                        <div class="form-group">
                                            <label class="col-md-12">手数料区分</label>
                                            <div class="col-md-12 col-sm-12 col-xs-12">
                                                <el-radio-group v-model="supplier.fee_kbn" v-for="fee in feekbn" :key="fee.id" v-bind:disabled="isReadOnly">
                                                    <div class="radio col-md-4 col-sm-12 col-xs-12">
                                                        <el-radio :label="fee.value_code">{{ fee.value_text_1 }}</el-radio>
                                                    </div>
                                                </el-radio-group>
                                            </div> 
                                        </div> 
                                    </div>
                                    <div class="col-md-12 col-sm-12 col-xs-12">
                                        <div class="form-group">
                                            <div class="col-sm-2 col-md-2 col-xs-10">
                                                <div v-bind:class="{'has-error': (errors.safety_org_cost != '') }">
                                                    <label class="text-left"><span class="required-mark">＊</span>安全協力会費</label>
                                                    <input type="number" class="form-control" v-model="supplier.safety_org_cost" v-bind:readonly="isReadOnly">
                                                    <span class="text-danger">{{ errors.safety_org_cost }}</span>
                                                </div>
                                            </div>
                                            <div class="col-xs-1 col-sm-1 col-md-1 percent-text">
                                                <span class="text-left">％</span>
                                            </div>
                                            <div class="col-sm-2 col-md-2 col-xs-10">
                                                <div v-bind:class="{'has-error': (errors.receive_rebate != '') }">
                                                    <label class="text-left"><span class="required-mark">＊</span>受取リベート</label>
                                                    <input type="number" class="form-control" v-model="supplier.receive_rebate" v-bind:readonly="isReadOnly">
                                                    <span class="text-danger">{{ errors.receive_rebate }}</span>
                                                </div>
                                            </div>
                                            <div class="col-xs-1 col-sm-1 col-md-1 percent-text">
                                                <span class="text-left">％</span>
                                            </div>
                                            <div class="col-sm-2 col-md-2 col-xs-10">
                                                <div v-bind:class="{'has-error': (errors.sponsor_cost != '') }">
                                                    <label class="text-left"><span class="required-mark">＊</span>協賛金</label>
                                                    <input type="number" class="form-control" v-model="supplier.sponsor_cost" v-bind:readonly="isReadOnly">
                                                    <span class="text-danger">{{ errors.sponsor_cost }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12 col-sm-12 col-xs-12">
                                        <label class="text-left">備考</label>
                                        <textarea class="col-md-12 col-sm-12 col-xs-12" v-model="supplier.memo" v-bind:readonly="isReadOnly"></textarea>
                                    </div>                                    
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- 更新情報 -->
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingFive">
                            <h4 class="panel-title collapse-height">
                                <a class="collapsed col-md-12" data-toggle="collapse" data-parent="#Accordion" href="#AccordionCollapse5" role="button" aria-expanded="false" aria-controls="AccordionCollapse5">
                                    更新情報
                                </a>
                            </h4>
                        </div>
                        <div id="AccordionCollapse5" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingFive">
                            <div class="panel-body">
                                <div class="col-md-12 card">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="col-md-6 col-sm-5 col-xs-12 control-label">更新日時</label>
                                            <p class="col-md-6 col-sm-7 col-xs-12 control-label form-control-static">{{ supplierdata.update_at|datetime_format }}</p>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-6 col-sm-5 col-xs-12 control-label">更新者</label>
                                            <p class="col-md-6 col-sm-7 col-xs-12 control-label form-control-static">{{ supplierdata.update_user_name }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-12" v-show="(this.rmUndefinedBlank(this.lockdata.id) != '')">
                                        <div class="form-group">
                                            <label class="col-md-6 col-sm-5 col-xs-12 control-label">ロック日時</label>
                                            <p class="col-md-6 col-sm-7 col-xs-12 control-label form-control-static">{{ lockdata.lock_dt|datetime_format }}</p>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-6 col-sm-5 col-xs-12 control-label">ロック者</label>
                                            <p class="col-md-6 col-sm-7 col-xs-12 control-label form-control-static">{{ lockdata.lock_user_name }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>                
            </form>
            <!-- ボタン -->
            <div class="col-md-12 col-sm-12 col-xs-12 text-center">
                <button type="button" class="btn btn-back" v-on:click="back">戻る</button>
                <button type="button" class="btn btn-danger btn-delete" v-show="(!isReadOnly && supplierdata.id)" v-on:click="del">削除</button>
            </div>
        </div>
    </div>
</template>


<script>
export default {
    data: () => ({
        loading: false,
        isReadOnly: true,
        isShowEditBtn: false,
        isLocked: false,
        isSearchLatLng: false,

        FLG_OFF: 0,
        FLG_ON: 1,
        FLG_DIRECT: 0,
        FLG_SUPPLIER: 1,
        FLG_MAKER: 2,

        viewImage: '',
        uploadImage: '',
        
        // GoogleMaps用
        LatLng: {
            latitude_jp: '',
            lngitude_jp: '',
            latitude_world: '',
            lngitude_world: '',
        },
        warehouseAddress: {
            address: '',
            number: '',
        },

        wjInputObj: {
            juridical_code: {},
            payment_sight: {},
            payee_id: {},
            bank_code: {},
            branch_code: {},
            bill_issuing_bank_id: {},
        },
        // 入力データ
        supplier: {
            supplier_name: '',
            supplier_kana: '',
            corporate_number: '',
            supplier_short_name: '',
            juridical_code: '',
            honorific: '',
            tel: '',
            fax: '',
            email: '',
            url: '',
            zipcode: '',
            address1: '',
            address2: '',
            supplier_maker_kbn: 0,
            payee_id: '',
            bank_code: '',
            branch_code: '',
            account_type: 1,
            account_number: '',
            account_name: '',
            closing_day: '',
            payment_sight: '',
            payment_day: '',
            cash_rate: 0,
            check_rate: 0,
            bill_rate: 0,
            transfer_rate: 0,
            bill_min_price: '',
            bill_deadline: '',
            bill_fee: '',
            bill_issuing_bank_id: '',
            fee_kbn: 0,
            safety_org_cost: '',
            receive_rebate: '',
            sponsor_cost: '',
            memo: '',
            print_exclusion_flg: 0,
            electricitydebtno: '',
        },
        // チェックボックス値
        cBigList: [],
        cConstList: [],

        // パーソンリスト
        personList: [{
            id: '',
            personImage: '',
            name: '',
            kana: '',
            belong_name: '',
            position: '',
            tel1: '',
            tel2: '',
            email1: '',
            email2: '',
            del_flg: 0,
            errors: {
                name: '',
                kana: '',
                belong_name: '',
                position: '',
                tel1: '',
                tel2: '',
                email1: '',
                email2: '',
            },
        }],
        photoList: [{
            imagePreview: '',
        }],

        // 倉庫リスト
        warehouseList: [{
            id: '',
            warehouse_name: '',
            warehouse_short_name: '',
            zipcode: '',
            address1: '',
            address2: '',
            latitude_world: '',
            longitude_world: '',
            latitude_jp: '',
            longitude_jp: '',
            del_flg: 0,
            errors: {
                warehouse_name: '',
                warehouse_short_name: '',
                zipcode: '',
                address1: '',
                address2: '',
                latitude_world: '',
                longitude_world: '',
                latitude_jp: '',
                longitude_jp: '',
            },
        }],

        errors: {
            supplier_name: '',
            supplier_kana: '',
            corporate_number: '',
            supplier_short_name: '',
            juridical_code: '',
            honorific: '',
            tel: '',
            fax: '',
            email: '',
            url: '',
            zipcode: '',
            address1: '',
            address2: '',
            supplier_maker_kbn: '',
            payee_id: '',
            bank_code: '',
            branch_code: '',
            account_type: '',
            account_number: '',
            account_name: '',
            electricitydebtno: '',
            closing_day: '',
            payment_sight: '',
            payment_day: '',
            cash_rate: '',
            check_rate: '',
            bill_rate: '',
            transfer_rate: '',
            bill_min_price: '',
            bill_deadline: '',
            bill_fee: '',
            bill_issuing_bank_id: '',
            fee_kbn: '',
            safety_org_cost: '',
            receive_rebate: '',
            sponsor_cost: '',
            memo: '',
            print_exclusion_flg: '',

            person_name: '',
            person_kana: '',
            person_belong_name: '',
            person_position: '',
            person_tel1: '',
            person_tel2: '',
            person_email1: '',
            person_email2: '',

            warehouse_name: '',
            warehouse_short_name: '',
            warehouse_tel: '',
            warehouse_fax: '',
            warehouse_email: '',
            warehouse_zipcode: '',
            warehouse_address1: '',
            warehouse_address2: '',
            warehouse_latitude_world: '',
            warehouse_longitude_world: '',
            warehouse_latitude_jp: '',
            warehouse_longitude_jp: '',
        },
    }),
    props: {
        isEditable: Number,
        isOwnLock: Number,
        lockdata: {},
        supplierdata: {},
        persondata: Array,
        warehousedata: Array,
        classbiglist: Array,
        constlist: Array,
        feekbn: Array,
        paysight: Array,
        acckbn: Array,
        banklist: Array,
        branchlist: Array,
        supplierlist: Array,
        juridlist: Array,
        iskbnlock: Boolean,
    },
    computed: {
        // 削除フラグ"0"のレコードのみ表示
        activePersons: function() {
            return this.personList.filter(function (person) {
                return person.del_flg == 0;
            })
        },
        activeWarehouse: function() {
            return this.warehouseList.filter(function (warehouse) {
                return warehouse.del_flg == 0;
            })
        },
    },
    created() {
         if (this.isEditable == FLG_EDITABLE) {
            // 編集可
            this.isShowEditBtn = true;
            if (this.supplierdata.id == null) {
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
        // div(mapPreview)クラスにGmapIDを付与
        setTimeout(() => {
            $('div.mapPreview').each(function (index, element) {
                $(element).attr('id', 'Gmap' + (index + 1).toString().padStart(2, '0'));
            });
        }, 500);

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
        // 仕入先データ
        if (this.supplierdata.id != null) {
            this.supplier = this.supplierdata;

            if (this.rmUndefinedBlank(this.supplierdata.product_line) != '') {
                // 取扱品目
                var pLine = this.supplierdata.product_line;
                pLine = pLine.substring(1, pLine.length - 1);
                pLine = pLine.split(',');
                for (var i = 0; i < pLine.length; i++) {
                    this.cBigList.push(parseInt(pLine[i]));
                }
            }

            if (this.rmUndefinedBlank(this.supplierdata.contractor_kbn) != '') {
                // 施工業者区分
                var cKbn = this.supplierdata.contractor_kbn;
                cKbn = cKbn.substring(1, cKbn.length - 1);
                cKbn = cKbn.split(',');
                for (var i = 0; i < cKbn.length; i++) {
                    this.cConstList.push(parseInt(cKbn[i]));
                }
            }
        }
        // パーソンデータ
        if (this.persondata.length != 0) {
            this.personList = this.persondata;
            for(var i = 0; i < this.persondata.length; i++){
                // this.personList.splice(i, 1, this.persondata[i])
                var addView = {imagePreview: this.personList[i].image_file};
                this.photoList.splice(i, 1, addView);

                this.personList[i].errors = {
                    name: '',
                    kana: '',
                    belong_name: '',
                    position: '',
                    tel1: '',
                    tel2: '',
                    email1: '',
                    email2: '',
                }
            }
        }
        // 倉庫データ
        if (this.warehousedata.length != 0) {
            this.warehouseList = this.warehousedata;
           
            for (var i = 0; i < this.warehouseList.length; i++) {
                this.warehouseList[i].errors = {
                    warehouse_name: '',
                    warehouse_short_name: '',
                    zipcode: '',
                    address1: '',
                    address2: '',
                    latitude_world: '',
                    longitude_world: '',
                    latitude_jp: '',
                    longitude_jp: '',
                };
            }
        }
    },
    methods: {
        changeIdxBank: function(sender){
            // 銀行を選択したら支店を絞り込む
            var tmpBranch = this.branchlist;
            if (sender.selectedValue) {
                tmpBranch = [];
                for(var key in this.branchlist) {
                    if (sender.selectedValue == this.branchlist[key].bank_code) {
                        tmpBranch.push(this.branchlist[key]);
                    }
                }             
            }
            this.wjInputObj.branch_code.itemsSource = tmpBranch;
            this.wjInputObj.branch_code.selectedIndex = -1;
        },
        // 仕入先の郵便番号から住所を自動入力
        getAddress() {
            var zipcode = this.supplier.zipcode
            var supplier = this.supplier
            new YubinBango.Core(zipcode, function(addr){
                var addr1 = addr.region + addr.locality + addr.street
                supplier.address1 = addr1
            })
        },
        // 倉庫の郵便番号から住所を自動入力
        getWarehouseAddress(index) {
            var zipcode = this.activeWarehouse[index].zipcode
            var warehouse = this.activeWarehouse[index]
            new YubinBango.Core(zipcode, function(addr){
                var addr1 = addr.region + addr.locality + addr.street
                warehouse.address1 = addr1
            })
        },
        save() {
            this.loading = true

            // エラーの初期化
            this.initErr(this.errors);

            var OkFlg = true;
            // 仕入先メーカー区分が「メーカー」の場合支払情報のエラーチェック無視
            if(this.supplier.supplier_maker_kbn != this.FLG_MAKER){
                if (!(this.supplier.closing_day >= 0 && this.supplier.closing_day <= 28) && this.supplier.closing_day != 99) {
                    this.errors.closing_day = MSG_ERROR_CLOSING_DATE_RANGE;
                    OkFlg = false;
                }
                
                if (!(this.supplier.payment_day >= 0 && this.supplier.payment_day <= 28) && this.supplier.payment_day != 99) {
                    this.errors.payment_day = MSG_ERROR_CLOSING_DATE_RANGE;
                    OkFlg = false;
                }

                var sum_rate = 0;
                if (this.rmUndefinedZero(this.supplier.cash_rate) > 0) {
                    sum_rate = this.bigNumberPlus(sum_rate, this.rmUndefinedZero(this.supplier.cash_rate));
                }
                if (this.rmUndefinedZero(this.supplier.check_rate) > 0) {
                    sum_rate = this.bigNumberPlus(sum_rate, this.rmUndefinedZero(this.supplier.check_rate));
                }
                if (this.rmUndefinedZero(this.supplier.bill_rate) > 0) {
                    sum_rate = this.bigNumberPlus(sum_rate, this.rmUndefinedZero(this.supplier.bill_rate));
                }
                if (this.rmUndefinedZero(this.supplier.transfer_rate) > 0) {
                    sum_rate = this.bigNumberPlus(sum_rate, this.rmUndefinedZero(this.supplier.transfer_rate));
                }
                if (sum_rate != 100) {
                    OkFlg = false;
                    this.errors.cash_rate = MSG_ERROR_OVER_RATE;
                    this.errors.check_rate = MSG_ERROR_OVER_RATE;
                    this.errors.bill_rate = MSG_ERROR_OVER_RATE;
                    this.errors.transfer_rate = MSG_ERROR_OVER_RATE;
                    alert(MSG_ERROR_OVER_RATE);
                }
            }else{
                this.errors.closing_day = '';
                this.errors.payment_day = '';
                this.errors.payment_sight = '';
                this.errors.safety_org_cost = '';
                this.errors.receive_rebate = '';
                this.errors.sponsor_cost = '';
            }
            var params = new FormData();
            // 仕入先基本情報
            params.append('id', this.rmUndefinedBlank(this.supplierdata.id));
            params.append('supplier_name', this.rmUndefinedBlank(this.supplier.supplier_name));
            params.append('supplier_kana', this.rmUndefinedBlank(this.supplier.supplier_kana));
            params.append('supplier_short_name', this.rmUndefinedBlank(this.supplier.supplier_short_name));
            params.append('corporate_number', this.rmUndefinedBlank(this.supplier.corporate_number));
            params.append('juridical_code', this.rmUndefinedBlank(this.wjInputObj.juridical_code.selectedValue));
            params.append('honorific', this.rmUndefinedBlank(this.supplier.honorific));
            params.append('email', this.rmUndefinedBlank(this.supplier.email));
            params.append('url', this.rmUndefinedBlank(this.supplier.url));
            params.append('zipcode', this.rmUndefinedBlank(this.supplier.zipcode));
            params.append('address1', this.rmUndefinedBlank(this.supplier.address1));
            params.append('address2', this.rmUndefinedBlank(this.supplier.address2));
            params.append('tel', this.rmUndefinedBlank(this.supplier.tel));
            params.append('fax', this.rmUndefinedBlank(this.supplier.fax));
            params.append('supplier_maker_kbn', this.rmUndefinedZero(this.supplier.supplier_maker_kbn));
            params.append('product_line', this.cBigList);
            params.append('contractor_kbn', this.cConstList);
            params.append('print_exclusion_flg', this.rmUndefinedZero(this.supplier.print_exclusion_flg));

            // 支払情報
            params.append('payee_id', this.rmUndefinedZero(this.wjInputObj.payee_id.selectedValue));
            params.append('bank_code', this.rmUndefinedBlank(this.wjInputObj.bank_code.selectedValue));
            params.append('branch_code', this.rmUndefinedBlank(this.wjInputObj.branch_code.selectedValue));
            params.append('account_type', this.rmUndefinedZero(this.supplier.account_type));
            params.append('account_number', this.rmUndefinedBlank(this.supplier.account_number));
            params.append('account_name', this.rmUndefinedBlank(this.supplier.account_name));
            params.append('electricitydebtno', this.rmUndefinedBlank(this.supplier.electricitydebtno));
            params.append('closing_day', this.rmUndefinedBlank(this.supplier.closing_day));
            params.append('payment_sight', this.rmUndefinedZero(this.wjInputObj.payment_sight.selectedValue));
            params.append('payment_day', this.rmUndefinedBlank(this.supplier.payment_day));
            params.append('cash_rate', this.rmUndefinedBlank(this.supplier.cash_rate));
            params.append('check_rate', this.rmUndefinedBlank(this.supplier.check_rate));
            params.append('bill_rate', this.rmUndefinedBlank(this.supplier.bill_rate));
            params.append('transfer_rate', this.rmUndefinedBlank(this.supplier.transfer_rate));
            params.append('bill_min_price', this.rmUndefinedBlank(this.supplier.bill_min_price));
            params.append('bill_deadline', this.rmUndefinedBlank(this.supplier.bill_deadline));
            params.append('bill_fee', this.rmUndefinedBlank(this.supplier.bill_fee));
            params.append('bill_issuing_bank_id', this.rmUndefinedZero(this.wjInputObj.bill_issuing_bank_id.selectedValue));
            params.append('fee_kbn', this.rmUndefinedBlank(this.supplier.fee_kbn));
            params.append('safety_org_cost', this.rmUndefinedBlank(this.supplier.safety_org_cost));
            params.append('receive_rebate', this.rmUndefinedBlank(this.supplier.receive_rebate));
            params.append('sponsor_cost', this.rmUndefinedBlank(this.supplier.sponsor_cost));
            params.append('memo', this.rmUndefinedBlank(this.supplier.memo));

            
            // パーソン情報
            this.personList.forEach((value, index) => {
                this.initErr(value.errors);
                params.append('person[' + index + ']' + '[id]', (value.id != undefined) ? value.id : '');
                // 名前が空の場合登録しない
                if (this.rmUndefinedBlank(value.name) != '') {                    
                    params.append('person[' + index + ']' + '[seq]', index + 1);

                    if (this.rmUndefinedBlank(value.name).length > 10) {
                        value.errors.name = '10' + MSG_ERROR_LIMIT_OVER;
                        OkFlg = false;
                    } else {
                        params.append('person[' + index + ']' + '[name]', this.rmUndefinedBlank(value.name));                            
                    }

                    if (this.rmUndefinedBlank(value.kana).length > 20) {
                        value.errors.kana = '20' + MSG_ERROR_LIMIT_OVER;
                        OkFlg = false;
                    } else {
                        params.append('person[' + index + ']' + '[kana]', this.rmUndefinedBlank(value.kana));                           
                    }

                    if (this.rmUndefinedBlank(value.belong_name).length > 50) {
                        value.errors.belong_name = '50' + MSG_ERROR_LIMIT_OVER;
                        OkFlg = false;
                    } else {
                        params.append('person[' + index + ']' + '[belong_name]', this.rmUndefinedBlank(value.belong_name));
                    }

                    if (this.rmUndefinedBlank(value.position).length > 50) {
                        value.errors.position = '50' + MSG_ERROR_LIMIT_OVER;
                        OkFlg = false;
                    } else {
                        params.append('person[' + index + ']' + '[position]', this.rmUndefinedBlank(value.position));
                    }

                    if (this.rmUndefinedBlank(value.tel1).length > 20) {
                        value.errors.tel1 = '20' + MSG_ERROR_LIMIT_OVER;
                        OkFlg = false;
                    } else {
                        params.append('person[' + index + ']' + '[tel1]', this.rmUndefinedBlank(value.tel1));
                    }

                    if (this.rmUndefinedBlank(value.tel2).length > 20) {
                        value.errors.tel2 = '20' + MSG_ERROR_LIMIT_OVER;
                        OkFlg = false;
                    } else {
                        params.append('person[' + index + ']' + '[tel2]', this.rmUndefinedBlank(value.tel2));
                    }

                    if (this.rmUndefinedBlank(value.email1).length > 100) {
                        value.errors.email1 = '100' + MSG_ERROR_LIMIT_OVER;
                        OkFlg = false;
                    } else {
                        params.append('person[' + index + ']' + '[email1]', this.rmUndefinedBlank(value.email1));
                    }

                    if (this.rmUndefinedBlank(value.email2).length > 100) {
                        value.errors.email2 = '100' + MSG_ERROR_LIMIT_OVER;
                        OkFlg = false;
                    } else {
                        params.append('person[' + index + ']' + '[email2]', this.rmUndefinedBlank(value.email2));
                    }
                    params.append('person[' + index + ']' + '[del_flg]', this.rmUndefinedBlank(value.del_flg));
                    if(value.personImage !== undefined && value.personImage !== null && value.personImage !== this.persondata.image_file){
                        params.append('person[' + index + ']' + '[file]', value.personImage);
                    }else {
                        params.append('person[' + index + ']' + '[file]', '');
                    }
                }
            });
            
            // 倉庫情報
            this.warehouseList.forEach((value, index) => {
                this.initErr(value.errors);
                params.append('warehouse[' + index + ']' + '[id]', (value.id != undefined) ? value.id : '');
                // 名前が空の場合登録しない
                if (this.rmUndefinedBlank(value.warehouse_name) != '') {
                    params.append('warehouse[' + index + ']' + '[seq]', index + 1);

                    if (this.rmUndefinedBlank(value.warehouse_name).length > 30) {
                        value.errors.warehouse_name = '30' + MSG_ERROR_LIMIT_OVER;
                        OkFlg = false;
                    } else {
                        params.append('warehouse[' + index + ']' + '[warehouse_name]', this.rmUndefinedBlank(value.warehouse_name));
                    }

                    if (this.rmUndefinedBlank(value.warehouse_short_name).length > 10) {
                        value.errors.warehouse_short_name = '10' + MSG_ERROR_LIMIT_OVER;
                        OkFlg = false;
                    } else {
                        params.append('warehouse[' + index + ']' + '[warehouse_short_name]', this.rmUndefinedBlank(value.warehouse_short_name));
                    }

                    if (this.rmUndefinedBlank(value.tel).length > 20) {
                        value.errors.tel = '20' + MSG_ERROR_LIMIT_OVER;
                        OkFlg = false;
                    } else {
                        params.append('warehouse[' + index + ']' + '[tel]', this.rmUndefinedBlank(value.tel));
                    }

                    if (this.rmUndefinedBlank(value.fax).length > 20) {
                        value.errors.fax = '20' + MSG_ERROR_LIMIT_OVER;
                        OkFlg = false;
                    } else {
                        params.append('warehouse[' + index + ']' + '[fax]', this.rmUndefinedBlank(value.fax));
                    }

                    if (this.rmUndefinedBlank(value.email).length > 20) {
                        value.errors.email = '20' + MSG_ERROR_LIMIT_OVER;
                        OkFlg = false;
                    } else {
                        params.append('warehouse[' + index + ']' + '[email]', this.rmUndefinedBlank(value.email));
                    }

                    if (this.rmUndefinedBlank(value.zipcode).length > 7) {
                        value.errors.zipcode = '7' + MSG_ERROR_LIMIT_OVER;
                        OkFlg = false;
                    } else {
                        params.append('warehouse[' + index + ']' + '[zipcode]', this.rmUndefinedBlank(value.zipcode));
                    }

                    if (this.rmUndefinedBlank(value.address1).length > 50) {
                        value.errors.address1 = '50' + MSG_ERROR_LIMIT_OVER;
                        OkFlg = false;
                    } else {
                        params.append('warehouse[' + index + ']' + '[address1]', this.rmUndefinedBlank(value.address1));
                    }

                    if (this.rmUndefinedBlank(value.address2).length > 50) {
                        value.errors.address2 = '50' + MSG_ERROR_LIMIT_OVER;
                        OkFlg = false;
                    } else {
                        params.append('warehouse[' + index + ']' + '[address2]', this.rmUndefinedBlank(value.address2));
                    }
                    params.append('warehouse[' + index + ']' + '[latitude_jp]', this.rmUndefinedBlank(value.latitude_jp));
                    params.append('warehouse[' + index + ']' + '[longitude_jp]', this.rmUndefinedBlank(value.longitude_jp));
                    params.append('warehouse[' + index + ']' + '[latitude_world]', this.rmUndefinedBlank(value.latitude_world));
                    params.append('warehouse[' + index + ']' + '[longitude_world]', this.rmUndefinedBlank(value.longitude_world));
                    params.append('warehouse[' + index + ']' + '[del_flg]', this.rmUndefinedZero(value.del_flg))
                }
            }); 
            if (OkFlg) {
                axios.post('/supplier-edit/save', params, {headers: {'Content-Type': 'multipart/form-data'}})
                .then( function (response) {
                    this.loading = false

                    if (response.data) {
                        // 成功
                        window.onbeforeunload = null;
                        var listUrl = '/supplier-list' + window.location.search
                        location.href = (listUrl)
                    } else {
                        // 失敗
                        // alert(MSG_ERROR)
                        // location.reload();
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
                        // location.reload()
                    }
                }.bind(this))
            } else {
                this.loading = false;
            }
        },
        del() {
            if(!confirm(MSG_CONFIRM_DELETE)) {
                return;
            }
            
            this.loading = true

            var params = new URLSearchParams();
            params.append('id', (this.supplierdata.id !== undefined) ? this.supplierdata.id : '');
            axios.post('/supplier-edit/delete', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/supplier-list' + window.location.search
                    location.href = (listUrl)
                } else {
                    // 失敗
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
                location.reload()
            }.bind(this))
        },
        // 戻る
        back() {
            var listUrl = '/supplier-list' + window.location.search

            if (!this.isReadOnly && this.supplierdata.id) {
                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'supplier-edit');
                params.append('keys', this.rmUndefinedBlank(this.supplierdata.id));
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
            params.append('screen', 'supplier-edit');
            params.append('keys', this.rmUndefinedBlank(this.supplierdata.id));
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
            params.append('screen', 'supplier-edit');
            params.append('keys', this.rmUndefinedBlank(this.supplierdata.id));
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
        // パーソン追加
        addForm: function() {
            var addPerson　= {
                id: '',
                name: '',
                kana: '',
                belong_name: '',
                position: '',
                tel1: '',
                tel2: '',
                email1: '',
                email2: '',
                personImage: '',
                del_flg: 0,
                errors: {
                    name: '',
                    kana: '',
                    belong_name: '',
                    position: '',
                    tel1: '',
                    tel2: '',
                    email1: '',
                    email2: '',
                },
            };
            var addView = {imagePreview: '',};
            this.personList.push(addPerson);
            this.photoList.push(addView);
        },
        // パーソン削除
        deleteForm(index) {
            if(this.activePersons[index].name ? confirm(this.activePersons[index].name + 'さんを' + MSG_CONFIRM_DELETE) : true ){
                if(this.activePersons[index].id !== ''){
                    this.$set(this.activePersons[index], 'del_flg', '1');
                }else {
                    this.personList.splice(index, 1);
                    this.photoList.splice(index, 1);
                }
            }
        },
        // 倉庫追加
        addWarehouse: function () {
            var addData = {
                id: '',
                warehouse_name: '',
                warehouse_short_name: '',
                zipcode: '',
                address1: '',
                address2: '',
                latitude_world: '',
                longitude_world: '',
                latitude_jp: '',
                longitude_jp: '',
                del_flg: 0,
                errors: {
                    warehouse_name: '',
                    warehouse_short_name: '',
                    zipcode: '',
                    address1: '',
                    address2: '',
                    latitude_world: '',
                    longitude_world: '',
                    latitude_jp: '',
                    longitude_jp: '',
                },
            };

            this.warehouseList.push(addData);
            // GmapID付与
            setTimeout(() => {
                $('div.mapPreview').each(function (index, element) {
                    $(element).attr('id', 'Gmap' + (index + 1).toString().padStart(2, '0'));
                });
            }, 300);
        },
        // 倉庫削除
        deleteWarehouse(index) {
            if(this.activeWarehouse[index].warehouse_name ? confirm(this.activeWarehouse[index].warehouse_name + 'を' + MSG_CONFIRM_DELETE) : true ){
                if(this.activeWarehouse[index].id !== ''){
                    this.$set(this.activeWarehouse[index], 'del_flg', '1');
                }else {
                    this.warehouseList.splice(index, 1);
                }
            }
        },
        // 倉庫住所をセット
        setWarehouseAddress(index) {
            if (this.rmUndefinedBlank(this.activeWarehouse[index].address1) == '') {
                alert('住所を' + MSG_ERROR_NO_INPUT);
                return;
            }
            this.warehouseAddress.address = this.activeWarehouse[index].address1 + this.activeWarehouse[index].address2;
            this.warehouseAddress.number = index;
            this.isSearchLatLng = true;
        },
        // 倉庫マーカーの座標取得
        getWarehouseLatLng(e) {
            if (!this.isSearchLatLng) {
                this.$set(this.activeWarehouse[e.index], 'address1', e.address1);
                this.$set(this.activeWarehouse[e.index], 'address2', e.address2);
                this.$set(this.activeWarehouse[e.index], 'zipcode', e.zipcode);
            }
            this.$set(this.activeWarehouse[e.index], 'latitude_jp', e.jpLat);
            this.$set(this.activeWarehouse[e.index], 'longitude_jp', e.jpLng);
            this.$set(this.activeWarehouse[e.index], 'latitude_world', e.wLat);
            this.$set(this.activeWarehouse[e.index], 'longitude_world', e.wLng);
            setTimeout(() => {
                this.isSearchLatLng = false;
            }, 1000);
        },
        // 画像プレビュー
        onPersonChange(index, e) {
            let files = e.target.files[0];
            let reader = new FileReader();
            reader.onload = (e) => {
                var result = e.target.result;
                this.$set(this.photoList[index], "imagePreview", result);
            };
            this.$set(this.personList[index], "personImage", e.target.files[0]);
            reader.readAsDataURL(files);
        },
        /* 以下Wijmoオートコンプリート設定 */
        initPaySight(sender) {
            this.wjInputObj.payment_sight = sender;
        },
        initPayee(sender) {
            this.wjInputObj.payee_id = sender;
        },
        initBank(sender) {
            this.wjInputObj.bank_code = sender;
        },
        initBBranch(sender) {
            this.wjInputObj.branch_code = sender;
        },
        initBillBank(sender) {
            this.wjInputObj.bill_issuing_bank_id = sender;
        },
        initJuridical(sender) {
            this.wjInputObj.juridical_code = sender;
        },

    },
}
</script>

<style>
.mapPreview {
    width: 100%;
    height: 280px;
    background-position: center center;
    background-size: cover;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    display: inline-block;
}
.perPreview {
    width: 200px;
    height: 220px;
    background-position: center center;
    background-size: cover;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    display: inline-block;
    cursor: pointer;
}
.main-body {
    width: 100%;
    background: #ffffff;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.checkboxStyle {
    padding: 10px 0px 0px 2px;
}
.percent-text {
    padding: 38px 0px 0px;
}
.form-control {
    height: 43px;
}
.text-left {
    padding-top: 7px;
    margin-bottom: 0px;
}
.form-control-static {
    font-size: 12px;
    margin-top: 5px;
    padding-bottom: 10px;
    text-align: left !important;
}


</style>

