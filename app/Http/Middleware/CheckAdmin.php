<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string $role The required role for the route.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user->role == 'admin' || $user->role == 'super_admin' || $user->role == 'subuser') {
            return $next($request);
        }
        
        return response()->json(['error' => 'Unauthorized'], 403);
    }
}
