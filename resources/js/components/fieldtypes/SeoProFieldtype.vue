<script setup>
import { Fieldtype } from '@statamic/cms';
import { PublishFieldsProvider as FieldsProvider, PublishFields, injectPublishContext } from '@statamic/cms/ui';
import { computed } from 'vue';

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const { expose, isReadOnly } = Fieldtype.use(emit, props);
defineExpose(expose);

const { originValues } = injectPublishContext();

const readOnly = computed(() => {
	// When localizing an entry AND "localizable" is disabled, make the fields read-only.
	if (originValues?.length > 0 && !props.config.localizable) {
		return true;
	}

	return isReadOnly.value;
});
</script>

<template>
    <div :class="{ 'opacity-50': readOnly }">
	    <FieldsProvider
		    :fields="meta.fields"
		    :read-only="readOnly"
		    :field-path-prefix="`seo`"
		    :meta-path-prefix="`seo.meta`"
	    >
			<PublishFields />
	    </FieldsProvider>
    </div>
</template>