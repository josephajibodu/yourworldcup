import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 96 96"
            role="img"
            aria-label="yourworldcup"
        >
            <rect width="96" height="96" rx="24" fill="#0A0A0B" />
            <g
                fill="none"
                stroke="#F7F5F0"
                strokeWidth="5.5"
                strokeLinecap="round"
            >
                <path d="M22 28C44 28 51 48 58 48" />
                <path d="M22 48H58" />
                <path d="M22 68C44 68 51 48 58 48" />
            </g>
            <circle cx="67" cy="48" r="11" fill="#E9A721" />
        </svg>
    );
}
