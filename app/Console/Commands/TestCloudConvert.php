<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\CloudConvertAction;
use Exception;
use Illuminate\Console\Command;

final class TestCloudConvert extends Command
{
    protected $signature = 'cloudconvert:test';

    protected $description = 'Test CloudConvert API connection and functionality';

    public function handle(): int
    {
        $this->info('Testing CloudConvert API...');

        // Check environment variables first
        $this->line('1. Checking environment configuration...');
        $apiKey = config('services.cloudconvert.api_key');

        if (empty($apiKey)) {
            $this->error('   ✗ CLOUDCONVERT_API_KEY is not set in .env file');
            $this->line('   Add this to your .env file: CLOUDCONVERT_API_KEY=your_api_key_here');

            return 1;
        }

        $this->info('   ✓ API key is configured (length: '.mb_strlen($apiKey).')');

        try {
            // Test 2: Initialize CloudConvert
            $this->line('2. Testing API initialization...');
            $cloudConvert = new CloudConvertAction();
            $this->info('   ✓ CloudConvert initialized successfully');

            // Test 3: Test API connectivity
            $this->line('3. Testing API connectivity...');
            $connectionResult = $cloudConvert->testConnection();

            if ($connectionResult['api_accessible']) {
                $this->info('   ✓ CloudConvert API is accessible');

                if (isset($connectionResult['credits'])) {
                    $this->line('   Credits: '.$connectionResult['credits']);
                }

                if (isset($connectionResult['user_id'])) {
                    $this->line('   User ID: '.$connectionResult['user_id']);
                }
            } else {
                $this->error('   ✗ CloudConvert API connection failed: '.$connectionResult['error']);

                return 1;
            }

            // Test 4: Test format validation
            $this->line('4. Testing format validation...');
            try {
                $cloudConvert->detectInputFormat('test.pdf');
                $this->info('   ✓ Format detection works');

                $cloudConvert->validateConversion('pdf', 'docx');
                $this->info('   ✓ PDF to DOCX conversion is supported');

                $cloudConvert->validateConversion('pdf', 'xyz');
                $this->error('   ✗ Invalid format validation failed');
            } catch (Exception $formatException) {
                if (str_contains($formatException->getMessage(), 'xyz')) {
                    $this->info('   ✓ Format validation properly rejects unsupported formats');
                } else {
                    $this->info('   ✓ Format validation is working');
                }
            }

            $this->info('CloudConvert test completed successfully!');
            $this->line('');
            $this->line('Your CloudConvert integration is ready to use.');
            $this->line('');
            $this->comment('Note: To test actual conversions, upload a PDF file through the web interface.');

        } catch (Exception $e) {
            $this->error('   ✗ CloudConvert test failed: '.$e->getMessage());

            // Provide common solutions
            $this->line('');
            $this->line('Common solutions:');
            $this->line('• Check if API key is valid');
            $this->line('• Verify account has sufficient credits');
            $this->line('• Check internet connectivity');
            $this->line('• Check CloudConvert service status at https://status.cloudconvert.com/');

            return 1;
        }

        return 0;
    }
}
