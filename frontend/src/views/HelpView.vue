<script setup>
import NexusFont from '../components/NexusFont.vue';
</script>

<template>

	<div class="about">
		<h1>CYBER_NOIR</h1>
		<h2>// INVESTIGATION_SYSTEM</h2>

    	<div class="flex mt-8 " style="width: 90%;">
		  <span class="flex w-1/2 justify-center">
		    <img src="/system/sectors.svg" />
		  </span>
		  <span class="flex w-1/2 justify-center">
		    <img src="/system/views.svg" />
		  </span>
		</div>
	</div>

</template>

<style>
	.about {
		min-height: 100vh;
		display: flex;
		align-items: center;
		flex-direction: column;
	}
.about h1 {
	font-size: 3em;
}
</style>
