<script setup>
import { Icon } from '@statamic/cms/ui';

defineProps({
	status: String,
});
</script>

<template>
    <div>
        <span v-if="status === 'pending'" class="icon icon-circular-graph animation-spin" />
        <Icon name="checkmark" class="text-green-600 dark:!text-green-400" v-else-if="status === 'pass'" />
        <Icon name="x" class="text-red-600 dark:!text-red-400" v-else-if="status === 'fail'" />
        <Icon name="alert-warning-exclamation-mark" class="text-amber-500 dark:!text-amber-300" v-else-if="status === 'warning'" />
    </div>
</template>
