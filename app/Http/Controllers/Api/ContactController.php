<?php

namespace App\\Http\\Controllers\\Api;

use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Mail;

class ContactController extends Controller
{
    /**
     * Receive a contact form submission, notify Flair Place, and acknowledge the sender.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $name = trim($validated['full_name']);
        $email = $validated['email'];
        $subject = trim($validated['subject']);
        $messageBody = trim($validated['message']);

        // Send the enquiry to Flair Place. Replying to this email will reach the visitor.
        Mail::raw(
            "A new message has been submitted through the Glam contact form.\n\n"
            . "Full name: {$name}\n"
            . "Email: {$email}\n"
            . "Subject: {$subject}\n\n"
            . "Message:\n{$messageBody}",
            function ($mail) use ($name, $email, $subject) {
                $mail->to('info@flairplace.com')
                    ->replyTo($email, $name)
                    ->subject("Contact Us: {$subject}");
            }
        );

        // Acknowledge receipt to the visitor.
        Mail::raw(
            "Hello {$name},\n\n"
            . "Thank you for contacting Flair Place. We have received your message "
            . "and our team will get back to you as soon as possible.\n\n"
            . "Your subject: {$subject}\n\n"
            . "Kind regards,\nFlair Place",
            function ($mail) use ($email) {
                $mail->to($email)
                    ->subject('We have received your message');
            }
        );

        return response()->json([
            'message' => 'Your message has been received. A confirmation email has been sent to you.',
        ]);
    }
}
