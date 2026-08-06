import { Link } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';

export default function AuthHero() {
    return (
        <div className="hidden lg:flex lg:w-1/2 relative overflow-hidden items-center justify-center">
            <div
                className="absolute inset-0 bg-cover bg-center"
                style={{
                    backgroundImage: `url('https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1200&q=80')`,
                }}
            />
            <div className="absolute inset-0 bg-gradient-to-br from-black/80 via-black/60 to-[#FFD300]/20" />
            <div className="relative z-10 text-center px-12">
                <Link href="/" className="inline-block mb-8">
                    <ApplicationLogo className="h-16 w-auto mx-auto" />
                </Link>
                <h1 className="text-4xl font-black text-white mb-4 leading-tight">
                    Welcome to <span className="text-[#FFD300]">Tena</span>
                </h1>
                <p className="text-lg text-white/60 font-medium">
                    Smart WiFi management for modern hospitality
                </p>
            </div>
        </div>
    );
}
