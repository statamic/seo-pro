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
                                <div class="text-lg">{{ report.pages_actionable || 'N/A' }}</div>
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
                        <div class="bg-gray-300 dark:bg-dark-650 h-4 w-full rounded mr-2 mt-4">
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
                <data-list
                    v-else
                    ref="dataList"
                    :columns="report.columns"
                    :rows="sortablePages"
                    :sort="false"
                    :sort-column="sortColumn"
                    :sort-direction="sortDirection"
                >
                    <div slot-scope="{ filteredRows: rows }">
                        <div class="card overflow-hidden p-0" >
                            <data-list-table :rows="sortablePages" @sorted="sorted">
                                <template slot="cell-status" slot-scope="{ row: page }">
                                    <status-icon :status="page.status" class="inline-block w-5" />
                                    {{ __('seo-pro::messages.rules.'+page.status) }}
                                </template>
                                <template slot="cell-url" slot-scope="{ row: page }">
                                    <a @click.prevent="selectedId = page.id" class="hover:text-black dark:hover:text-dark-100" v-text="page.url" />
                                </template>
                                <template slot="cell-actionable" slot-scope="{ row: page }">
                                    <page-details v-if="selectedId === page.id" :item="page" @closed="selectedId = null" />
                                    <a @click.prevent="selectedId = page.id" class="flex" style="gap: 0.25rem;">
                                        <span
                                            v-for="pill in actionablePageResults(page)"
                                            :key="page.id+'_actionable_pill_'+pill"
                                            class="inline-block text-xs bg-gray-300 dark:bg-dark-200 hover:bg-gray-800 text-gray-800 dark:text-dark-100 shrink rounded-full px-2 py-0.5 text-center justify-center"
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

                        <data-list-pagination
                            class="mt-6"
                            :resource-meta="paginationMeta"
                            :per-page="perPage"
                            :scroll-to-top="false"
                            :show-totals="true"
                            @per-page-changed="selectPaginationPerPage"
                            @page-selected="selectPaginationPage"
                        />
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

    props: [
        'initialReport',
        'initialPage',
        'initialPerPage',
    ],

    data() {
        return {
            loading: true,
            report: this.initialReport,
            selectedId: null,
            sortColumn: 'status',
            sortDirection: 'asc',
            page: this.initialPage,
            perPage: this.initialPerPage,
            paginationMeta: {},
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
            if (this.loading) {
                return [];
            }

            return this.report.pages.data;
        },

        parameters() {
            return {
                sortColumn: this.sortColumn,
                sortDirection: this.sortDirection,
                page: this.page,
                perPage: this.perPage,
            };
        },

    },

    watch: {
        parameters: {
            deep: true,
            handler(after, before) {
                if (JSON.stringify(before) === JSON.stringify(after)) return;

                this.load();
                this.pushState();
            },
        },
    },

    mounted() {
        window.history.replaceState({ parameters: this.parameters }, '');
        window.addEventListener('popstate', this.popState);
    },

    beforeDestroy() {
        window.removeEventListener('popstate', this.popState);
    },

    created() {
        this.load();
    },

    methods: {

        load() {
            this.$axios.get(cp_url(`seo-pro/reports/${this.id}/pages`), { params: this.parameters }).then(response => {
                if (response.data.status === 'pending' || response.data.status === 'generating') {
                    setTimeout(() => this.load(), 1000);
                    return;
                }

                this.report = response.data;

                this.sortColumn = response.data.sortColumn;
                this.sortDirection = response.data.sortDirection;
                this.page = response.data.pages.current_page;
                this.perPage = response.data.pages.per_page;
                this.paginationMeta = response.data.pages;

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

        selectPaginationPage(page) {
            this.page = parseInt(page);
        },

        selectPaginationPerPage(perPage) {
            this.perPage = parseInt(perPage);
        },

        sorted(column, direction) {
            this.sortColumn = column;
            this.sortDirection = direction;
        },

        popState(event) {
            if (! event.state) {
                return;
            }
            this.popping = true;
            this.page = event.state.parameters.page;
            this.perPage = event.state.parameters.perPage;
            this.$nextTick(() => {
                this.popping = false;
            });
        },

        pushState() {
            if (this.popping) {
                return;
            }
            const parameters = this.parameters;
            const searchParams = new URLSearchParams(parameters);
            window.history.pushState({ parameters }, '', '?' + searchParams.toString());
        },

    }

}
</script>
