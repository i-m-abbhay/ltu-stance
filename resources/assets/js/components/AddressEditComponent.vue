<template>
    <div>
        <loading-component :loading="loading" />
        <div class="form-horizontal save-form">
            <form id="saveForm" name="saveForm" class="form-horizontal" enctype="multipart/form-data" method="post" action="/address-edit/save">
                <div class="col-sm-12 col-md-12 main-body">
                    <div class="col-sm-8 col-md-8">
                        <div id="Gmap01" class="mapPreview">
                            <googlemaps-component
                                :propsLatLng="setLatLng()"
                                :propsaddress="this.propsAddress"
                                @setLatLng="getLatLng"
                            ><slot></slot></googlemaps-component>
                        </div>
                    </div>
                    <div class="col-sm-4 col-md-4">
                        <label class="control-label">現場写真</label>
                        <label class="imagePreview">
                            <img class="imagePreview" v-show="viewImage" :src="viewImage">
                            <span>
                                <input type="file" class="uploadfile" accept="image/png, image/jpeg" style="display:none" @change="Preview">
                            </span>
                        </label>     
                    </div>

                    <div class="col-md-6 col-sm-6">
                        <div class="form-group">
                            <label class="col-sm-12">郵便番号</label>
                            <div class="col-sm-4 col-md-4">
                                <input type="text" class="form-control" v-model="myAddresss.zipcode" maxlength="7" @change="getAddress">
                            </div>
                            <div class="col-sm-8 col-md-8 text-right">
                                <button type="button" class="btn btn-search" @click="setAddress()">住所から緯度経度を自動入力</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-12">住所１</label>
                            <div class="col-sm-12 col-md-12">
                                <input type="text" class="form-control" v-model="myAddresss.address1">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-12">住所２</label>
                            <div class="col-sm-12 col-md-12">
                                <input type="text" class="form-control" v-model="myAddresss.address2">
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-5 col-md-5 col-md-offset-1">
                        <div class="form-group">
                            <label class="col-sm-10 col-md-offset-2">日本測地計</label>
                            <div class="col-sm-10 col-md-offset-2">
                                <div v-bind:class="{'has-error': (errors.latitude_jp != '') }">
                                    <div class="col-sm-6">
                                    <label class="col-sm-12">緯度</label>
                                        <input type="text" class="form-control" v-model="myAddresss.latitude_jp">
                                        <p class="text-danger">{{ errors.latitude_jp }}</p>
                                    </div>
                                </div>
                                <div v-bind:class="{'has-error': (errors.longitude_jp != '') }">
                                    <div class="col-sm-6">
                                    <label class="col-sm-12">経度</label>
                                        <input type="text" class="form-control" v-model="myAddresss.longitude_jp">
                                        <p class="text-danger">{{ errors.longitude_jp }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-10 col-md-offset-2">世界測地計</label>
                            <div class="col-sm-10 col-md-offset-2">
                                <div v-bind:class="{'has-error': (errors.latitude_world != '') }">
                                    <div class="col-sm-6">
                                    <label class="col-sm-12">緯度</label>
                                        <input type="text" class="form-control" v-model="myAddresss.latitude_world">
                                        <p class="text-danger">{{ errors.latitude_world }}</p>
                                    </div>
                                </div>
                                <div v-bind:class="{'has-error': (errors.longitude_world != '') }">
                                    <div class="col-sm-6">
                                    <label class="col-sm-12">経度</label>
                                        <input type="text" class="form-control" v-model="myAddresss.longitude_world">
                                        <p class="text-danger">{{ errors.longitude_world }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ボタン -->
                    <div class="col-md-12 text-right">
                        <button type="button" class="btn btn-save" @click="save">住所情報登録</button>
                    </div>
                </div>
                <div class="col-md-12 text-right">
                    <button type="button" class="btn btn-warning btn-back" v-on:click="back">戻る</button>
                </div>
            </form>
        </div>
    </div>
</template>


<script>
export default {
    data: () => ({
        loading: false,

        viewImage: null,
        uploadImage: null,

        gmapSearchFlg: false,

        myAddresss: {
            zipcode: '',
            address1: '',
            address2: '',
            latitude_jp: '',
            longitude_jp: '',
            latitude_world: '',
            longitude_world: '',
        },
        propsAddress: {
            address: '',
        },
        LatLng: {
            latitude_jp: '',
            lngitude_jp: '',
            latitude_world: '',
            lngitude_world: '',
        },


        errors: {
            zipcode: '',
            address1: '',
            address2: '',
            latitude_jp: '',
            longitude_jp: '',
            latitude_world: '',
            longitude_world: ''
        },

    }),
    props: {
        addressdata: {},
    },
    created: function() {
    },
    mounted: function(){
        this.myAddresss.matter_id = this.addressdata.matter_id;
        if (this.addressdata != null && this.rmUndefinedZero(this.addressdata.id) != 0){
            this.myAddresss = this.addressdata;
            this.viewImage = this.addressdata.image;
        }
    },
    methods: {
        // 保存
        save() {
            this.loading = true

            // エラーの初期化
            this.initErr(this.errors);

            // 入力値を取得
            var params = new FormData();
            params.append('id', this.rmUndefinedBlank(this.addressdata.id));
            params.append('matter_id', this.rmUndefinedBlank(this.addressdata.matter_id));
            params.append('zipcode', this.rmUndefinedBlank(this.myAddresss.zipcode));
            params.append('address1', this.rmUndefinedBlank(this.myAddresss.address1));
            params.append('address2', this.rmUndefinedBlank(this.myAddresss.address2));
            params.append('latitude_jp', this.rmUndefinedZero(this.myAddresss.latitude_jp));
            params.append('longitude_jp', this.rmUndefinedZero(this.myAddresss.longitude_jp));
            params.append('latitude_world', this.rmUndefinedZero(this.myAddresss.latitude_world));
            params.append('longitude_world', this.rmUndefinedZero(this.myAddresss.longitude_world));
            params.append('image', this.uploadImage);

            axios.post('/address-edit/save', params, {headers: {'Content-Type': 'multipart/form-data'}})

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    var query = window.location.search
                    var listUrl = this.getLocationUrl(query)
                    var sendUrl = listUrl + query;

                    location.href = sendUrl
                } else {
                    // 失敗
                    // location.reload();
                    alert(MSG_ERROR)
                }
            }.bind(this))

            .catch(function (error) {
                if (error.response.data.errors) {
                    // エラーメッセージ表示
                    this.showErrMsg(error.response.data.errors, this.errors)
                } else {
                    if (error.response.data.message) {
                        alert(error.response.data.message)
                    } else {
                        alert(MSG_ERROR)
                    }
                    location.reload()
                }
                this.loading = false
            }.bind(this))
        },
        // 戻る
        back() {
            var query = window.location.search
            var listUrl = this.getLocationUrl(query)
            var sendUrl = listUrl + query;

            location.href = sendUrl
        },
        // 画像プレビュー
        Preview(e) {
            let files = e.target.files[0];
            let reader = new FileReader();
            reader.onload = (e) => {
                this.viewImage = e.target.result;
            };
            this.uploadImage = e.target.files[0];
            reader.readAsDataURL(files);
        },
        // 郵便番号から住所自動入力
        getAddress() {
            var zipcode = this.myAddresss.zipcode
            var myaddress = this.myAddresss
            new YubinBango.Core(zipcode, function(addr){
                var addr1 = addr.region + addr.locality + addr.street
                myaddress.address1 = addr1
            })
        },
        // 住所をセット
        setAddress() {
            this.gmapSearchFlg = true;
            this.propsAddress.address = this.myAddresss.address1 + this.myAddresss.address2;
        },
        // 編集時、緯度経度が入力されていた場合のみGoogleMapへ渡す
        setLatLng() {
            if(this.myAddresss.latitude_world != '' && this.myAddresss.longitude_world != '' && this.myAddresss.latitude_world != undefined
                && this.myAddresss.longitude_world != undefined)
            {
                this.LatLng.latitude_world = this.myAddresss.latitude_world;
                this.LatLng.longitude_world = this.myAddresss.longitude_world;
            }
            return this.LatLng;
        },
        // マーカーの座標取得
        getLatLng(e){ 
            if (this.rmUndefinedBlank(e.address1) != '' && !this.gmapSearchFlg) {
                this.$set(this.myAddresss, 'address1', e.address1);
                this.$set(this.myAddresss, 'zipcode', e.zipcode);
            // this.$set(this.myAddresss, 'address2', e.address2);
            }

            this.$set(this.myAddresss, 'latitude_jp', e.jpLat);
            this.$set(this.myAddresss, 'longitude_jp', e.jpLng);
            this.$set(this.myAddresss, 'latitude_world', e.wLat);
            this.$set(this.myAddresss, 'longitude_world', e.wLng);
            setTimeout(() => {
                this.gmapSearchFlg = false;
            }, 1000);

        },
    },
}
</script>

<style>
.main-body {
    width: 100%;
    background: #ffffff;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.mapPreview {
    width: 100%;
    height: 400px;
    margin: 3px;
    background-position: center center;
    background-size: cover;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    display: inline-block;
}
.imagePreview {
    width: 100%;
    height: 370px;
    background-position: center center;
    background-size: cover;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    display: inline-block;
    cursor: pointer;
}


</style>
