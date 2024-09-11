<template>
    <div>

        <header class="mb-6">
            <breadcrumb :url="cp_url('seo-pro/links')" :title="__('seo-pro::messages.link_manager')" />

            <div class="flex items-center">
                <h1 class="flex-1">Collection Linking Behavior</h1>
            </div>
        </header>

        <div>
            <div v-if="initialing" class="card loading">
                <loading-graphic />
            </div>

            <data-list
                v-if="!initialing"
                ref="data-list"
                :columns="columns"
                :rows="collections"
            >
                <div>
                    <div class="card overflow-hidden p-0 relative">
                        <div v-show="collections.length == 0" class="p-6 text-center text-gray-500" v-text="__('No results')" />

                        <data-list-table
                            v-show="collections.length"
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
                                        :text="'Reset Collection Settings'"
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
                :values="values"
            ></behavior-editor>
        </stack>
    </div>
</template>

<script>
import CollectionBehaviorEditor from './CollectionBehaviorEditor.vue';
import FakesResources from '../FakesResources.vue';
import ConfigResetter from './ConfigResetter.vue';

export default {
    mixins: [FakesResources],

    props: [
        'blueprint',
        'fields',
        'meta',
        'values',
    ],

    components: {
        'config-resetter': ConfigResetter,
        'behavior-editor': CollectionBehaviorEditor,
    },

    data() {
        return {
            initialing: false,
            columns: [
                { label: 'Title', field: 'title' },
                { label: 'Cross-Site Linking', field: 'allow_cross_site_linking' },
                { label: 'Allow Suggestions from all Collections', field: 'allow_cross_collection_suggestions' },
                { label: 'Receive Suggestions From', field: 'allowed_collections' },
            ],
            collections: [],
            editingCollection: null,
        };
    },

    methods: {

        handleSaved() {
            this.editingCollection = null;
            this.loadCollections();
        },

        loadCollections() {
            this.initialing = true;
            this.$axios.get(cp_url('seo-pro/links/config/collections')).then(response => {
                this.collections = response.data;
                this.initialing = false;
            }).catch(err => {
                this.initialing = false;
            });
        }

    },

    created() {
        this.loadCollections();
    }
};
</script>