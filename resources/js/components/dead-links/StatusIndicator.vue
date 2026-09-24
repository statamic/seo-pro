<script setup>
import { computed } from 'vue';

const props = defineProps({
	status: {
		type: String,
		required: false,
		default: 'pending',
		validator: (value) => ['ok', 'failing', 'pending'].includes(value),
	},
	showDot: { type: Boolean, default: true },
	showLabel: { type: Boolean, default: false },
});

const statusClass = computed(() => ({
	ok: 'bg-green-400',
	failing: 'bg-red-400',
	pending: 'bg-gray-300 dark:bg-gray-200',
}[props.status]));

const label = computed(() => ({
	ok: __('seo-pro::messages.ok'),
	failing: __('seo-pro::messages.failing'),
	pending: __('seo-pro::messages.pending'),
}[props.status]));
</script>

<template>
    <span class="flex items-center gap-2">
        <span v-if="showDot" class="size-2 rounded-full" :class="statusClass" v-tooltip="label" />
        <span v-if="showLabel" class="select-none" v-text="label" />
    </span>
</template>
