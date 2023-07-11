<template>
    <div>
        <loading-component :loading="loading" />
        <div id="viewer"></div>
    </div>
</template>

<script>
export default {
    props: {
        quoteVersion: Object,
        dataSource: Object,
    },
    data: () => ({
        loading: false,
        viewer: null,
        report: null,
        pageDocument: null,
        fonts: [
                {
                    "name": "ＭＳ Ｐゴシック",
                    "source": "/fonts/ms_gothic/msgothic.ttc",
                    "postscriptName": "MS-PGothic"
                },
            ]
     }),
    mounted: function() {
        this.viewer = new ActiveReports.Viewer('#viewer', {language: 'ja'});
        this.viewer.zoom = 'FitPage';
        this.viewer.availableExports = ['pdf'];
        this.viewer.toggleSidebar(false);
        this.viewer.registerFont(this.fonts);

        this.report = new GC.ActiveReports.Core.PageReport();
        // this.report.load('/template/reports/quotation.rdlx-json').then(
        this.report.load('/template/reports/quotation-v2.rdlx-json').then(
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

        // ツールバーカスタマイズ
        // カスタムボタン
        const printButton  = {
            key: '$print',
            title: '印刷',
            enabled: false,
            action: function(item) {
                this.runPrintProcess();
            }.bind(this)
        };

        // ツールバーデフォルトレイアウト
        const defaultLayout = [
            '$navigation',
            '$zoom',
            '$singlepagemode',
            '$continuousmode',
            '$print',
        ];
        
        // カスタマイズ反映
        this.viewer.toolbar.updateItem('$print', printButton);  // 元々の印刷ボタンを書き換え
        this.viewer.toolbar.updateLayout({default: defaultLayout });
    },
    methods: {
        // プリントボタン押下時の処理（PDF保存,印刷ダイアログ表示）
        runPrintProcess() {
            this.loading = true;
            this.report.run()
                .then(function(pageDocument) {
                    var settings = {
                        pdfVersion:"1.7",
                        fonts: this.fonts,
                    }
                    this.pageDocument = pageDocument;
                    return GC.ActiveReports.PdfExport.exportDocument(pageDocument, settings)
                }.bind(this))
                .then(function(result) {
                    // 見積日更新
                    var params = new FormData();
                    params.append('quote_version_id', this.quoteVersion.id);
                    params.append('file', result.data);
                    axios.post('/quote-report/save', params, {headers: {'Content-Type': 'multipart/form-data'}})
                        .then(function (response) {
                            if (!response.data.status) {
                                // 失敗
                                alert(MSG_ERROR);
                            }
                        })
                        .catch(function (error) {
                            alert(MSG_ERROR);
                        })
                        .finally(function() {
                            this.loading = false;
                            this.pageDocument.print();
                        }.bind(this))
                }.bind(this))
        },
    }
}
</script>
<style>
#viewer{
    height: 90vh;
}
</style>