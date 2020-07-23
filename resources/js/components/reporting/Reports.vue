<template>

    <div>

        <div v-if="currentReportId" @click="currentReportId = null">
            <breadcrumb title="Reports" />
        </div>

        <div class="flex items-center mb-3">
            <h1 class="flex-1">{{ title }}</h1>
            <a href=""
                @click.prevent="generateReport"
                class="btn btn-primary"
                v-text="__('seo-pro::messages.generate_report')">
            </a>
        </div>

        <seo-report-listing
            v-if="showingListing"
            :route="listingRoute"
            @report-selected="selectReport"
        ></seo-report-listing>

        <seo-report
            v-if="showingReport"
            :id="currentReportId"
        ></seo-report>

    </div>

</template>

<script>
import SeoReportListing from './Listing';
import SeoReport from './Report';

export default {

    components: {
        SeoReportListing,
        SeoReport,
    },

    props: [
        'listingRoute',
        'generateRoute',
    ],

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

            Statamic.$request.post(this.generateRoute).then(response => {
                this.currentReportId = response.data;
                this.loading = false;
            });
        }

    }

}
</script>
