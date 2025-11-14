<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @if($attributes['title'])
        <title>{{ $attributes['title'] }} | {{ config('app.name') }}</title>
    @else
    <title>Every tool you need to work with PDFs in one place | {{ config('app.name') }}</title>
    @endif
    @if($attributes['description'])
        <meta name="description" content="{{ $attributes['description'] }}">
    @else
    <meta name="description" content="PDFCLICKS is a comprehensive platform offering a suite of tools for working with PDFs, including conversion, editing, and management.">
    @endif
    <meta name="author" content="PDFCLICKS Team">
    <meta name="theme-color" content="#ffffff">
    @if (isset($attributes['title']))
        <meta property="og:title" content="{{ $attributes['title'] }} | {{ config('app.name') }}">
    @else
    <meta property="og:title" content="PDFCLICKS - Every tool you need to work with PDFs in one place">
    @endif
    @if (isset($attributes['description']))
        <meta property="og:description" content="{{ $attributes['description'] }}">
    @else
    <meta property="og:description" content="PDFCLICKS is a comprehensive platform offering a suite of tools for working with PDFs, including conversion, editing,
    management.">
    @endif
    <meta property="og:image" content="/images/og-image.png">
    <meta property="og:url" content="https://pdfclicks.com">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    @if ($attributes['title'])
        <meta name="twitter:title" content="{{ $attributes['title'] }} | {{ config('app.name') }}">
    @else
    <meta name="twitter:title" content="PDFCLICKS - Every tool you need to work with PDFs in one place">
    @endif
    @if ($attributes['description'])
        <meta name="twitter:description" content="{{ $attributes['description'] }}">
    @else
    <meta name="twitter:description" content="PDFCLICKS is a comprehensive platform offering a suite of tools for working with PDFs, including conversion, editing,
management.">
    @endif
    <meta name="twitter:image" content="/images/og-image.png">
    <meta name="twitter:site" content="@pdfclicks">
    <meta name="twitter:creator" content="@pdfclicks">
    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="PDFCLICKS" />
    <link rel="manifest" href="/site.webmanifest" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @if(config('app.env') === 'production')
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5191830351225534"
     crossorigin="anonymous"></script>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-WFJ3SP62');</script>
    @endif
</head>
<body class="bg-[#F5F2F6]">
    @if(config('app.env') === 'production')
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WFJ3SP62"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif
<x-global.navbar />
     <main class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-12">
        {{ $slot }}
        </main>
<x-global.footer />
    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });

        function showTab(tabName) {
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.add('hidden'));
        
            // Remove active state from all tabs
            const tabs = document.querySelectorAll('.tab-button');
            tabs.forEach(tab => {
                tab.classList.remove('text-[#E5322D]', 'border-b-2', 'border-[#E5322D]');
                tab.classList.add('text-gray-500');
            });
        
            // Show selected tab content
            const contentToShow = document.getElementById(tabName + '-content');
            if (contentToShow) {
                contentToShow.classList.remove('hidden');
            } else {
                console.warn(`Tab content not found: ${tabName}-content`);
            }
        
            // Add active state to selected tab
            const activeTab = document.getElementById(tabName + '-tab');
            if (activeTab) {
                activeTab.classList.remove('text-gray-500');
                activeTab.classList.add('text-[#E5322D]', 'border-b-2', 'border-[#E5322D]');
            } else {
                console.warn(`Tab button not found: ${tabName}-tab`);
            }
        }

        function toggleFAQ(id) {
            const faqContent = document.getElementById(`faq-${id}`);
            const icon = document.getElementById(`icon-${id}`);

            if (faqContent.classList.contains('hidden')) {
                faqContent.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
            } else {
                faqContent.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            }
        }
    </script>
    @livewireScripts
</body>
</html>
