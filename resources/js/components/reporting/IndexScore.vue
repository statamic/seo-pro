<template>

    <div v-if="score">
        <seo-pro-status-icon :status="status" class="inline-block mr-3"></seo-pro-status-icon>
        {{ score }}%
    </div>

</template>


<script>
export default {

    props: [
        'id',
        'initialStatus',
        'initialScore',
    ],

    data() {
        return {
            status: this.initialStatus,
            score: this.initialScore,
        }
    },

    created() {
        if (! this.score) this.updateScore();
    },

    methods: {
        updateScore() {
            Statamic.$request.get(cp_url(`seo-pro/reports/${this.id}`)).then(response => {
                if (response.data.status === 'pending' || response.data.status === 'generating') {
                    setTimeout(() => this.updateScore(), 1000);
                    return;
                }

                this.status = response.data.status;
                this.score = response.data.score;
            });
        },
    },

}
</script>
