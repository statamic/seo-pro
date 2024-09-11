<template>
    <div>

        <header class="mb-6">
            <breadcrumb :url="cp_url('seo-pro/links')" :title="__('seo-pro::messages.link_manager')" />

            <div class="flex items-center">
                <h1 class="flex-1">Site Linking Configuration</h1>
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
                :rows="sites"
            >
                <div>
                    <div class="card overflow-hidden p-0 relative">

                        <div v-show="sites.length == 0" class="p-6 text-center text-gray-500" v-text="__('No results')" />

                        <data-list-table
                            v-show="sites.length"
                            :allow-bulk-actions="false"
                            :loading="initialing"
                            :reorderable="false"
                            :sortable="false"
                        >
                            <template slot="cell-name" slot-scope="{ row: site }">
                                <a class="title-index-field inline-flex items-center cursor-pointer" @click="editingSite = site">
                                    <span>{{ site.name }}</span>
                                </a>
                            </template>
                            <template slot="actions" slot-scope="{ row: site }">
                                <dropdown-list>
                                    <dropdown-item
                                        :text="'Reset Site Settings'"
                                        class="warning"
                                        @click="$refs[`site_settings_resetter${site.handle}`].confirm()"
                                    >
                                        <config-resetter
                                            :ref="`site_settings_resetter${site.handle}`"
                                            :resource="makeSiteSettingsResource(site)"
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
            v-if="editingSite != null"
            name="seopro__site-config-editor"
            @closed="editingSite = null"
        >
            <site-config-editor
                @closed="editingSite = null"
                @saved="handleSaved"
                :site="editingSite"
                :blueprint="blueprint"
                :fields="fields"
                :meta="meta"
                :values="values"
            ></site-config-editor>
        </stack>
    </div>
</template>

<script>
import SiteConfigEditor from './SiteConfigEditor.vue';
import ConfigResetter from './ConfigResetter.vue';
import FakesResources from '../FakesResources.vue';

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
        'site-config-editor': SiteConfigEditor,
    },

    data() {
        return {
            initialing: false,
            columns: [
                { label: 'Site', field: 'name' },
            ],
            sites: [],
            editingSite: null,
        };
    },

    methods: {

        handleSaved() {
            this.editingSite = null;
            this.loadSites();
        },

        loadSites() {
            this.initialing = true;
            this.$axios.get(cp_url('seo-pro/links/config/sites')).then(response => {
                this.sites = response.data;
                this.initialing = false;
            }).catch(err => {
                this.initialing = false;
            });
        }

    },

    created() {
        this.loadSites();
    }
};
</script>