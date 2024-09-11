<template>
    <div>
        <header class="mb-6">
            <breadcrumb :url="cp_url('seo-pro/links')" :title="__('seo-pro::messages.link_manager')" />

            <div class="flex items-center">
                <div class="flex-1">
                    <h1 class="flex-1">{{ report.entry.title ?? report.entry.uri }}</h1>
                    <a :href="report.entry.uri" class="font-mono text-gray-700" target="_blank">{{ report.entry.uri }}</a>
                </div>

                <dropdown-list class="rtl:ml-2 ltr:mr-2">
                    <dropdown-item v-text="'Edit Entry Linking Settings'" @click="editingEntryConfig = report.entry.id" />
                    <div class="divider"></div>
                    <dropdown-item
                        :text="'Reset Entry Suggestions'"
                        class="warning"
                        @click="$refs[`link_settings_resetter${initialReport.entry.id}`].confirm()"
                    >
                        <config-resetter
                            :ref="`link_settings_resetter${initialReport.entry.id}`"
                            :resource="makeEntrySettingsResource(initialReport.entry)"
                            :reload="true"
                            mode="suggestions"
                        ></config-resetter>
                    </dropdown-item>
                </dropdown-list>

                <a :href="report.entry.edit_url" class="btn-primary cursor-pointer rtl:mr-1 ltr:ml-1">Edit Entry</a>
            </div>
        </header>

        <overview :report="report" />

        <div class="tabs-container">
            <div class="publish-tabs tabs">
                <a v-for="item in navItems" class="tab-button" :href="getUrl(item.url)" :class="{'active': tab === item.url}">{{ item.text }}</a>
            </div>
        </div>
        <div class="card p-0 mt-6" v-if="report">
            <component
                v-bind:is="tab"
                :entry="report.entry.id"
                :edit-url="report.entry.edit_url"
                :site="report.entry.site"
            ></component>
        </div>

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
import Overview from './Overview.vue';
import Suggestions from './SuggestionsListing.vue';
import RelatedContent from './RelatedContent.vue';
import InternalLinkListing from './InternalLinkListing.vue';
import InboundInternalLinks from './InboundInternalLinks.vue'
import ExternalLinkListing from './ExternalLinkListing.vue';
import EntryConfigEditor from './config/EntryConfigEditor.vue';
import ConfigResetter from './config/ConfigResetter.vue';
import FakesResources from './FakesResources.vue';

export default  {
    mixins: [FakesResources],

    props: [
        'initialReport',
        'initialTab',
        'blueprint',
        'fields',
        'meta',
        'values',
    ],

    components: {
        'config-resetter': ConfigResetter,
        'overview': Overview,
        'suggestions': Suggestions,
        'related': RelatedContent,
        'internal': InternalLinkListing,
        'external': ExternalLinkListing,
        'inbound': InboundInternalLinks,
        'entry-config': EntryConfigEditor,
    },

    data() {
        return {
            report: _.clone(this.initialReport),
            tab: this.initialTab,
            navItems: [
                { url: 'suggestions', text: 'Suggestions' },
                { url: 'related', text: 'Related Content' },
                { url: 'internal', text: 'Internal Links' },
                { url: 'external', text: 'External Links' },
                { url: 'inbound', text: 'Inbound Internal Links' },
            ],
            editingEntryConfig: null,
        };
    },

    methods: {

        handleEntryConfigSaved() {
            this.editingEntryConfig = null;
        },

        getUrl(suffix) {
            return cp_url('seo-pro/links/' + this.report.entry.id + '/' + suffix)
        },

    },

};
</script>