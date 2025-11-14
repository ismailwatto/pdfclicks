<x-global.layout>
    <!-- Page Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">Legal & Privacy</h1>
            <p class="text-lg text-gray-600">
                Your privacy and security are our top priorities. Learn about our policies and commitments.
            </p>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex flex-wrap justify-center mb-8 border-b border-gray-200">
            <button onclick="showTab('privacy')" id="privacy-tab" class="tab-button px-6 py-3 font-medium text-[#E5322D] border-b-2 border-[#E5322D]">
                Privacy Policy
            </button>
            <button onclick="showTab('terms')" id="terms-tab" class="tab-button px-6 py-3 font-medium text-gray-500 hover:text-gray-700">
                Terms of Service
            </button>
            <button onclick="showTab('cookies')" id="cookies-tab" class="tab-button px-6 py-3 font-medium text-gray-500 hover:text-gray-700">
                Cookie Policy
            </button>
        </div>

        <!-- Privacy Policy Tab -->
        <div id="privacy-content" class="tab-content w-full max-w-3xl mx-auto">
            <div class="bg-white rounded-lg shadow-sm border p-8 w-full max-w-3xl mx-auto">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Privacy Policy</h2>
                <div class="prose max-w-none">
                    <p class="text-gray-600 mb-4">
                        <strong>Last updated:</strong> January 1, 2025
                    </p>

                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Information We Collect</h3>
                    <p class="text-gray-600 mb-4">
                        PDFCLICKS is committed to protecting your privacy. We do not collect, store, or process your personal files. All PDF processing happens locally in your browser using client-side technology.
                    </p>

                    <h3 class="text-xl font-semibold text-gray-800 mb-4">How We Use Information</h3>
                    <ul class="list-disc list-inside text-gray-600 mb-4 space-y-2">
                        <li>We may collect anonymous usage statistics to improve our services</li>
                        <li>We use cookies for website functionality and analytics</li>
                        <li>We do not sell, rent, or share your personal information with third parties</li>
                    </ul>

                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Data Security</h3>
                    <p class="text-gray-600 mb-4">
                        Your files are processed entirely in your browser and are never uploaded to our servers. This ensures complete privacy and security of your documents.
                    </p>

                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Third-Party Services</h3>
                    <p class="text-gray-600 mb-4">
                        We may use third-party services for analytics and website functionality. These services may collect anonymous usage data in accordance with their own privacy policies.
                    </p>

                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Contact Us</h3>
                    <p class="text-gray-600">
                        If you have any questions about this Privacy Policy, please contact us at privacy@pdfclicks.com
                    </p>
                </div>
            </div>
        </div>

        <!-- Terms of Service Tab -->
        <div id="terms-content" class="tab-content hidden w-full max-w-3xl mx-auto">
            <div class="bg-white rounded-lg shadow-sm border p-8 w-full max-w-3xl mx-auto">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Terms of Service</h2>
                <div class="prose max-w-none">
                    <p class="text-gray-600 mb-4">
                        <strong>Last updated:</strong> January 1, 2025
                    </p>

                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Acceptance of Terms</h3>
                    <p class="text-gray-600 mb-4">
                        By accessing and using PDFCLICKS, you accept and agree to be bound by the terms and provision of this agreement.
                    </p>

                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Use License</h3>
                    <p class="text-gray-600 mb-4">
                        Permission is granted to use PDFCLICKS for personal and commercial purposes temporarily. This license shall automatically terminate if you violate any of these restrictions.
                    </p>

                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Disclaimer</h3>
                    <p class="text-gray-600 mb-4">
                        The materials on PDFCLICKS are provided on an 'as is' basis. PDFCLICKS makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.
                    </p>

                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Limitations</h3>
                    <p class="text-gray-600 mb-4">
                        In no event shall PDFCLICKS or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use PDFCLICKS, even if PDFCLICKS or an authorized representative has been notified orally or in writing of the possibility of such damage.
                    </p>

                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Governing Law</h3>
                    <p class="text-gray-600">
                        These terms and conditions are governed by and construed by the laws and you irrevocably submit to the exclusive jurisdiction of the courts in that state or location.
                    </p>
                </div>
            </div>
        </div>

        <!-- Cookie Policy Tab -->
        <div id="cookies-content" class="tab-content hidden w-full max-w-3xl mx-auto">
            <div class="bg-white rounded-lg shadow-sm border p-8 w-full max-w-3xl mx-auto">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Cookie Policy</h2>
                <div class="prose max-w-none">
                    <p class="text-gray-600 mb-4">
                        <strong>Last updated:</strong> January 1, 2025
                    </p>

                    <h3 class="text-xl font-semibold text-gray-800 mb-4">What Are Cookies</h3>
                    <p class="text-gray-600 mb-4">
                        Cookies are small text files that are placed on your computer by websites that you visit. They are widely used to make websites work more efficiently and provide information to website owners.
                    </p>

                    <h3 class="text-xl font-semibold text-gray-800 mb-4">How We Use Cookies</h3>
                    <ul class="list-disc list-inside text-gray-600 mb-4 space-y-2">
                        <li><strong>Essential Cookies:</strong> Required for the website to function properly</li>
                        <li><strong>Analytics Cookies:</strong> Help us understand how visitors interact with our website</li>
                        <li><strong>Functional Cookies:</strong> Remember your preferences and settings</li>
                    </ul>

                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Managing Cookies</h3>
                    <p class="text-gray-600 mb-4">
                        You can control and/or delete cookies as you wish. You can delete all cookies that are already on your computer and you can set most browsers to prevent them from being placed.
                    </p>

                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Third-Party Cookies</h3>
                    <p class="text-gray-600 mb-4">
                        We may use third-party services that place cookies on your device. These cookies are governed by the respective third parties' privacy policies.
                    </p>

                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Contact Information</h3>
                    <p class="text-gray-600">
                        If you have any questions about our use of cookies, please contact us at cookies@pdfclicks.com
                    </p>
                </div>
            </div>
        </div>
</x-global.layout>
