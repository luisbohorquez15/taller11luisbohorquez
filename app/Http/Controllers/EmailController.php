<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SendGridService;

class EmailController extends Controller
{
    // Muestra la vista del formulario
    public function index()
    {
        return view('envio_email');
    }

    // Procesa la petición POST y envía el correo mediante el servicio
    public function send(Request $request, SendGridService $sendGridService)
    {
        $request->validate([
            'email' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $success = $sendGridService->sendEmail(
            $request->input('email'),
            $request->input('subject'),
            nl2br(e($request->input('message')))
        );

        if ($success) {
            return back()->with('status', 'Correo enviado con éxito a través de la API de SendGrid');
        }

        return back()->withErrors(['error' => 'No se pudo enviar el correo. Revisa tus credenciales de SendGrid.']);
    }
}
