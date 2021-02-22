<template>

    <div>
        <div class="card p-0" v-if="reports">
            <table class="data-table">
                <tbody>
                    <tr v-for="report in reports">
                        <td class="w-1 text-center">
                            <status-icon :status="report.status"></status-icon>
                        </td>
                        <td class="text-xs w-16 whitespace-no-wrap"
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
                        <td class="float-right">
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
import StatusIcon from './StatusIcon';
import RelativeDate from './RelativeDate';

export default {

    components: {
        StatusIcon,
        RelativeDate,
    },

    props: [
        'route',
        'reports'
    ],

    data() {
        return {
            loading: true,
        }
    },

}
</script>
