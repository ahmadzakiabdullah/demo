import AppSidebar from '@/Components/AppSidebar';
import OrganizationSwitcher from '@/Components/OrganizationSwitcher';
import { Button } from '@/components/ui/button';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import {
    SidebarInset,
    SidebarProvider,
    SidebarTrigger,
} from '@/components/ui/sidebar';
import { Fragment } from 'react';
import { Link, usePage } from '@inertiajs/react';

export default function AdminLayout({
    header,
    breadcrumbs = [],
    children,
}) {
    const user = usePage().props.auth.user;

    return (
        <SidebarProvider>
            <AppSidebar />
            <SidebarInset>
                <header className="flex h-14 shrink-0 items-center gap-3 border-b px-4">
                    <SidebarTrigger className="-ml-1" />
                    <Separator
                        orientation="vertical"
                        className="hidden h-4 sm:block"
                    />
                    <OrganizationSwitcher />
                    {breadcrumbs.length > 0 && (
                        <>
                            <Separator
                                orientation="vertical"
                                className="hidden h-4 md:block"
                            />
                            <Breadcrumb className="hidden min-w-0 md:block">
                                <BreadcrumbList>
                                    {breadcrumbs.map((crumb, index) => (
                                        <Fragment key={crumb.label}>
                                            {index > 0 && <BreadcrumbSeparator />}
                                            <BreadcrumbItem>
                                                {crumb.href ? (
                                                    <BreadcrumbLink
                                                        render={
                                                            <Link
                                                                href={crumb.href}
                                                            />
                                                        }
                                                    >
                                                        {crumb.label}
                                                    </BreadcrumbLink>
                                                ) : (
                                                    <BreadcrumbPage>
                                                        {crumb.label}
                                                    </BreadcrumbPage>
                                                )}
                                            </BreadcrumbItem>
                                        </Fragment>
                                    ))}
                                </BreadcrumbList>
                            </Breadcrumb>
                        </>
                    )}
                    <div className="ml-auto hidden sm:flex">
                        <DropdownMenu>
                            <DropdownMenuTrigger
                                render={
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="gap-2"
                                    />
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
                </header>

                {(header || breadcrumbs.length > 0) && (
                    <div className="flex flex-col gap-1 border-b px-6 py-4">
                        {breadcrumbs.length > 0 && (
                            <Breadcrumb className="md:hidden">
                                <BreadcrumbList>
                                    {breadcrumbs.map((crumb, index) => (
                                        <Fragment key={crumb.label}>
                                            {index > 0 && <BreadcrumbSeparator />}
                                            <BreadcrumbItem>
                                                {crumb.href ? (
                                                    <BreadcrumbLink
                                                        render={
                                                            <Link
                                                                href={crumb.href}
                                                            />
                                                        }
                                                    >
                                                        {crumb.label}
                                                    </BreadcrumbLink>
                                                ) : (
                                                    <BreadcrumbPage>
                                                        {crumb.label}
                                                    </BreadcrumbPage>
                                                )}
                                            </BreadcrumbItem>
                                        </Fragment>
                                    ))}
                                </BreadcrumbList>
                            </Breadcrumb>
                        )}
                        {header}
                    </div>
                )}

                <main className="flex-1 p-4 md:p-6">{children}</main>
            </SidebarInset>
        </SidebarProvider>
    );
}