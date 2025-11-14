<x-global.layout>
      <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">PDF Tips & Tutorials</h1>
            <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                Learn how to work with PDFs more efficiently with our helpful guides, tips, and tutorials.
            </p>
        </div>


        <!-- Blog Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse ($blogs as $blog)
            <article class="bg-white rounded-lg shadow-sm border overflow-hidden hover:shadow-md transition-shadow">
                <img src="{{ asset('storage/'.$blog->featured_image) }}" alt="{{ $blog->title }}" class="w-full h-48 object-cover">
                <div class="p-6">
                    <div class="flex items-center mb-3">
                    <span class="text-gray-500 text-sm ml-4">
                            {{ $blog->created_at->diffForHumans() }}
                        </span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-3">
                        {{ $blog->title }}
                    </h3>
                    <a href="{{ route('blog', $blog->slug) }}" class="text-[#E5322D] hover:text-red-600 font-medium">Read More →</a>
                </div>
            </article>
            @empty
                <div class="col-span-1 md:col-span-2 lg:col-span-3 text-center">
                    <p class="text-gray-500">No blog posts available at the moment.</p>
                </div>
            @endforelse
        </div>

        <section class="mt-16">
            <livewire:front-end.newsletter />
        </section>
</x-global.layout>
