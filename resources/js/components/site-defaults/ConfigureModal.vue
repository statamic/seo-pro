<script setup>
import axios from 'axios';
import { Modal, Icon, Field, Heading, Select, Button, } from '@statamic/cms/ui';
import { ref, onMounted } from 'vue';

const emit = defineEmits(['closed', 'saved']);
const props = defineProps({ route: String });

const open = ref(true);
const busy = ref(false);
const sites = ref(null);
const error = ref(null);

const siteOriginOptions = (site) => {
	return sites.value
		.map((s) => ({ value: s.handle, label: __(s.name) }))
		.filter((s) => s.value !== site.handle);
};

const save = () => {
	busy.value = true;
	error.value = null;

	axios.patch(props.route, { sites: sites.value })
		.then(() => {
			Statamic.$toast.success(__('Saved'));
			emit('saved');
			close();
		})
		.catch((e) => {
			error.value = e.response.data.errors?.sites[0];
			Statamic.$toast.error(__('Something went wrong'));
		})
		.finally(() => {
			busy.value = false;
		});
};

const close = () => {
	open.value = false;
	setTimeout(() => emit('closed'), 200);
};

onMounted(() => {
	busy.value = true;

	axios.get(props.route)
		.then((response) => {
			sites.value = response.data.sites;
		})
		.catch((error) => {
			Statamic.$toast.error(__('Something went wrong'));
			close();
		})
		.finally(() => {
			busy.value = false;
		});
});
</script>

<template>
	<Modal
		:title="__('Configure Site Defaults')"
		:open
		:dismissable="!busy"
		@dismissed="close"
		@update:model-value="close"
	>
		<div
			v-if="!sites"
			class="pointer-events-none absolute inset-0 flex select-none items-center justify-center bg-white bg-opacity-75 dark:bg-dark-700"
		>
			<Icon name="loading" />
		</div>

		<Field v-else :label="__('Sites')" :error required>
			<table class="grid-table">
				<thead>
				<tr>
					<th scope="col">
						<div class="flex items-center justify-between">
							{{ __('Site') }}
						</div>
					</th>
					<th scope="col">
						<div class="flex items-center justify-between">
							{{ __('Origin') }}
						</div>
					</th>
				</tr>
				</thead>
				<tbody>
				<tr v-for="site in sites" :key="site.handle">
					<td class="grid-cell">
						<div class="flex items-center gap-2">
							<Heading :text="__(site.name)" />
						</div>
					</td>
					<td class="grid-cell">
						<Select
							class="w-full"
							:options="siteOriginOptions(site)"
							:clearable="true"
							:model-value="site.origin"
							@update:model-value="site.origin = $event"
						/>
					</td>
				</tr>
				</tbody>
			</table>
		</Field>

		<template #footer>
			<div class="flex items-center justify-end space-x-3 pt-3 pb-1">
				<Button
					variant="ghost"
					:disabled="busy"
					:text="__('Cancel')"
					@click="close"
				/>
				<Button
					type="submit"
					variant="primary"
					:disabled="busy"
					:text="__('Save')"
					@click="save"
				/>
			</div>
		</template>
	</Modal>
</template>