<template>

    <modal :show="true" @closed="$emit('closed')">
        <template slot="header">
            <h1>Page Details</h1>
        </template>

        <template slot="body">

            <dl>
                <dt class="mb-1">URL</dt>
                <dd class="mb-2 font-mono text-xs">{{ item.url }}</dd>

                <template v-for="(key, field) in item.fields">
                    <dt class="mb-1 font-mono">
                        <span class="icon-status status-{{ field.unique ? 'live' : 'hidden' }}"></span>
                        {{ key }}
                    </dt>
                    <dd class="mb-2">{{ field.value }}</td>
                </template>
            </dl>

        </template>
    </modal>

</template>


<script>
export default {

    props: ['item']

}
</script>
