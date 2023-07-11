<template>
    <nav class="navbar navbar-inverse navbar-custom">
		<div class="container-fluid">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbarEexample" aria-expanded="true">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="/" style="color:#fff;"><img src="/logo.png" class="pull-left" style="height:15px;" /><span class="pull-left"> For Internship</span></a>
			</div>
	
			<div class="navbar-collapse collapse" id="navbarEexample" aria-expanded="false" style="">
				<ul class="nav navbar-nav navbar-right">
					<li role="presentation">
						<a href="/mobile-menu" class="portal-menu-item"><svg class="header-menu-icon"><use width="30" height="30" xlink:href="#truckIcon" /></svg></a>
					</li>

					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
							<span class="glyphicon glyphicon-bell" aria-hidden="true"></span>
							<div class="badge badge-notice" v-show="alertShowBadge">&nbsp;</div>
						</a>
						<ul class="dropdown-menu dropdown-menu-notice" role="menu" v-loading="alertLoading">
							<li class="notice-header">
								<div>
									<strong class="un-read-cnt">未読のお知らせ:{{ alertUnReadCnt }}件</strong>
									<button class="btn btn-default btn-xs pull-right" v-on:click="alertReadAll"><strong>全て既読</strong></button>
								</div>
							</li>
							<li class="notice-body">
								<ul class="notice-body-detail" role="menu">
									<li v-for="notice in alertData" :key="notice.id">
										<a :class="{ read: notice.read_at != null }" :href="'/notice/read/' + notice.id" tabindex="-1">
											<div>{{ notice.created_at|datetime_format }}　{{ notice.created_user_name }}さん</div>
											<div>{{ notice.content }}</div>
										</a>
									</li>
								</ul>
							</li>
						</ul>
					</li>

					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
							<span class="glyphicon glyphicon-envelope" aria-hidden="true"></span>
							<div class="badge badge-notice" v-show="showBadge">&nbsp;</div>
						</a>
						<ul class="dropdown-menu dropdown-menu-notice" role="menu" v-loading="loading">
							<li class="notice-header">
								<div>
									<strong class="un-read-cnt">未読のお知らせ:{{ unReadCnt }}件</strong>
									<button class="btn btn-default btn-xs pull-right" v-on:click="readAll"><strong>全て既読</strong></button>
								</div>
							</li>
							<li class="notice-body">
								<ul class="notice-body-detail" role="menu">
									<li v-for="notice in mailNoticesData" :key="notice.id">
										<a :class="{ read: notice.read_at != null }" :href="'/notice/read/' + notice.id" tabindex="-1">
											<div>{{ notice.created_at|datetime_format }}　{{ notice.created_user_name }}さん</div>
											<div>{{ notice.content }}</div>
										</a>
									</li>
								</ul>
							</li>
						</ul>
					</li>

					<li role="presentation">
						<a href="https://ltu-main.wixsite.com/website-2" target="_blank" class="help-link-icon"><i class="el-icon-question"></i></a>
					</li>
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                            <span class="glyphicon glyphicon-user" aria-hidden="true"></span>
                            {{ loginuser.staff_name }}
                            <span class="caret"></span>
                        </a>
						<ul class="dropdown-menu" role="menu">
							<!-- <li role="presentation"><a role="menuitem" tabindex="-1" href="#">パスワード変更</a></li> -->
							<li role="presentation"><a role="menuitem" tabindex="-1" href="/logout">ログアウト</a></li>
						</ul>
					</li>
				</ul>
			</div>
		</div>
	</nav>
</template>

<style>
.btn-inverse {
    color: #636b6f;
    background-color: #222;
    border-color: #222;
}
.btn-inverse:hover {
    color: #fff;
}

.navbar-custom {
    margin: 0;
}

.help-link-icon {
	font-size: 18px;
}
.portal-menu-item {
	padding-top: 8px !important;
	padding-bottom: 0px !important;
}
.header-menu-icon {
	color: transparent !important;
	height: 30px;
	width: 30px;
}
.header-menu-icon:hover {
	color: #696969 !important;
}

/**
	通知ドロップダウン用
*/
/* 通知コンテナ */
.dropdown-menu-notice{
	padding: 0;
}
.notice-header{
	padding: 5px 15px 5px 15px;
	background-color:#696969;
}
.un-read-cnt{
	color:#FFFFFF;
}
.notice-body{
	background-color: #fff;
}
.notice-body-detail {
	padding: 0px;
	width: 450px;
    height: auto;
	max-height: 300px;
	overflow-x: hidden;
	list-style-type: none;
}

.notice-body-detail >li{
	height: 100%;
	border-bottom: 1px solid;
	
}

.notice-body-detail >li:last-child{
	border-bottom: none;
}

.notice-body-detail >li >a{
	display: block;
	padding: 5px 15px 5px 25px;;
	height: 100%;
	width: 100%;
	text-decoration: none;
}

.notice-body-detail >li >a.read{
	background-color: darkgray;
}

.notice-body-detail >li >a >div{
	overflow: hidden;
	/* white-space: nowrap; */
	word-break: break-all;
	text-overflow: ellipsis;
}

.notice-body-detail > li > a:hover,
.notice-body-detail > li > a:focus {
	background-color: #f5f5f5;
}

/** バッジ */
.badge.badge-notice{
	background-color: #FFC06A;
	position: relative;
	top: -10px;
	left: -8px;
	width:10px;
	height:10px;
	padding:0px;
}

/**
	@media
*/
@media (max-width: 768px) {
	.notice-body-detail {
		width: auto;
	}
}
</style>

<script>
export default {
	data:() => ({
		loading: false,
		showBadge: false,
		unReadCnt: 0,
		alertLoading: false,
		alertShowBadge: false,
		alertUnReadCnt: 0,
		mailNoticesData: [], 
		alertData: [],
	}),
    props: {
		loginuser: Object,
	},
    created() {
		// 通知
		axios.get('/notice/getNoticesByNoticeflag/0')
		.then( function (response) {
                if (response.data) {
					response.data.forEach(notice => {

						// 未読をカウント
						if(notice.read_at == null ){
							this.unReadCnt +=1;
						}
						this.mailNoticesData.push(notice)	
						this.showBadge = this.unReadCnt > 0 ? true : false;

					});
                }
            }.bind(this))

		//アラート
		axios.get('/notice/getNoticesByNoticeflag/1')
		.then( function (response) {
                if (response.data) {
					response.data.forEach(notice => {

						// 未読をカウント
						if(notice.read_at == null ){
							this.alertUnReadCnt +=1;
						}
						this.alertData.push(notice)	
						this.alertShowBadge = this.alertUnReadCnt > 0 ? true : false;

					});
                }
            }.bind(this))
	},
	methods:{
		readAll: function(){
            this.loading = true

            axios.get('/notice/readAllByNoticeflag/0')

            .then( function (response) {
                this.loading = false
                if (response.data) {
					this.mailNoticesData = response.data;
					this.unReadCnt = 0;
					response.data.forEach(notice => {
						if(notice.read_at == null){
							this.unReadCnt +=1;
						}
					});
					this.showBadge = this.unReadCnt > 0 ? true : false;
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


		alertReadAll: function(){
            this.alertLoading = true

            axios.get('/notice/readAllByNoticeflag/1')

            .then( function (response) {
                this.alertLoading = false
                if (response.data) {
					this.alertData = response.data;
					this.alertUnReadCnt = 0;
					response.data.forEach(notice => {
						if(notice.read_at == null){
							
							this.alertUnReadCnt +=1;
						}
					});
					this.alertShowBadge = this.alertUnReadCnt > 0 ? true : false;
                }
            }.bind(this))

            .catch(function (error) {
                this.alertLoading = false
                if (error.response.data.message) {
                    alert(error.response.data.message)
                } else {
                    alert(MSG_ERROR)
                }
                location.reload()
            }.bind(this))
		}
	}
}
</script>
