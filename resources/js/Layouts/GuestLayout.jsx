import BrandLogo from '@/Components/BrandLogo';
import { Link } from '@inertiajs/react';
import './GuestLayout.css';


export default function Guest({ children }) {
    return (
        <div className="guest-layout">
            <div>
                <Link href="/">
                    <BrandLogo className="guest-logo" />
                </Link>
            </div>

            <div className="guest-card">
                {children}
            </div>
        </div>
    );
}
