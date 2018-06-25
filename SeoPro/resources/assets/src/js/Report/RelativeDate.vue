<template>

    <span>{{ text }}</span>

</template>


<script>
export default {

    props: ['date'],

    data() {
        return {
            text: null
        }
    },

    ready() {
        this.update();
    },

    methods: {

        update() {
            this.text = moment(this.date * 1000).fromNow();

            setTimeout(() => this.update(), 60000); // once a minute
        }

    }

}
</script>
