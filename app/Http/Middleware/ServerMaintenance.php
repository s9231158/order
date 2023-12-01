<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ServerMaintenance as ModelServerMaintenance;

class ServerMaintenance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $Maintenance = ModelServerMaintenance::select('maintenances')->find(1);
            if ($Maintenance['maintenances'] == true) {
                return response()->json(['err' => '伺服器維修中']);
            }
        } catch (\Throwable $e) {
            return response()->json(['err' => '伺服器維修中']);
        }
        return $next($request);
    }
}
