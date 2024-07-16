<script>
import { useAppOptionStore } from '@/stores/app-option';

export default {
	setup() {

const appOption = useAppOptionStore();
		return {
			year: (new Date()).getFullYear(),
			version: appOption.project.version,
		}
	}
}
</script>
<template>
	<!-- BEGIN #footer -->
	<div id="footer" class="app-footer">
		&copy; {{ year }} Jerry Lin. All Right Reserved v.{{version}}
	</div>
	<!-- END #footer -->
</template>
