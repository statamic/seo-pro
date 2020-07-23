<template>

    <div>

        <div v-if="loading" class="card loading">
            <span class="icon icon-circular-graph animation-spin"></span>
            {{ __('seo-pro::messages.report_is_being_generated')}}
        </div>

        <div v-else-if="!loading && report">

            <div class="card mb-2 text-sm text-grey flex items-center justify-between">
                <div>
                    {{ __('seo-pro::messages.generated') }}:
                    <relative-date :date="report.date"></relative-date>
                    <span class="mx-1">&bull;</span>
                    {{ __('Pages') }}:
                    {{ report.pages.length }}
                </div>
                <div class="text-xl leading-none"
                    :class="{
                        'text-red': report.score < 70,
                        'text-yellow-dark': report.score < 90,
                        'text-green': report.score >= 90 }">
                    {{ report.score }}%
                </div>
            </div>

            <div class="card p-0 mb-2">
                <table class="data-table">
                    <tbody>
                        <tr v-for="item in report.results">
                            <td class="w-8 text-center">
                                <status-icon :status="item.status"></status-icon>
                            </td>
                            <td class="pl-0">{{ item.description }}</td>
                            <td class="text-grey text-right">{{ item.comment }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card p-0">
                <table class="data-table">
                    <tbody>
                        <tr v-for="item in report.pages">
                            <td class="w-8 text-center">
                                <status-icon :status="item.status"></status-icon>
                            </td>
                            <td class="pl-0">
                                <a href="" @click.prevent="selected = item.id">{{ item.url }}</a>
                                <report-details
                                    v-if="selected === item.id"
                                    :item="item"
                                    @closed="selected = null"
                                ></report-details>
                            </td>
                            <td class="text-right text-xs pr-0">
                                <a @click.prevent="selected = item.id" class="text-grey-60 mr-2 hover:text-grey-80" v-text="__('Details')"></a>
                                <a v-if="item.edit_url" target="_blank" :href="item.edit_url" class="mr-2 text-grey-60 hover:text-grey-80" v-text="__('Edit')"></a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>

    </div>

</template>

<script>
import ReportDetails from './Details';
import RelativeDate from './RelativeDate';
import StatusIcon from './StatusIcon';

export default {

    components: {
        ReportDetails,
        RelativeDate,
        StatusIcon,
    },

    props: ['id'],

    data() {
        return {
            loading: false,
            report: null,
            selected: null
        }
    },

    mounted() {
        this.load();
    },

    methods: {

        load() {
            this.loading = true;
            this.report = null;

            Statamic.$request.get(cp_url(`seo-pro/reports/${this.id}`)).then(response => {
                if (response.data.status === 'pending') {
                    setTimeout(() => this.load(), 1000);
                    return;
                }

                this.report = response.data;
                this.loading = false;
            });
        }

    }

}
</script>
