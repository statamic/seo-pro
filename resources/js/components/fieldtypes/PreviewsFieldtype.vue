<script setup>
import { Fieldtype } from '@statamic/cms';
import { Field, injectPublishContext } from '@statamic/cms/ui';
import { computed } from 'vue';
import striptags from "striptags";

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const { expose } = Fieldtype.use(emit, props);
defineExpose(expose);

const { values: publishValues, meta: publishMeta, blueprint } = injectPublishContext();

function resolveSeoValue(field) {
	let value = publishValues.value.seo[field];

	// todo: handle inherited images
	if (value.source === 'inherit') {
		let seoField = publishMeta.value.seo.fields.find(f => f.handle === field);

		return seoField.field?.placeholder || seoField.placeholder;
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
		return value.value;
	}
}

// Borrowed from Cascade::compiledTitle()
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

const url = computed(() => props.meta.url); // todo: figure out how to handle slug changes...
const domain = computed(() => new URL(url.value).hostname);
const description = computed(() => resolveSeoValue('description'));
const image = computed(() => resolveSeoValue('image'));
const twitterTitle = computed(() => resolveSeoValue('twitter_title') || resolveSeoValue('title'));
const twitterDescription = computed(() => resolveSeoValue('twitter_description') || resolveSeoValue('description'));
const facebookTitle = computed(() => resolveSeoValue('og_title') || resolveSeoValue('title'));

const googleUrlComponents = computed(() => {
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
		<Field :label="__('Google Preview')">
			<div class="bg-white dark:!bg-[#1f1f1f] max-w-[652px] border rounded-lg p-4 flex">
				<div class="min-w-0">
					<a class="flex flex-row items-center mb-1.5" :href="url" target="_blank">
						<div class="size-[28px] bg-[#f3f5f6] !border !border-[#d2d2d2] dark:!border-[#5c5f5e] rounded-[50%] mr-3">
							<img class="size-full" :src="`https://www.google.com/s2/favicons?domain=${domain}&sz=64}`" />
						</div>
						<div>
							<div class="text-[#202124] dark:text-[#dadce0] text-sm" v-text="domain" />
							<div class="text-[#4d5156] dark:text-[#bdc1c6] text-xs">
								<span v-text="googleUrlComponents[0]" />
								<span v-for="(component, index) in googleUrlComponents.slice(1)" :key="index" v-text="' › ' + component" />
							</div>
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

		<Field :label="__('X (Twitter) Preview')">
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

		<Field :label="__('Facebook Preview')">
			<a class="block max-w-[680px] border rounded-lg overflow-hidden" :href="url" target="_blank">
				<div v-if="image" class="w-full h-[354px]">
					<img class="size-full object-cover" :src="image" />
				</div>
				<div class="bg-[#F2F4F7] dark:bg-[#1C1C1D] px-4 py-3">
					<div class="uppercase text-[#65686C] dark:text-[#B0B3B8] text-[.8125rem] mb-2" v-text="domain" />
					<div class="text-[#080809] dark:text-[#E2E5E9] text-[1.0625rem] font-semibold truncate max-w-xl" v-text="facebookTitle" />
				</div>
			</a>
		</Field>
	</div>
</template>