<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckInterpreter
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
        if ($user->role == 'staff_interpreter' || $user->role == 'interpreter') {
            return $next($request);
        }
        
        return response()->json(['error' => 'Unauthorized'], 403);
    }
}
