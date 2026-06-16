import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import InputError from '@/components/input-error';

type TurnstileProps = {
    enabled: boolean;
    siteKey: string | null;
};

type TurnstileRenderOptions = {
    sitekey: string;
    callback: (token: string) => void;
    'expired-callback': () => void;
    'error-callback': () => void;
};

declare global {
    interface Window {
        turnstile?: {
            render: (
                element: HTMLElement,
                options: TurnstileRenderOptions,
            ) => string;
            remove: (widgetId: string) => void;
        };
        onTurnstileLoad?: () => void;
    }
}

const SCRIPT_ID = 'cloudflare-turnstile-script';

function loadTurnstileScript(onLoad: () => void): void {
    if (window.turnstile) {
        onLoad();

        return;
    }

    window.onTurnstileLoad = onLoad;

    if (document.getElementById(SCRIPT_ID)) {
        return;
    }

    const script = document.createElement('script');
    script.id = SCRIPT_ID;
    script.src =
        'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=onTurnstileLoad';
    script.async = true;
    script.defer = true;
    document.head.appendChild(script);
}

type Props = {
    error?: string;
    onTokenChange?: (token: string) => void;
};

export default function TurnstileWidget({ error, onTokenChange }: Props) {
    const { turnstile } = usePage().props as { turnstile: TurnstileProps };
    const containerRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);
    const widgetIdRef = useRef<string | null>(null);

    useEffect(() => {
        if (!turnstile.enabled || !turnstile.siteKey || !containerRef.current) {
            return;
        }

        const renderWidget = () => {
            if (!containerRef.current || !turnstile.siteKey || !window.turnstile) {
                return;
            }

            if (widgetIdRef.current) {
                window.turnstile.remove(widgetIdRef.current);
            }

            widgetIdRef.current = window.turnstile.render(containerRef.current, {
                sitekey: turnstile.siteKey,
                callback: (token: string) => {
                    if (inputRef.current) {
                        inputRef.current.value = token;
                    }

                    onTokenChange?.(token);
                },
                'expired-callback': () => {
                    if (inputRef.current) {
                        inputRef.current.value = '';
                    }

                    onTokenChange?.('');
                },
                'error-callback': () => {
                    if (inputRef.current) {
                        inputRef.current.value = '';
                    }

                    onTokenChange?.('');
                },
            });
        };

        loadTurnstileScript(renderWidget);

        return () => {
            if (widgetIdRef.current && window.turnstile) {
                window.turnstile.remove(widgetIdRef.current);
                widgetIdRef.current = null;
            }
        };
    }, [onTokenChange, turnstile.enabled, turnstile.siteKey]);

    if (!turnstile.enabled || !turnstile.siteKey) {
        return null;
    }

    return (
        <div className="grid gap-2">
            <div ref={containerRef} />
            <input
                ref={inputRef}
                type="hidden"
                name="cf-turnstile-response"
                defaultValue=""
            />
            <InputError message={error} />
        </div>
    );
}
