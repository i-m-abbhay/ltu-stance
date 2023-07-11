<template>
    <div>
        <loading-component :loading="loading" />
        <div class="form-horizontal save-form">
            <form id="saveForm" name="saveForm" class="form-horizontal" enctype="multipart/form-data" method="post" action="/new-customer-edit/save">
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
                                <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label class="control-label" for="acCustomer">得意先名</label>
                                            <wj-auto-complete class="form-control" id="acCustomer" 
                                                search-member-path="customer_name, customer_kana"
                                                display-member-path="customer_name"
                                                :items-source="customerlist"
                                                selected-value-path="customer_name" 
                                                :min-length=1
                                                :lost-focus="selectCustomer">
                                            </wj-auto-complete>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="control-label">案件番号</label>
                                            <wj-auto-complete class="form-control" id="acMatter" 
                                                search-member-path="matter_no"
                                                display-member-path="matter_no"
                                                :items-source="matterlist"
                                                selected-value-path="matter_no" 
                                                :min-length=1
                                                :lost-focus="selectMatterNo">
                                            </wj-auto-complete>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="control-label">案件名</label>
                                            <wj-auto-complete class="form-control" id="acMatter" 
                                                search-member-path="matter_name"
                                                display-member-path="matter_name"
                                                :items-source="matterlist"
                                                selected-value-path="matter_name" 
                                                :min-length=1
                                                :lost-focus="selectMatterName">
                                            </wj-auto-complete>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="control-label">出荷元倉庫</label>
                                            <wj-auto-complete class="form-control" id="acMatter" 
                                                search-member-path="matter_name"
                                                display-member-path="matter_name"
                                                :items-source="matterlist"
                                                selected-value-path="matter_name" 
                                                :min-length=1
                                                :lost-focus="selectMatterName">
                                            </wj-auto-complete>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label class="control-label">部門名</label>
                                            <wj-auto-complete class="form-control" id="acDepartment" 
                                                search-member-path="department_name"
                                                display-member-path="department_name"
                                                :items-source="departmentlist"
                                                selected-value-path="department_name" 
                                                :min-length=1
                                                :lost-focus="selectDepartment">
                                            </wj-auto-complete>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="control-label">担当者名</label>
                                            <wj-auto-complete class="form-control" id="acStaff" 
                                                search-member-path="staff_name"
                                                display-member-path="staff_name"
                                                :items-source="stafflist"
                                                selected-value-path="staff_name" 
                                                :min-length=1
                                                :lost-focus="selectStaff">
                                            </wj-auto-complete>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <label class="col-md-12 text-left">受注登録日</label>
                                        <div class="col-md-3">
                                            <input type="date" class="form-control" name="order_from_date" v-model="searchParams.order_from_date">
                                        </div>
                                        <span class="col-md-1 text-center">～</span>
                                        <div class="col-md-3">
                                            <input type="date" class="form-control" name="order_to_date" v-model="searchParams.order_to_date">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <label class="col-md-12 text-left">入荷予定日</label>
                                        <div class="col-md-3">
                                            <input type="date" class="form-control" name="arrival_from_date" v-model="searchParams.arrival_from_date">
                                        </div>
                                        <span class="col-md-1 text-center">～</span>
                                        <div class="col-md-3">
                                            <input type="date" class="form-control" name="arrival_to_date" v-model="searchParams.arrival_to_date">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <label class="col-md-12 text-left">出荷予定日</label>
                                        <div class="col-md-3">
                                            <input type="date" class="form-control" name="shipping_from_date" v-model="searchParams.shipping_from_date">
                                        </div>
                                        <span class="col-md-1 text-center">～</span>
                                        <div class="col-md-3">
                                            <input type="date" class="form-control" name="shipping_to_date" v-model="searchParams.shipping_to_date">
                                        </div>
                                    </div>

                                    <div class="pull-right">
                                        <button type="submit" class="btn btn-primary btn-search">出荷案件検索</button>
                                    </div>
                                    
                                    <div class="clearfix"></div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>


<script>
export default {
    components:
    {
    },
    data: () => ({
        loading: false,
        searchParams: {
            customer_name: '',
            matter_no: '',
            matter_name: '',
            department_name: '',
            staff_name: '',
            order_from_date: '',
            order_to_date: '',
            arrival_from_date: '',
            arrival_to_date: '',
            shipping_from_date: '',
            shipping_to_date: '',
        },

        index: 0,

        customerAddress: {
            address: '',
        },
        branchAddress: [{
            number: '',
            address: '',
        }],
        
        LatLng: {
            latitude_jp: '',
            lngitude_jp: '',
            latitude_world: '',
            lngitude_world: '',
        },

        customer: [{
            customer_name: '',
            customer_short_name: '',
            customer_kana: '',
            corporate_number: '',
            company_category: '',
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
        }],

        errors: {
            customer_name: '',
            customer_kana: '',
            customer_short_name: '',
            corporate_number: '',
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
            checkList: '',
            person_name: '',
            person_kana:'',
            belong_name: '',
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
        customerlist: Array,
        matterlist: Array,
        departmentlist: Array,
        stafflist: Array,
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
        // コンボボックスの先頭に空行追加
        this.customerlist.splice(0, 0, '')
        this.departmentlist.splice(0, 0, '')
        this.stafflist.splice(0, 0, '')
        this.matterlist.splice(0, 0, '')
    },
    mounted() {
    },
    methods: {
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

            // 得意先基本情報
            params.append('id', (this.customer.id !== undefined) ? this.customer.id : '');
            params.append('customer_name', this.rmUndefinedBlank(this.customer.customer_name));
            params.append('customer_kana', this.rmUndefinedBlank(this.customer.customer_kana));
            params.append('customer_short_name', this.rmUndefinedBlank(this.customer.customer_short_name));
            params.append('corporate_number', this.rmUndefinedBlank(this.customer.corporate_number));
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
            axios.post('/new-customer-edit/save', params, {headers: {'Content-Type': 'multipart/form-data'}})

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    var listUrl = '/new-customer-list' + window.location.search
                    location.href = (listUrl)
                } else {
                    // 失敗
                    alert(MSG_ERROR)
                    // location.reload();
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
        del() {
            if(!confirm(MSG_CONFIRM_DELETE)) {
                return;
            }
            
            this.loading = true

            var params = new URLSearchParams();
            params.append('id', (this.customer.id !== undefined) ? this.customer.id : '');
            axios.post('/new-customer-edit/delete', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    var listUrl = '/new-customer-list' + window.location.search
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
            var listUrl = '/new-customer-list' + window.location.search
            location.href = (listUrl)
        },
        // 編集モード
        edit() {

            // this.isReadOnly = false;
            // this.isShowEditBtn = false;
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
        selectCustomer: function(sender) {
            this.searchParams.customer_name = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
        },
        selectMatterNo: function(sender) {
            this.searchParams.matter_no = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
        },
        selectMatterName: function(sender) {
            this.searchParams.matter_name = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
        },
        selectDepartment: function(sender) {
           this.searchParams.department_name = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
        },
        selectStaff: function(sender) {
            this.searchParams.staff_name = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
        },
    },
}
</script>

<style>
.mapPreview {
    width: 100%;
    height: 210px;
    background-position: center center;
    background-size: cover;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    display: inline-block;
}
.imagePreview {
    width: 100%;
    height: 200px;
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

</style>

