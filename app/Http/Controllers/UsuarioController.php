<?php

namespace App\Http\Controllers;

use App\Enums\TipoNotificacion;
use App\Http\Resources\InsigniaObtenidaResource;
use App\Http\Resources\PerfilPublicoResource;
use App\Models\Reporte;
use App\Models\User;
use App\Services\Notificador;
use App\Support\ArchivosPublicos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UsuarioController extends Controller
{
    /**
     * Perfil de un usuario. El propio usuario recibe su perfil completo;
     * el resto solo los datos públicos (sin correo ni teléfono).
     */
    public function show(User $usuario): JsonResponse|PerfilPublicoResource
    {
        if (auth('api')->id() === $usuario->id) {
            return response()->json($usuario);
        }

        return new PerfilPublicoResource($usuario);
    }

    /** Insignias obtenidas por un usuario, las más recientes primero */
    public function insignias(User $usuario): AnonymousResourceCollection
    {
        $insignias = $usuario->insignias()->orderByPivot('fecha_obtencion', 'desc')->get();

        return InsigniaObtenidaResource::collection($insignias);
    }

    /** Sube o reemplaza la foto de perfil del usuario autenticado */
    public function subirAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $url = ArchivosPublicos::guardar($request->file('avatar'), 'avatars', $user->url_avatar);
        $user->forceFill(['url_avatar' => $url])->save();

        return response()->json(['url' => $url, 'user' => $user]);
    }

    /** Denuncia a un usuario ante los administradores */
    public function denunciar(Request $request, User $usuario, Notificador $notificador): JsonResponse
    {
        $validated = $request->validate([
            'motivo' => ['required', 'string', 'max:1000'],
            'id_reporte' => ['nullable', 'uuid', 'exists:reportes,id'],
        ]);

        $denunciante = $request->user();
        abort_if($denunciante->id === $usuario->id, 422, 'No puedes denunciarte a ti mismo.');

        $reporte = isset($validated['id_reporte']) ? Reporte::find($validated['id_reporte']) : null;
        $idReporte = $reporte?->id ?? 'N/A';
        $notificador->notificar(
            $notificador->idsAdministradores(),
            TipoNotificacion::AlertaSistema,
            'Denuncia de incidencia / usuario',
            "{$denunciante->nombre_completo} ha reportado al usuario {$usuario->nombre_completo} (Motivo: {$validated['motivo']}). ID Reporte: {$idReporte}",
            $reporte,
        );

        return response()->json(['message' => 'Denuncia enviada a la administración.']);
    }
}
