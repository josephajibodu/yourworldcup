import type { ReactNode } from 'react';
import { ImpersonationBanner } from '@/components/impersonation-banner';

export default function RootLayout({ children }: { children: ReactNode }) {
    return (
        <>
            <ImpersonationBanner />
            {children}
        </>
    );
}
