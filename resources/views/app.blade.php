<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Resolve the theme before first paint to avoid a flash: the stored
         choice wins, otherwise the OS preference. Keep in sync with
         ThemeToggle.jsx. --}}
    <script>
      try {
        document.documentElement.dataset.theme =
          localStorage.getItem('theme') ||
          (window.matchMedia('(prefers-color-scheme: dark)').matches
            ? 'padiushdark'
            : 'padiushlight');
      } catch (e) {}
    </script>
    @if (config('padiush.analytics.umami_src') && config('padiush.analytics.umami_website_id'))
      {{-- Self-hosted Umami: no cookies, no cross-site tracking. It follows
           Inertia's pushState navigations on its own, so no manual pageview
           calls are needed. Honours Do Not Track. --}}
      <script
        defer
        src="{{ config('padiush.analytics.umami_src') }}"
        data-website-id="{{ config('padiush.analytics.umami_website_id') }}"
        data-do-not-track="true"
      ></script>
    @endif
    @routes
    @viteReactRefresh
    @vite('resources/js/app.jsx')
    @inertiaHead
  </head>
  <body>
    @inertia
  </body>
</html>