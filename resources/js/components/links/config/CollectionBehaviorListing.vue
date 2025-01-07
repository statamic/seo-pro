<template>
    <div>
        <header class="mb-6">
            <breadcrumb
                :url="cp_url('seo-pro/links')"
                :title="__('seo-pro::messages.link_manager')"
            />

            <div class="flex items-center">
                <h1 class="flex-1">{{ __('seo-pro::messages.collection_linking_behavior') }}</h1>

                <link-dashboard-actions
                    :can-edit-link-collections="canEditLinkCollections"
                    :can-edit-link-sites="canEditLinkSites"
                    :can-edit-global-links="canEditGlobalLinks"
                />
            </div>
        </header>

        <div>
            <div v-if="initialing" class="card loading">
                <loading-graphic />
            </div>

            <data-list
                v-if="!initialing"
                ref="datalist"
                :rows="items"
                :columns="columns"
                :sort="false"
                :sort-column="sortColumn"
                :sort-direction="sortDirection"
            >
                <div>
                    <div class="card overflow-hidden p-0 relative">
                        <div v-show="items.length == 0" class="p-6 text-center text-gray-500" v-text="__('No results')" />

                        <data-list-table
                            v-show="items.length"
                            :allow-bulk-actions="false"
                            :loading="initialing"
                            :reorderable="false"
                            :sortable="false"
                        >
                            <template slot="cell-title" slot-scope="{ row: collection }">
                                <a class="title-index-field inline-flex items-center cursor-pointer" @click="editingCollection = collection">
                                    <span>{{ collection.title }}</span>
                                </a>
                            </template>
                            <template slot="actions" slot-scope="{ row: collection }">
                                <dropdown-list>
                                    <dropdown-item
                                        v-text="__('seo-pro::messages.edit_collection_linking_behavior')"
                                        @click="editingCollection = collection"
                                    />
                                    <div class="divider"></div>
                                    <dropdown-item
                                        :text="__('seo-pro::messages.reset_collection_settings')"
                                        class="warning"
                                        @click="$refs[`collection_settings_resetter${collection.handle}`].confirm()"
                                    >
                                        <config-resetter
                                            :ref="`collection_settings_resetter${collection.handle}`"
                                            :resource="makeCollectionSettingsResource(collection)"
                                            :reload="true"
                                        ></config-resetter>
                                    </dropdown-item>
                                </dropdown-list>
                            </template>
                        </data-list-table>
                    </div>
                </div>
            </data-list>
        </div>

        <stack
            v-if="editingCollection != null"
            name="seopro__collection-behavior-editor"
            @closed="editingCollection = null"
            narrow
        >
            <behavior-editor
                @closed="editingCollection = null"
                @saved="handleSaved"
                :collection="editingCollection"
                :blueprint="blueprint"
                :fields="fields"
                :meta="meta"
            ></behavior-editor>
        </stack>
    </div>
</template>

<script>
import CollectionBehaviorEditor from './CollectionBehaviorEditor.vue';
import Listing from '../../../../../vendor/statamic/cms/resources/js/components/Listing.vue';
import FakesResources from '../FakesResources.vue';
import ConfigResetter from './ConfigResetter.vue';
import LinkDashboardActions from './../LinkDashboardActions.vue';

export default {
    mixins: [FakesResources, Listing],

    props: [
        'blueprint',
        'fields',
        'meta',
        'canEditLinkCollections',
        'canEditLinkSites',
        'canEditGlobalLinks',
    ],

    components: {
        'config-resetter': ConfigResetter,
        'behavior-editor': CollectionBehaviorEditor,
        'link-dashboard-actions': LinkDashboardActions,
    },

    data() {
        return {
            initialing: false,
            collections: [],
            editingCollection: null,
            requestUrl: cp_url('seo-pro/links/config/collections'),
        };
    },

    methods: {

        handleSaved() {
            this.editingCollection = null;
            this.request();
        },

    },

}
</script>