<template>
    <div>
        <div id="viewer"></div>
        <div v-if="order.map_print_flg == FLG_ON" id="Gmap01" class="mapPreview">
            <googlemaps-component
                :propsLatLng="setLatLng()"
                :propsaddress="this.propsAddress"
            ><slot></slot></googlemaps-component>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        order: Object,
        dataSource: Object,
        addressdata: {},
    },
    data: () => ({
        viewer: null,
        report: null,
        fonts: [
                {
                    "name": "ＭＳ ゴシック",
                    "source": "/fonts/ms_gothic/msgothic.ttc",
                    "postscriptName": "MS-Gothic"
                },
                {
                    "name": "Arial",
                    "source": "/fonts/arial/arialbd.ttf"
                },
            ],

        // 地図用
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
     }),
    created: function() {
        if (this.addressdata != null && this.addressdata != 0){
            this.myAddresss = this.addressdata;
            this.viewImage = this.addressdata.image;
        }
    },
    mounted: function() {
        this.viewer = new ActiveReports.Viewer('#viewer', {language: 'ja'});
        this.viewer.zoom = 'FitPage';
        this.viewer.toggleSidebar(false);
        this.viewer.registerFont(this.fonts);

        this.report = new GC.ActiveReports.Core.PageReport();
        this.report.load('/template/reports/order-v2.rdlx-json')
        .then(
            function(){
                var url = 
                this.report._instance.definition.DataSources[0].ConnectionProperties.ConnectString = "jsondata=" + JSON.stringify(this.dataSource);
                return this.report.load(this.report._instance.definition);
            }.bind(this)
        ).then(
            function(){
                this.report.run();
                this.viewer.open(this.report);
            }.bind(this)
        );

        var defaultLayout = [
            '$navigation',
            '$zoom',
            '$singlepagemode',
            '$continuousmode',
            '$print',
        ];

        // 地図印刷フラグたっている場合のカスタマイズ
        if (this.order.map_print_flg == this.FLG_ON) {
            // ツールバーカスタマイズ
            // カスタムボタン
            const printButton  = {
                key: '$print',
                title: '印刷',
                enabled: false,
                action: function(item) {
                    this.report.run()
                        .then(function(pageDocument) {
                            pageDocument.print();
                        }.bind(this))
                        .then(function() {
                            setTimeout(function() {
                                var result =  window.confirm('地図を印刷しますか？');
                                if (result) {
                                    this.printMap();
                                }                                
                            }.bind(this), 500);
                        }.bind(this))
                }.bind(this)
            };
            this.viewer.toolbar.updateItem('$print', printButton);  // 元々の印刷ボタンを書き換え

            // カスタムボタン
            var icon = document.createElement('i');
            icon.classList.add('el-icon-location-outline');
            const printMapButton = {
                key: '$printMap',
                title: '地図印刷',
                icon: { type: 'svg', content: icon.outerHTML },
                enabled: true,
                action: function(item) {
                    this.printMap();
                }.bind(this)
            };
            defaultLayout.push('$printMap');
            // ツールバーにカスタムボタンを追加する
            this.viewer.toolbar.addItem(printMapButton);
        }
        this.viewer.toolbar.updateLayout({default: defaultLayout });
    },
    methods: {
        // 地図印刷
        printMap(){
            /* Gmap01のdisplayの初期値をnoneに設定しておき、直前にblockにするやりかたでは、地図上に白い帯が現れる */
            var printPage = document.getElementById('Gmap01').cloneNode(true);
            printPage.id = 'tmpGmap';
            document.body.appendChild(printPage);
            var invisibleElements = document.querySelectorAll('body > :not(#tmpGmap)');
            invisibleElements.forEach(function(item) {
                item.classList.add('print-off');
            });
            window.print();
            document.getElementById('tmpGmap').remove();
            invisibleElements.forEach(function(item) {
                item.classList.remove('print-off');
            });
        },
        // 緯度経度を場合のみGoogleMapへ渡す
        setLatLng() {
            if(this.myAddresss.latitude_world != '' && this.myAddresss.longitude_world != '' && this.myAddresss.latitude_world != undefined
                && this.myAddresss.longitude_world != undefined)
            {
                this.LatLng.latitude_world = this.myAddresss.latitude_world;
                this.LatLng.longitude_world = this.myAddresss.longitude_world;
            }
            return this.LatLng;
        },
    }
}
</script>
<style>
#viewer{
    height: 90vh;
}
.mapPreview {
    width: 100%;
    /* height: 700px; */
    height: 99%;
    margin: 3px;
    background-position: center center;
    background-size: cover;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    display: inline-block;
}
/* 地図印刷 */
.print-off {
    display: none;
}
@page {
    size: auto;   /* auto is the initial value */
    margin: 5mm;  /* this affects the margin in the printer settings */
}
</style>