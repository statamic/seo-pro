<template>
    <div>
        <header class="mb-6">
            <div class="flex items-center">
                <h1 class="flex-1">{{ __('seo-pro::messages.link_manager') }}</h1>

                <dropdown-list>
                    <dropdown-item v-text="'Collection Linking Behavior'" :redirect="cp_url('seo-pro/links/config/collections')" />
                    <dropdown-item v-text="'Global Automatic Links'" :redirect="cp_url('seo-pro/links/automatic')" />
                    <dropdown-item v-text="'Site Link Settings'" :redirect="cp_url('seo-pro/links/config/sites')" />
                </dropdown-list>
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
                        <template slot="cell-title" slot-scope="{ row: entry }">
                            <a class="title-index-field inline-flex items-center" :href="makeSuggestionsUrl(entry.entry_id)" @click.stop>
                                <span v-text="entry.title" />
                            </a>
                        </template>
                        <template slot="cell-uri" slot-scope="{ row: entry }">
                            <a class="title-index-field inline-flex items-center" :href="makeSuggestionsUrl(entry.entry_id)" @click.stop>
                                <span v-text="entry.uri" />
                            </a>
                        </template>
                        <template slot="actions" slot-scope="{ row: entry }">
                            <dropdown-list>
                                <dropdown-item v-text="'Edit Entry Linking Settings'" @click="editingEntryConfig = entry.entry_id" />
                                <div class="divider"></div>
                                <dropdown-item
                                    :text="'Reset Entry Suggestions'"
                                    class="warning"
                                    @click="$refs[`link_settings_resetter${entry.entry_id}`].confirm()"
                                >
                                    <config-resetter
                                        :ref="`link_settings_resetter${entry.entry_id}`"
                                        :resource="makeEntrySettingsResource(entry)"
                                        :reload="true"
                                        mode="suggestions"
                                    ></config-resetter>
                                </dropdown-item>
                            </dropdown-list>
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

        <stack
            v-if="editingEntryConfig != null"
            name="seopro__entry-config-editor"
            @closed="editingEntryConfig = null"
            narrow
        >
            <entry-config
                @closed="editingEntryConfig = null"
                @saved="handleEntryConfigSaved"
                :blueprint="blueprint"
                :fields="fields"
                :meta="meta"
                :values="values"
                :entry="editingEntryConfig"
            ></entry-config>
        </stack>
    </div>
</template>

<script>
import Listing from '../../../../vendor/statamic/cms/resources/js/components/Listing.vue';
import EntryConfigEditor from './config/EntryConfigEditor.vue';
import FakesResources from './FakesResources.vue';
import ConfigResetter from './config/ConfigResetter.vue';
import ProvidesControlPanelLinks from './ProvidesControlPanelLinks.vue';

export default  {
    mixins: [Listing, FakesResources, ProvidesControlPanelLinks],

    props: [
        'site',
        'blueprint',
        'fields',
        'meta',
        'values',
    ],

    components: {
        'entry-config': EntryConfigEditor,
        'config-resetter': ConfigResetter,
    },

    data() {
        return {
            listingKey: 'seoprolinks',
            preferencesPrefix: 'seopro.links',
            requestUrl: cp_url('seo-pro/links/filter'),
            currentSite: this.site,
            initialSite: this.site,
            pushQuery: true,
            editingEntryConfig: null,
        }
    },

    methods: {

        handleEntryConfigSaved() {
            this.editingEntryConfig = null;
        },

    },
}
</script>