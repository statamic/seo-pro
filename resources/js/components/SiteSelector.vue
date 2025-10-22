<script setup>
// Copied from Statamic Core.

import { Select } from '@statamic/cms/ui';

defineProps({
	sites: { type: Array, required: true },
	modelValue: { type: String, required: true },
});

defineEmits(['update:modelValue']);
</script>

<template>
	<Select
		:options="sites"
		option-label="name"
		option-value="handle"
		:model-value="modelValue"
		@update:model-value="$emit('update:modelValue', $event)"
	/>
</template>
