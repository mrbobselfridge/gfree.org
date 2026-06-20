@php($siteDesignCss = $settings?->publicDesignCss())

@if (filled($siteDesignCss))
    <style>
{!! $siteDesignCss !!}
    </style>
@endif
