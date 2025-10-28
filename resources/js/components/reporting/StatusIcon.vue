<script setup>
import { Icon } from '@statamic/cms/ui';

defineProps({
	status: String,
});
</script>

<template>
    <div>
        <span v-if="status === 'pending'" class="icon icon-circular-graph animation-spin" />
        <Icon name="checkmark" class="text-green-600" v-else-if="status === 'pass'" />
        <Icon name="x" class="text-red-500" v-else-if="status === 'fail'" />
        <Icon name="alert-circle" class="text-orange" v-else-if="status === 'warning'" />
    </div>
</template>
