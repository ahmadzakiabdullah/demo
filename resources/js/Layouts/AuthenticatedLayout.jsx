import ApplicationLogo from '@/Components/ApplicationLogo';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';
import { MenuIcon } from 'lucide-react';
import { useState } from 'react';

function navLinkClass(active) {
    return cn(
        'inline-flex h-8 items-center justify-center rounded-lg px-2.5 text-sm font-medium transition-colors',
        active
            ? 'bg-secondary text-secondary-foreground'
            : 'text-foreground hover:bg-muted hover:text-foreground',
    );
}

function mobileNavLinkClass(active) {
    return cn(
        'flex w-full items-center rounded-lg px-2.5 py-2 text-sm font-medium transition-colors',
        active
            ? 'bg-secondary text-secondary-foreground'
            : 'text-foreground hover:bg-muted hover:text-foreground',
    );
}

export default function AuthenticatedLayout({ header, children }) {
    const user = usePage().props.auth.user;
    const [mobileOpen, setMobileOpen] = useState(false);

    const isDashboard = route().current('dashboard');
    const isProfile = route().current('profile.edit');

    return (
        <div className="min-h-screen bg-muted/40">
            <nav className="border-b bg-background">
                <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center gap-6">
                        <Link href="/">
                            <ApplicationLogo className="block h-9 w-auto fill-current text-foreground" />
                        </Link>

                        <div className="hidden sm:flex sm:items-center sm:gap-1">
                            <Link
                                href={route('dashboard')}
                                className={navLinkClass(isDashboard)}
                            >
                                Dashboard
                            </Link>
                        </div>
                    </div>

                    <div className="hidden sm:flex sm:items-center">
                        <DropdownMenu>
                            <DropdownMenuTrigger
                                render={
                                    <Button variant="outline" className="gap-2" />
                                }
                            >
                                {user.name}
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem>
                                    <Link
                                        href={route('profile.edit')}
                                        className="w-full"
                                    >
                                        Profile
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem variant="destructive">
                                    <Link
                                        href={route('logout')}
                                        method="post"
                                        as="button"
                                        className="w-full text-left"
                                    >
                                        Log Out
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
                        <SheetTrigger
                            className="sm:hidden"
                            render={<Button variant="ghost" size="icon" />}
                        >
                            <MenuIcon />
                            <span className="sr-only">Open menu</span>
                        </SheetTrigger>
                        <SheetContent side="left" className="w-72">
                            <SheetHeader>
                                <SheetTitle>Menu</SheetTitle>
                            </SheetHeader>
                            <div className="mt-6 flex flex-col gap-1">
                                <Link
                                    href={route('dashboard')}
                                    className={mobileNavLinkClass(isDashboard)}
                                    onClick={() => setMobileOpen(false)}
                                >
                                    Dashboard
                                </Link>
                            </div>
                            <Separator className="my-4" />
                            <div className="space-y-1">
                                <p className="px-2 text-sm font-medium">{user.name}</p>
                                <p className="px-2 text-sm text-muted-foreground">{user.email}</p>
                            </div>
                            <div className="mt-4 flex flex-col gap-1">
                                <Link
                                    href={route('profile.edit')}
                                    className={mobileNavLinkClass(isProfile)}
                                    onClick={() => setMobileOpen(false)}
                                >
                                    Profile
                                </Link>
                                <Link
                                    href={route('logout')}
                                    method="post"
                                    as="button"
                                    className={cn(
                                        mobileNavLinkClass(false),
                                        'text-destructive hover:text-destructive',
                                    )}
                                    onClick={() => setMobileOpen(false)}
                                >
                                    Log Out
                                </Link>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>
            </nav>

            {header && (
                <header className="border-b bg-background">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}