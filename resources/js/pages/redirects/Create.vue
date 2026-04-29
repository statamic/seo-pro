<script setup>
import { Head, router } from '@statamic/cms/inertia';
import RedirectPublishForm from "../../components/redirects/PublishForm.vue";

const props = defineProps({
	blueprint: { type: Object, required: true },
	values: { type: Object, required: true },
	meta: { type: Object, required: true },
	submitUrl: { type: String, required: true },
});

const saved = (response) => {
	router.get(response.data.redirect);
};
</script>

<template>
	<Head :title="__('Create Redirect')" />

	<RedirectPublishForm
		:title="__('Create Redirect')"
		:blueprint
		:initial-values="values"
		:initial-meta="meta"
		:submit-url
		submit-method="post"
		@saved="saved"
	/>
</template>
