<x-global.layout>
     <!-- Page Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">Frequently Asked Questions</h1>
            <p class="text-lg text-gray-600">
                Find answers to common questions about our PDF tools and services.
            </p>
        </div>

        <!-- FAQ Accordion -->
        <div class="space-y-4 w-full max-w-3xl mx-auto">
            <!-- FAQ Item 1 -->
            <div class="bg-white rounded-lg shadow-sm border">
                <button class="faq-button w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50" onclick="toggleFAQ(1)">
                    <span class="font-semibold text-gray-800">Are your PDF tools really free?</span>
                    <svg id="icon-1" class="h-5 w-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="faq-1" class="hidden px-6 pb-4">
                    <p class="text-gray-600">Yes, all our PDF tools are completely free to use. There are no hidden charges, subscription fees, or premium features. You can use all tools unlimited times without any restrictions.</p>
                </div>
            </div>

            <!-- FAQ Item 2 -->
            <div class="bg-white rounded-lg shadow-sm border">
                <button class="faq-button w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50" onclick="toggleFAQ(2)">
                    <span class="font-semibold text-gray-800">Is my data secure when using your tools?</span>
                    <svg id="icon-2" class="h-5 w-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="faq-2" class="hidden px-6 pb-4">
                    <p class="text-gray-600">Absolutely. We take data security very seriously. All file processing happens in your browser using client-side technology. Your files are never uploaded to our servers, ensuring complete privacy and security.</p>
                </div>
            </div>

            <!-- FAQ Item 3 -->
            <div class="bg-white rounded-lg shadow-sm border">
                <button class="faq-button w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50" onclick="toggleFAQ(3)">
                    <span class="font-semibold text-gray-800">What file formats do you support?</span>
                    <svg id="icon-3" class="h-5 w-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="faq-3" class="hidden px-6 pb-4">
                    <p class="text-gray-600">We support PDF as the primary format, along with conversions to and from Word (DOC, DOCX), PowerPoint (PPT, PPTX), Excel (XLS, XLSX), JPG, PNG, and other common formats.</p>
                </div>
            </div>

            <!-- FAQ Item 4 -->
            <div class="bg-white rounded-lg shadow-sm border">
                <button class="faq-button w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50" onclick="toggleFAQ(4)">
                    <span class="font-semibold text-gray-800">Is there a file size limit?</span>
                    <svg id="icon-4" class="h-5 w-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="faq-4" class="hidden px-6 pb-4">
                    <p class="text-gray-600">For optimal performance, we recommend files under 100MB. However, our tools can handle larger files depending on your device's capabilities and internet connection.</p>
                </div>
            </div>

            <!-- FAQ Item 5 -->
            <div class="bg-white rounded-lg shadow-sm border">
                <button class="faq-button w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50" onclick="toggleFAQ(5)">
                    <span class="font-semibold text-gray-800">Do I need to create an account?</span>
                    <svg id="icon-5" class="h-5 w-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="faq-5" class="hidden px-6 pb-4">
                    <p class="text-gray-600">No account creation is required. You can use all our PDF tools immediately without signing up, providing email addresses, or any registration process.</p>
                </div>
            </div>

            <!-- FAQ Item 6 -->
            <div class="bg-white rounded-lg shadow-sm border">
                <button class="faq-button w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50" onclick="toggleFAQ(6)">
                    <span class="font-semibold text-gray-800">Can I use these tools on mobile devices?</span>
                    <svg id="icon-6" class="h-5 w-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="faq-6" class="hidden px-6 pb-4">
                    <p class="text-gray-600">Yes, our tools are fully responsive and work on all devices including smartphones, tablets, laptops, and desktop computers. No app download required.</p>
                </div>
            </div>

            <!-- FAQ Item 7 -->
            <div class="bg-white rounded-lg shadow-sm border">
                <button class="faq-button w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50" onclick="toggleFAQ(7)">
                    <span class="font-semibold text-gray-800">How accurate are the PDF conversions?</span>
                    <svg id="icon-7" class="h-5 w-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="faq-7" class="hidden px-6 pb-4">
                    <p class="text-gray-600">Our conversion tools maintain high accuracy, preserving formatting, fonts, and layout as much as possible. Complex documents may require minor adjustments, but most conversions are nearly 100% accurate.</p>
                </div>
            </div>

            <!-- FAQ Item 8 -->
            <div class="bg-white rounded-lg shadow-sm border">
                <button class="faq-button w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50" onclick="toggleFAQ(8)">
                    <span class="font-semibold text-gray-800">What browsers are supported?</span>
                    <svg id="icon-8" class="h-5 w-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="faq-8" class="hidden px-6 pb-4">
                    <p class="text-gray-600">Our tools work on all modern browsers including Chrome, Firefox, Safari, Edge, and Opera. We recommend using the latest version of your browser for the best experience.</p>
                </div>
            </div>
        </div>

        <!-- Contact Section -->
        <div class="mt-16 text-center bg-blue-50 rounded-lg p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Still have questions?</h2>
            <p class="text-gray-600 mb-6">
                Can't find the answer you're looking for? Our support team is here to help.
            </p>
            <a href="{{ route('page', 'contact') }}" class="inline-block bg-[#E5322D] text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-600 transition-colors">
                Contact Support
            </a>
        </div>
</x-global.layout>
