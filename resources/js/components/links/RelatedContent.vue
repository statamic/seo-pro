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
                            <a class="title-index-field inline-flex items-center" :href="item.entry.edit_url" @click.stop target="_blank">
                                <span>{{ item.entry.title ?? item.entry.uri }}</span>
                            </a>
                        </template>
                        <template slot="cell-score" slot-scope="{ row: item }">
                            <span>{{ item.score.toFixed(2) }}</span>
                        </template>
                    </data-list-table>
                </div>
            </div>
        </data-list>
    </div>
</template>

<script>
export default  {
    props: [
        'entry',
    ],

    data() {
        return {
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

        loadData() {
            this.loading = true;

            this.$axios.get(cp_url(`seo-pro/links/${this.entry}/related`)).then(response => {
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