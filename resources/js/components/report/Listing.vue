<template>

    <div>

        <div v-if="loading" class="card loading">
            <span class="icon icon-circular-graph animation-spin"></span>
            {{ translate('cp.loading') }}
        </div>

        <div class="card" v-if="reports.length == 0">
            <div class="no-results">
                <span class="icon icon-documents"></span>
                <h2>{{ translate('addons.SeoPro::messages.seo_reports') }}</h2>
                <h3>{{ translate('addons.SeoPro::messages.report_no_results_text') }}</h3>
                <button class="btn btn-default btn-lg" @click.prevent="$parent.generateReport" v-text="translate('addons.SeoPro::messages.generate_your_first_report')"</button>
            </div>
        </div>

        <div class="card flush dossier">
            <div class="dossier-table-wrapper">
                <table class="dossier">
                    <tbody>
                            <tr v-for="report in reports">
                                <td class="w-1 text-center">
                                    <status-icon :status="report.status"></status-icon>
                                </td>
                                <td class="w-2 text-xs"
                                    :class="{
                                        'text-red': report.score < 70,
                                        'text-yellow-dark': report.score > 70 && report.score < 90,
                                        'text-green': report.score >= 90 }">
                                    {{ report.score }}%
                                </td>
                                <td>
                                    <a @click.prevent="$emit('report-selected', report.id)">
                                        <relative-date :date="report.date"></relative-date>
                                    </a>
                                </td>
                            </tr>

                    </tbody>
                </table>
            </div>
        </div>

    </div>

</template>

<script>
export default {

    components: {
        StatusIcon: require('./StatusIcon.vue'),
        RelativeDate: require('./RelativeDate.vue')
    },

    data() {
        return {
            loading: true,
            reports: null
        }
    },

    ready() {

        this.$http.get(cp_url('addons/seo-pro/reports')).then(response => {
            this.reports = response.data;
            this.loading = false;
        });

    }

}
</script>
