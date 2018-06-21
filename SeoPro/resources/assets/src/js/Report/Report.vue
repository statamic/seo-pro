<template>

    <div>

        <div class="flex items-center mb-3">
            <h1 class="flex-1">SEO Report</h1>
            <div class="controls" v-if="!loading">
                <button @click="load" class="btn btn-primary">{{ translate('cp.refresh') }}</button>
            </div>
        </div>

        <div v-if="loading" class="card loading">
            <span class="icon icon-circular-graph animation-spin"></span> {{ translate('cp.loading') }}
        </div>

        <div v-else>

            <div class="card">
                <div class="metrics">
                    <div class="metric simple">
                        <div class="count">
                            <small>SEO Pro Grade</small>
                            <h2>{{ grade }}</h2>
                        </div>
                    </div>
                    <div class="metric simple">
                        <div class="count">
                            <small>Complete</small>
                            <h2>{{ completionPercent }}%</h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card flush dossier">
                <div class="dossier-table-wrapper">
                    <table class="dossier">
                        <tbody>
                            <tr v-for="item in items.site">
                                <td class="w-8 text-center">
                                    <span class="icon-status icon-status-{{ item.valid ? 'live' : 'error' }}"></span>
                                </div>
                                <td>{{{ item.description }}}</td>
                                <td class="text-grey text-right">{{{ item.comment }}}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card flush dossier">
                <div class="dossier-table-wrapper">
                    <table class="dossier">
                        <tbody>
                            <tr v-for="item in items.pages">
                                <td class="w-8 text-center">
                                    <span class="icon-status icon-status-{{ item.valid ? 'live' : 'error' }}"></span>
                                </div>
                                <td>
                                    <a href="" @click.prevent="selected = item.id">{{{ item.url }}}</a>
                                    <report-details
                                        v-if="selected === item.id"
                                        :item="item"
                                        @closed="selected = null"
                                    ></report-details>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

</template>


<script>
export default {

    components: {
        ReportDetails: require('./Details.vue')
    },

    data() {
        return {
            loading: true,
            items: null,
            selected: null
        }
    },

    computed: {

        completionPercent() {
            const total = this.items.pages.length;
            const unique = this.items.pages.filter(page => page.valid).length;
            return Math.round(unique / total * 100);
        },

        grade() {
            const score = this.completionPercent;
            if (score >= 97) return 'A+';
            if (score >= 93) return 'A';
            if (score >= 90) return 'A-';
            if (score >= 87) return 'B+';
            if (score >= 83) return 'B';
            if (score >= 80) return 'B-';
            if (score >= 77) return 'C+';
            if (score >= 73) return 'C';
            if (score >= 70) return 'C-';
            if (score >= 67) return 'D+';
            if (score >= 63) return 'D';
            if (score >= 60) return 'D-';
            return 'F';
        }

    },

    ready() {
        this.load();
    },

    methods: {

        load() {
            this.loading = true;
            this.items = null;

            this.$http.get(cp_url('addons/seo-pro/report/summary')).then(response => {
                this.items = response.data;
                this.loading = false;
            });
        }

    }

}
</script>
