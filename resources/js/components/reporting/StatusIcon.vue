<template>

    <div>
        <span v-if="status === 'pending'" class="icon icon-circular-graph animation-spin"></span>

        <span
            v-else
            class="little-dot"
            :class="{
                'bg-green-600': status === 'pass',
                'bg-red-500': status === 'fail',
                'bg-orange': status === 'warning'
            }"
        />

    </div>

</template>


<script>
export default {

    props: ['status']

}
</script>
