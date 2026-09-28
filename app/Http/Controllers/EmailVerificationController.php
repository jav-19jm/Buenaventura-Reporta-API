<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * Enlace del correo de confirmación (URL firmada y con expiración).
     * Se abre desde el navegador, así que siempre redirige al login del frontend
     * indicando el resultado en ?verificado=ok|invalido.
     */
    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::find($id);

        $valido = $request->hasValidSignature()
            && $user !== null
            && hash_equals(sha1($user->getEmailForVerification()), $hash);

        if (! $valido) {
            return $this->redirigirAlLogin('invalido');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return $this->redirigirAlLogin('ok');
    }

    /**
     * Reenvía el correo de confirmación. La respuesta es la misma exista o no
     * la cuenta, para no revelar qué correos están registrados.
     */
    public function resend(Request $request): JsonResponse
    {
        $validated = $request->validate(['email' => ['required', 'email']], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
        ]);

        $user = User::where('email', mb_strtolower(trim($validated['email'])))->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json([
            'message' => 'Si la cuenta existe y no ha sido verificada, te enviamos un nuevo correo de confirmación.',
        ]);
    }

    private function redirigirAlLogin(string $resultado): RedirectResponse
    {
        return redirect()->away(rtrim(config('app.frontend_url'), '/').'/login?verificado='.$resultado);
    }
}
