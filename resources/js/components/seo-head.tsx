import { Head, usePage } from '@inertiajs/react';

interface SeoHeadProps {
    title: string;
    description: string;
    path: string;
    image?: string;
    robots?: string;
    type?: 'website' | 'article';
}

function absoluteUrl(appUrl: string, path: string): string {
    const base = appUrl.replace(/\/$/, '');
    const normalizedPath = path.startsWith('/') ? path : `/${path}`;

    return `${base}${normalizedPath}`;
}

export function SeoHead({
    title,
    description,
    path,
    image = '/apple-touch-icon.png',
    robots,
    type = 'website',
}: SeoHeadProps) {
    const { name: appName, appUrl } = usePage().props;
    const url = absoluteUrl(appUrl, path);
    const imageUrl = image.startsWith('http')
        ? image
        : absoluteUrl(appUrl, image);
    const fullTitle = `${title} - ${appName}`;

    return (
        <Head title={title}>
            <meta
                head-key="description"
                name="description"
                content={description}
            />
            {robots !== undefined && (
                <meta head-key="robots" name="robots" content={robots} />
            )}
            <link head-key="canonical" rel="canonical" href={url} />
            <meta head-key="og:type" property="og:type" content={type} />
            <meta
                head-key="og:site_name"
                property="og:site_name"
                content={appName}
            />
            <meta
                head-key="og:title"
                property="og:title"
                content={fullTitle}
            />
            <meta
                head-key="og:description"
                property="og:description"
                content={description}
            />
            <meta head-key="og:url" property="og:url" content={url} />
            <meta head-key="og:image" property="og:image" content={imageUrl} />
            <meta
                head-key="twitter:card"
                name="twitter:card"
                content="summary"
            />
            <meta
                head-key="twitter:title"
                name="twitter:title"
                content={fullTitle}
            />
            <meta
                head-key="twitter:description"
                name="twitter:description"
                content={description}
            />
            <meta
                head-key="twitter:image"
                name="twitter:image"
                content={imageUrl}
            />
        </Head>
    );
}
