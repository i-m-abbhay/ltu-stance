<template>
    <div>
        <loading-component :loading="loading" />
        <!-- モード変更 -->
        <div class="col-md-12 col-sm-12 col-xs-12 text-right">
            <div class="row">
                <button type="button" class="btn btn-delete" v-show="(!isReadOnly && isEditable && !isLocked && !customer.del_flg && !isNew)" @click="del">無効化</button>
                <button type="button" class="btn btn-save" v-show="(!isReadOnly && isEditable && !isLocked && customer.del_flg && !isNew)" @click="active">有効化</button>
                <button type="button" class="btn btn-danger btn-unlock" v-on:click="unlock" v-show="isLocked">ロック解除</button>
                <button type="button" class="btn btn-primary btn-edit" v-on:click="edit" v-show="isShowEditBtn">編集</button>
                <p class="btn btn-default btn-editing" v-show="(!isLocked && !isShowEditBtn && isEditable)">編集中</p>
                <button type="button" class="btn btn-save" v-show="!isReadOnly" v-on:click="save">保存</button>
            </div>
        </div>
        <div class="form-horizontal save-form col-md-12 col-sm-12 col-xs-12">
            <form id="saveForm" name="saveForm" class="form-horizontal" enctype="multipart/form-data" method="post" action="/new-customer-edit/save">
            <!-- 基本情報 -->
                <div class="panel-group" id="Accordion" role="tablist">
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
                                <div class="col-md-12" style="height: 50px;">
                                    <div class="form-group pull-right">
                                        <label class="col-md-12 col-sm-12 control-label">最終更新日</label>
                                        <p class="col-md-12 col-sm-12 text-right">{{ customer.update_at|datetime_format }}</p>
                                    </div>
                                </div>
                                <!-- メイン -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <div class="col-md-12 col-sm-12 col-xs-12">
                                            <label class="control-label">法人格</label>
                                            <wj-auto-complete class="form-control"
                                                search-member-path="value_text_1"
                                                display-member-path="value_text_1"
                                                selected-value-path="value_code"
                                                :selected-index="-1"
                                                :selected-value="customer.juridical_code"
                                                :is-required="false"
                                                :initialized="initJuridical"
                                                :max-items="juridlist.length"
                                                :isReadOnly="isReadOnly"
                                                :min-length="1"
                                                :items-source="juridlist">
                                            </wj-auto-complete>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-12 col-md-12 col-xs-12" v-bind:class="{'has-error': (errors.customer_name != '') }">
                                            <label for="cusName" class="inp">
                                                <input type="text" id="cusName" class="form-control" v-bind:readonly="isReadOnly" v-model="customer.customer_name" placeholder=" ">
                                                <span for="cusName" class="label"><span style="color:red;">*</span>得意先名</span>
                                                <span class="border"></span>
                                            </label>
                                            <p class="text-danger">{{ errors.customer_name }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-12 col-md-12 col-xs-12" v-bind:class="{'has-error': (errors.customer_kana != '') }">
                                            <label for="cusKana" class="inp">
                                                <input type="text" id="cusKana" class="form-control" v-bind:readonly="isReadOnly" placeholder=" " v-model="customer.customer_kana">
                                                <span for="cusKana" class="label"><span style="color:red;">*</span>カタカナ</span>
                                                <span class="border"></span>
                                            </label>
                                            <p class="text-danger">{{ errors.customer_kana }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-8 col-md-8 col-xs-8" v-bind:class="{'has-error': (errors.corporate_number != '') }">
                                            <label for="cusNum" class="inp">
                                                <input type="text" id="cusNum" class="form-control" v-bind:readonly="isReadOnly" placeholder=" " v-model="customer.corporate_number">
                                                <span for="cusNum" class="label">法人番号</span>
                                                <span class="border"></span>
                                            </label>
                                            <p class="text-danger">{{ errors.corporate_number }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-10 col-md-10 col-xs-12" v-bind:class="{'has-error': (errors.customer_short_name != '') }">
                                            <label for="cusSName" class="inp">
                                                <input type="text" id="cusSName" class="form-control" v-bind:readonly="isReadOnly" placeholder=" " v-model="customer.customer_short_name">
                                                <span for="cusSName" class="label"><span style="color:red;">*</span>略称名／表示名</span>
                                                <span class="border"></span>
                                            </label>
                                            <p class="text-danger">{{ errors.customer_short_name }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-10 col-md-10 col-xs-12" v-bind:class="{'has-error': (errors.honorific != '') }">
                                            <label for="cusHonorific" class="inp">
                                                <input type="text" id="cusHonorific" class="form-control" v-bind:readonly="isReadOnly" placeholder=" " v-model="customer.honorific">
                                                <span for="cusHonorific" class="label">敬称</span>
                                                <span class="border"></span>
                                            </label>
                                            <p class="text-danger">{{ errors.honorific }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-10 col-md-10 col-xs-12" v-bind:class="{'has-error': (errors.tel != '') }">
                                            <label for="cusTel" class="inp">
                                                <input type="text" id="cusTel" class="form-control" v-bind:readonly="isReadOnly" placeholder=" " v-model="customer.tel">
                                                <span for="cusTel" class="label">ＴＥＬ</span>
                                                <span class="border"></span>
                                            </label>

                                            <p class="text-danger">{{ errors.tel }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-10 col-md-10 col-xs-12" v-bind:class="{'has-error': (errors.fax != '') }">
                                            <label for="cusFax" class="inp">
                                                <input type="text" id="cusFax" class="form-control" v-bind:readonly="isReadOnly" placeholder=" " v-model="customer.fax">
                                                <span for="cusFax" class="label">ＦＡＸ</span>
                                                <span class="border"></span>
                                            </label>
                                            <p class="text-danger">{{ errors.fax }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-12 col-md-12" v-bind:class="{'has-error': (errors.email != '') }">
                                            <label for="cusEmail" class="inp">
                                                <input type="text" id="cusEmail" class="form-control" v-bind:readonly="isReadOnly" placeholder=" " v-model="customer.email">
                                                <span for="cusEmail" class="label">メールアドレス</span>
                                                <span class="border"></span>
                                            </label>
                                            <p class="text-danger">{{ errors.email }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-12 col-md-12 col-xs-12" v-bind:class="{'has-error': (errors.url != '') }">
                                            <label for="cusUrl" class="inp">
                                            <input type="text" id="cusUrl" class="form-control" v-bind:readonly="isReadOnly" placeholder=" " v-model="customer.url">
                                            <span for="cusUrl" class="label">ＵＲＬ</span>
                                            <span class="border"></span>
                                        </label>
                                            <p class="text-danger">{{ errors.url }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-12 col-md-12 col-xs-12" v-bind:class="{'has-error': (errors.customer_code != '') }">
                                            <label for="cusCode" class="inp">
                                            <input type="text" id="cusCode" class="form-control" v-bind:readonly="isReadOnly" placeholder=" " v-model="customer.customer_code">
                                            <span for="cusCode" class="label">得意先コード</span>
                                            <span class="border"></span>
                                        </label>
                                            <p class="text-danger">{{ errors.customer_code }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-6 col-md-6 col-xs-8" v-bind:class="{'has-error': (errors.customer_rank != '') }">
                                            <label for="cusRank" class="inp">
                                            <input type="text" id="cusRank" class="form-control" v-bind:readonly="isReadOnly" placeholder=" " v-model="customer.customer_rank">
                                            <span for="cusRank" class="label">得意先ランク</span>
                                            <span class="border"></span>
                                        </label>
                                            <p class="text-danger">{{ errors.customer_rank }}</p>
                                        </div>
                                    </div>
                                </div> 
                                <div class="col-sm-6 col-md-6 col-xs-8">
                                    <p>地図</p>
                                    <div class="mapPreview">
                                        <mapA
                                            :propsLatLng="customerLatLng()"
                                            :searchAddressMap="searchAddress(customer.address1 + customer.address2)"
                                            :propsaddress="this.customerAddress"
                                            @setLatLng="getCustomerLatLng"
                                            :readonly="isReadOnly"
                                        ><slot></slot></mapA>
                                    </div>    
                                    <p style="padding-top: 30px;">会社写真　<i class="el-icon-paperclip"></i></p>
                                    <label class="imagePreview" @dragleave.prevent @dragover.prevent @drop.prevent="customerPreview" >
                                        <img class="imagePreview" v-show="viewImage" :src="viewImage" for="file-up">
                                        <span for="file-up">
                                            <input type="file" class="uploadfile" accept="image/png, image/jpeg" v-bind:disabled="isReadOnly" style="display:none" id="file-up" @change="customerPreview">
                                        </span>
                                    </label>      
                                    <div class="col-md-12 col-sm-12 col-xs-12" style="padding-top:20px;">
                                    <el-checkbox-group v-model="checkList">
                                        <div class="checkbox col-md-3 col-sm-3" v-for="checkbox in checkboxdata" :key="checkbox.value_code">
                                            <el-checkbox class="checkboxStyle col-md-3 col-sm-3" v-bind:disabled="isReadOnly" :label="checkbox.value_code">{{ checkbox.value_text_1 }}</el-checkbox>
                                        </div>
                                    </el-checkbox-group>
                                    <span class="text-danger">{{ errors.checkList }}</span>
                                </div>  
                                </div>                               
                                <!-- サブ -->
                                <div class="col-md-3 col-xs-12">
                                    <!-- <div class="form-group">
                                        <label class="col-sm-12 text-left">郵便番号</label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control" v-bind:readonly="isReadOnly" v-model="customer.zipcode" maxlength="7" @change="getAddress">
                                            <p class="text-danger">{{ errors.zipcode }}</p>
                                        </div>
                                    </div> -->
                                    <div class="form-group">
                                        <div class="col-sm-6 col-md-6" v-bind:class="{'has-error': (errors.zipcode != '') }">
                                            <label for="cusZip" class="inp">
                                            <input type="text" id="cusZip" class="form-control" v-bind:readonly="isReadOnly" placeholder=" " v-model="customer.zipcode" maxlength="7" @change="getAddress">
                                            <span for="cusZip" class="label">郵便番号</span>
                                            <span class="border"></span>
                                        </label>
                                            <p class="text-danger">{{ errors.zipcode }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-12 col-md-12" v-bind:class="{'has-error': (errors.address1 != '') }">
                                            <label for="cusAdd1" class="inp">
                                            <input type="text" id="cusAdd1" class="form-control" v-bind:readonly="isReadOnly" placeholder=" " v-model="customer.address1">
                                            <span for="cusAdd1" class="label">住所1</span>
                                            <span class="border"></span>
                                        </label>
                                            <p class="text-danger">{{ errors.address1 }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-12 col-md-12" v-bind:class="{'has-error': (errors.address2 != '') }">
                                            <label for="cusAdd2" class="inp">
                                            <input type="text" id="cusAdd2" class="form-control" v-bind:readonly="isReadOnly" placeholder=" " v-model="customer.address2">
                                            <span for="cusAdd2" class="label">住所2</span>
                                            <span class="border"></span>
                                        </label>
                                            <p class="text-danger">{{ errors.address2 }}</p>
                                        </div>
                                    </div>
                                    <!-- <div class="form-group">
                                        <label class="col-sm-3 text-left">住所1</label>
                                        <div class="col-sm-12">
                                            <input type="text" class="form-control" v-bind:readonly="isReadOnly" v-model="customer.address1">
                                            <p class="text-danger">{{ errors.address1 }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group" v-bind:class="{'has-error': (errors.address2 != '') }">
                                        <label class="col-sm-3 text-left">住所2</label>
                                        <div class="col-sm-12">
                                            <input type="text" class="form-control" v-bind:readonly="isReadOnly" v-model="customer.address2">
                                            <p class="text-danger">{{ errors.address2 }}</p>
                                        </div>
                                    </div> -->
                                    <div class="form-group">
                                        <label class="col-sm-12 text-left">日本測地計</label>
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <div class="col-sm-6 col-md-6" v-bind:class="{'has-error': (errors.latitude_jp != '') }">
                                                    <label for="cusLatJP" class="inp">
                                                    <input type="text" id="cusLatJP" class="form-control" v-bind:readonly="isReadOnly" placeholder=" " v-model="customer.latitude_jp">
                                                    <span for="cusLatJP" class="label">緯度</span>
                                                    <span class="border"></span>
                                                </label>
                                                    <p class="text-danger">{{ errors.latitude_jp }}</p>
                                                </div>
                                                <div class="col-sm-6 col-md-6" v-bind:class="{'has-error': (errors.longitude_jp != '') }">
                                                    <label for="cusLongJP" class="inp">
                                                    <input type="text" id="cusLongJP" class="form-control" v-bind:readonly="isReadOnly" placeholder=" " v-model="customer.longitude_jp">
                                                    <span for="cusLongJP" class="label">経度</span>
                                                    <span class="border"></span>
                                                </label>
                                                    <p class="text-danger">{{ errors.longitude_jp }}</p>
                                                </div>
                                            </div>
                                            <!-- <div v-bind:class="{'has-error': (errors.latitude_jp != '') }">
                                                <label class="col-sm-6 text-left">緯度</label>
                                                <label class="col-sm-6 text-left">経度</label>
                                                <div class="col-sm-6">
                                                    <input type="text" class="form-control" v-bind:readonly="isReadOnly" v-model="customer.latitude_jp">
                                                    <p class="text-danger">{{ errors.latitude_jp }}</p>
                                                </div>
                                            </div>
                                            <div v-bind:class="{'has-error': (errors.longitude_jp != '') }">
                                                <div class="col-sm-6">
                                                    <input type="text" class="form-control" v-bind:readonly="isReadOnly" v-model="customer.longitude_jp">
                                                    <p class="text-danger">{{ errors.longitude_jp }}</p>
                                                </div>
                                            </div> -->
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-12 text-left">世界測地計</label>
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <div class="col-sm-6 col-md-6" v-bind:class="{'has-error': (errors.latitude_world != '') }">
                                                    <label for="cusLatWrd" class="inp">
                                                    <input type="text" id="cusLatWrd" class="form-control" v-bind:readonly="isReadOnly" placeholder=" " v-model="customer.latitude_world">
                                                    <span for="cusLatWrd" class="label">緯度</span>
                                                    <span class="border"></span>
                                                </label>
                                                    <p class="text-danger">{{ errors.latitude_world }}</p>
                                                </div>
                                                <div class="col-sm-6 col-md-6" v-bind:class="{'has-error': (errors.longitude_world != '') }">
                                                    <label for="cusLongWrd" class="inp">
                                                    <input type="text" id="cusLongWrd" class="form-control" v-bind:readonly="isReadOnly" placeholder=" " v-model="customer.longitude_world">
                                                    <span for="cusLongWrd" class="label">経度</span>
                                                    <span class="border"></span>
                                                </label>
                                                    <p class="text-danger">{{ errors.longitude_world }}</p>
                                                </div>
                                            </div>
                                            <!-- <div v-bind:class="{'has-error': (errors.latitude_world != '') }">
                                                <label class="col-sm-6 text-left">緯度</label>
                                                <label class="col-sm-6 text-left">経度</label>
                                                <div class="col-sm-6">
                                                    <input type="text" class="form-control" v-bind:readonly="isReadOnly" v-model="customer.latitude_world" maxlength="8">
                                                    <p class="text-danger">{{ errors.latitude_world }}</p>
                                                </div>
                                            </div>
                                            <div v-bind:class="{'has-error': (errors.longitude_world != '') }">
                                                <div class="col-sm-6">
                                                    <input type="text" class="form-control" v-bind:readonly="isReadOnly" v-model="customer.longitude_world">
                                                    <p class="text-danger">{{ errors.longitude_world }}</p>
                                                </div>
                                            </div> -->
                                            <div class="text-right" style="padding-bottom:10px;">
                                                <button type="button" class="btn btn-search" v-bind:disabled="isReadOnly" @click="setAddress()">住所から緯度経度を検索</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-md-12 col-sm-12 col-xs-12">
                                            <label class="control-label">担当部門</label>
                                            <wj-auto-complete class="form-control"
                                                search-member-path="department_name"
                                                display-member-path="department_name"
                                                selected-value-path="id"
                                                :selected-index="-1"
                                                :selected-value="customer.charge_department_id"
                                                :is-required="false"
                                                :selectedIndexChanged="changeIdxDepartment"
                                                :initialized="initDepartment"
                                                :max-items="departmentlist.length"
                                                :isReadOnly="(isReadOnly || isChargeLocked.department)"
                                                :min-length="1"
                                                :items-source="departmentlist">
                                            </wj-auto-complete>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-md-12 col-sm-12 col-xs-12">
                                            <label class="control-label">担当営業</label>
                                            <wj-auto-complete class="form-control"
                                                search-member-path="staff_name"
                                                display-member-path="staff_name"
                                                selected-value-path="id"
                                                :selected-index="-1"
                                                :selected-value="customer.charge_staff_id"
                                                :is-required="false"
                                                :initialized="initStaff"
                                                :max-items="stafflist.length"
                                                :isReadOnly="(isReadOnly || isChargeLocked.staff)"
                                                :min-length="1"
                                                :items-source="stafflist">
                                            </wj-auto-complete>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-12 col-md-12 col-xs-12" v-bind:class="{'has-error': (errors.housing_history_login_id != '') }">
                                            <label for="cusHousingID" class="inp">
                                            <input type="text" id="cusHousingID" class="form-control" v-bind:readonly="isReadOnly" placeholder=" " v-model="customer.housing_history_login_id">
                                            <span for="cusHousingID" class="label">住宅履歴ログインID</span>
                                            <span class="border"></span>
                                        </label>
                                            <p class="text-danger">{{ errors.housing_history_login_id }}</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-12 col-md-12 col-xs-12" v-bind:class="{'has-error': (errors.housing_history_password != '') }">
                                            <label for="cusHousingPW" class="inp">
                                            <input type="text" id="cusHousingPW" class="form-control" v-bind:readonly="isReadOnly" placeholder=" " v-model="customer.housing_history_password">
                                            <span for="cusHousingPW" class="label">住宅履歴パスワード</span>
                                            <span class="border"></span>
                                        </label>
                                            <p class="text-danger">{{ errors.housing_history_password }}</p>
                                        </div>
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
                                <div v-for="(person, index) in activePersons" v-bind:key="index" class="col-md-12 col-xs-12 list-group-item person"  style="margin-top:10px;">
                                    <div class="col-md-5 col-xs-12 input-margin">
                                        <div class="form-group">
                                            <div class="col-sm-10 col-md-10" v-bind:class="{'has-error': (person.errors.name != '') }">
                                                <label v-bind:for="person.index" class="inp">
                                                    <input type="text" v-bind:key="person.index" v-bind:readonly="isReadOnly" class="form-control" placeholder=" " v-model="person.name">
                                                    <span class="label">氏名</span>
                                                    <span class="border"></span>
                                                </label>
                                                <p class="text-danger">{{ person.errors.name }}</p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-10 col-md-10" v-bind:class="{'has-error': (person.errors.kana != '') }">
                                                <label v-bind:for="person.index" class="inp">
                                                    <input type="text" v-bind:key="person.index" v-bind:readonly="isReadOnly" class="form-control" placeholder=" " v-model="person.kana">
                                                    <span class="label">カタカナ</span>
                                                    <span class="border"></span>
                                                </label>
                                                <p class="text-danger">{{ person.errors.kana }}</p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-10 col-md-10" v-bind:class="{'has-error': (person.errors.belong_name != '') }">
                                                <label v-bind:for="person.index" class="inp">
                                                    <input type="text" v-bind:key="person.index" v-bind:readonly="isReadOnly" class="form-control" placeholder=" " v-model="person.belong_name">
                                                    <span class="label">所属名</span>
                                                    <span class="border"></span>
                                                </label>
                                                <p class="text-danger">{{ person.errors.belong_name }}</p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-8 col-md-8" v-bind:class="{'has-error': (person.errors.position != '') }">
                                                <label v-bind:for="person.index" class="inp">
                                                    <input type="text" v-bind:key="person.index" v-bind:readonly="isReadOnly" class="form-control" placeholder=" " v-model="person.position">
                                                    <span class="label">役職</span>
                                                    <span class="border"></span>
                                                </label>
                                                <p class="text-danger">{{ person.errors.position }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-xs-12 input-margin">
                                        <div class="form-group">
                                            <div class="col-sm-10 col-md-10" v-bind:class="{'has-error': (person.errors.tel1 != '') }">
                                                <label v-bind:for="person.index" class="inp">
                                                <input type="text" v-bind:key="person.index" v-bind:readonly="isReadOnly" class="form-control" placeholder=" " v-model="person.tel1">
                                                <span class="label">ＴＥＬ１</span>
                                                <span class="border"></span>
                                            </label>
                                            <p class="text-danger">{{ person.errors.tel1 }}</p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-10 col-md-10" v-bind:class="{'has-error': (person.errors.tel2 != '') }">
                                                <label v-bind:for="person.index" class="inp">
                                                    <input type="text" v-bind:key="person.index" v-bind:readonly="isReadOnly" class="form-control" placeholder=" " v-model="person.tel2">
                                                    <span class="label">ＴＥＬ２</span>
                                                    <span class="border"></span>
                                                </label>
                                                <p class="text-danger">{{ person.errors.tel2 }}</p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-12 col-md-12" v-bind:class="{'has-error': (person.errors.email1 != '') }">
                                                <label v-bind:for="person.index" class="inp">
                                                    <input type="text" v-bind:key="person.index" v-bind:readonly="isReadOnly" class="form-control" placeholder=" " v-model="person.email1">
                                                    <span class="label">メールアドレス１</span>
                                                    <span class="border"></span>
                                                </label>
                                                <p class="text-danger">{{ person.errors.email1 }}</p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-12 col-md-12" v-bind:class="{'has-error': (person.errors.email2 != '') }">
                                                <label v-bind:for="person.index" class="inp">
                                                    <input type="text" v-bind:key="person.index" v-bind:readonly="isReadOnly" class="form-control" placeholder=" " v-model="person.email2">
                                                    <span class="label">メールアドレス２</span>
                                                    <span class="border"></span>
                                                </label>
                                            <p class="text-danger">{{ person.errors.email2 }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-3 col-md-3 col-xs-12">
                                        <label class="perPreview pull-right"  @dragleave.prevent @dragover.prevent @drop.prevent="onPersonChange(index, $event)">
                                            <img class="perPreview" v-show="photoList[index].imagePreview" v-bind:src="photoList[index].imagePreview">
                                            <span>
                                                <input type="file" class="uploadfile" accept="image/png, image/jpeg" style="display:none" v-bind:disabled="isReadOnly" @change="onPersonChange(index, $event)">
                                            </span>
                                        </label>
                                        <div class="col-sm-12 col-xs-12 col-md-12 text-right">
                                            <button type="button" class="btn btn-danger pull-right" v-bind:disabled="isReadOnly" @click="deleteForm(index)">パーソンの削除</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12 col-sm-12 col-xs-12 list-group-item input-margin">
                                    <p class="text-center" style="font-size:2em;">パーソンの追加 <button type="button" class="btn btn-primary" style="width:200px;" v-bind:disabled="isReadOnly" @click="addForm">＋</button></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <!-- 支店等入力フォーム -->
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingThree">
                            <h4 class="panel-title collapse-height">
                                <a class="collapsed col-md-12" data-toggle="collapse" data-parent="#Accordion" href="#AccordionCollapse3" role="button" aria-expanded="false" aria-controls="AccordionCollapse3">
                                    支店等情報
                                </a>
                            </h4>
                        </div>
                        <div id="AccordionCollapse3" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
                            <p class="col-md-12 h4">支店／作業場</p>
                            <div class="panel-body">
                                <div v-for="(branch, index) in activeBranchs" v-bind:key="index" class="col-md-12 list-group-item card input-margin">
                                    <div class="col-md-3 input-margin">
                                        <div class="form-group">
                                            <div class="col-sm-12 col-md-12" v-bind:class="{'has-error': (branch.errors.branch_name != '') }">
                                                <label v-bind:for="branch.index" class="inp">
                                                    <input type="text" v-bind:key="branch.index" v-bind:readonly="isReadOnly" class="form-control" placeholder=" " v-model="branch.branch_name">
                                                    <span class="label"><span style="color:red;">＊</span>支店／作業場名</span>
                                                    <span class="border"></span>
                                                </label>
                                                <p class="text-danger">{{ branch.errors.branch_name }}</p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-12 col-md-12" v-bind:class="{'has-error': (branch.errors.branch_kana != '') }">
                                                <label v-bind:for="branch.index" class="inp">
                                                    <input type="text" v-bind:key="branch.index" v-bind:readonly="isReadOnly" class="form-control" placeholder=" " v-model="branch.branch_kana">
                                                    <span class="label">カタカナ</span>
                                                    <span class="border"></span>
                                                </label>
                                                <p class="text-danger">{{ branch.errors.branch_kana }}</p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-10 col-md-10" v-bind:class="{'has-error': (branch.errors.tel != '') }">
                                                <label v-bind:for="branch.index" class="inp">
                                                    <input type="text" v-bind:key="branch.index" v-bind:readonly="isReadOnly" class="form-control" placeholder=" " v-model="branch.tel">
                                                    <span class="label">ＴＥＬ</span>
                                                    <span class="border"></span>
                                                </label>
                                                <p class="text-danger">{{ branch.errors.tel }}</p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-10 col-md-10" v-bind:class="{'has-error': (branch.errors.fax != '') }">
                                                <label v-bind:for="branch.index" class="inp">
                                                    <input type="text" v-bind:key="branch.index" v-bind:readonly="isReadOnly" class="form-control" placeholder=" " v-model="branch.fax">
                                                    <span class="label">ＦＡＸ</span>
                                                    <span class="border"></span>
                                                </label>
                                                <p class="text-danger">{{ branch.errors.fax }}</p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-12 col-md-12" v-bind:class="{'has-error': (branch.errors.email != '') }">
                                                <label v-bind:for="branch.index" class="inp">
                                                    <input type="text" v-bind:key="branch.index" v-bind:readonly="isReadOnly" class="form-control" placeholder=" " v-model="branch.email">
                                                    <span class="label">メールアドレス</span>
                                                    <span class="border"></span>
                                                </label>
                                                <p class="text-danger">{{ branch.errors.email }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-md-6">
                                        <p>地図</p>
                                        <div class="mapPreview">
                                            <mapB
                                                :propsLatLng="activeBranchs[index]"
                                                :searchAddressMap="searchAddress(branch.address1 + branch.address2)"
                                                :index="(index + 2)"
                                                :propsListIndex="index"
                                                :propsAddressList="branchAddress[index]"
                                                :readonly="isReadOnly"
                                                @setLatLng="getBranchLatLng"
                                            ><slot></slot></mapB>
                                        </div>  
                                    </div>
                                    <!-- サブ -->
                                    <div class="col-md-3 input-margin">
                                        <div class="form-group" v-bind:class="{'has-error': (branch.errors.zipcode != '') }">
                                            <label class="col-sm-12 text-left">郵便番号</label>
                                            <div class="col-sm-6">
                                                <input type="text" class="form-control" v-bind:readonly="isReadOnly" v-model="branch.zipcode" maxlength="7" @change="getBranchAddress(index)">
                                                <span class="text-danger">{{ branch.errors.zipcode }}</span>
                                            </div>
                                        </div>
                                        <div class="form-group" v-bind:class="{'has-error': (branch.errors.address1 != '') }">
                                            <label class="col-sm-3 text-left">住所1</label>
                                            <div class="col-sm-12">
                                                <input type="text" id="branch_address1" v-bind:readonly="isReadOnly" class="form-control" name="branch_address1" v-model="branch.address1">
                                                <span class="text-danger">{{ branch.errors.address1 }}</span>
                                            </div>
                                        </div>
                                        <div class="form-group" v-bind:class="{'has-error': (branch.errors.address2 != '') }">
                                            <label class="col-sm-3 text-left">住所2</label>
                                            <div class="col-sm-12">
                                                <input type="text" id="branch_address2" v-bind:readonly="isReadOnly" class="form-control" name="branch_address2" v-model="branch.address2">
                                                <span class="text-danger">{{ branch.errors.address2 }}</span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-12 text-left">日本測地計</label>
                                            <div class="col-sm-12">
                                                <div v-bind:class="{'has-error': (branch.errors.latitude_jp != '') }">
                                                    <label class="col-sm-6 text-left">緯度</label>
                                                    <label class="col-sm-6 text-left">経度</label>
                                                    <div class="col-sm-6">
                                                        <input type="text" class="form-control" v-bind:readonly="isReadOnly" v-model="branch.latitude_jp">
                                                        <span class="text-danger">{{ branch.errors.latitude_jp }}</span>
                                                    </div>
                                                </div>
                                                <div v-bind:class="{'has-error': (branch.errors.longitude_jp != '') }">
                                                    <div class="col-sm-6">
                                                        <input type="text" class="form-control" v-bind:readonly="isReadOnly" v-model="branch.longitude_jp">
                                                        <span class="text-danger">{{ branch.errors.longitude_jp }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-12 text-left">世界測地計</label>
                                            <div class="col-sm-12">
                                                <div v-bind:class="{'has-error': (branch.errors.latitude_world != '') }">
                                                    <label class="col-sm-6 text-left">緯度</label>
                                                    <label class="col-sm-6 text-left">経度</label>
                                                    <div class="col-sm-6">
                                                        <input type="text" class="form-control" v-bind:readonly="isReadOnly" v-model="branch.latitude_world">
                                                        <span class="text-danger">{{ branch.errors.latitude_world }}</span>
                                                    </div>
                                                </div>
                                                <div v-bind:class="{'has-error': (branch.errors.longitude_world != '') }">
                                                    <div class="col-sm-6">
                                                        <input type="text" class="form-control" v-bind:readonly="isReadOnly" v-model="branch.longitude_world">
                                                        <span class="text-danger">{{ branch.errors.longitude_world }}</span>
                                                    </div>
                                                </div>
                                                <div class="col-sm-12 text-right">
                                                    <button type="button" class="btn btn-primary btn-search pull-right" v-bind:disabled="isReadOnly" @click="setBranchAddress(index)">住所から緯度経度を検索</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-12 text-right">
                                            <button type="button" class="btn btn-danger pull-right" v-bind:disabled="isReadOnly" @click="deleteBranch(index)">支店／作業場の削除</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12 col-sm-12 list-group-item input-margin">
                                    <p class="text-center" style="font-size:2em;">支店／作業場の追加 <button type="button" class="btn btn-primary" style="width:200px;" v-bind:disabled="isReadOnly" @click="addBranch">＋</button></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- 請求情報 -->
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingFour">
                            <h4 class="panel-title collapse-height">
                                <a class="collapsed col-md-12" data-toggle="collapse" data-parent="#Accordion" href="#AccordionCollapse4" role="button" aria-expanded="false" aria-controls="AccordionCollapse4">
                                    請求情報
                                </a>
                            </h4>
                        </div>
                        <div id="AccordionCollapse4" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingFour">
                            <div class="panel-body">
                                <div class="col-md-12 card">
                                    <div class="form-group">
                                        <div class="col-md-3 col-sm-4 col-xs-12">
                                            <label class="control-label">請求先</label>
                                            <wj-auto-complete class="form-control"
                                                search-member-path="customer_name"
                                                display-member-path="customer_name"
                                                selected-value-path="id"
                                                :selected-index="-1"
                                                :selected-value="customer.bill_customer_id"
                                                :is-required="false"
                                                :initialized="initBillCustomer"
                                                :max-items="customerlist.length"
                                                :isReadOnly="isReadOnly"
                                                :min-length="1"
                                                :items-source="customerlist">
                                            </wj-auto-complete>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-2 col-md-2">
                                            <div v-bind:class="{'has-error': (errors.closing_day != '') }">
                                                <label class="control-label text-left">締日</label>
                                                <input type="number" class="form-control" min="0" max="99"  v-model="customer.closing_day" v-bind:readonly="isReadOnly">
                                                <span class="text-danger">{{ errors.closing_day }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-sm-4 col-xs-12">
                                            <label class="control-label">回収サイト</label>
                                            <wj-auto-complete class="form-control"
                                                search-member-path="value_text_1"
                                                display-member-path="value_text_1"
                                                selected-value-path="value_code"
                                                :selected-index="-1"
                                                :selected-value="customer.collection_sight"
                                                :is-required="false"
                                                :initialized="initColSight"
                                                :max-items="collectsight.length"
                                                :isReadOnly="isReadOnly"
                                                :min-length="1"
                                                :items-source="collectsight">
                                            </wj-auto-complete>
                                        </div>
                                        <div class="col-sm-2 col-md-2">
                                            <div v-bind:class="{'has-error': (errors.collection_day != '') }">
                                                <label class="control-label text-left">回収日</label>
                                                <input type="number" class="form-control" min="0" max="99"  v-model="customer.collection_day" v-bind:readonly="isReadOnly">
                                                <span class="text-danger">{{ errors.collection_day }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-sm-4 col-xs-12">
                                            <label class="control-label">回収区分</label>
                                            <wj-auto-complete class="form-control"
                                                search-member-path="value_text_1"
                                                display-member-path="value_text_1"
                                                selected-value-path="value_code"
                                                :selected-index="-1"
                                                :selected-value="customer.collection_kbn"
                                                :is-required="false"
                                                :initialized="initColKbn"
                                                :max-items="collectkbn.length"
                                                :isReadOnly="isReadOnly"
                                                :min-length="1"
                                                :items-source="collectkbn">
                                            </wj-auto-complete>
                                        </div>
                                    </div>                                    
                                    <div class="form-group">
                                        <div class="col-sm-2 col-md-2">
                                            <div v-bind:class="{'has-error': (errors.bill_min_price != '') }">
                                                <label class="control-label text-left">手形最低金額</label>
                                                <input type="text" class="form-control" v-model="customer.bill_min_price" v-bind:readonly="isReadOnly">
                                                <span class="text-danger">{{ errors.bill_min_price }}</span>
                                            </div>
                                        </div>
                                        <div class="col-sm-2 col-md-2">
                                            <div v-bind:class="{'has-error': (errors.bill_rate != '') }">
                                                <label class="control-label text-left">手形割合</label>
                                                <input type="text" class="form-control" v-model="customer.bill_rate" v-bind:readonly="isReadOnly">
                                                <span class="text-danger">{{ errors.bill_rate }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-sm-4 col-xs-12">
                                            <label class="control-label">手形サイト</label>
                                            <wj-auto-complete class="form-control"
                                                search-member-path="value_text_1"
                                                display-member-path="value_text_1"
                                                selected-value-path="value_code"
                                                :selected-index="-1"
                                                :selected-value="customer.bill_sight"
                                                :is-required="false"
                                                :initialized="initBillSight"
                                                :max-items="collectsight.length"
                                                :isReadOnly="isReadOnly"
                                                :min-length="1"
                                                :items-source="collectsight">
                                            </wj-auto-complete>
                                        </div>
                                        <div class="col-md-3 col-sm-4 col-xs-12">
                                            <label>手数料区分</label>
                                            <div class="col-md-12 col-sm-12 col-xs-12">
                                                <el-radio-group v-model="customer.fee_kbn" v-for="fee in feekbn" :key="fee.value_code" v-bind:disabled="isReadOnly">
                                                    <div class="radio col-md-4 col-sm-12 col-xs-12">
                                                        <el-radio :label="fee.value_code">{{ fee.value_text_1 }}</el-radio>
                                                    </div>
                                                </el-radio-group>
                                            </div> 
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-md-4 col-sm-6 col-xs-12">
                                            <label>税計算区分</label>
                                            <div class="col-md-12 col-sm-12 col-xs-12">
                                                <el-radio-group v-model="customer.tax_calc_kbn" v-for="taxcalc in taxcalckbn" :key="taxcalc.value_code" v-bind:disabled="isReadOnly">
                                                    <div class="radio col-md-4 col-sm-12 col-xs-12">
                                                        <el-radio :label="taxcalc.value_code">{{ taxcalc.value_text_1 }}</el-radio>
                                                    </div>
                                                </el-radio-group>
                                            </div> 
                                        </div>
                                        <div class="col-md-4 col-sm-6 col-xs-12">
                                            <label>税端数処理</label>
                                            <div class="col-md-12 col-sm-12 col-xs-12">
                                                <el-radio-group v-model="customer.tax_rounding" v-for="round in taxrounding" :key="round.value_code" v-bind:disabled="isReadOnly">
                                                    <div class="radio col-md-4 col-sm-12 col-xs-12">
                                                        <el-radio :label="round.value_code">{{ round.value_text_1 }}</el-radio>
                                                    </div>
                                                </el-radio-group>
                                            </div> 
                                        </div>
                                        <div class="col-md-4 col-sm-4 col-xs-12">
                                            <label class="control-label">相殺仕入先</label>
                                            <wj-auto-complete class="form-control"
                                                search-member-path="supplier_name"
                                                display-member-path="supplier_name"
                                                selected-value-path="id"
                                                :selected-index="-1"
                                                :selected-value="customer.offset_supplier_id"
                                                :is-required="false"
                                                :initialized="initSupplier"
                                                :max-items="supplierlist.length"
                                                :isReadOnly="isReadOnly"
                                                :min-length="1"
                                                :items-source="supplierlist">
                                            </wj-auto-complete>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- 与信情報 -->
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingFive">
                            <h4 class="panel-title collapse-height">
                                <a class="collapsed col-md-12" data-toggle="collapse" data-parent="#Accordion" href="#AccordionCollapse5" role="button" aria-expanded="false" aria-controls="AccordionCollapse5">
                                    与信情報
                                </a>
                            </h4>
                        </div>
                        <div id="AccordionCollapse5" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingFive">
                            <div class="panel-body">
                                <div class="col-md-12 card">
                                    <div class="col-sm-3 col-md-3">
                                        <p>得意先区分</p>
                                        <input type="text" class="form-control" readonly>
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="col-sm-3 col-md-3">
                                        <p>創立年月日</p>
                                        <input type="text" class="form-control" readonly>
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="col-sm-2 col-md-2">
                                        <p>従業員数</p>
                                        <input type="text" class="form-control" readonly>
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="col-sm-2 col-md-2">
                                        <p>ＴＳＲ評点</p>
                                        <input type="text" class="form-control" readonly>
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="col-sm-2 col-md-2">
                                        <p>ＴＲＳ企業コード</p>
                                        <input type="text" class="form-control" readonly>
                                        <p class="text-danger"></p>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="col-sm-3 col-md-3">
                                        <p>資本金</p>
                                        <input type="text" class="form-control" readonly>
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="col-sm-3 col-md-3">
                                        <p>年商</p>
                                        <input type="text" class="form-control" readonly>
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="col-sm-3 col-md-3">
                                        <p>自己資本比率</p>
                                        <input type="text" class="form-control" readonly>
                                        <p class="text-danger"></p>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="col-sm-3 col-md-3">
                                        <p>前々期 決算年月</p>
                                        <input type="text" class="form-control" readonly>
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="col-sm-3 col-md-3">
                                        <p>前々期 売上高</p>
                                        <input type="text" class="form-control" readonly>
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="col-sm-3 col-md-3">
                                        <p>前々期 利益金</p>
                                        <input type="text" class="form-control" readonly>
                                        <p class="text-danger"></p>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="col-sm-3 col-md-3">
                                        <p>前期 決算年月</p>
                                        <input type="text" class="form-control" readonly>
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="col-sm-3 col-md-3">
                                        <p>前期 売上高</p>
                                        <input type="text" class="form-control" readonly>
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="col-sm-3 col-md-3">
                                        <p>前期 利益金</p>
                                        <input type="text" class="form-control" readonly>
                                        <p class="text-danger"></p>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="col-sm-3 col-md-3">
                                        <p>当期 決算年月</p>
                                        <input type="text" class="form-control" readonly>
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="col-sm-3 col-md-3">
                                        <p>当期 売上高</p>
                                        <input type="text" class="form-control" readonly>
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="col-sm-3 col-md-3">
                                        <p>当期 利益金</p>
                                        <input type="text" class="form-control" readonly>
                                        <p class="text-danger"></p>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="col-sm-3 col-md-3">
                                        <p>与信限度額</p>
                                        <input type="text" class="form-control" readonly>
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="col-sm-3 col-md-3">
                                        <p>与信限度額更新日</p>
                                        <input type="text" class="form-control" readonly>
                                        <p class="text-danger"></p>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="col-sm-3 col-md-3">
                                        <p>保証額</p>
                                        <input type="text" class="form-control" readonly>
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="col-sm-3 col-md-3">
                                        <p>保証額更新日</p>
                                        <input type="text" class="form-control" readonly>
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="col-sm-3 col-md-3">
                                        <p>ＭＡＸ保証額</p>
                                        <input type="text" class="form-control" readonly>
                                        <p class="text-danger"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <!-- ボタン -->
            <div class="col-md-12 col-sm-12 col-xs-12 text-center">
                <button type="button" class="btn btn-warning btn-back" v-on:click="back">戻る</button>
                <!-- <button type="button" class="btn btn-danger btn-delete" v-show="(!isReadOnly && customerdata.id)" v-on:click="del">削除</button> -->
            </div>
        </div>
    </div>
</template>


<script>
import GoogleMaps from './GoogleMapsComponent'
export default {
    components:
    {
        mapA: GoogleMaps,
        mapB: GoogleMaps,
    },
    data: () => ({
        loading: false,
        isReadOnly: true,
        isShowEditBtn: false,
        isLocked: false,
        isNew: true,
        isInit: true,
        initEnded: false,

        gmapSearchFlg: false,
        isChargeLocked: {
            department: false,
            staff: false,
        },

        checkList: [],
        viewImage: '',
        uploadImage: '',

        index: 0,

        customerAddress: {
            address: '',
        },
        searchMapByAddress: {
            address: ''
        },
        branchAddress: [{
            number: '',
            address: '',
        }],
        
        LatLng: {
            latitude_jp: '',
            lngitude_jp: '',
            latitude_world: '',
            longitude_world: '',
        },

        wjInputObj: {
            juridical_code: {},
            bill_customer_id: {},
            collection_sight: {},
            collection_kbn: {},
            bill_sight: {},
            offset_supplier_id: {},
            charge_department_id: {},
            charge_staff_id: {},
        },

        customer: {
            customer_name: '',
            customer_short_name: '',
            customer_kana: '',
            corporate_number: '',
            company_category: '',
            juridical_code: '',
            honorific: '',
            zipcode: '',
            address1: '',
            address2: '',
            tel: '',
            fax: '',
            email: '',
            url: '',
            latitude_jp: '',
            longitude_jp: '',
            latitude_world: '',
            longitude_world: '',

            customer_code: '',
            customer_rank: '',
            housing_history_login_id: '',
            housing_history_password: '',
            charge_department_id: '',
            charge_staff_id: '',

            bill_customer_id: '',
            closing_day: '',
            collection_sight: '',
            collection_day: '',
            collection_kbn: '',
            bill_min_price: '',
            bill_rate: '',
            fee_kbn: 0,
            tax_calc_kbn: 0,
            tax_rounding: 0,
            offset_supplier_id: '',
            bill_sight: '',            
        },

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

        // 支店／作業場リスト
        // branchList: [],
        branchList: [{
            id: '',
            branch_name: '',
            branch_kana: '',
            tel: '',
            fax: '',
            email: '',
            zipcode: '',
            address1: '',
            address2: '',
            latitude_jp: '',
            longitude_jp: '',
            latitude_world: '',
            longitude_world: '',
            del_flg: 0,
            errors: {
                branch_name: '',
                branch_kana: '',
                tel: '',
                fax: '',
                email: '',
                zipcode: '',
                address1: '',
                address2: '',
                latitude_jp: '',
                longitude_jp: '',
                latitude_world: '',
                longitude_world: '',
            },  
        }],

        errors: {
            customer_name: '',
            customer_kana: '',
            customer_short_name: '',
            corporate_number: '',
            juridical_code: '',
            honorific: '',
            tel: '',
            fax: '',
            email: '',
            url: '',
            zipcode: '',
            address1: '',
            address2: '',
            latitude_jp: '',
            longitude_jp: '',
            latitude_world: '',
            longitude_world: '',
            customer_code: '',
            customer_rank: '',
            housing_history_login_id: '',
            housing_history_password: '',
            bill_customer_id: '',
            closing_day: '',
            collection_sight: '',
            collection_day: '',
            collection_kbn: '',
            bill_min_price: '',
            bill_rate: '',
            fee_kbn: '',
            tax_calc_kbn: '',
            tax_rounding: '',
            offset_supplier_id: '',
            bill_sight: '',      
            checkList: '',
            person_name: '',
            person_kana:'',
            belong_name: '',
            person_belong_name: '',
            person_position: '',
            position: '',
            person_tel1: '',
            person_tel2: '',
            person_email1: '',
            person_email2: '',
            branch_name: '',
            branch_kana: '',
            branch_tel: '',
            branch_fax: '',
            branch_email: '',
            branch_zipcode: '',
            branch_address1: '',
            branch_address2: '',
            branch_latitude_jp: '',
            branch_longitude_jp: '',
            branch_latitude_world: '',
            branch_longitude_world: '',
        },
    }),
    props: {
        isEditable: Number,
        isOwnLock: Number,
        lockdata: {},
        customerdata: Object,
        categorydata: Array,
        persondata: Array,
        branchdata: Array,
        checkboxdata: Object,
        juridlist: Array,
        customerlist: Array,
        collectsight: Array,
        collectkbn: Array,
        feekbn: Array,
        taxcalckbn: Array,
        taxrounding: Array,
        supplierlist: Array,
        stafflist: Array,
        departmentlist: Array,
        staffdepartlist: Array,
    },
    computed: {
        // 削除フラグ"0"のレコードのみ表示
        activePersons: function() {
            return this.personList.filter(function (person) {
                return person.del_flg === 0;
            })
        },
        activeBranchs: function() {
            return this.branchList.filter(function (branch) {
                return branch.del_flg === 0;
            })
        },
    },
    created() {
        // if (this.isEditable == FLG_EDITABLE) {
        //     this.isShowEditBtn = true;
        //     if (this.customerdata.id == null || this.customerdata.id == '') {
        //         this.isShowEditBtn = false;
        //         this.isReadOnly = false;
        //     }
        // }
        if (this.isEditable == FLG_EDITABLE) {
            // 編集可
            this.isShowEditBtn = true;
            if (this.customerdata.id == null) {
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

        if(this.categorydata !== undefined && this.categorydata !== null){
            for(var i = 0; i < this.categorydata.length; i++){
                this.checkList.push(this.categorydata[i].value_code)
            }
        }
        if(this.customerdata.id !== undefined && this.customerdata.id !== null){
            this.isNew = false;
            this.customer = this.customerdata;
            this.viewImage = this.customer.customer_image;
        }
        if(this.persondata !== undefined && this.persondata !== null && this.persondata.length > 0) {
            this.personList = [];
            this.persondata.forEach((element, i) => {
                var addPm = {
                    id: element.id,
                    personImage: '',
                    name: element.name,
                    kana: element.kana,
                    belong_name: element.belong_name,
                    position: element.position,
                    tel1: element.tel1,
                    tel2: element.tel2,
                    email1: element.email1,
                    email2: element.email2,
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
                }};
                this.personList.push(addPm);
                var addView = {imagePreview: element.image_file,};
                this.photoList.splice(i, 1, addView);
            });
        }
        if(this.branchdata !== undefined && this.branchdata !== null && this.branchdata.length > 0){
            var add = [];
            this.branchList = [];
            this.branchdata.forEach((element, i) => {
                var branch = {
                    id: element.id,
                    branch_name: element.branch_name,
                    branch_kana: element.branch_kana,
                    tel: element.tel,
                    fax: element.fax,
                    email: element.email,
                    zipcode: element.zipcode,
                    address1: element.address1,
                    address2: element.address2,
                    latitude_jp: element.latitude_jp,
                    longitude_jp: element.longitude_jp,
                    latitude_world: element.latitude_world,
                    longitude_world: element.longitude_world,
                    del_flg: 0,
                    errors: {
                        branch_name: '',
                        branch_kana: '',
                        tel: '',
                        fax: '',
                        email: '',
                        zipcode: '',
                        address1: '',
                        address2: '',
                        latitude_jp: '',
                        longitude_jp: '',
                        latitude_world: '',
                        longitude_world: '',
                    },  
                };

                this.branchList.push(branch);
                var addAddress = {
                    number: '',
                    address: '',
                };
                add.push(addAddress);
            });
            // for(var i = 0; i < this.branchdata.length; i++){
            //     this.branchList.splice(i, 1, this.branchdata[i])
            //     var addAddress = {
            //         number: '',
            //         address: '',
            //     };
            //     add.push(addAddress);
            // };
            for(var i = 0; i < add.length; i++){
                this.branchAddress.splice(i, 1, add[i])
            }
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

        if (this.rmUndefinedZero(this.customer.charge_department_id) != 0) {
            this.isChargeLocked.department = true;
        }

        if (this.rmUndefinedZero(this.customer.charge_staff_id) != 0) {
            this.isChargeLocked.staff = true;
        }
        var cnt = -1;
        $('div.mapPreview').each(function (index, element) {
            cnt++;
        });
        // div(mapPreview)クラスにGmapIDを付与
        $('div.mapPreview').each(function (index, element) {
            setTimeout(() => {
                $(element).attr('id', 'Gmap' + (index + 1).toString().padStart(2, '0'));

                if (index == cnt) {
                    this.initEnded = true;
                }
            }, 500);
            
        }.bind(this));

        // setTimeout(() => {
        //     if (initEnd) {
        //         this.isInit = false;
        //     }
        // }, 3000);
    },
    watch: {
        initEnded: {
            handler: function(newVal) {
                if (newVal) {
                    setTimeout(() => {
                        this.isInit = false;
                    }, 3000);
                }
            },
            deep: true,
        },
    },
    methods: {
        // 緯度経度が未入力&住所入力済
        searchAddress(address) {
            if (this.rmUndefinedBlank(address) != '') {
                var adr = '';
                if (this.rmUndefinedBlank(this.customer.latitude_world) == '' && this.rmUndefinedBlank(this.customer.longitude_world) == ''
                    && this.rmUndefinedBlank(this.customer.address1) != '')
                {
                    adr = address;
                }
            }
            return adr;
        },
        initJuridical(sender) {
            this.wjInputObj.juridical_code = sender;
        },
        initBillCustomer(sender) {
            this.wjInputObj.bill_customer_id = sender;
        },
        initColSight(sender) {
            this.wjInputObj.collection_sight = sender;
        },
        initColKbn(sender) {
            this.wjInputObj.collection_kbn = sender;
        },
        initBillSight(sender) {
            this.wjInputObj.bill_sight = sender;
        },
        initSupplier(sender) {
            this.wjInputObj.offset_supplier_id = sender;
        },
        initDepartment(sender) {
            this.wjInputObj.charge_department_id = sender;
        },
        initStaff(sender) {
            this.wjInputObj.charge_staff_id = sender;
        },
        changeIdxDepartment: function(sender){
            // 部門を選択したら担当者を絞り込む
            var tmpArr = this.staffdepartlist;
            var tmpStaff = this.stafflist;
            if (sender.selectedItem) {
                tmpArr = [];
                for(var key in this.staffdepartlist) {
                    if (sender.selectedItem.id == this.staffdepartlist[key].department_id) {
                        tmpArr.push(this.staffdepartlist[key]);
                    }
                }
                tmpStaff = [];
                for(var key in this.stafflist) {
                    for(var i = 0; i < tmpArr.length; i++) {
                        if (tmpArr[i].staff_id == this.stafflist[key].id) {
                            tmpStaff.push(this.stafflist[key]);
                            break;
                        }
                    }
                }      
            }
            this.wjInputObj.charge_staff_id.itemsSource = tmpStaff;
            this.wjInputObj.charge_staff_id.selectedIndex = -1;
        },
        // 得意先の郵便番号から住所を自動入力
        getAddress() {
            var zipcode = this.customer.zipcode
            var customer = this.customer
            new YubinBango.Core(zipcode, function(addr){
                var addr1 = addr.region + addr.locality + addr.street
                customer.address1 = addr1
            })
        },
        // 支店の郵便番号から住所を自動入力
        getBranchAddress(index) {
            var zipcode = this.activeBranchs[index].zipcode
            var branch = this.activeBranchs[index]
            new YubinBango.Core(zipcode, function(addr){
                var addr1 = addr.region + addr.locality + addr.street
                branch.address1 = addr1
            })
        },
        save() {
            this.loading = true

            // エラーの初期化
            this.initErr(this.errors);

            var params = new FormData();
            // 得意先情報でチェック済みの値のみPOST
            if(this.checkList.length > 0) {
                this.checkList.forEach((value, index) => {
                    params.append('company_category[' + index + ']', value);
                })
            }else {
                // チェック無
                this.checkList[0] = 0;
                params.append('company_category', this.checkList);
            };
            // 締日、回収日チェック
            var OkFlg = true;
            if (!(this.customer.closing_day >= 0 && this.customer.closing_day <= 28) && this.customer.closing_day != 99) {
                this.errors.closing_day = MSG_ERROR_CLOSING_DATE_RANGE;
                OkFlg = false;
            }
            if (!(this.customer.collection_day >= 0 && this.customer.collection_day <= 28) && this.customer.collection_day != 99) {
                this.errors.collection_day = MSG_ERROR_CLOSING_DATE_RANGE;
                OkFlg = false;
            }

            // 得意先基本情報
            params.append('id', (this.customer.id !== undefined) ? this.customer.id : '');
            params.append('customer_name', this.rmUndefinedBlank(this.customer.customer_name));
            params.append('customer_kana', this.rmUndefinedBlank(this.customer.customer_kana));
            params.append('customer_short_name', this.rmUndefinedBlank(this.customer.customer_short_name));
            params.append('corporate_number', this.rmUndefinedBlank(this.customer.corporate_number));
            params.append('juridical_code', this.rmUndefinedZero(this.wjInputObj.juridical_code.selectedValue));
            params.append('honorific', this.rmUndefinedBlank(this.customer.honorific));
            params.append('tel', this.rmUndefinedBlank(this.customer.tel));
            params.append('fax', this.rmUndefinedBlank(this.customer.fax));
            params.append('email', this.rmUndefinedBlank(this.customer.email));
            params.append('url', this.rmUndefinedBlank(this.customer.url));
            params.append('zipcode', this.rmUndefinedBlank(this.customer.zipcode));
            params.append('address1', this.rmUndefinedBlank(this.customer.address1));
            params.append('address2', this.rmUndefinedBlank(this.customer.address2));
            params.append('latitude_jp', this.rmUndefinedBlank(this.customer.latitude_jp));
            params.append('longitude_jp', this.rmUndefinedBlank(this.customer.longitude_jp));
            params.append('latitude_world', this.rmUndefinedBlank(this.customer.latitude_world));
            params.append('longitude_world', this.rmUndefinedBlank(this.customer.longitude_world));
            params.append('image', this.uploadImage);
            params.append('customer_code', this.rmUndefinedBlank(this.customer.customer_code));
            params.append('customer_rank', this.rmUndefinedBlank(this.customer.customer_rank));
            params.append('housing_history_login_id', this.rmUndefinedBlank(this.customer.housing_history_login_id));
            params.append('housing_history_password', this.rmUndefinedBlank(this.customer.housing_history_password));
            params.append('bill_customer_id', this.rmUndefinedBlank(this.wjInputObj.bill_customer_id.selectedValue));
            params.append('closing_day', this.rmUndefinedBlank(this.customer.closing_day));
            params.append('collection_sight', this.rmUndefinedBlank(this.wjInputObj.collection_sight.selectedValue));
            params.append('collection_day', this.rmUndefinedBlank(this.customer.collection_day));
            params.append('collection_kbn', this.rmUndefinedBlank(this.wjInputObj.collection_kbn.selectedValue));
            params.append('bill_min_price', this.rmUndefinedBlank(this.customer.bill_min_price));
            params.append('bill_rate', this.rmUndefinedBlank(this.customer.bill_rate));
            params.append('fee_kbn', this.rmUndefinedBlank(this.customer.fee_kbn));
            params.append('tax_calc_kbn', this.rmUndefinedBlank(this.customer.tax_calc_kbn));
            params.append('tax_rounding', this.rmUndefinedBlank(this.customer.tax_rounding));
            params.append('offset_supplier_id', this.rmUndefinedBlank(this.wjInputObj.offset_supplier_id.selectedValue));
            params.append('bill_sight', this.rmUndefinedBlank(this.wjInputObj.bill_sight.selectedValue));
            params.append('charge_department_id', this.rmUndefinedZero(this.wjInputObj.charge_department_id.selectedValue));
            params.append('charge_staff_id', this.rmUndefinedZero(this.wjInputObj.charge_staff_id.selectedValue));

            // パーソン情報            
            this.personList.forEach((value, index) => {
                this.initErr(value.errors);
                params.append('person[' + index + ']' + '[id]', (value.id !== undefined) ? value.id : '');
                params.append('person[' + index + ']' + '[seq]', index + 1);
                if (this.rmUndefinedBlank(value.name).toString().length > 10) {
                    value.errors.name = '10'+MSG_ERROR_LIMIT_OVER;
                    OkFlg = false;
                } else {
                    params.append('person[' + index + ']' + '[name]', this.rmUndefinedBlank(value.name));                    
                }
                if (this.rmUndefinedBlank(value.kana).toString().length > 20) {
                    value.errors.kana = '20'+MSG_ERROR_LIMIT_OVER;
                    OkFlg = false;
                } else {
                    params.append('person[' + index + ']' + '[kana]', this.rmUndefinedBlank(value.kana));                    
                }
                if (this.rmUndefinedBlank(value.belong_name).toString().length > 50) {
                    value.errors.belong_name = '50'+MSG_ERROR_LIMIT_OVER;
                    OkFlg = false;
                } else {
                    params.append('person[' + index + ']' + '[belong_name]', this.rmUndefinedBlank(value.belong_name));                    
                }
                if (this.rmUndefinedBlank(value.position).toString().length > 50) {
                    value.errors.position = '50'+MSG_ERROR_LIMIT_OVER;
                    OkFlg = false;
                } else {
                    params.append('person[' + index + ']' + '[position]', this.rmUndefinedBlank(value.position));                    
                }
                if (this.rmUndefinedBlank(value.tel1).toString().length > 20) {
                    value.errors.tel1 = '20'+MSG_ERROR_LIMIT_OVER;
                    OkFlg = false;
                } else {
                    params.append('person[' + index + ']' + '[tel1]', this.rmUndefinedBlank(value.tel1));                    
                }

                if (this.rmUndefinedBlank(value.tel2).toString().length > 20) {
                    value.errors.tel2 = '20'+MSG_ERROR_LIMIT_OVER;
                    OkFlg = false;
                } else {
                    params.append('person[' + index + ']' + '[tel2]', this.rmUndefinedBlank(value.tel2));                    
                }

                if (this.rmUndefinedBlank(value.email1).toString().length > 100) {
                    value.errors.email1 = '100'+MSG_ERROR_LIMIT_OVER;
                    OkFlg = false;
                } else {
                    params.append('person[' + index + ']' + '[email1]', this.rmUndefinedBlank(value.email1));                    
                }

                if (this.rmUndefinedBlank(value.email2).toString().length > 100) {
                    value.errors.email2 = '100'+MSG_ERROR_LIMIT_OVER;
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
            });
            // 支店／作業場情報
            this.branchList.forEach((value, index) => {
                this.initErr(value.errors);
                params.append('branch[' + index + ']' + '[id]', (value.id !== undefined) ? value.id : '');
                params.append('branch[' + index + ']' + '[seq]', index + 1);
                if (this.rmUndefinedBlank(value.branch_name).toString().length > 50) {
                    value.errors.branch_name = '50'+MSG_ERROR_LIMIT_OVER;
                    OkFlg = false;
                } else {
                    params.append('branch[' + index + ']' + '[branch_name]', this.rmUndefinedBlank(value.branch_name));                    
                }

                if (this.rmUndefinedBlank(value.branch_kana).toString().length > 50) {
                    value.errors.branch_kana = '50'+MSG_ERROR_LIMIT_OVER;
                    OkFlg = false;
                } else {
                    params.append('branch[' + index + ']' + '[branch_kana]', this.rmUndefinedBlank(value.branch_kana));                    
                }

                if (this.rmUndefinedBlank(value.tel).toString().length > 20) {
                    value.errors.tel = '20'+MSG_ERROR_LIMIT_OVER;
                    OkFlg = false;
                } else {
                    params.append('branch[' + index + ']' + '[tel]', this.rmUndefinedBlank(value.tel));                    
                }

                if (this.rmUndefinedBlank(value.fax).toString().length > 20) {
                    value.errors.fax = '20'+MSG_ERROR_LIMIT_OVER;
                    OkFlg = false;
                } else {
                    params.append('branch[' + index + ']' + '[fax]', this.rmUndefinedBlank(value.fax));                    
                }

                if (this.rmUndefinedBlank(value.email).toString().length > 100) {
                    value.errors.email = '100'+MSG_ERROR_LIMIT_OVER;
                    OkFlg = false;
                } else {
                    params.append('branch[' + index + ']' + '[email]', this.rmUndefinedBlank(value.email));                    
                }

                if (this.rmUndefinedBlank(value.zipcode).toString().length > 7) {
                    value.errors.zipcode = '7'+MSG_ERROR_LIMIT_OVER;
                    OkFlg = false;
                } else {
                    params.append('branch[' + index + ']' + '[zipcode]', this.rmUndefinedBlank(value.zipcode));                    
                }

                if (this.rmUndefinedBlank(value.address1).toString().length > 50) {
                    value.errors.address1 = '50'+MSG_ERROR_LIMIT_OVER;
                    OkFlg = false;
                } else {
                    params.append('branch[' + index + ']' + '[address1]', this.rmUndefinedBlank(value.address1));                    
                }

                if (this.rmUndefinedBlank(value.address2).toString().length > 50) {
                    value.errors.address2 = '50'+MSG_ERROR_LIMIT_OVER;
                    OkFlg = false;
                } else {
                    params.append('branch[' + index + ']' + '[address2]', this.rmUndefinedBlank(value.address2));                    
                }
                params.append('branch[' + index + ']' + '[latitude_jp]', this.rmUndefinedBlank(value.latitude_jp));
                params.append('branch[' + index + ']' + '[longitude_jp]', this.rmUndefinedBlank(value.longitude_jp));
                params.append('branch[' + index + ']' + '[latitude_world]', this.rmUndefinedBlank(value.latitude_world));
                params.append('branch[' + index + ']' + '[longitude_world]', this.rmUndefinedBlank(value.longitude_world));
                params.append('branch[' + index + ']' + '[del_flg]', this.rmUndefinedBlank(value.del_flg))
            });

                
            if (!OkFlg) {
                this.loading = false;
            } else {
                axios.post('/new-customer-edit/save', params, {headers: {'Content-Type': 'multipart/form-data'}})

                .then( function (response) {
                    this.loading = false

                    if (response.data) {
                        // 成功
                        window.onbeforeunload = null;
                        var listUrl = '/new-customer-list' + window.location.search
                        location.href = (listUrl)
                    } else {
                        // 失敗
                        // alert(MSG_ERROR)
                        window.onbeforeunload = null;
                        location.reload();
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
                        location.reload()
                    }
                }.bind(this))
            }
        },
        // 削除
        del() {
            if (!confirm(MSG_CONFIRM_DELETE)) {
                return;
            }
            
            this.loading = true

            var params = new URLSearchParams();
            params.append('id', this.rmUndefinedBlank(this.customerdata.id));
            axios.post('/new-customer-edit/delete', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/new-customer-list' + window.location.search
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
        // 有効化
        active() {
            if (!confirm(MSG_CONFIRM_ACTIVATE)) {
                return;
            }
            
            this.loading = true

            var params = new URLSearchParams();
            params.append('id', this.rmUndefinedBlank(this.customerdata.id));
            axios.post('/new-customer-edit/activate', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/new-customer-list' + window.location.search
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
        // 戻る
        back() {
            var listUrl = '/new-customer-list' + window.location.search

            if (!this.isReadOnly && this.customerdata.id) {
                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'new-customer-edit');
                params.append('keys', this.rmUndefinedBlank(this.customerdata.id));
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
            params.append('screen', 'new-customer-edit');
            params.append('keys', this.rmUndefinedBlank(this.customerdata.id));
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
            params.append('screen', 'new-customer-edit');
            params.append('keys', this.rmUndefinedBlank(this.customerdata.id));
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
        // 支店／作業場追加
        addBranch: function () {
            var addBranch = {
                id: '',
                branch_name: '',
                branch_kana: '',
                tel: '',
                fax: '',
                email: '',
                zipcode: '',
                address1: '',
                address2: '',
                latitude_jp: '',
                longitude_jp: '',
                latitude_world: '',
                longitude_world: '',
                del_flg: 0,
                errors: {
                    branch_name: '',
                    branch_kana: '',
                    tel: '',
                    fax: '',
                    email: '',
                    zipcode: '',
                    address1: '',
                    address2: '',
                    latitude_jp: '',
                    longitude_jp: '',
                    latitude_world: '',
                    longitude_world: '',
                },  
            };
            var addAddress = {
                number: '',
                address: '',
            };
            this.index++; 
            this.branchList.push(addBranch);
            this.branchAddress.push(addAddress);
            // GmapID付与
            setTimeout(() => {
                $('div.mapPreview').each(function (index, element) {
                    $(element).attr('id', 'Gmap' + (index + 1).toString().padStart(2, '0'));
                });
            }, 300);
        },
        // 支店／作業場削除
        deleteBranch(index) {
            if(this.activeBranchs[index].branch_name ? confirm(this.activeBranchs[index].branch_name + 'を' + MSG_CONFIRM_DELETE) : true ){
                if(this.activeBranchs[index].id !== ''){
                    this.$set(this.activeBranchs[index], 'del_flg', '1');
                }else {
                    this.branchList.splice(index, 1);
                    this.branchAddress.splice(index, 1);
                }
            }
        },
        // 得意先の住所をセット
        setAddress() {
            this.gmapSearchFlg = true;
            if (this.rmUndefinedBlank(this.customer.address1) == '') {
                alert('住所を' + MSG_ERROR_NO_INPUT);
                return;
            }
            this.customerAddress.address = this.customer.address1 + this.customer.address2;
        },
        // 支店の住所をセット
        setBranchAddress(index) {
            this.gmapSearchFlg = true;
            if (this.rmUndefinedBlank(this.activeBranchs[index].address1) == '') {
                alert('住所を' + MSG_ERROR_NO_INPUT);
                return;
            }
            this.branchAddress[index].address = this.activeBranchs[index].address1 + this.activeBranchs[index].address2;
            this.branchAddress[index].number = index;
        },
        // 得意先マーカーの座標取得
        getCustomerLatLng(e){
            if (this.rmUndefinedBlank(e.address1) != '' && !this.gmapSearchFlg) {
                this.$set(this.customer, 'address1', e.address1);
                // this.$set(this.customer, 'address2', e.address2);
                this.$set(this.customer, 'zipcode', e.zipcode);
            }
            this.$set(this.customer, 'latitude_jp', e.jpLat);
            this.$set(this.customer, 'longitude_jp', e.jpLng);
            this.$set(this.customer, 'latitude_world', e.wLat);
            this.$set(this.customer, 'longitude_world', e.wLng);
            setTimeout(() => {
                this.gmapSearchFlg = false;
            }, 1000);
        },
        // 支店マーカーの座標取得
        getBranchLatLng(e){
            if (this.rmUndefinedBlank(e.address1) != '' && !this.gmapSearchFlg) {
                this.$set(this.activeBranchs[e.index], 'address1', e.address1);
                // this.$set(this.activeBranchs[e.index], 'address2', e.address2);
                this.$set(this.activeBranchs[e.index], 'zipcode', e.zipcode);
            }
            this.$set(this.activeBranchs[e.index], 'latitude_jp', e.jpLat);
            this.$set(this.activeBranchs[e.index], 'longitude_jp', e.jpLng);
            this.$set(this.activeBranchs[e.index], 'latitude_world', e.wLat);
            this.$set(this.activeBranchs[e.index], 'longitude_world', e.wLng);
            setTimeout(() => {
                this.gmapSearchFlg = false;
            }, 1000);
        },
        // 編集時、緯度経度が入力されていた場合のみGoogleMapへ渡す
        customerLatLng() {
            if(this.rmUndefinedBlank(this.customer.latitude_world) != '' && this.rmUndefinedBlank(this.customer.longitude_world) != ''){
                this.LatLng.latitude_world = this.customer.latitude_world;
                this.LatLng.longitude_world = this.customer.longitude_world;
            }
            return this.LatLng;
        },
        // 画像プレビュー
        customerPreview(e) {
            let files = e.target.files ? 
                               e.target.files:
                               e.dataTransfer.files;
            let file = files[0];
            let reader = new FileReader();
            reader.onload = (e) => {
                this.viewImage = e.target.result;
            };
            this.uploadImage = file;
            reader.readAsDataURL(file);
        },
        onPersonChange(index, e) {
            let files = e.target.files ? 
                               e.target.files:
                               e.dataTransfer.files;
            let file = files[0];
            let reader = new FileReader();
            reader.onload = (e) => {
                var result = e.target.result;
                this.$set(this.photoList[index], "imagePreview", result);
            };
            this.$set(this.personList[index], "personImage", file);
            reader.readAsDataURL(file);
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
.imagePreview {
    width: 500px;
    height: 300px;
    background-position: center center;
    background-size: cover;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    display: inline-block;
    cursor: pointer;
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
.photo {
    line-height:200px;
    text-align:center;
}
.checkboxStyle {
    padding: 10px 0px 0px 2px;
}
.wj-state-readonly {
    opacity: 5 !important;
    background-color: #eeeeee !important;
}

</style>

