@php
    $siteAlerts = collect($siteAlerts ?? []);
    $utilityLinks = collect($utilityLinks ?? []);
    $utilitySocialLinks = collect($utilitySocialLinks ?? []);
    $hasUtilityBar = $utilityLinks->isNotEmpty() || $utilitySocialLinks->isNotEmpty();
@endphp

@if ($siteAlerts->isNotEmpty() || $hasUtilityBar)
    <div class="site-top-chrome" data-site-top-chrome>
        @if ($siteAlerts->isNotEmpty())
            <div class="site-alert-stack" aria-label="Site alerts">
                @foreach ($siteAlerts as $alert)
                    @php($alertUrl = $alert->publicLinkUrl())
                    <section
                        class="site-alert"
                        data-site-alert
                        data-site-alert-key="{{ $alert->dismissalKey() }}"
                    >
                        <div class="site-alert__inner">
                            <div class="site-alert__content">
                                @if (filled($alert->label))
                                    <strong class="site-alert__label">{!! \App\Support\SiteVariables::renderText($alert->label, $settings ?? null) !!}</strong>
                                @endif

                                <div class="site-alert__message">
                                    {!! \App\Support\RichContent::renderTextarea($alert->message, $settings ?? null) !!}
                                </div>

                                @if ($alertUrl && filled($alert->link_label))
                                    <a class="site-alert__link" href="{{ $alertUrl }}"{!! \App\Support\LinkAttributes::externalAttributes($alertUrl) !!}>
                                        {!! \App\Support\SiteVariables::renderText($alert->link_label, $settings ?? null) !!}
                                    </a>
                                @endif
                            </div>

                            @if ($alert->is_dismissible)
                                <button
                                    type="button"
                                    class="site-alert__dismiss"
                                    data-site-alert-dismiss
                                    aria-label="Dismiss alert"
                                >
                                    <span aria-hidden="true"></span>
                                </button>
                            @endif
                        </div>
                    </section>
                @endforeach
            </div>
        @endif

        @if ($hasUtilityBar)
            <div class="site-utility-bar" aria-label="Utility navigation">
                <nav class="site-utility-bar__links" aria-label="Utility links">
                    @foreach ($utilityLinks as $link)
                        <a
                            href="{{ data_get($link, 'url') }}"
                            @if (data_get($link, 'opens_in_new_tab', false) === true) target="_blank" rel="noreferrer" @endif
                        >
                            {!! \App\Support\SiteVariables::renderText(data_get($link, 'label'), $settings ?? null) !!}
                        </a>
                    @endforeach
                </nav>

                @if ($utilitySocialLinks->isNotEmpty())
                    <nav class="site-utility-bar__social" aria-label="Social media links">
                        @foreach ($utilitySocialLinks as $link)
                            @php($icon = $link['icon'] ?? str($link['label'])->lower()->slug()->toString())
                            <a class="site-utility-social-link site-utility-social-link--{{ str($icon)->slug() }}" href="{{ $link['url'] }}" aria-label="{{ $link['label'] }}" title="{{ $link['label'] }}" target="_blank" rel="noopener noreferrer">
                                @include('partials.social-icon', ['icon' => $icon, 'label' => $link['label']])
                                <span class="site-utility-social-link__label">{!! \App\Support\SiteVariables::renderText($link['label'], $settings ?? null) !!}</span>
                            </a>
                        @endforeach
                    </nav>
                @endif
            </div>
        @endif
    </div>
@endif
