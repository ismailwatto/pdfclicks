# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PDFClick is a comprehensive PDF manipulation web application built with Laravel 12, Livewire, and CloudConvert API. The application provides users with various PDF conversion and manipulation tools including PDF to Word/Excel/PowerPoint/Images conversion, image to PDF conversion, PDF merging/splitting, and PDF compression.

## Development Guidelines

**IMPORTANT**: Before implementing any new features or making changes:
- Always reference the official **Laravel 12.x documentation**: https://laravel.com/docs/12.x
- Always reference the official **Livewire documentation**: https://livewire.laravel.com/docs/quickstart
- This project requires **PHP 8.4** - ensure all code follows PHP 8.4 standards and features

## Architecture & Key Components

### Backend Architecture
- **Laravel 12** framework with **Livewire** for reactive components (PHP 8.4 required)
- **Filament 4** admin panel for content management
- **CloudConvert API** integration for file conversions
- **Trait-based architecture** for shared functionality across PDF tools

### Core Application Structure

#### PDF Processing Actions (`app/Actions/PdfProcessing/`)
- `BasePdfAction.php` - Abstract base class for all PDF operations
- Specific action classes for each tool (compression, conversion, splitting, etc.)
- Uses traits for file validation, progress tracking, and temporary file management

#### Livewire Components (`app/Livewire/Tools/`)
- Each PDF tool has its own Livewire component
- Uses `PdfToolsBase` trait for common functionality (progress tracking, file handling, error management)
- Components handle file upload, conversion progress, and download functionality

#### Shared Traits (`app/Traits/`)
- `PdfToolsBase.php` - Common functionality for Livewire PDF tool components
- `FileValidation.php` - File type and size validation
- `TemporaryFileManagement.php` - Handles temporary file creation and cleanup
- `ProgressTracking.php` - Manages conversion progress and status updates

#### CloudConvert Integration (`app/Actions/`)
- `CloudConvertAction.php` - Main API integration class
- Handles API communication, job management, and file processing

### Frontend Architecture
- **TailwindCSS 4** for styling
- **Livewire Flux** UI components
- **Vite** for asset compilation
- Responsive design with drag-and-drop file upload

## Common Development Commands

### Development Environment
- **Start full development environment**: `composer run dev`
  - Runs Laravel server, queue worker, log viewer (Pail), and Vite concurrently
- **Laravel development server only**: `php artisan serve`
- **Asset compilation**: `npm run dev` (watch mode) or `npm run build` (production)

### Testing
- **Run all tests**: `composer test` or `php artisan test`
- **Run specific test**: `php artisan test --filter TestName`
- **Run tests with coverage**: `php artisan test --coverage`

### Code Quality
- **Code formatting**: `composer run lint` (uses Laravel Pint)
- **Code refactoring**: `composer run refactor` (uses Rector)
- **Check code style**: `vendor/bin/pint --test`

### Database
- **Run migrations**: `php artisan migrate`
- **Seed database**: `php artisan db:seed`
- **Fresh migration with seeding**: `php artisan migrate:fresh --seed`

### Artisan Commands
- **Generate keys**: `php artisan key:generate`
- **Clear caches**: `php artisan config:clear`, `php artisan cache:clear`, `php artisan view:clear`
- **Queue worker**: `php artisan queue:work` or `php artisan queue:listen --tries=1`

## Key Implementation Patterns

### PDF Tool Development
When creating new PDF tools:
1. Create a new action class extending `BasePdfAction` in `app/Actions/PdfProcessing/`
2. Create a Livewire component in `app/Livewire/Tools/` using the `PdfToolsBase` trait
   - Follow Livewire 3.x conventions: https://livewire.laravel.com/docs/quickstart
3. Create corresponding Blade view in `resources/views/livewire/tools/`
4. Add route in `routes/web.php` following Laravel 12 routing patterns: https://laravel.com/docs/12.x/routing

### File Processing Pattern
All PDF tools follow this pattern:
1. File upload and validation using `FileValidation` trait
2. Temporary file management using `TemporaryFileManagement` trait  
3. Progress tracking using `ProgressTracking` trait
4. CloudConvert API integration for conversions
5. Result handling and file download

### Error Handling
- Use `try-catch` blocks in action classes
- Log errors with context using Laravel's Log facade
- Display user-friendly error messages in Livewire components
- Clean up temporary files in error scenarios

## Environment Configuration

### Required Environment Variables
```env
CLOUDCONVERT_API_KEY=your_api_key_here
CLOUDCONVERT_SANDBOX=false
```

### File Storage
- Uploaded files: `storage/app/private/uploads/`
- Converted files: `storage/app/public/converted/`
- Temporary files: `storage/app/temp/`
- Livewire temporary files: `storage/app/livewire-tmp/`

## Testing Strategy
- **Unit tests**: Test individual action classes and methods
- **Feature tests**: Test complete user workflows and API endpoints
- **Pest framework**: Used for more expressive test syntax
- Test files located in `tests/Unit/Actions/` and `tests/Feature/`

## Admin Panel (Filament)
- Access at `/admin` route
- Manages blogs, pages, site settings, and form submissions
- Resource files in `app/Filament/Resources/`
- Custom pages in `app/Filament/Pages/`

## File Size Limits
- Default upload limit: 15MB (configurable)
- Implemented in `FileValidation` trait
- CloudConvert API has its own limits

## Security Considerations
- CSRF protection on all forms
- File type validation prevents malicious uploads
- Secure temporary file handling with automatic cleanup
- User authentication for admin features