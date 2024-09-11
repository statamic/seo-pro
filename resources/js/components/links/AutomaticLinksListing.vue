<template>
    <div>
        <header class="mb-6">
            <breadcrumb :url="cp_url('seo-pro/links')" title="Links" />

            <div class="flex items-center">
                <h1 class="flex-1">Automatic Links</h1>

                <dropdown-list>
                    <dropdown-item v-text="'Collection Linking Behavior'" :redirect="cp_url('seo-pro/links/config/collections')" />
                    <dropdown-item v-text="'Global Automatic Links'" :redirect="cp_url('seo-pro/links/automatic')" />
                    <dropdown-item v-text="'Site Link Settings'" :redirect="cp_url('seo-pro/links/config/sites')" />
                </dropdown-list>

                <a @click="createLink" class="btn-primary cursor-pointer rtl:mr-1 ltr:ml-1">Create Link</a>
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
            @visible-columns-updated="visibleColumns = $event"
        >
            <div>
                <div class="card overflow-hidden p-0 relative">
                    <div v-show="items.length === 0" class="p-6 text-center text-gray-500" v-text="__('No results')" />

                    <data-list-table
                        v-show="items.length"
                        :allow-bulk-actions="false"
                        :loading="loading"
                        :reorderable="false"
                        :sortable="true"
                        @sorted="sorted"
                    >
                        <template slot="cell-link_text" slot-scope="{ row: link }">
                            <a class="title-index-field inline-flex items-center cursor-pointer" @click="editLink(link)">
                                <span v-text="link.link_text" />
                            </a>
                        </template>
                        <template slot="actions" slot-scope="{ row: link, index }">
                            <dropdown-list>
                                <dropdown-item :text="__('Edit')" @click="editLink(link)" />
                                <div class="divider"></div>
                                <dropdown-item
                                    :text="__('Delete')"
                                    class="warning"
                                    @click="$refs[`link_deleter_${link.id}`].confirm()"
                                >
                                    <resource-deleter
                                        :ref="`link_deleter_${link.id}`"
                                        :resource="makeAutomaticLinkResource(link)"
                                        @deleted="handleLinkDeleted"
                                    ></resource-deleter>
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
            v-if="managingLink"
            name="seopro__automatic-link-editor"
            @closed="closeLinkEditor"
            narrow
        >
            <automatic-link-editor
                @closed="closeLinkEditor"
                :mode="dataMode"
                :site="site"
                :blueprint="blueprint"
                :fields="fields"
                :meta="meta"
                :values="values"
                :link="activeLink"
            ></automatic-link-editor>
        </stack>
    </div>
</template>

<script>
import Listing from '../../../../vendor/statamic/cms/resources/js/components/Listing.vue';
import AutomaticLinkEditor from './AutomaticLinkEditor.vue';
import FakesResources from './FakesResources.vue';

export default  {
    mixins: [Listing, FakesResources],

    props: [
        'blueprint',
        'fields',
        'meta',
        'values',
        'site',
    ],

    components: {
        'automatic-link-editor': AutomaticLinkEditor,
    },

    data() {
        return {
            listingKey: 'seoproautomaticlinks',
            preferencesPrefix: 'seopro.automaticlinks',
            requestUrl: cp_url('seo-pro/links/automatic/filter'),
            initializing: false,
            currentSite: this.site,
            initialSite: this.site,
            pushQuery: false,
            managingLink: false,
            dataMode: 'new',
            activeLink: null,
        };
    },

    methods: {

        handleLinkDeleted() {
            this.request();
        },

        editLink(link) {
            this.activeLink = link;
            this.dataMode = 'edit';
            this.managingLink = true;
        },

        closeLinkEditor() {
            this.managingLink = false;
            this.activeLink = null;
            this.request();
        },

        createLink() {
            this.activeLink = null;
            this.dataMode = 'new';
            this.managingLink = true;
        }

    },

    created() {
    },

    mounted() {

    },

};
</script>