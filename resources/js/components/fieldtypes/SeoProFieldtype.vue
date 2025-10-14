<script setup>
import { Fieldtype } from '@statamic/cms';
import { PublishFieldsProvider as FieldsProvider, PublishFields } from '@statamic/cms/ui';

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const { expose, isReadOnly } = Fieldtype.use(emit, props);
defineExpose(expose);

// todo: sections?
</script>

<template>
    <div>
	    <FieldsProvider
		    :fields="meta.fields"
		    :read-only="isReadOnly || !config.localizable"
		    :field-path-prefix="`seo`"
		    :meta-path-prefix="`seo.meta`"
	    >
			<PublishFields />
	    </FieldsProvider>
    </div>
</template>