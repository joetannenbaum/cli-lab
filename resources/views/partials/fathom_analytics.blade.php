@if (config('services.fathom_analytics.site_id'))
<script src="https://cdn.usefathom.com/script.js" data-site="{{ config('services.fathom_analytics.site_id') }}" defer>
</script>
@endif