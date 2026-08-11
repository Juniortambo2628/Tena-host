import { Link } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import TypingEffect from '@/Components/Dashboard/TypingEffect';
import './AuthHero.css';

export default function AuthHero() {
    return (
        <div className="auth-hero">
            <div className="auth-hero__content">
                <Link href="/" className="auth-hero__logo-link">
                    <ApplicationLogo className="h-14 w-auto" />
                </Link>
                <div className="auth-hero__typing-wrapper">
                    <TypingEffect
                        text="Tena...na Tena"
                        speed={100}
                        deleteSpeed={60}
                        pause={2000}
                        className="auth-hero__typing"
                    />
                </div>
                <p className="auth-hero__tagline">
                    Smart WiFi management for modern hospitality
                </p>
            </div>
        </div>
    );
}
