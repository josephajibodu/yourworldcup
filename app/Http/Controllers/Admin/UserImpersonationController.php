<?php

namespace App\Http\Controllers\Admin;

use App\Auth\ImpersonationManager;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserImpersonationController extends Controller
{
    public function store(Request $request, User $user, ImpersonationManager $impersonation): RedirectResponse
    {
        abort_if($impersonation->isImpersonating(), 403);
        abort_if($request->user()->is($user), 403);
        abort_if($user->isSiteAdmin(), 403);

        $impersonation->start($request->user(), $user);

        return to_route('predict');
    }
}
