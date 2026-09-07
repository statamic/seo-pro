<script setup>
import { Head, router } from '@statamic/cms/inertia';
import RedirectPublishForm from "../../components/redirects/PublishForm.vue";

const props = defineProps({
	title: { type: String, required: true },
	blueprint: { type: Object, required: true },
	values: { type: Object, required: true },
	meta: { type: Object, required: true },
	submitUrl: { type: String, required: true },
	readOnly: { type: Boolean, required: false },
});

const saved = () => router.reload({ only: ['title'] });
</script>

<template>
	<Head :title />

	<RedirectPublishForm
		:title
		:blueprint
		:initial-values="values"
		:initial-meta="meta"
		:submit-url
		submit-method="patch"
		:read-only
		@saved="saved"
	/>
</template>
