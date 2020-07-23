<template>

    <div>

        <div v-if="loading" class="card loading">
            <span class="icon icon-circular-graph animation-spin"></span>
            {{ __('Loading') }}
        </div>

        <div v-else-if="reports.length == 0" class="card">
            <p class="p-1 italic text-grey-60">{{ __('seo-pro::messages.report_no_results_text') }}</p>
        </div>

        <div v-else class="card p-0">
            <table class="data-table">
                <tbody>
                    <tr v-for="report in reports">
                        <td class="w-1 text-center">
                            <status-icon :status="report.status"></status-icon>
                        </td>
                        <td class="text-xs w-16"
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

</template>

<script>
import StatusIcon from './StatusIcon';
import RelativeDate from './RelativeDate';

export default {

    components: {
        StatusIcon,
        RelativeDate,
    },

    props: [
        'route',
    ],

    data() {
        return {
            loading: true,
            reports: [],
        }
    },

    mounted() {

        Statamic.$request.get(this.route).then(response => {
            this.reports = response.data;
            this.loading = false;
        });

    }

}
</script>
