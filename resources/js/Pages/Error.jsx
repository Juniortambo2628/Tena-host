import React from 'react';
import { Link } from '@inertiajs/react';

export default function ErrorPage({ status }) {
    const titles = {
        404: 'Page Not Found',
        403: 'Access Denied',
        405: 'Method Not Allowed',
        419: 'Session Expired',
        429: 'Too Many Requests',
        500: 'Server Error',
        503: 'Service Unavailable',
    };

    const descriptions = {
        404: 'The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.',
        403: 'You do not have permission to access this page.',
        405: 'The request method is not supported for this page.',
        419: 'This page expired due to inactivity. Please refresh and try again.',
        429: 'Too many requests. Please slow down and try again later.',
        500: 'Something went wrong on our end. Our team has been notified.',
        503: 'We are performing maintenance. Please check back soon.',
    };

    const title = titles[status] || 'Error';
    const description = descriptions[status] || 'An unexpected error occurred.';

    return (
        <div className="min-h-screen bg-white flex items-center justify-center px-4">
            <div className="max-w-lg w-full text-center">
                <div className="mb-8">
                    <div className="w-20 h-20 bg-[#FFD300]/20 rounded-3xl flex items-center justify-center mx-auto mb-6">
                        <span className="text-4xl font-bold text-[#FFD300]">{status}</span>
                    </div>
                    <h1 className="text-3xl font-bold tracking-tight mb-3" style={{ letterSpacing: '-0.5px' }}>{title}</h1>
                    <p className="text-gray-500 text-lg leading-relaxed">{description}</p>
                </div>

                <div className="flex items-center justify-center gap-4">
                    <Link
                        href="/"
                        className="bg-[#1b1b1b] text-white px-8 py-3 rounded-xl font-bold hover:bg-black transition-all hover:shadow-lg"
                    >
                        Back to Home
                    </Link>
                    <button
                        onClick={() => window.location.reload()}
                        className="text-gray-500 hover:text-black font-medium transition-colors px-6 py-3"
                    >
                        Try Again
                    </button>
                </div>

                <div className="mt-12 pt-8 border-t border-gray-100">
                    <p className="text-xs text-gray-400 uppercase tracking-widest font-bold">
                        Tena — Built by Superhosts for Superhosts
                    </p>
                </div>
            </div>
        </div>
    );
}
