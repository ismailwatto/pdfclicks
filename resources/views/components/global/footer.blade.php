    <!-- Footer -->
    <footer class="bg-gray-800 text-white">
        <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div class="col-span-1 md:col-span-2">
                    <div class="text-2xl font-bold mb-4">
                        <span class="text-[#E5322D]">PDF</span>CLICKS
                    </div>
                    <p class="text-gray-300 mb-6 max-w-md">
                        The most complete PDF solution with over 25 tools to work with digital documents.
                        All tools are 100% free and easy to use.
                    </p>
                    <div class="flex space-x-4 items-center">
                        <a href="https://www.linkedin.com/in/pdf-ckicks-260899375/" target="_blank" class="text-gray-400 hover:text-white">
                            <svg width="40px" height="40px" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" fill="none"><path fill="#0A66C2" d="M12.225 12.225h-1.778V9.44c0-.664-.012-1.519-.925-1.519-.926 0-1.068.724-1.068 1.47v2.834H6.676V6.498h1.707v.783h.024c.348-.594.996-.95 1.684-.925 1.802 0 2.135 1.185 2.135 2.728l-.001 3.14zM4.67 5.715a1.037 1.037 0 01-1.032-1.031c0-.566.466-1.032 1.032-1.032.566 0 1.031.466 1.032 1.032 0 .566-.466 1.032-1.032 1.032zm.889 6.51h-1.78V6.498h1.78v5.727zM13.11 2H2.885A.88.88 0 002 2.866v10.268a.88.88 0 00.885.866h10.226a.882.882 0 00.889-.866V2.865a.88.88 0 00-.889-.864z"/></svg>
                        </a>
                        <a href="https://www.facebook.com/profile.php?id=61578144521830" target="_blank" class="text-gray-400 hover:text-white">
                            <svg fill="#0866fe" width="40px" height="40px"  viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M12 2.03998C6.5 2.03998 2 6.52998 2 12.06C2 17.06 5.66 21.21 10.44 21.96V14.96H7.9V12.06H10.44V9.84998C10.44 7.33998 11.93 5.95998 14.22 5.95998C15.31 5.95998 16.45 6.14998 16.45 6.14998V8.61998H15.19C13.95 8.61998 13.56 9.38998 13.56 10.18V12.06H16.34L15.89 14.96H13.56V21.96C15.9164 21.5878 18.0622 20.3855 19.6099 18.57C21.1576 16.7546 22.0054 14.4456 22 12.06C22 6.52998 17.5 2.03998 12 2.03998Z"></path> </g></svg>
                        </a>
                        <a href="https://x.com/PDFCLICKS" target="_blank" class="text-gray-400 hover:text-white">
                            <svg width="25px" height="25px" viewBox="0 0 1200 1227" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M714.163 519.284L1160.89 0H1055.03L667.137 450.887L357.328 0H0L468.492 681.821L0 1226.37H105.866L515.491 750.218L842.672 1226.37H1200L714.137 519.284H714.163ZM569.165 687.828L521.697 619.934L144.011 79.6944H306.615L611.412 515.685L658.88 583.579L1055.08 1150.3H892.476L569.165 687.854V687.828Z" fill="white"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2 text-gray-300">
                        <li><a href="{{ route('page', 'tools') }}" class="hover:text-white">All Tools</a></li>
                        <li><a href="{{ route('page', 'faqs') }}" class="hover:text-white">FAQs</a></li>
                        <li><a href="{{ route('page', 'blogs') }}" class="hover:text-white">Blog</a></li>
                        <li><a href="{{ route('page', 'contact') }}" class="hover:text-white">Contact</a></li>
                        <li><a href="{{ route('page', 'privacy-policy') }}" class="hover:text-white">Privacy Policy</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h3 class="font-semibold mb-4">Support</h3>
                    <ul class="space-y-2 text-gray-300">
                        <li><a href="{{ route('page', 'contact') }}" class="hover:text-white">Help Center</a></li>
                        <li><a href="{{ route('page','contact') }}" class="hover:text-white">Contact Us</a></li>
                        <li><a href="{{ route('page', 'privacy-policy') }}" class="hover:text-white">Terms of Service</a></li>
                        <li><a href="{{ route('page', 'privacy-policy') }}" class="hover:text-white">Cookie Policy</a></li>
                        <li><a href="{{ route('page', 'faqs') }}" class="hover:text-white">FAQ</a></li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Footer -->
            <div class="border-t border-gray-700 mt-8 pt-8 text-center">
                <p class="text-gray-400 text-sm">
                    © {{ date('Y') }} PDFCLICKS. All rights reserved.
                </p>
            </div>
        </div>
    </footer>


