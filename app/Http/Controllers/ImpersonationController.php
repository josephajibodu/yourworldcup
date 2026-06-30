<?php

namespace App\Http\Controllers;

use App\Auth\ImpersonationManager;
use Illuminate\Http\RedirectResponse;

class ImpersonationController extends Controller
{
    public function destroy(ImpersonationManager $impersonation): RedirectResponse
    {
        abort_unless($impersonation->isImpersonating(), 403);

        $impersonation->stop();

        return to_route('admin.users.index');
    }
}
