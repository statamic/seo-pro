<template>

    <div>
        <div class="card p-0 overflow-hidden" v-if="reports">
            <table class="data-table">
                <tbody>
                    <tr v-for="report in reports">
                        <td class="text-xs whitespace-no-wrap">
                            <div class="flex items-center">
                                <status-icon :status="report.status" class="mr-3" />
                                {{ report.score }}%
                            </div>
                        </td>
                        <td>
                            <a @click.prevent="$emit('report-selected', report.id)">
                                <relative-date :date="report.date"></relative-date>
                            </a>
                        </td>
                        <td class="float-right" v-if="canDeleteReports">
                            <dropdown-list>
                                <dropdown-item :text="__('seo-pro::messages.delete_report')" @click="$emit('report-deleted', report.id)" />
                            </dropdown-list>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

</template>

<script>
import StatusIcon from './StatusIcon.vue';
import RelativeDate from './RelativeDate.vue';

export default {

    components: {
        StatusIcon,
        RelativeDate,
    },

    props: [
        'route',
        'reports',
        'canDeleteReports',
    ],

    data() {
        return {
            loading: true,
        }
    },

}
</script>
