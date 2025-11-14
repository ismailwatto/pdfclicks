<form class="space-y-6" wire:submit.prevent="submit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="firstName"  class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                            <input type="text" id="firstName" wire:model="first_name" name="first_name" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#E5322D] focus:border-transparent">

                            {{-- error --}}
                            @if ($errors->has('first_name'))
                                <span class="text-red-500 text-sm mt-2">
                                    {{ $errors->first('first_name') }}
                                </span>
                            @endif
                        </div>
                        <div>
                            <label for="lastName" class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                            <input type="text" id="lastName" wire:model="last_name" name="last_name" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#E5322D] focus:border-transparent">
                            {{-- error --}}
                            @if ($errors->has('last_name'))
                                <span class="text-red-500 text-sm mt-2">
                                    {{ $errors->first('last_name') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <input type="email" id="email" name="email" wire:model="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#E5322D] focus:border-transparent">
                        {{-- error --}}
                        @if ($errors->has('email'))
                            <span class="text-red-500 text-sm mt-2">
                                {{ $errors->first('email') }}
                            </span>
                        @endif
                    </div>

                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                        <select id="subject" name="subject" wire:model="subject" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#E5322D] focus:border-transparent">
                            <option value="">Select a subject</option>
                            <option value="general">General Inquiry</option>
                            <option value="support">Technical Support</option>
                            <option value="feature">Feature Request</option>
                            <option value="bug">Bug Report</option>
                            <option value="business">Business Inquiry</option>
                        </select>
                        {{-- error --}}
                        @if ($errors->has('subject'))
                            <span class="text-red-500 text-sm mt-2">
                                {{ $errors->first('subject') }}
                            </span>
                        @endif
                    </div>

                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                        <textarea id="message" name="message" rows="6" required wire:model="message" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#E5322D] focus:border-transparent" placeholder="Tell us how we can help you..."></textarea>
                        {{-- error --}}
                        @if ($errors->has('message'))
                            <span class="text-red-500 text-sm mt-2">
                                {{ $errors->first('message') }}
                            </span>
                        @endif
                    </div>

                    <button type="submit" class="w-full bg-[#E5322D] text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-600 transition-colors">
                        Send Message
                    </button>
                    @if ($successMessage)
                        <div class="text-green-600 text-sm mt-4">
                            {{ $successMessage }}
                        </div>
                    @endif
                    @if ($errorMessage)
                        <div class="text-red-600 text-sm mt-4">
                            {{ $errorMessage }}
                        </div>
                    @endif
                </form>
