<script setup>
import NexusFont from '../components/NexusFont.vue';
</script>

<template>

	<div class="about">
		<h1>CYBER_NOIR</h1>
		<h2>// INVESTIGATION_SYSTEM</h2>
		<p>
			Trying to create an editor for a point and click game.
		</p>
		<br />
		<nexus-font type="slant" label="Blade Runners" />
		<p>of the</p>
    	<nexus-font type="blocky" label="NEO TOKYO POLICE DEP" />


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
