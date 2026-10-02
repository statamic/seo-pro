<script setup>
import { DateFormatter } from '@statamic/cms';
import { Head } from '@statamic/cms/inertia';
import { Header, Button, Listing, DropdownItem, DocsCallout, Alert } from '@statamic/cms/ui';
import { ref, useTemplateRef, getCurrentInstance } from 'vue';

const props = defineProps({
	columns: Array,
	filters: Array,
	recheckAllUrl: String,
});

const instance = getCurrentInstance();
const { $axios } = instance.appContext.config.globalProperties;

const rechecking = ref(false);
const uncheckedCount = ref(0);
const listing = useTemplateRef('listing');

function requestCompleted({ response }) {
	uncheckedCount.value = response.data.meta.uncheckedCount;
}

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
	<Head :title="__('seo-pro::messages.broken_links')" />

	<Header :title="__('seo-pro::messages.broken_links')" icon="external-link">
		<Button
			:text="__('seo-pro::messages.recheck_all')"
			:loading="rechecking"
			@click="recheckAll"
		/>
	</Header>

	<Alert
		v-if="uncheckedCount"
		class="mb-4"
		icon="info"
		:text="__n('seo-pro::messages.links_not_yet_checked', uncheckedCount, { count: uncheckedCount })"
	/>

	<Listing
		ref="listing"
		:url="cp_url(`seo-pro/broken-links`)"
		:action-url="cp_url(`seo-pro/broken-links/actions`)"
		:columns
		:allow-presets="false"
		:allow-customizing-columns="false"
		:filters
		sort-column="failing_since"
		sort-direction="asc"
		preferences-prefix="seo-pro.broken-links"
		push-query
		@request-completed="requestCompleted"
	>
		<template #cell-url="{ row: link }">
			<a class="title-index-field" :href="link.url" target="_blank" rel="noopener noreferrer" v-text="link.url" />
		</template>
		<template #cell-failing_since="{ row: link }">
			<span
				v-if="link.failing_since"
				v-text="DateFormatter.format(link.failing_since, { relative: true })"
				v-tooltip="DateFormatter.format(link.failing_since, { preset: 'datetime', timeZoneName: 'short' })"
			/>
		</template>
		<template #cell-checked_at="{ row: link }">
			<span
				v-if="link.checked_at"
				v-text="DateFormatter.format(link.checked_at, { relative: true })"
				v-tooltip="DateFormatter.format(link.checked_at, { preset: 'datetime', timeZoneName: 'short' })"
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

	<DocsCallout :topic="__('seo-pro::messages.broken_links')" url="https://statamic.com/addons/statamic/seo-pro/docs" />
</template>
