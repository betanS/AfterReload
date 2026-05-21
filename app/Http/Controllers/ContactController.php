<?php

namespace App\Http\Controllers;

use App\Mail\ContactReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    private const RECIPIENT = 'alexmagnusxd@gmail.com';

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email', 'max:150'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        try {
            Mail::to(self::RECIPIENT)->send(new ContactReport(
                senderName:    $data['name'],
                senderEmail:   $data['email'],
                reportSubject: $data['subject'],
                body:          $data['message'],
            ));
        } catch (\Throwable) {
            // fallo silencioso — el usuario ve éxito igualmente
        }

        return response()->json(['ok' => true]);
    }
}
