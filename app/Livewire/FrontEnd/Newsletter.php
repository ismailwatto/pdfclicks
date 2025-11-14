<?php

declare(strict_types=1);

namespace App\Livewire\FrontEnd;

use App\Models\FormSubmissions;
use Livewire\Component;

final class Newsletter extends Component
{
    public $email;

    public $successMessage = '';

    public $errorMessage = '';

    public $formName = 'Newsletter_Subscription';

    public $formData = [];

    public $listeners = ['subscribe'];

    public function subscribe(): void
    {
        $this->validate([
            'email' => 'required|email',
        ]);

        $existingSubmission = FormSubmissions::where('form_name', $this->formName)
            ->where('email', $this->email)
            ->first();
        // Check if the email already exists in the form submissions
        if ($existingSubmission) {
            $this->successMessage = 'This email is already subscribed to our newsletter.';

            return;
        }

        try {
            // Store the form submission
            FormSubmissions::create([
                'form_name' => $this->formName,
                'email' => $this->email,
                'data' => json_encode($this->formData),
            ]);

            $this->successMessage = 'Thank you for subscribing to our newsletter!';
            $this->email = ''; // Clear the email input
        } catch (Exception) {
            $this->errorMessage = 'There was an error processing your subscription. Please try again later.';
        }
    }

    public function render()
    {
        return view('livewire.front-end.newsletter');
    }
}
