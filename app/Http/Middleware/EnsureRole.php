<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;


class EnsureRole
{
public function handle(Request $request, Closure $next, ...$roles)
{
if (!$request->user() || !$request->user()->hasAnyRole($roles)) {
abort(403);
}
return $next($request);
}
}