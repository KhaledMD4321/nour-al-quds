<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBusinessUnit
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && ! $user->isSuperAdmin() && ! $user->business_unit_id) {
            abort(403, 'المستخدم غير مرتبط بوحدة تشغيلية');
        }

        return $next($request);
    }
}
