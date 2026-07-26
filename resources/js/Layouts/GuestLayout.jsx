import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';
import './GuestLayout.css';


export default function Guest({ children }) {
    return (
        <div className="guest-layout">
            <div>
                <Link href="/">
                    <ApplicationLogo className="guest-logo" />
                </Link>
            </div>

            <div className="guest-card">
                {children}
            </div>
        </div>
    );
}
