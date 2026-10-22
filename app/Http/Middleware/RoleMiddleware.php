<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect('/login');
        }

        if ($user->is_banned) {
            abort(403, 'Вы забанены.');
        }

        if (!$user->role || !in_array($user->role->name, $roles)) {
            abort(403, 'Доступ запрещён.');
        }

        return $next($request);
    }
}