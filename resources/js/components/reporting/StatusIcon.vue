<script setup>
defineProps({
	status: String,
});
</script>

<template>
    <div>
        <span v-if="status === 'pending'" class="icon icon-circular-graph animation-spin" />
        <ui-icon name="checkmark" class="text-green-600" v-else-if="status === 'pass'" />
        <ui-icon name="x" class="text-red-500" v-else-if="status === 'fail'" />
        <ui-icon name="alert-circle" class="text-orange" v-else-if="status === 'warning'" />
    </div>
</template>
