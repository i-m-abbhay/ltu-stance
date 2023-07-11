<template>

</template>


<script>
export default {
    props: {
            mapWidth: {
                type: Number,
                default: 100
            },
            mapHeight: {
                type: Number,
                default: 100
            },
            lat: {
                type: Number,
                default: 32.93923,
            },
            lng: {
                type: Number,
                default: 129.93554,
            },
            zoom: {
                type: Number,
                default: 10
            },
            markers: {
                type: Array,
                default: () => {
                    return [];
                }
            },
            // 緯度経度から住所取得時に使用
            propsLatLng: {
                type: Object,
            },
            // 住所から緯度経度取得時に使用
            propsaddress: {
                type: Object,
            },
            searchAddressMap: {
                type: String,
            },
            propsList: {
                type: Object,
            },
            propsAddressList: {
                type: Object,                
            },
            propsListIndex: {
                type: Number,
                default: 1,
            },
            // 一画面でGoogleMapを複数使用する時
            index: {
                type: Number,
                default: 1,
            },
            readonly: Boolean,
    },
    data() {
        return {
            map: null,
            formattedMarkers: [],
            rtnLatLng: {
                jpLat: null,
                jpLng: null,
                wLat: null,
                wLng: null,
                address1: '',
                address2: '',
                zipcode: '',
            },
            address: '',

            tmpLat: 32.93923,
            tmpLng: 129.93554,

            firstClicked: false,
        };
        searchAddFlg = false;
    },
    watch: {
        propsaddress: {
            handler: function(newVal) {
                this.addressGeocoding(newVal.address);
                setTimeout(() => {
                    this.$emit('setLatLng', this.rtnLatLng);
                }, 350);
            },
            deep: true,
        },
        propsAddressList: {
            handler: function(newVal) {
                this.addressGeocoding(newVal.address);
            },
            deep: true,
        },
        map: {
            handler: function() {
                if (this.rmUndefinedBlank(this.searchAddressMap) != '') {
                    this.searchMapByAddress();
                }
            }
        },
    },
    created() {
        $('div.mapPreview').each(function (index, element) {
            $(element).attr('id', 'Gmap' + (index + 1).toString().padStart(2, '0'));
        });
        setTimeout(this.initMap, 1500);
    },
    mounted() {
        // google.maps.event.addDomListener(window, 'load', this.initMap);
        // GoogleMapを読み込み終わってから実行しないと正常に動作しない？
        if(this.propsLatLng != null){
            this.checkLatLng(this.propsLatLng);
        }
        if(this.propsList != null){
            this.checkLatLng(this.propsList);
        }

        var _this = this;
        setTimeout(function() {
            _this.firstClicked = true;
        }, 3000);
    },
    methods:{
        searchMapByAddress() {
            if (this.rmUndefinedBlank(this.searchAddressMap) != '') {
                this.searchAddFlg = true;
                this.addressGeocoding(this.searchAddressMap);
            }
        },
        checkLatLng(val) {
            if(val.latitude_world !== undefined && val.longitude_world !== undefined && val.latitude_world != '' && val.longitude_world != ''
                && val.latitude_world != null && val.longitude_world != null){
                this.tmpLat = val.latitude_world;
                this.tmpLng = val.longitude_world;
            }
        },
        // マーカーを設置
        addMarker() {
            this.markers.forEach(markerInfo => {
                var contentString =
                    '<div id="content">' +
                    '<div id="siteNotice">' +
                    "</div>" +
                    '<h1 id="firstHeading" class="firstHeading">Uluru</h1>' +
                    '<div id="bodyContent">' +
                    "<p><b>Uluru</b>, also referred to as <b>Ayers Rock</b>, is a large " +
                    "sandstone rock formation in the southern part of the " +
                    "Northern Territory, central Australia. It lies 335&#160;km (208&#160;mi) " +
                    "south west of the nearest large town, Alice Springs; 450&#160;km " +
                    "(280&#160;mi) by road. Kata Tjuta and Uluru are the two major " +
                    "features of the Uluru - Kata Tjuta National Park. Uluru is " +
                    "sacred to the Pitjantjatjara and Yankunytjatjara, the " +
                    "Aboriginal people of the area. It has many springs, waterholes, " +
                    "rock caves and ancient paintings. Uluru is listed as a World " +
                    "Heritage Site.</p>" +
                    '<p>Attribution: Uluru, <a href="https://en.wikipedia.org/w/index.php?title=Uluru&oldid=297882194">' +
                    "https://en.wikipedia.org/w/index.php?title=Uluru</a> " +
                    "(last visited June 22, 2009).</p>" +
                    "</div>" +
                    "</div>";


                // マーカーオプション
                let marker = new google.maps.Marker({
                    position: markerInfo.position,
                    map: this.map,
                    animation: google.maps.Animation.DROP
                });    

                // マーカーの情報window
                let infowindow = new google.maps.infowindow({
                    content: contentString
                });

                // クリック時にwindow表示
                marker.addListener('click', function() {
                    infowindow.open(this.map, marker);
                });
                this.formattedMarkers.push(marker);
            });

        },
        fixedFloat(number, digits){
            return Number.parseFloat(number).toFixed(digits);
        },
        latlngSlice() {
            if(this.rtnLatLng.jpLat != '' && this.rtnLatLng.jpLng != ''){
                this.rtnLatLng.jpLat = this.fixedFloat(this.rtnLatLng.jpLat, 5);
                this.rtnLatLng.jpLng = this.fixedFloat(this.rtnLatLng.jpLng, 5);
            }
            if(this.rtnLatLng.wLat != '' && this.rtnLatLng.wLng != ''){
                this.rtnLatLng.wLat = this.fixedFloat(this.rtnLatLng.wLat, 5);
                this.rtnLatLng.wLng = this.fixedFloat(this.rtnLatLng.wLng, 5);
            }
        },
        // 世界測地計から日本測地計へ変換
        getFromWorldToJp(lat, lng) {
            this.rtnLatLng.jpLat = lat + lat * 0.00010696 - lng * 0.000017467-0.004602
            this.rtnLatLng.jpLng = lng + lat * 0.000046047 + lng * 0.000083049-0.010041
        },
        // マーカー位置の緯度経度取得
        getLatLng(marker) {
            if (!this.readonly && this.firstClicked) {
                var pos = marker.getPosition();
                this.rtnLatLng.wLat = pos.lat();
                this.rtnLatLng.wLng = pos.lng();
                if(this.propsListIndex != null || this.propsListIndex != undefined){
                    this.rtnLatLng.index = this.propsListIndex;
                }

                this.getFromWorldToJp(this.rtnLatLng.wLat, this.rtnLatLng.wLng);
                this.latlngSlice();
                setTimeout(() => {
                    this.$emit('setLatLng', this.rtnLatLng);
                }, 350);
            }
        },
        latlngGeocoding(latlng) {
            let _this = this;
            if (!this.readonly && this.firstClicked) {
                var geocoder = new google.maps.Geocoder();

                var map = this.map;
                var formattedMarkers = this.formattedMarkers;
                
                // 緯度経度から住所取得
                geocoder.geocode({
                    latLng: latlng
                }, function (results, status) {
                    if(status == google.maps.GeocoderStatus.OK) {
                        if (results[0]) {
                            var zipcode = '';
                            var adr1Value = '';
                            var adr2Value = '';
                            for(var i = (results[0].address_components.length - 1); i > 0; i--) {
                                var val = results[0].address_components[i].long_name;                            
                                var isConf = true;
                                var pattern = ['Unnamed Road', '日本'];

                                // 取得した住所を整形
                                pattern.forEach(function (col) {
                                        if(val.indexOf(col) > -1){
                                            isConf = false;
                                        }
                                })
                                if(isConf && val.search(/\d{3}[-]?\d{4}$/) > -1){
                                    val = val.replace(/-/g, '');
                                    zipcode = val;
                                }else if(isConf && val.search(/^[0-9 ０-９]/) > -1){
                                    adr2Value += val;
                                }else if(isConf){
                                    adr1Value += val;
                                }
                            }
                            _this.rtnLatLng.zipcode = zipcode;
                            _this.rtnLatLng.address1 = adr1Value;
                            _this.rtnLatLng.address2 = adr2Value;

                        }

                    } else if (status == google.maps.GeocoderStatus.ERROR) {
                        alert("通信エラー");
                    } else if (status == google.maps.GeocoderStatus.INVALID_REQUEST) {
                        alert("リクエストエラー");
                    } else if (status == google.maps.GeocoderStatus.OVER_QUERY_LIMIT) {
                        alert("クエリオーバー");
                    } else if (status == google.maps.GeocoderStatus.REQUEST_DENIED) {
                        alert("Geocoderが無効");
                    } else if (status == google.maps.GeocoderStatus.UNKNOWN_ERROR) {
                        alert("サーバーエラー");
                    } else if (status == google.maps.GeocoderStatus.ZERO_RESULTS) {
                        alert("見つかりません");
                    } else {
                        alert("Error");
                    }
                });
            }
        },
        addressGeocoding(adr){
            if ((!this.readonly || this.searchAddFlg) && this.rmUndefinedBlank(adr) != '' && this.firstClicked) {
                this.clearMarker();
                var geocoder = new google.maps.Geocoder();

                if((this.propsAddressList != null || this.propsAddressList != undefined) && this.rmUndefinedBlank(adr) == ''){
                    this.rtnLatLng.index = adr.number;
                    var adr = adr.address;
                }
                var map = this.map;
                var formattedMarkers = this.formattedMarkers;
                var latlng = '';

                let _this = this;

                // 住所から緯度経度取得
                geocoder.geocode({
                    address: adr
                }, function (results, status) {
                    if(status == google.maps.GeocoderStatus.OK) {
                        if (results[0]) {
                            latlng = results[0].geometry.location;
                            _this.rtnLatLng.wLat = results[0].geometry.location.lat();
                            _this.rtnLatLng.wLng = results[0].geometry.location.lng();

                            var marker = new google.maps.Marker({
                                position: {lat: _this.rtnLatLng.wLat, lng: _this.rtnLatLng.wLng},
                                map: map,
                                animation: google.maps.Animation.DROP
                            });
                            _this.formattedMarkers.push(marker);
                            _this.clickMap(latlng);
                        }

                    } else if (status == google.maps.GeocoderStatus.ERROR) {
                        alert("通信エラー");
                    } else if (status == google.maps.GeocoderStatus.INVALID_REQUEST) {
                        alert("リクエストエラー");
                    } else if (status == google.maps.GeocoderStatus.OVER_QUERY_LIMIT) {
                        alert("クエリオーバー");
                    } else if (status == google.maps.GeocoderStatus.REQUEST_DENIED) {
                        alert("Geocoderが無効");
                    } else if (status == google.maps.GeocoderStatus.UNKNOWN_ERROR) {
                        alert("サーバーエラー");
                    } else if (status == google.maps.GeocoderStatus.ZERO_RESULTS) {
                        alert("見つかりません");
                    } else {
                        alert("Error");
                    }
                });
            }
        },
        // マーカー設置処理
        clickMap(latlng) {
            if (!this.readonly || this.searchAddFlg) {
                // TODO: 複数のマーカーを可にする？
                var marker = new google.maps.Marker({
                    position: latlng,
                    map: this.map,
                    animation: google.maps.Animation.DROP,
                });
                this.formattedMarkers.push(marker);
                // this.map.setZoom(10);
                this.latlngGeocoding(latlng);
                this.map.panTo(latlng);

                // if (this.rmUndefinedBlank(this.searchAddressMap) != '') {
                this.getLatLng(marker);
                // } else {
                //     this.searchAddFlg = false;
                // }
            }
        },
        // 全マーカー削除
        clearMarker() {
            if (!this.readonly) {
                this.formattedMarkers.forEach(marker => {
                marker.setMap(null);
                })
                // props削除
                this.formattedMarkers.splice(0, this.formattedMarkers.length);
                this.addMarker();
            }
        },
        // GoogleMap取得
        initMap() {
            var _this = this;

            if (this.rmUndefinedBlank(this.propsLatLng) != '' && this.rmUndefinedBlank(this.propsLatLng.latitude_world) && this.rmUndefinedBlank(this.propsLatLng.longitude_world)) {
                var divId = 'Gmap' + this.index.toString().padStart(2, '0');
                _this.map = new google.maps.Map(document.getElementById(divId), {
                    center: {lat: _this.propsLatLng.latitude_world, lng: _this.propsLatLng.longitude_world},
                    zoom: _this.zoom,
                });
                var marker = new google.maps.Marker({
                    position: {lat: _this.propsLatLng.latitude_world, lng: _this.propsLatLng.longitude_world},
                    map: _this.map,
                });
            } else {
                var divId = 'Gmap' + this.index.toString().padStart(2, '0');
                _this.map = new google.maps.Map(document.getElementById(divId), {
                    center: {lat: _this.tmpLat, lng: _this.tmpLng},
                    zoom: _this.zoom,
                });
                var marker = new google.maps.Marker({
                    position: {lat: _this.tmpLat, lng: _this.tmpLng},
                    map: _this.map,
                });
            }
            _this.map.addListener('click', function(event){
                _this.clearMarker();
                _this.clickMap(event.latLng);
            });                 

            _this.formattedMarkers.push(marker);
            _this.addMarker();
        },
    }
}

</script>