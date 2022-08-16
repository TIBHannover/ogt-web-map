<template>
    <div>
        <address-info
            :selectedPlace="selectedPlace"
            @switchLocation="$emit('switchLocation', $event)"
            @undoZoomIntoPlace="$emit('undoZoomIntoPlace')"
            @zoomIntoPlace="$emit('zoomIntoPlace')"
        ></address-info>
    </div>
</template>

<script>
import AddressInfo from './AddressInfo';

export default {
    name: 'MemorialPlace',
    components: {AddressInfo},
    props: ['selectedPlace'],
};
</script>

<style scoped>

</style>