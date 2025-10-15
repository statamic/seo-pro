<script setup>
defineProps({
	status: String,
});
</script>

<template>
    <div>
        <span v-if="status === 'pending'" class="icon icon-circular-graph animation-spin" />
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