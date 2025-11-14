
    <!-- Header -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-50">
        <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="text-2xl font-bold text-gray-800">
                        <img src="{{ asset('assets/pdfclicks-logo.png') }}" alt="PDF Clicks" width="160" height="25" loading="lazy" decoding="async" fetchpriority="auto" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <nav class="hidden md:flex items-center space-x-8">
                    {{-- if current route than show red color --}}

                    <a href="{{ route('home') }}" class="text-gray-700 font-medium transition-colors" wire:current="text-[#E5322D]">Home</a>
                    <a href="{{ route('page', 'tools') }}" class="text-gray-700 hover:text-[#E5322D] font-medium transition-colors">Tools</a>
                    <a href="{{ route('page', 'faqs') }}" class="text-gray-700 hover:text-[#E5322D] font-medium transition-colors">FAQs</a>
                    <a href="{{ route('page', 'blogs') }}" class="text-gray-700 hover:text-[#E5322D] font-medium transition-colors">Blogs</a>
                    <a href="{{ route('page', 'privacy-policy') }}" class="text-gray-700 hover:text-[#E5322D] font-medium transition-colors">Legal & Privacy</a>
                    <a href="{{ route('page', 'contact') }}" class="text-gray-700 hover:text-[#E5322D] font-medium transition-colors">Contact Us</a>
                </nav>

                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button id="mobile-menu-button" class="text-gray-700 hover:text-[#E5322D] p-2">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile menu -->
            <div id="mobile-menu" class="md:hidden hidden border-t border-gray-200 py-4">
                <div class="flex flex-col space-y-4">
                    <a href="{{ route('home') }}" class="text-[#E5322D] font-medium">Home</a>
                    <a href="{{ route('page', 'tools') }}" class="text-gray-700 hover:text-[#E5322D] font-medium">Tools</a>
                    <a href="{{ route('page', 'faqs') }}" class="text-gray-700 hover:text-[#E5322D] font-medium">FAQs</a>
                    <a href="{{ route('page', 'blogs') }}" class="text-gray-700 hover:text-[#E5322D] font-medium">Blogs</a>
                    <a href="{{ route('page', 'privacy-policy') }}" class="text-gray-700 hover:text-[#E5322D] font-medium">Legal & Privacy</a>
                    <a href="{{ route('page', 'contact') }}" class="text-gray-700 hover:text-[#E5322D] font-medium">Contact Us</a>
                </div>
            </div>
        </div>
    </header>
