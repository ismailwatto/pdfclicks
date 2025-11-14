 <!-- Newsletter Section -->
        <section class="bg-gradient-to-r from-[#E5322D] to-red-500 rounded-2xl p-12 mb-20 text-center">
            <div class="max-w-2xl mx-auto">
                <h2 class="text-3xl font-bold text-white mb-4">Stay Updated with PDF Tips & Tools</h2>
                <p class="text-blue-100 mb-8 text-lg">
                    Get the latest PDF productivity tips, new feature announcements, and exclusive offers delivered to your inbox.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 max-w-md mx-auto">
                    <input
                        type="email"
                        wire:model="email"
                        wire:keydown.enter="subscribe"
                        id="newsletter-email"
                        placeholder="Enter your email address"
                        class="flex-1 px-4 py-3 rounded-lg border-0 focus:ring-2 focus:ring-white focus:outline-none bg-white text-gray-800 placeholder:text-gray-400"
                    >
                    <button class="bg-white text-[#E5322D] px-6 py-3 cursor-pointer rounded-lg font-semibold hover:bg-gray-100 transition-colors" wire:click="subscribe">
                        Subscribe
                    </button>
                </div>
                <div class="mt-4 ml-[18%] text-left text-white">
                    @if ($errors->has('email'))
                        <span class="text-light-300 text-sm mt-2">
                            {{ $errors->first('email') }}
                        </span>
                    @endif

                    @if ($successMessage)
                        <span class="text-light-300 text-sm mt-2">
                            {{ $successMessage }}
                        </span>
                    @endif
                </div>
                <p class="text-blue-100 text-sm mt-4">
                    No spam, unsubscribe at any time. We respect your privacy.
                </p>
            </div>
        </section>
