<?php

declare(strict_types=1);

namespace App\Livewire\FrontEnd;

use App\Models\FormSubmissions;
use Livewire\Component;

final class ContactUsForm extends Component
{
    public $first_name;

    public $last_name;

    public $email;

    public $subject;

    public $message;

    public $successMessage = '';

    public $errorMessage = '';

    public $formName = 'Contact_Us_Form';

    public $formData = [];
    // public $listeners = ['submit'];

    public function submit(): void
    {
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
        ]);

        try {
            // Store the form submission
            FormSubmissions::create([
                'form_name' => $this->formName,
                'email' => $this->email,
                'data' => json_encode([
                    'first_name' => $this->first_name,
                    'last_name' => $this->last_name,
                    'subject' => $this->subject,
                    'message' => $this->message,
                ]),
            ]);

            $this->successMessage = 'Thank you for contacting us! We will get back to you soon.';
            $this->reset(['first_name', 'last_name', 'email', 'subject', 'message']); // Clear the form inputs
        } catch (Exception) {
            $this->errorMessage = 'There was an error processing your request. Please try again later.';
        }
    }

    public function render()
    {
        return view('livewire.front-end.contact-us-form');
    }
}
