<x-global.layout :title="$blog->title" :description="$blog->meta_description ?? 'Read our latest blog post on PDFClick, where we share insights and updates on PDF generation and management.'">
    @section('title', $blog->title)
    @section('description', $blog->meta_description ?? 'Read our latest blog post on PDFClick, where we share insights and updates on PDF generation and management.')
     <!-- Breadcrumb -->
        <nav class="mb-8">
            <div class="flex items-center space-x-2 text-sm text-gray-600">
                <a href="{{ route('home') }}" class="hover:text-[#E5322D]">Home</a>
                <span>/</span>
                <a href="{{ route('page','blogs') }}" class="hover:text-[#E5322D]">Blog</a>
                <span>/</span>
                <span class="text-gray-800">{{ $blog->title }}</span>
            </div>
        </nav>

        <!-- Article Header -->
        <article class="max-w-4xl mx-auto">
            <header class="mb-8">
                <div class="flex items-center mb-4">
                    <span class="text-gray-500 text-sm ml-4">
                        {{ $blog->created_at->diffForHumans() }}
                    </span>
                </div>
                <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-6 leading-tight">
                    {{ $blog->title }}
                </h1>
            </header>

            <!-- Featured Image -->
            <div class="mb-8">
                <img src="{{ asset('storage/' . $blog->featured_image) }}" alt="{{ $blog->title }}" class="w-full h-64 object-cover rounded-lg shadow-md">
            </div>

            <!-- Article Content -->
            <div class="prose prose-lg max-w-none blog-post-content">
                {!! $blog->content !!}
            </div>

        </article>
</x-global.layout>
