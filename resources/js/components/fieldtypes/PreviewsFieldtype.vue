<script setup>
import axios from 'axios';
import { Fieldtype } from '@statamic/cms';
import { Field, injectPublishContext } from '@statamic/cms/ui';
import { computed, ref, watch } from 'vue';
import striptags from "striptags";

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const { expose } = Fieldtype.use(emit, props);
defineExpose(expose);

const { values: publishValues, meta: publishMeta, blueprint } = injectPublishContext();

const resolveSeoValue = (field) => {
	let value = publishValues.value.seo[field];

	if (value.source === 'inherit') {
		let seoField = publishMeta.value.seo.fields.find(f => f.handle === field);

		if (seoField.field?.type === 'assets') {
			return props.meta.assetContainerUrl + '/' + seoField.placeholder;
		}

		return seoField.placeholder;
	}

	if (value.source === 'field') {
		if (! publishValues.value.hasOwnProperty(value.value)) return;

		let sourceField = blueprint.value.tabs
			.flatMap(tab => tab.sections)
			.flatMap(section => section.fields)
			.find(field => field.handle === value.value);

		let sourceFieldValue = publishValues.value[value.value];

		switch (sourceField.type) {
			case 'markdown':
				return striptags(markdown(sourceFieldValue));
			case 'bard':
				if (typeof sourceFieldValue === 'string') {
					return striptags(sourceFieldValue);
				}

				let text = '';
				let originalValue = clone(sourceFieldValue);

				while (originalValue.length > 0) {
					let item = originalValue.shift();

					if (! item.type) continue;

					if (item.type === 'text') {
						text += ` ${item.text || ''}`;
					}

					originalValue.unshift(...(item.content ?? []));
				}

				return text;
			case 'assets':
				return publishMeta.value[value.value]?.data[0]?.url;
			default:
				return sourceFieldValue;
		}
	}

	if (value.source === 'custom') {
		let seoField = publishMeta.value.seo.fields.find(f => f.handle === field);

		if (seoField.field?.type === 'assets') {
			return publishMeta.value.seo.meta[field].fieldMeta.data[0]?.url;
		}

		return value.value;
	}
}

const title = computed(() => {
	const seoTitle = resolveSeoValue('title');
	const siteName = resolveSeoValue('site_name');
	const siteNameSeparator = resolveSeoValue('site_name_separator');
	const siteNamePosition = resolveSeoValue('site_name_position');

	if (! seoTitle) {
		return siteName;
	}

	if (! siteName || siteNamePosition === 'none') {
		return seoTitle;
	}

	let compiled = [seoTitle, siteNameSeparator, siteName];

	if (siteNamePosition === 'before') {
		compiled = compiled.reverse();
	}

	return compiled.join(' ');
});

const url = ref(props.meta.initialUrl);
const domain = computed(() => url.value ? new URL(url.value).hostname : window.location.hostname);
const description = computed(() => resolveSeoValue('description'));
const image = computed(() => resolveSeoValue('image'));
const twitterTitle = computed(() => resolveSeoValue('twitter_title') || resolveSeoValue('title') || title.value);
const twitterDescription = computed(() => resolveSeoValue('twitter_description') || resolveSeoValue('description'));
const facebookTitle = computed(() => resolveSeoValue('og_title') || resolveSeoValue('title') || title.value);

watch(
	() => props.meta.initialUrl,
	() => (url.value = props.meta.initialUrl),
);

const fetchUpdatedUrl = async () => {
	axios
		.post(props.meta.previewUrl, {
			id: publishValues.value.id,
			values: props.meta.routeFields.reduce((acc, handle) => {
				acc[handle] = publishValues.value[handle];
				return acc;
			}, {}),
		})
		.then(response => (url.value = response.data.url))
		.catch(error => Statamic.$toast.error(__('Something went wrong')));
};

if (publishValues.value.id) {
	props.meta.routeFields.forEach(field => {
		watch(
			() => publishValues.value[field],
			() => fetchUpdatedUrl(),
			{ deep: true }
		);
	});
}

const googleUrlComponents = computed(() => {
	if (! url.value) return [];

	const urlObject = new URL(url.value);

	const origin = urlObject.origin;

	const pathSegments = urlObject.pathname
		.split('/')
		.filter(segment => segment.length > 0);

	return [origin, ...pathSegments];
});
</script>

<template>
	<div class="flex flex-col gap-4">
        <Field :label="__('seo-pro::messages.google_preview')">
			<div class="bg-white dark:!bg-[#1f1f1f] max-w-[652px] border rounded-lg p-4 flex justify-between">
				<div class="min-w-0">
					<a class="flex flex-row items-center mb-1.5" :href="url" target="_blank">
						<div class="shrink-0 size-[28px] bg-[#f3f5f6] !border !border-[#d2d2d2] dark:!border-[#5c5f5e] rounded-[50%] mr-3">
							<img v-if="meta.faviconUrl" class="size-full" :src="meta.faviconUrl" />
							<div v-else class="p-1">
								<svg focusable="false" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
									<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"></path>
								</svg>
							</div>
						</div>
						<div class="min-w-0">
							<div class="text-[#202124] dark:text-[#dadce0] text-sm" v-text="domain" />
							<div class="text-[#4d5156] dark:text-[#bdc1c6] text-xs truncate" v-text="googleUrlComponents.join(' › ')" />
						</div>
					</a>

					<a class="block !text-[#1a0dab] dark:!text-[#99c3ff] text-xl mb-1 truncate max-w-xl" :href="url" target="_blank" v-text="title" />
					<div v-if="description" class="text-[#1f1f1f] dark:text-[#bfbfbf] text-sm line-clamp-2" v-text="description" />
				</div>
				<a v-if="image" class="block shrink-0 !pl-[20px]" :href="url" target="_blank">
					<div class="size-[92px]">
						<img class="rounded-[8px] size-full object-cover" :src="image">
					</div>
				</a>
			</div>
		</Field>

		<Field :label="__('seo-pro::messages.x_twitter_preview')">
			<a v-if="image" class="block max-w-[663px] max-h-[347px] rounded-2xl border border-[#CFD9DE] relative overflow-hidden" :href="url" target="_blank">
				<img class="size-full object-cover" :src="image" />
				<div class="absolute bottom-3 left-3 right-3">
					<div class="bg-[#000000C4] text-white text-[13px] px-2 inline-flex rounded truncate max-w-xl" v-text="twitterTitle" />
				</div>
			</a>

			<a v-else class="flex max-w-[663px] h-[131px] rounded-2xl border overflow-hidden dark:bg-[#060606]" :href="url" target="_blank">
				<div class="w-[130px] max-h-full bg-[#F7F9F9] dark:bg-[#16181C] flex items-center justify-center border-r">
					<svg viewBox="0 0 24 24" aria-hidden="true" class="size-[30px] text-[#536471] dark:text-[#71767B]">
						<g>
							<path fill="currentColor" d="M1.998 5.5c0-1.38 1.119-2.5 2.5-2.5h15c1.381 0 2.5 1.12 2.5 2.5v13c0 1.38-1.119 2.5-2.5 2.5h-15c-1.381 0-2.5-1.12-2.5-2.5v-13zm2.5-.5c-.276 0-.5.22-.5.5v13c0 .28.224.5.5.5h15c.276 0 .5-.22.5-.5v-13c0-.28-.224-.5-.5-.5h-15zM6 7h6v6H6V7zm2 2v2h2V9H8zm10 0h-4V7h4v2zm0 4h-4v-2h4v2zm-.002 4h-12v-2h12v2z"></path>
						</g>
					</svg>
				</div>
				<div class="p-3 flex-1 flex flex-col justify-center gap-0.5 text-[15px]">
					<div class="text-[#536471] dark:text-[#71767B]" v-text="domain" />
					<div class="text-[#0F1419] dark:text-[#E7E9EA]" v-text="twitterTitle" />
					<div class="text-[#536471] dark:text-[#71767B] line-clamp-2" v-text="twitterDescription" />
				</div>
			</a>
		</Field>

		<Field :label="__('seo-pro::messages.facebook_preview')">
			<a class="block max-w-[680px] border rounded-lg overflow-hidden" :href="url" target="_blank">
				<div v-if="image" class="w-full h-[354px]">
					<img class="size-full object-cover" :src="image" />
				</div>
				<div class="bg-[#F2F4F7] dark:bg-[#1C1C1D] px-4 py-3">
					<div class="uppercase text-[#65686C] dark:text-[#B0B3B8] text-[.8125rem] mb-[8px]" v-text="domain" />
					<div class="text-[#080809] dark:text-[#E2E5E9] text-[1.0625rem] font-semibold truncate max-w-xl" v-text="facebookTitle" />
				</div>
			</a>
		</Field>
	</div>
</template>
