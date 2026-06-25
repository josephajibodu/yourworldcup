<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUserUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('admin/users/index', $this->pageProps($request));
    }

    public function show(Request $request, User $user): Response
    {
        return Inertia::render('admin/users/index', [
            ...$this->pageProps($request),
            'selectedUser' => $this->formatUserDetail($user),
        ]);
    }

    public function update(AdminUserUpdateRequest $request, User $user): RedirectResponse
    {
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('admin.users.index', $request->only('search', 'page'));
    }

    /**
     * @return array<string, mixed>
     */
    private function pageProps(Request $request): array
    {
        $search = $request->string('search')->trim()->toString();

        $users = User::query()
            ->withCount(['predictions', 'referralsGiven'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(50)
            ->withQueryString()
            ->through(fn (User $user): array => $this->formatUserSummary($user));

        return [
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
            'selectedUser' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatUserSummary(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'emailVerifiedAt' => $user->email_verified_at?->toISOString(),
            'isSiteAdmin' => $user->isSiteAdmin(),
            'predictionsCount' => $user->predictions_count,
            'referralsCount' => $user->referrals_given_count,
            'createdAt' => $user->created_at->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatUserDetail(User $user): array
    {
        $user->load([
            'referrer:id,name,email',
        ]);
        $user->loadCount(['predictions', 'referralsGiven']);

        return [
            ...$this->formatUserSummary($user),
            'twoFactorEnabled' => $user->two_factor_confirmed_at !== null,
            'referralCode' => $user->referral_code,
            'referrer' => $user->referrer === null ? null : [
                'id' => $user->referrer->id,
                'name' => $user->referrer->name,
                'email' => $user->referrer->email,
            ],
            'updatedAt' => $user->updated_at->toISOString(),
        ];
    }
}
