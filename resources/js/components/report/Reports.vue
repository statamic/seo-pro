<template>

    <div>

        <div class="flex items-center mb-3">
            <h1 class="flex-1">{{ title }}</h1>
            <a href=""
                v-if="showingReport"
                @click.prevent="currentReportId = null"
                class="btn btn-default mr-2">
                &larr; {{ translate('addons.SeoPro::messages.back_to_reports') }}
            </a>
            <a href=""
                @click.prevent="generateReport"
                class="btn btn-primary"
                v-text="translate('addons.SeoPro::messages.generate_report')">
            </a>
        </div>

        <seo-report-listing
            v-if="showingListing"
            @report-selected="selectReport"
        ></seo-report-listing>

        <seo-report
            v-if="showingReport"
            :id="currentReportId"
        ></seo-report>

    </div>

</template>


<script>
export default {

    components: {
        SeoReport: require('./Report.vue'),
        SeoReportListing: require('./Listing.vue')
    },

    data() {
        return {
            currentReportId: null,
        }
    },

    computed: {

        showingListing() {
            return !this.currentReportId;
        },

        showingReport() {
            return !this.showingListing;
        },

        title() {
            return this.showingListing ? 'SEO Reports' : 'SEO Report';
        }

    },

    methods: {

        selectReport(id) {
            this.currentReportId = id;
        },

        generateReport() {
            this.loading = true;
            this.currentReportId = null;

            // this.$nextTick(() => {
                this.$http.post(cp_url('addons/seo-pro/reports')).then(response => {
                    this.currentReportId = response.data;
                    this.loading = false;
                });
            // });
        }

    }

}
</script>
