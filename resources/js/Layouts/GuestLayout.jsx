import ApplicationLogo from '@/Components/ApplicationLogo';
import { Card, CardContent } from '@/components/ui/card';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="flex min-h-screen flex-col items-center bg-muted/40 px-4 pt-6 sm:justify-center sm:pt-0">
            <div>
                <Link href="/">
                    <ApplicationLogo className="h-20 w-20 fill-current text-muted-foreground" />
                </Link>
            </div>

            <Card className="mt-6 w-full sm:max-w-md">
                <CardContent className="pt-6">{children}</CardContent>
            </Card>
        </div>
    );
}