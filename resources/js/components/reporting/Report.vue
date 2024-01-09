<template>

    <div>

        <header class="flex items-center mb-6">
            <h1 class="flex-1">{{ __('seo-pro::messages.seo_report') }}</h1>
            <a :href="cp_url('seo-pro/reports/create')" class="btn-primary" v-if="! loading">{{ __('seo-pro::messages.generate_report') }}</a>
        </header>

        <div v-if="report">

            <div v-if="isCachedHeaderReady">

                <div class="flex flex-wrap -mx-4">
                    <div class="w-1/3 px-4">
                        <div class="card py-2">
                            <h2 class="text-sm text-gray-700">{{ __('seo-pro::messages.generated') }}</h2>
                            <div class="text-lg"><relative-date :date="report.date" /></div>
                        </div>
                    </div>
                    <div class="w-1/3 px-4">
                        <div class="card py-2">
                            <h2 class="text-sm text-gray-700">{{ __('Pages Crawled') }}</h2>
                            <div class="text-lg">{{ report.pages_crawled }}</div>
                        </div>
                    </div>
                    <div class="w-1/3 px-4">
                        <div class="card py-2">
                            <h2 class="text-sm text-gray-700">{{ __('Site Score') }}</h2>
                            <div class="text-lg flex items-center">
                                <div class="bg-gray-200 h-3 w-full rounded flex mr-2 ">
                                    <div class="h-3 rounded-l" :style="`width: ${report.score}%`" :class="{ 'bg-red-500': report.score < 70, 'bg-yellow-dark': report.score < 90, 'bg-green-500': report.score >= 90 }" />
                                </div>
                                <span>{{ report.score }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <h3 class="little-heading pl-0 mt-4">{{ __('Summary') }}</h3>
                <div class="card p-0 mt-2">
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

            </div>

            <div v-if="loading && isGenerating" class="card loading">
                <loading-graphic :text="__('seo-pro::messages.report_is_being_generated')" />
            </div>

            <div v-else>
                <h3 class="little-heading pl-0 mt-4 mb-2">{{ __('Pages') }}</h3>
                <div v-if="loading" class="card loading">
                    <loading-graphic />
                </div>
                <data-list v-else ref="dataList" :columns="report.columns" :rows="report.pages">
                    <div class="card overflow-hidden p-0" slot-scope="{ filteredRows: rows }">
                        <data-list-table :rows="report.pages">
                            <template slot="cell-page" slot-scope="{ row: page }">
                                <status-icon :status="page.status" class="mr-4 inline-block"/>
                                <a :href="page.url" target="_blank" v-text="page.url" />
                                <report-details v-if="selected === page.id" :item="page" @closed="selected = null" />
                            </template>
                            <template slot="cell-actionable" slot-scope="{ row: page }">
                                <a @click.prevent="selected = page.id">
                                    <span
                                        v-for="pill in actionablePageResults(page)"
                                        :key="page.id+'_actionable_pill_'+pill"
                                        class="inline-block text-xs bg-gray-300 hover:bg-gray-800 text-gray-800 shrink rounded-full px-2 py-0.5 text-center justify-center mr-2"
                                        style="padding-top: 2px; padding-bottom: 2px;"
                                    >{{ pill }}</span>
                                </a>
                            </template>
                            <td slot="actions" slot-scope="{ row: page }" class="text-right text-xs pr-0 whitespace-no-wrap">
                                <a v-if="page.edit_url" :href="page.edit_url" target="_blank" class="font-normal text-gray-700 hover:text-gray-800 mr-4" v-text="__('Edit')" />
                            </td>
                        </data-list-table>
                    </div>
                </data-list>
            </div>

        </div>

    </div>

</template>

<script>
import ReportDetails from './Details.vue';
import RelativeDate from './RelativeDate.vue';
import StatusIcon from './StatusIcon.vue';

export default {

    components: {
        ReportDetails,
        RelativeDate,
        StatusIcon,
    },

    props: ['initialReport'],

    data() {
        return {
            loading: false,
            report: this.initialReport,
            selected: null
        }
    },

    computed: {

        isGenerating() {
            return this.initialReport.status === 'pending'
                || this.initialReport.status === 'generating';
        },

        id() {
            return this.report.id;
        },

        isCachedHeaderReady() {
            return this.report.date
                && this.report.pages_crawled
                && this.report.score;
        }

    },

    mounted() {
        this.load();
    },

    methods: {

        load() {
            this.loading = true;

            Statamic.$request.get(cp_url(`seo-pro/reports/${this.id}`)).then(response => {
                if (response.data.status === 'pending' || response.data.status === 'generating') {
                    setTimeout(() => this.load(), 1000);
                    return;
                }

                this.report = response.data;
                this.loading = false;
            });
        },

        actionablePageResults(page) {
            return _.chain(page.results)
                .reject(result => result.status === 'pass')
                .map(result => result.actionable_pill)
                .unique()
                .value();
        },

    }

}
</script>
