<template>

    <div>

        <header class="flex items-center mb-6">
            <h1 class="flex-1">{{ __('seo-pro::messages.seo_report') }}</h1>
            <a :href="cp_url('seo-pro/reports/create')" class="btn-primary" v-if="! loading">{{ __('seo-pro::messages.generate_report') }}</a>
        </header>

        <div v-if="report">

            <div v-if="isCachedHeaderReady">
                <h3 class="little-heading rtl:pr-0 ltr:pl-0 mt-4 mb-2">{{ __('Summary') }}</h3>
                <div class="card p-0 mt-2">
                    <div class="p-4 border-b dark:border-dark-900">
                        <div class="w-full flex">
                            <div>
                                <h2 class="text-sm text-gray-700">{{ __('seo-pro::messages.generated') }}</h2>
                                <div class="text-lg"><relative-date :date="report.date" /></div>
                            </div>
                            <div class="grow text-right mr-8">
                                <h2 class="text-sm text-gray-700">{{ __('Actionable Pages') }}</h2>
                                <div class="text-lg">10</div>
                            </div>
                            <div class="text-right mr-8">
                                <h2 class="text-sm text-gray-700">{{ __('Total Pages Crawled') }}</h2>
                                <div class="text-lg">{{ report.pages_crawled }}</div>
                            </div>
                            <div class="text-right">
                                <h2 class="text-sm text-gray-700">{{ __('Site Score') }}</h2>
                                <div class="text-lg" :class="{ 'text-red-500': report.score < 70, 'text-orange': report.score < 90, 'text-green-600': report.score >= 90 }">{{ report.score }}%</div>
                            </div>
                        </div>
                        <div class="bg-gray-300 h-4 w-full rounded mr-2 mt-4">
                            <div class="h-4 rounded-l" :style="`width: ${report.score}%`" :class="{ 'bg-red-500': report.score < 70, 'bg-orange': report.score < 90, 'bg-green-600': report.score >= 90 }" />
                        </div>
                    </div>
                    <table class="data-table">
                        <tbody>
                            <tr v-for="item in report.results">
                                <td class="w-8 text-center">
                                    <status-icon :status="item.status"></status-icon>
                                </td>
                                <td class="p-0">{{ item.description }}</td>
                                <td class="text-right">{{ item.comment }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <div v-if="loading && isGenerating" class="card loading">
                <loading-graphic :text="__('seo-pro::messages.report_is_being_generated')" />
            </div>

            <div v-else>
                <h3 class="little-heading rtl:pr-0 ltr:pl-0 mt-4 mb-2">{{ __('seo-pro::messages.page_details') }}</h3>
                <div v-if="loading" class="card loading">
                    <loading-graphic />
                </div>
                <data-list v-else ref="dataList" :columns="report.columns" :rows="report.pages">
                    <div class="card overflow-hidden p-0" slot-scope="{ filteredRows: rows }">
                        <data-list-table :rows="sortablePages">
                            <template slot="cell-status" slot-scope="{ row: page }">
                                <status-icon :status="page.status_raw" class="inline-block w-5" />
                                {{ __('seo-pro::messages.rules.'+page.status_raw) }}
                            </template>
                            <template slot="cell-page" slot-scope="{ row: page }">
                                <a @click.prevent="selected = page.id" class="hover:text-black" v-text="page.url" />
                            </template>
                            <template slot="cell-actionable" slot-scope="{ row: page }">
                                <page-details v-if="selected === page.id" :item="page" @closed="selected = null" />
                                <a @click.prevent="selected = page.id" class="flex" style="gap: 0.25rem;">
                                    <span
                                        v-for="pill in actionablePageResults(page)"
                                        :key="page.id+'_actionable_pill_'+pill"
                                        class="inline-block text-xs bg-gray-300 hover:bg-gray-800 text-gray-800 shrink rounded-full px-2 py-0.5 text-center justify-center"
                                        style="padding-top: 2px; padding-bottom: 2px;"
                                    >{{ pill }}</span>
                                </a>
                            </template>
                            <td slot="actions" slot-scope="{ row: page }" class="text-right text-xs p-0 whitespace-no-wrap">
                                <a v-if="page.url" :href="page.url" target="_blank" class="font-normal text-gray-700 hover:text-blue" v-text="__('Visit')" />
                                <a v-if="page.edit_url" :href="page.edit_url" target="_blank" class="font-normal text-gray-700 hover:text-blue ml-4" v-text="__('Edit')" />
                            </td>
                        </data-list-table>
                    </div>
                </data-list>
            </div>

        </div>

    </div>

</template>

<script>
import PageDetails from './PageDetails.vue';
import RelativeDate from './RelativeDate.vue';
import StatusIcon from './StatusIcon.vue';

export default {

    components: {
        PageDetails,
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
        },

        sortablePages() {
            return this.report.pages.map(page => {
                page.status_raw = page.status;

                if (page.status === 'fail') page.status = '1fail';
                if (page.status === 'warning') page.status = '2warning';
                if (page.status === 'pass') page.status = '3pass';

                return page;
            });
        },

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
