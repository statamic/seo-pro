<template>
    <div>
        <div v-if="loading" class="card loading">
            <loading-graphic />
        </div>

        <data-list
            v-if="!loading"
            ref="data-list"
            :columns="columns"
            :rows="internalLinks"
            :sort="true"
            sort-column="title"
        >
            <div>
                <div class="card internalLinks p-0 relative">
                    <div v-show="internalLinks.length == 0" class="p-6 text-center text-gray-500" v-text="__('No results')" />

                    <data-list-table
                        v-show="internalLinks.length"
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
                        <template slot="actions" slot-scope="{ row: item }">
                            <dropdown-list>
                                <dropdown-item text="Edit Entry" :redirect="item.entry.edit_url"></dropdown-item>
                            </dropdown-list>
                        </template>
                    </data-list-table>
                </div>
            </div>
        </data-list>
    </div>
</template>

<script>
import ProvidesControlPanelLinks from './ProvidesControlPanelLinks.vue';
import HandlesRequestErrors from './HandlesRequestErrors.vue';

export default  {
    mixins: [HandlesRequestErrors, ProvidesControlPanelLinks],

    props: [
        'entry',
    ],

    data() {
        return {
            columns: [
                {
                    label: __('Entry'),
                    field: 'entry.title'
                },
                {
                    label: __('seo-pro::messages.link_target'),
                    field: 'uri'
                },
            ],
            loading: false,
            internalLinks: [],
        }
    },

    methods: {

        loadData() {
            this.loading = true;

            this.$axios.get(this.makeInternalLinksUrl(this.entry)).then(response => {
                this.internalLinks = response.data;
                this.loading = false;
            }).catch(err => {
                this.loading = false;
                this.handleAxiosError(err);
            });
        },

    },

    mounted() {
        this.loadData();
    },

}
</script>