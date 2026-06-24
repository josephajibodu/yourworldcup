import type { Auth } from '@/types/auth';
import type { PredictionsConfig } from '@/types/predictions';
import type { TurnstileConfig } from '@/types/turnstile';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            appUrl: string;
            auth: Auth;
            sidebarOpen: boolean;
            turnstile: TurnstileConfig;
            predictions: PredictionsConfig;
            [key: string]: unknown;
        };
    }
}
