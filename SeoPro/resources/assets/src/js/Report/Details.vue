<template>

    <modal :show="true" @closed="$emit('closed')">
        <template slot="header">
            <h1>Page Details</h1>
        </template>

        <template slot="body">

            <p class="mb-4 font-mono text-grey text-xs">{{ item.url }}</p>

            <div class="mb-3">
                <div v-for="item in item.results" class="flex mb-1 leading-normal">
                    <div>
                        <span class="icon-status" :class="{'icon-status-live': item.status === 'pass', 'icon-status-error': item.status === 'fail', 'icon-status-warning': item.status === 'warning'}"></span>
                    </div>
                    <div class="flex-1 pl-2">
                        <span :class="{ 'font-bold': item.status !== 'pass' }">{{{ item.description }}}</span>
                        <div class="text-grey text-xs" v-if="item.comment">{{{ item.comment }}}</div>
                    </div>
                </div>
            </div>

        </template>
    </modal>

</template>


<script>
export default {

    props: ['item']

}
</script>
