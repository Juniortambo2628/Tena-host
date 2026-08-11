import TypingEffect from '@/Components/Dashboard/TypingEffect';
import './AuthHero.css';

export default function AuthHero() {
    return (
        <div className="auth-hero">
            <div className="auth-hero__content">
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
                    Own the guest. Build the Relationship
                </p>
            </div>
        </div>
    );
}
