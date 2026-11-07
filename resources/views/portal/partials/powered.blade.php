{{-- "Powered by" with the TenaFi logo. Both versions load from this site (no CDN),
     so they show before the guest is online; CSS shows the one that reads on the
     background: dark text on light pages, light text on dark ones. --}}
<span class="powered-by">Powered by
    <img class="powered-by__logo powered-by__logo--on-light" src="{{ asset(\App\Support\Brand::asset('logo_header_url')) }}" alt="{{ \App\Support\Brand::name() }}" height="18">
    <img class="powered-by__logo powered-by__logo--on-dark" src="{{ asset(\App\Support\Brand::asset('logo_footer_url')) }}" alt="" aria-hidden="true" height="18">
</span>
