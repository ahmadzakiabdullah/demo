import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';
import {
    AwardIcon,
    CalendarRangeIcon,
    DumbbellIcon,
    LayoutDashboardIcon,
    MapPinIcon,
    MedalIcon,
    ShieldIcon,
    SwordsIcon,
    TrophyIcon,
    UsersIcon,
    UserSquare2Icon,
    LandmarkIcon,
} from 'lucide-react';

/**
 * @param {{ id: number, name: string }} event
 */
export default function EventModuleNav({ event }) {
    const { auth } = usePage().props;
    const user = auth.user;

    const items = [
        {
            label: 'Overview',
            href: route('admin.events.show', event.id),
            active:
                route().current('admin.events.show', event.id) ||
                route().current('admin.events.edit', event.id),
            icon: LayoutDashboardIcon,
            visible: user?.can_view_events,
        },
        {
            label: 'Sports',
            href: route('admin.events.sports.index', event.id),
            active: route().current('admin.events.sports.*'),
            icon: DumbbellIcon,
            visible: user?.can_view_sports,
        },
        {
            label: 'Participants',
            href: route('admin.events.participants.index', event.id),
            active: route().current('admin.events.participants.*'),
            icon: LandmarkIcon,
            visible: user?.can_view_event_participants,
        },
        {
            label: 'Athletes',
            href: route('admin.events.athletes.index', event.id),
            active: route().current('admin.events.athletes.*'),
            icon: UserSquare2Icon,
            visible: user?.can_view_athletes,
        },
        {
            label: 'Teams',
            href: route('admin.events.teams.index', event.id),
            active: route().current('admin.events.teams.*'),
            icon: UsersIcon,
            visible: user?.can_view_teams,
        },
        {
            label: 'Officials',
            href: route('admin.events.officials.index', event.id),
            active: route().current('admin.events.officials.*'),
            icon: ShieldIcon,
            visible: user?.can_view_officials,
        },
        {
            label: 'Venues',
            href: route('admin.events.venues.index', event.id),
            active: route().current('admin.events.venues.*'),
            icon: MapPinIcon,
            visible: user?.can_view_venues,
        },
        {
            label: 'Competitions',
            href: route('admin.events.competitions.index', event.id),
            active: route().current('admin.events.competitions.*'),
            icon: SwordsIcon,
            visible: user?.can_view_competitions,
        },
        {
            label: 'Schedule',
            href: route('admin.events.schedule.index', event.id),
            active: route().current('admin.events.schedule.*'),
            icon: CalendarRangeIcon,
            visible: user?.can_view_competitions,
        },
        {
            label: 'Rankings',
            href: route('admin.events.rankings.index', event.id),
            active: route().current('admin.events.rankings.*'),
            icon: TrophyIcon,
            visible: user?.can_view_results,
        },
        {
            label: 'Medals',
            href: route('admin.events.medals.index', event.id),
            active: route().current('admin.events.medals.*'),
            icon: MedalIcon,
            visible: user?.can_view_results,
        },
        {
            label: 'Ceremonies',
            href: route('admin.events.medal-ceremonies.index', event.id),
            active: route().current('admin.events.medal-ceremonies.*'),
            icon: AwardIcon,
            visible: user?.can_view_results,
        },
    ].filter((item) => item.visible);

    if (items.length === 0) {
        return null;
    }

    return (
        <nav
            aria-label="Event modules"
            className="-mx-6 border-b bg-muted/30 px-6"
        >
            <div className="flex gap-1 overflow-x-auto py-2">
                {items.map((item) => {
                    const Icon = item.icon;

                    return (
                        <Link
                            key={item.label}
                            href={item.href}
                            className={cn(
                                'inline-flex shrink-0 items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                                item.active
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:bg-background/60 hover:text-foreground',
                            )}
                        >
                            <Icon className="size-4" />
                            {item.label}
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}