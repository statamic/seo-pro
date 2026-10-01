<script setup>
import { DateFormatter } from '@statamic/cms';
import { Head } from '@statamic/cms/inertia';
import { Header, Button, Listing, DropdownItem, DocsCallout } from '@statamic/cms/ui';
import StatusIndicator from '../../components/dead-links/StatusIndicator.vue';
import { ref, useTemplateRef, getCurrentInstance } from 'vue';

const props = defineProps({
	blueprint: Object,
	columns: Array,
	filters: Array,
	canManage: Boolean,
	recheckAllUrl: String,
});

const instance = getCurrentInstance();
const { $axios } = instance.appContext.config.globalProperties;

const rechecking = ref(false);
const listing = useTemplateRef('listing');

function recheckAll() {
	rechecking.value = true;

	$axios.post(props.recheckAllUrl)
		.then((response) => Statamic.$toast.success(response.data.message))
		.catch(() => Statamic.$toast.error(__('Something went wrong')))
		.finally(() => {
			rechecking.value = false;
			listing.value.refresh();
		});
}
</script>

<template>
	<Head :title="__('seo-pro::messages.dead_links')" />

	<Header :title="__('seo-pro::messages.dead_links')" icon="external-link">
		<Button
			v-if="canManage"
			:text="__('seo-pro::messages.recheck_all')"
			:loading="rechecking"
			@click="recheckAll"
		/>
	</Header>

	<Listing
		ref="listing"
		:url="cp_url(`seo-pro/dead-links`)"
		:action-url="cp_url(`seo-pro/dead-links/actions`)"
		:columns
		:allow-presets="false"
		:filters
		sort-column="status"
		sort-direction="asc"
		preferences-prefix="seo-pro.dead-links"
		push-query
	>
		<template #cell-url="{ row: link }">
			<a class="title-index-field" :href="link.url" target="_blank" rel="noopener noreferrer">
				<StatusIndicator :status="link.status" :label="link.status_label" />
				<span v-text="link.url" />
			</a>
		</template>
		<template #cell-status="{ row: link }">
			<StatusIndicator
				:status="link.status"
				:label="link.status_label"
				show-label
				:show-dot="false"
				v-tooltip="link.failing_since ? DateFormatter.format(link.failing_since, 'datetime') : null"
			/>
		</template>
		<template #prepended-row-actions="{ row: link }">
			<DropdownItem
				v-for="reference in link.references"
				:key="reference.edit_url"
				:text="__('Edit :title', { title: reference.title })"
				:href="reference.edit_url"
				icon="edit"
			/>
		</template>
	</Listing>

	<DocsCallout :topic="__('seo-pro::messages.dead_links')" url="https://statamic.com/addons/statamic/seo-pro/docs" />
</template>
