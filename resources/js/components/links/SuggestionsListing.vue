<template>
    <div>
        <div v-if="loading" class="card loading">
            <loading-graphic />
        </div>

        <data-list
            v-if="!loading"
            ref="data-list"
            :columns="columns"
            :rows="suggestions"
            :sort="true"
            sort-column="score"
            sort-direction="desc"
        >
            <div>
                <div class="card overflow-hidden p-0 relative">
                    <div v-show="suggestions.length == 0" class="p-6 text-center text-gray-500" v-text="__('No results')" />

                    <data-list-table
                        v-show="suggestions.length"
                        :allow-bulk-actions="false"
                        :loading="loading"
                        :reorderable="false"
                        :sortable="true"
                    >
                        <template slot="cell-phrase" slot-scope="{ row: suggestion }">
                            <div v-if="suggestion.context.can_replace" class="cursor-pointer" @click="activeSuggestion = suggestion">
                                <p><span v-html="getItemPreviewText(suggestion)"></span></p>
                            </div>
                            <div v-if="!suggestion.context.can_replace">
                                <span>{{ suggestion.phrase }}</span>
                            </div>
                        </template>
                        <template slot="cell-context.can_replace" slot-scope="{ row: suggestion }">
                            <toggle-index :value="suggestion.context.can_replace"></toggle-index>
                        </template>
                        <template slot="cell-context.field_handle" slot-scope="{ row: suggestion }">
                            <span>{{ suggestion.context.field_handle ?? '' }}</span>
                        </template>
                        <template slot="cell-score" slot-scope="{ row: suggestion }">
                            <span>{{ suggestion.score.toFixed(0) }}</span>
                        </template>
                        <template slot="cell-uri" slot-scope="{ row: suggestion }">
                            <a :href="makeSuggestionsUrl(suggestion.entry)">{{ suggestion.uri }}</a>
                        </template>
                        <template slot="actions" slot-scope="{ row: suggestion }">
                            <dropdown-list
                                v-if="canEditEntry"
                            >
                                <dropdown-item
                                    :text="__('Edit Entry')"
                                    :redirect="editUrl"
                                ></dropdown-item>
                                <dropdown-item
                                    :text="__('seo-pro::messages.accept_suggestion')"
                                    @click="activeSuggestion = suggestion"
                                    v-if="suggestion.context.can_replace"
                                />
                                <div class="divider"></div>
                                <dropdown-item
                                    :text="__('seo-pro::messages.ignore_suggestion')"
                                    @click="ignoringSuggestion = suggestion" class="warning"
                                />
                            </dropdown-list>
                        </template>
                    </data-list-table>
                </div>
            </div>
        </data-list>

        <IgnoreConfirmation
            :suggestion="ignoringSuggestion"
            :entry-id="entry"
            @closed="ignoringSuggestion = null"
            mode="suggestion"
            :site="site"
            @saved="handleSuggestionIgnored"
        ></IgnoreConfirmation>

        <stack
            v-if="activeSuggestion != null"
            name="seopro__adding-link"
            @closed="activeSuggestion = null"
            narrow
        >
            <suggestion-editor
                :suggestion="activeSuggestion"
                :entry-id="entry"
                :edit-url="editUrl"
                @closed="activeSuggestion = null"
                @saved="handleSuggestionSaved"
            ></suggestion-editor>
        </stack>
    </div>
</template>

<script>
import SuggestionEditor from './suggestions/SuggestionEditor.vue';
import IgnoreConfirmation from './suggestions/IgnoreConfirmation.vue';
import ProvidesControlPanelLinks from './ProvidesControlPanelLinks.vue';
import HandlesRequestErrors from './HandlesRequestErrors.vue';
import ToggleIndexFieldType from '../../../../vendor/statamic/cms/resources/js/components/fieldtypes/ToggleIndexFieldtype.vue';

export default {
    mixins: [ProvidesControlPanelLinks, HandlesRequestErrors],

    props: [
        'entry',
        'editUrl',
        'site',
        'canEditEntry',
    ],

    components: {
        SuggestionEditor,
        IgnoreConfirmation,
        'toggle-index': ToggleIndexFieldType,
    },

    data() {
        return {
            loading: false,
            ignoringSuggestion: null,
            activeSuggestion: null,
            columns: [
                {
                    label: __('seo-pro::messages.link_text'),
                    field: 'phrase'
                },
                {
                    label: __('seo-pro::messages.can_auto_apply'),
                    field: 'context.can_replace'
                },
                {
                    label: __('seo-pro::messages.relevancy_score'),
                    field: 'score'
                },
                {
                    label: __('seo-pro::messages.link_target'),
                    field: 'uri'
                }
            ],
            suggestions: [],
        };
    },

    methods: {

        loadData() {
            this.loading = true;

            this.$axios.get(this.makeSuggestionsUrl(this.entry)).then(response => {
                this.suggestions = response.data;
                this.loading = false;
            }).catch(err => {
                this.loading = false;
                this.handleAxiosError(err);
            });
        },

        handleSuggestionSaved() {
            this.activeSuggestion = null;
            this.loadData();
        },

        handleSuggestionIgnored() {
            this.ignoringSuggestion = null;
            this.loadData();
        },

        getItemPreviewText(item) {
            const replacement = `
                <span class="text-blue font-bold">${item.phrase}</span>
            `;
            return item.context.context.replace(item.phrase, replacement);
        },

    },

    mounted() {
        this.loadData();
    },

}
</script>