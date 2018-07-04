<template>

    <div>

        <div v-if="loading" class="card loading">
            <span class="icon icon-circular-graph animation-spin"></span>
            Your report is being generated.
        </div>

        <div v-if="!loading">

            <div class="card text-sm text-grey">
                Generated <relative-date :date="report.date"></relative-date>
                <span class="mx-1">&bull;</span>
                {{ report.pages.length }} pages
            </div>

            <div class="card flush dossier">
                <div class="dossier-table-wrapper">
                    <table class="dossier">
                        <tbody>
                            <tr v-for="item in report.results">
                                <td class="w-8 text-center">
                                    <status-icon :status="item.status"></status-icon>
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
                            <tr v-for="item in report.pages">
                                <td class="w-8 text-center">
                                    <status-icon :status="item.status"></status-icon>
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
        ReportDetails: require('./Details.vue'),
        RelativeDate: require('./RelativeDate.vue'),
        StatusIcon: require('./StatusIcon.vue'),
    },

    props: ['id'],

    data() {
        return {
            loading: false,
            report: null,
            selected: null
        }
    },

    ready() {
        this.load();
    },

    methods: {

        load() {
            this.loading = true;
            this.report = null;

            this.$http.get(cp_url(`addons/seo-pro/reports/${this.id}`)).then(response => {
                if (response.data.status === 'pending') {
                    setTimeout(() => this.load(), 1000);
                    return;
                }

                this.report = response.data;
                this.loading = false;
            });
        }

    }

}
</script>
