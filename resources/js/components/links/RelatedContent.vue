<template>
    <div>
        <div v-if="loading" class="card loading">
            <loading-graphic />
        </div>

        <data-list
            v-if="!loading"
            ref="data-list"
            :columns="columns"
            :rows="relatedItems"
            :sort="true"
            sort-column="score"
            sort-direction="desc"
        >
            <div>
                <div class="card overflow-hidden p-0 relative">
                    <div v-show="relatedItems.length == 0" class="p-6 text-center text-gray-500" v-text="__('No results')" />

                    <data-list-table
                        v-show="relatedItems.length"
                        :allow-bulk-actions="false"
                        :loading="loading"
                        :reorderable="false"
                        :sortable="true"
                    >
                        <template slot="cell-entry.title" slot-scope="{ row: item }">
                            <a class="title-index-field inline-flex items-center" :href="makeSuggestionsUrl(item.entry.id)" @click.stop>
                                <span>{{ item.entry.title ?? item.entry.uri }}</span>
                            </a>
                        </template>
                        <template slot="cell-score" slot-scope="{ row: item }">
                            <span>{{ item.score.toFixed(2) }}</span>
                        </template>
                        <template slot="actions" slot-scope="{ row: item }">
                            <dropdown-list>
                                <dropdown-item v-if="item.can_edit_entry" text="Edit Entry" :redirect="item.entry.edit_url"></dropdown-item>
                                <div v-if="item.can_edit_entry" class="divider"></div>
                                <dropdown-item text="Not Related" class="warning" @click="ignoringSuggestion = makeSuggestion(item)" />
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
            mode="related"
            :site="site"
            @saved="handleRelatedContentIgnored"
        ></IgnoreConfirmation>
    </div>
</template>

<script>
import IgnoreConfirmation from './suggestions/IgnoreConfirmation.vue';
import ProvidesControlPanelLinks from './ProvidesControlPanelLinks.vue';

export default  {
    props: [
        'entry',
        'site',
    ],

    mixins: [ProvidesControlPanelLinks],

    components: {
        IgnoreConfirmation,
    },

    data() {
        return {
            ignoringSuggestion: null,
            columns: [
                { label: 'Entry', field: 'entry.title' },
                { label: 'Score', field: 'score' },
                { label: 'Related Keywords', field: 'related_keywords' },
            ],
            loading: false,
            relatedItems: [],
        }
    },

    methods: {

        makeSuggestion(related) {
            return {
                phrase: '',
                entry: related.entry.id,
            };
        },

        handleRelatedContentIgnored() {
            this.ignoringSuggestion = null;
            this.loadData();
        },

        loadData() {
            this.loading = true;

            this.$axios.get(this.makeRelatedUrl(this.entry)).then(response => {
                this.relatedItems = response.data;
                this.loading = false;
            });
        }

    },

    mounted() {
        this.loadData();
    }
}
</script>