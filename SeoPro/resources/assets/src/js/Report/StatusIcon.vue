<template>

    <span>
        <span v-if="status === 'pending'" class="icon icon-circular-graph animation-spin"></span>

        <span
            v-else
            class="icon-status"
            :class="{
                'icon-status-live': status === 'pass',
                'icon-status-error': status === 'fail',
                'icon-status-warning': status === 'warning'
            }"></span>
    </span>

</template>


<script>
export default {

    props: ['status']

}
</script>
