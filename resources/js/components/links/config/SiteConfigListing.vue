<template>
    <div>
        <header class="mb-6">
            <breadcrumb
                :url="cp_url('seo-pro/links')"
                :title="__('seo-pro::messages.link_manager')"
            />

            <div class="flex items-center">
                <h1 class="flex-1">{{ __('seo-pro::messages.site_linking_behavior') }}</h1>

                <link-dashboard-actions
                    :can-edit-link-collections="canEditLinkCollections"
                    :can-edit-link-sites="canEditLinkSites"
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
                            <template slot="actions" slot-scope="{ row: site }">
                                <dropdown-list>
                                    <dropdown-item
                                        v-text="__('seo-pro::messages.edit_site_linking_behavior')"
                                        @click="editingSite = site"
                                    />
                                    <div class="divider"></div>
                                    <dropdown-item
                                        :text="__('seo-pro::messages.reset_site_settings')"
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
                :config-site="editingSite"
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
import Listing from '../../../../../vendor/statamic/cms/resources/js/components/Listing.vue';
import ConfigResetter from './ConfigResetter.vue';
import FakesResources from './../FakesResources.vue';
import LinkDashboardActions from './../LinkDashboardActions.vue';

export default {
    mixins: [FakesResources, Listing],

    props: [
        'blueprint',
        'fields',
        'meta',
        'canEditLinkCollections',
        'canEditLinkSites',
    ],

    components: {
        'config-resetter': ConfigResetter,
        'site-config-editor': SiteConfigEditor,
        'link-dashboard-actions': LinkDashboardActions,
    },

    data() {
        return {
            initialing: false,
            editingSite: null,
            requestUrl: cp_url('seo-pro/links/config/sites'),
        };
    },

    methods: {

        handleSaved() {
            this.editingSite = null;
        },

    },

}
</script>