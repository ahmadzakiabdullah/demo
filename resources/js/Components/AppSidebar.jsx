import ApplicationLogo from '@/Components/ApplicationLogo';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarRail,
} from '@/components/ui/sidebar';
import { Link, usePage } from '@inertiajs/react';
import {
    Building2Icon,
    CalendarDaysIcon,
    ClipboardListIcon,
    LayoutDashboardIcon,
    MapPinIcon,
    UsersIcon,
} from 'lucide-react';

function NavItem({ href, active, icon: Icon, children }) {
    return (
        <SidebarMenuItem>
            <SidebarMenuButton
                isActive={active}
                tooltip={children}
                render={<Link href={href} />}
            >
                <Icon />
                <span>{children}</span>
            </SidebarMenuButton>
        </SidebarMenuItem>
    );
}

export default function AppSidebar() {
    const { auth } = usePage().props;
    const user = auth.user;

    const isDashboard = route().current('dashboard');
    const isUsers = route().current('admin.users.*');
    const isOrganizations = route().current('admin.organizations.*');
    const isAuditLogs = route().current('admin.audit-logs.*');
    const isEvents = route().current('admin.events.*');
    const isVenues = route().current('admin.venues.*');

    return (
        <Sidebar collapsible="icon">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            render={<Link href={route('dashboard')} />}
                        >
                            <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                                <ApplicationLogo className="size-5 fill-current" />
                            </div>
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-semibold">
                                    SportOS
                                </span>
                                <span className="truncate text-xs text-muted-foreground">
                                    Sports Management
                                </span>
                            </div>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <SidebarGroup>
                    <SidebarGroupLabel>Platform</SidebarGroupLabel>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            <NavItem
                                href={route('dashboard')}
                                active={isDashboard}
                                icon={LayoutDashboardIcon}
                            >
                                Dashboard
                            </NavItem>
                            {user?.can_view_events && (
                                <NavItem
                                    href={route('admin.events.index')}
                                    active={isEvents}
                                    icon={CalendarDaysIcon}
                                >
                                    Events
                                </NavItem>
                            )}
                            {user?.can_view_venues && (
                                <NavItem
                                    href={route('admin.venues.index')}
                                    active={isVenues}
                                    icon={MapPinIcon}
                                >
                                    Venues
                                </NavItem>
                            )}
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>

                {(user?.is_admin || user?.can_view_audit_logs) && (
                    <SidebarGroup>
                        <SidebarGroupLabel>Administration</SidebarGroupLabel>
                        <SidebarGroupContent>
                            <SidebarMenu>
                                {user?.is_admin && (
                                    <>
                                        <NavItem
                                            href={route(
                                                'admin.organizations.index',
                                            )}
                                            active={isOrganizations}
                                            icon={Building2Icon}
                                        >
                                            Organizations
                                        </NavItem>
                                        <NavItem
                                            href={route('admin.users.index')}
                                            active={isUsers}
                                            icon={UsersIcon}
                                        >
                                            Users
                                        </NavItem>
                                    </>
                                )}
                                {user?.can_view_audit_logs && (
                                    <NavItem
                                        href={route('admin.audit-logs.index')}
                                        active={isAuditLogs}
                                        icon={ClipboardListIcon}
                                    >
                                        Audit Logs
                                    </NavItem>
                                )}
                            </SidebarMenu>
                        </SidebarGroupContent>
                    </SidebarGroup>
                )}
            </SidebarContent>

            <SidebarFooter>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            render={<Link href={route('profile.edit')} />}
                        >
                            <div className="flex size-8 items-center justify-center rounded-lg bg-muted">
                                <span className="text-xs font-semibold uppercase">
                                    {user?.name?.slice(0, 2)}
                                </span>
                            </div>
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-medium">
                                    {user?.name}
                                </span>
                                <span className="truncate text-xs text-muted-foreground">
                                    {user?.email}
                                </span>
                            </div>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarFooter>

            <SidebarRail />
        </Sidebar>
    );
}