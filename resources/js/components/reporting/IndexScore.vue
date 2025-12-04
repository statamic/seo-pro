<script setup>
import { ref, computed, getCurrentInstance } from 'vue';
import StatusIcon from "./StatusIcon.vue";
import { Icon } from '@statamic/cms/ui';

const instance = getCurrentInstance();
const { $axios } = instance.appContext.config.globalProperties;

const props = defineProps({
	id: String,
	initialStatus: String,
	initialScore: Number,
});

const status = ref(props.initialStatus);
const score = ref(props.initialScore);

const statusByScore = computed(() => {
	if (! score.value) {
		return status.value;
	}

	// Ensure we color status icon on index view to match site score color on report show view
	if (score.value < 70) {
		return 'fail';
	} else if (score.value < 90) {
		return 'warning';
	} else if (score.value >= 90) {
		return 'pass';
	}
});

const pollReport = () => {
	$axios.get(cp_url(`seo-pro/reports/${props.id}`)).then(response => {
		if (response.data.status === 'pending' || response.data.status === 'generating') {
			setTimeout(() => pollReport(), 2000);
			return;
		}

		status.value = response.data.status;
		score.value = response.data.score;
	});
};

if (! score.value) pollReport();
</script>

<template>
	<div>
		<div v-if="score" class="flex items-center">
			<StatusIcon :status="statusByScore" class="inline-block ml-1 mr-3" />
			{{ score }}%
		</div>
		<Icon v-else name="loading" />
	</div>
</template>