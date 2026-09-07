<script setup>
import { computed } from 'vue';

const props = defineProps({
	status: {
		type: String,
		required: false,
		default: 'active',
		validator: (value) => ['active', 'inactive'].includes(value),
	},
	showDot: { type: Boolean, default: true },
	showLabel: { type: Boolean, default: false },
});

const statusClass = computed(() => {
	if (props.status === 'active') {
		return 'bg-green-400';
	}

	return 'bg-gray-300 dark:bg-gray-200';
});

const label = computed(() => {
	const labels = {
		active: __('seo-pro::messages.active'),
		inactive: __('seo-pro::messages.inactive'),
	};
	return labels[props.status];
});
</script>

<template>
    <span class="flex items-center gap-2">
        <span v-if="showDot" class="size-2 rounded-full" :class="statusClass" v-tooltip="label" />
        <span v-if="showLabel" class="status-index-field select-none" :class="`status-${ status === 'active' ? 'published' : 'draft' }`" v-text="label" />
    </span>
</template>
