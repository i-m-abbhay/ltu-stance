<template>
    <div>
        <div class="col-md-12">
            <div class="col-md-6 col-md-offset-4 qreq-complete-div">
                <label>案件名：{{ completeData.matter_name }}</label>
            </div>
            <div class="col-md-6 col-md-offset-4 qreq-complete-div">
                <label>案件番号：{{ completeData.matter_no }}</label>
            </div>
            <div class="col-md-6 col-md-offset-4">
                <label>で案件が登録されました。</label>
            </div>

            <div class="col-md-6 col-md-offset-4 qreq-complete-div">
                <label>見積提出期限：{{ completeData.quote_limit_date|date_format }}</label>
            </div>

            <div class="col-md-6 col-md-offset-4 qreq-complete-div">
                <div v-for="(item, iCnt) in completeData.wait_count" :key="iCnt">
                    <label>{{ item.construction_name }}は、{{ item.cnt }}番目で受付待ちです。</label>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.qreq-complete-div {
    margin-top: 7px;
}
</style>

<script>
export default {
    data: () => ({
    }),
    props: {
        completeData: Object
    },
    created() {
    },
    mounted() {
    },
    methods: {

    },
};

</script>
