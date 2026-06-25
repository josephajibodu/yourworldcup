import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { formatTwitterHandle } from '@/lib/twitter-handle';
import type { AdminUserDetail } from '@/types/admin';

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function DetailRow({
    label,
    children,
}: {
    label: string;
    children: ReactNode;
}) {
    return (
        <div className="flex items-start justify-between gap-4 border-b border-border/60 py-3 last:border-b-0">
            <dt className="shrink-0 text-sm text-muted-foreground">{label}</dt>
            <dd className="text-right text-sm font-medium">{children}</dd>
        </div>
    );
}

export function UserViewDialogContent({ user }: { user: AdminUserDetail }) {
    return (
        <dl className="divide-y divide-border/60">
            <DetailRow label="X handle">
                {formatTwitterHandle(user.name)}
            </DetailRow>
            <DetailRow label="Email">{user.email}</DetailRow>
            <DetailRow label="Role">
                {user.isSiteAdmin ? (
                    <Badge variant="gold">Site admin</Badge>
                ) : (
                    <Badge variant="secondary">Player</Badge>
                )}
            </DetailRow>
            <DetailRow label="Email verified">
                {user.emailVerifiedAt ? (
                    <Badge variant="secondary">Verified</Badge>
                ) : (
                    <Badge variant="outline">Unverified</Badge>
                )}
            </DetailRow>
            <DetailRow label="Two-factor auth">
                {user.twoFactorEnabled ? 'Enabled' : 'Disabled'}
            </DetailRow>
            <DetailRow label="Referral code">
                <span className="font-mono">{user.referralCode ?? '—'}</span>
            </DetailRow>
            <DetailRow label="Referred by">
                {user.referrer ? (
                    <span>
                        {formatTwitterHandle(user.referrer.name)}
                        <span className="mt-0.5 block text-xs font-normal text-muted-foreground">
                            {user.referrer.email}
                        </span>
                    </span>
                ) : (
                    '—'
                )}
            </DetailRow>
            <DetailRow label="Predictions">
                {user.predictionsCount}
            </DetailRow>
            <DetailRow label="Referrals">{user.referralsCount}</DetailRow>
            <DetailRow label="Joined">{formatDate(user.createdAt)}</DetailRow>
            <DetailRow label="Last updated">
                {formatDate(user.updatedAt)}
            </DetailRow>
        </dl>
    );
}
