<template>
    <div>
        <header class="mb-6">
            <div class="flex items-center">
                <h1 class="flex-1">{{ __('seo-pro::messages.link_manager') }}</h1>

                <link-dashboard-actions
                    :can-edit-link-collections="canEditLinkCollections"
                    :can-edit-link-sites="canEditLinkSites"
                    :can-edit-global-links="canEditGlobalLinks"
                />
            </div>
        </header>

        <div v-if="initializing" class="card loading">
            <loading-graphic />
        </div>

        <data-list
            v-if="!initializing"
            ref="datalist"
            :rows="items"
            :columns="columns"
            :sort="false"
            :sort-column="sortColumn"
            :sort-direction="sortDirection"
        >
            <div>
                <div class="card overflow-hidden p-0 relative">
                    <div class="flex flex-wrap items-center justify-between px-2 pb-2 text-sm border-b dark:border-dark-900">
                        <data-list-filter-presets
                            ref="presets"
                            :active-preset="activePreset"
                            :active-preset-payload="activePresetPayload"
                            :active-filters="activeFilters"
                            :has-active-filters="hasActiveFilters"
                            :preferences-prefix="preferencesPrefix"
                            :search-query="searchQuery"
                            @selected="selectPreset"
                            @reset="filtersReset"
                        />

                        <data-list-search class="h-8 mt-2 min-w-[240px] w-full" ref="search" v-model="searchQuery" :placeholder="searchPlaceholder" />
                    </div>
                    <data-list-filters
                        ref="filters"
                        :filters="filters"
                        :active-preset="activePreset"
                        :active-preset-payload="activePresetPayload"
                        :active-filters="activeFilters"
                        :active-filter-badges="activeFilterBadges"
                        :active-count="activeFilterCount"
                        :search-query="searchQuery"
                        :is-searching="true"
                        :saves-presets="true"
                        :preferences-prefix="preferencesPrefix"
                        @changed="filterChanged"
                        @saved="$refs.presets.setPreset($event)"
                        @deleted="$refs.presets.refreshPresets()"
                    />
                    <div v-show="items.length === 0" class="p-6 text-center text-gray-500" v-text="__('No results')" />

                    <data-list-table
                        v-show="items.length"
                        :allow-bulk-action="false"
                        :loading="loading"
                        :reorderable="false"
                        :sortable="true"
                        @sorted="sorted"
                    >
                        <template slot="cell-cached_title" slot-scope="{ row: entry }">
                            <a class="title-index-field inline-flex items-center" :href="makeSuggestionsUrl(entry.entry_id)" @click.stop>
                                <span v-text="entry.cached_title" />
                            </a>
                        </template>
                        <template slot="cell-cached_uri" slot-scope="{ row: entry }">
                            <a class="title-index-field inline-flex items-center" :href="makeSuggestionsUrl(entry.entry_id)" @click.stop>
                                <span v-text="entry.cached_uri" />
                            </a>
                        </template>
                    </data-list-table>
                </div>
                <data-list-pagination
                    class="mt-6"
                    :resource-meta="meta"
                    :per-page="perPage"
                    :show-totals="true"
                    @page-selected="selectPage"
                    @per-page-changed="changePerPage"
                />
            </div>
        </data-list>
    </div>
</template>

<script>
import LinkDashboardActions from './LinkDashboardActions.vue';
import Listing from '../../../../vendor/statamic/cms/resources/js/components/Listing.vue';
import FakesResources from './FakesResources.vue';
import ProvidesControlPanelLinks from './ProvidesControlPanelLinks.vue';

export default  {
    mixins: [Listing, FakesResources, ProvidesControlPanelLinks],

    props: [
        'site',
        'blueprint',
        'fields',
        'meta',
        'values',
        'canEditLinkCollections',
        'canEditLinkSites',
        'canEditGlobalLinks',
    ],

    components: {
        'link-dashboard-actions': LinkDashboardActions,
    },

    data() {
        return {
            listingKey: 'seoprolinks',
            preferencesPrefix: 'seopro.links',
            requestUrl: cp_url('seo-pro/links/filter'),
            currentSite: this.site,
            initialSite: this.site,
            pushQuery: true,
        }
    },

}
</script>